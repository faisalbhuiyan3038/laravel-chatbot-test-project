# Knowledge Ingestion, Chunking & Vector Embedding Pipeline

## Purpose
Automates the parsing, chunking, vector embedding, and database storage of structured documentation and FAQ datasets across multiple projects (e.g. `ansar_recruitment`, `av-crm`) and languages (English and Bengali). It ensures the retrieval augmented generation (RAG) system has up-to-date, densely indexed semantic vectors stored as binary BLOBs for cosine similarity search.

## Entry Points
- **Console Commands**:
  - `php artisan knowledge:ingest {project_slug?}` -> `KnowledgeIngestCommand` (Ingests FAQs and markdown docs into `knowledge_chunks`)
  - `php artisan faq:embed {--force}` -> `EmbedFaqs` (Legacy batch embedding generator for the `faqs` table)
  - `php artisan faq:preview {--limit=5}` -> `PreviewFaqChunks` (CLI utility to inspect ticket splitting and chunk prefixes)

## Key Files & Classes
- `app/Console/Commands/KnowledgeIngestCommand.php` — Primary orchestration command traversing documentation files, invoking parsers, requesting vector embeddings, and creating `KnowledgeChunk` records.
- `app/Services/Faq/MarkdownParserService.php` — Parses frontmatter and markdown sections delimited by structured headers (e.g. `## [id: ..., type: ...] Title`).
- `app/Services/Faq/TextChunker.php` — Sliding-window text chunker (default 2000 chars with 200 char overlap) that prepends question/context titles to every chunk part.
- `app/Services/Faq/VectorCodec.php` — Fast binary pack/unpack utility encoding float vectors into 32-bit little-endian binary strings (`pack('g*')`) and calculating Euclidean L2 norms.
- `app/Models/Project.php` — Represents multi-tenant knowledge scopes, aliases, and support contact metadata.
- `app/Models/KnowledgeChunk.php` — Stores individual document sections and FAQ pairs with binary BLOB embeddings.
- `database/migrations/2026_08_11_000000_create_projects_table.php` — Schema for projects table.
- `database/migrations/2026_08_11_000001_create_knowledge_chunks_table.php` — Schema for knowledge chunks table with BLOB embedding columns.

## Data Flow

### 1. Project-Based Ingestion Walkthrough
1. **Trigger**: Developer runs `php artisan knowledge:ingest [project_slug]`.
2. **Project Resolution**: If `project_slug` is provided, fetches matching `Project` model; otherwise fetches all `Project` records.
3. **Optional Truncation**: Prompts whether to clear existing knowledge base (`KnowledgeChunk::truncate()`).
4. **FAQ Ingestion (`ingestFaqs`)**:
   - Searches candidate paths: `documentation/{project_slug}/faqs.json` or `documentation/{slug-hyphen}/faqs.json`.
   - Iterates FAQ objects: generates English chunk (`type: 'faq'`, `language: 'en'`) and optional Bengali chunk (`language: 'bn'`).
   - Generates vector embedding via `EmbeddingProvider->embed("Q: {$question}\nA: {$answer}")`.
   - Computes L2 norm via `VectorCodec::norm($vector)` and binary packs via `VectorCodec::encode($vector)`.
   - Calls `KnowledgeChunk::updateOrCreate(...)`.
5. **Documentation Ingestion (`ingestDocs`)**:
   - Searches candidate paths for `en.md` and `bn.md` (e.g. `documentation/{slug}/en.md` or `{slug}-docs-en.md`).
   - Reads markdown content and parses frontmatter/sections via `MarkdownParserService::parse()`.
   - Iterates each extracted section: creates `KnowledgeChunk` (`type: 'doc_section'`) with vector embedding generated over `"Q: {$title}\nA: {$content}"`.

## Relevant Code Snippets

```php
// app/Services/Faq/VectorCodec.php
class VectorCodec
{
    public static function encode(array $vector): string
    {
        return pack('g*', ...$vector); // 32-bit float, little-endian
    }

    public static function decode(string $binary): array
    {
        return array_values(unpack('g*', $binary));
    }

    public static function norm(array $vector): float
    {
        return sqrt(array_sum(array_map(fn ($v) => $v * $v, $vector)));
    }
}
```

```php
// app/Services/Faq/MarkdownParserService.php
// Regex pattern matching: ## [id: ..., type: ...] Title
$pattern = '/^##\s+\[(.*?)\]\s*(.*?)$/m';
$parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
for ($i = 1; $i < count($parts); $i += 3) {
    $title = trim($parts[$i + 1]);
    $body = trim($parts[$i + 2]);
    if (!empty($title)) {
        $sections[] = ['title' => $title, 'content' => $body];
    }
}
```

## Database Involvement

### Tables Touched
- `projects`: Configures project identifiers and metadata.
- `knowledge_chunks`: Stores ingested doc sections and FAQ pairs.

| Column (`knowledge_chunks`) | Type | Description |
|---|---|---|
| `id` | bigint | Primary Key |
| `project_id` | bigint (FK) | References `projects.id` (cascade delete) |
| `type` | varchar(50) | Chunk classifier (`'faq'`, `'doc_section'`) |
| `language` | varchar(10) | Language code (`'en'`, `'bn'`) |
| `title` | varchar(255) | Section header or FAQ question |
| `content` | text | Section markdown body or FAQ answer |
| `embedding` | blob / longblob | Binary packed 32-bit float vector |
| `embedding_model` | varchar(100) | Identifier of model used to produce vector (e.g. `bge-m3`) |
| `embedding_norm` | double | Pre-computed Euclidean norm ($\sqrt{\sum v_i^2}$) for fast cosine calc |
| `embedded_at` | timestamp | Timestamp of last embedding calculation |

## Edge Cases & Conditional Logic
- **Header Parsing Format**: `MarkdownParserService` strictly expects markdown section headings matching `/^##\s+\[(.*?)\]\s*(.*?)$/m`. Markdown files with standard headings like `## Introduction` (without bracketed tags) will not match and will be skipped by the parser loop.
- **Model Invalidation**: `KnowledgeChunk` tracks `embedding_model`. If the embedding provider model changes (e.g., from `bge-m3` to `gemini-embedding-001`), dimension lengths differ and previous cosine dot products become incompatible.
- **Compound Embedding Input Format**: All chunks are normalized before embedding with the unified format `"Q: {title}\nA: {content}"` ensuring question-answering symmetry during retriever query embedding.

## Notes & Concerns
- **Header Structure Dependency**: If external markdown files do not conform to `## [tag] Title`, `MarkdownParserService` extracts zero sections without logging a warning.
- **Synchronous Batch Ingestion**: `KnowledgeIngestCommand` processes all embeddings sequentially in the console process. Ingestion of large documentation sets with remote cloud models (e.g., Gemini) may hit HTTP rate limits or take substantial time without chunked queue workers.
- **Dual Schema (faqs vs knowledge_chunks)**: The database contains both legacy `faqs` table migrations (`2026_07_28_104204_create_faqs_table.php`) and the newer `projects` / `knowledge_chunks` architecture. While `KnowledgeChunk` is used by `FaqRetriever`, commands like `faq:embed` and `PreviewFaqChunks` still reference `faqs` and `DbFaqSource`.
