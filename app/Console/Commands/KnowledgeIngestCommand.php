<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Project;
use App\Models\KnowledgeChunk;
use App\Services\Faq\MarkdownParserService;
use App\Services\AI\Contracts\EmbeddingProvider;
use App\Services\Faq\VectorCodec;

class KnowledgeIngestCommand extends Command
{
    protected $signature = 'knowledge:ingest {project_slug?}';
    protected $description = 'Ingest FAQs and Docs into Knowledge Chunks';

    public function handle(MarkdownParserService $markdownParser, EmbeddingProvider $embedder)
    {
        $slug = $this->argument('project_slug');

        $projects = $slug 
            ? Project::where('slug', $slug)->get() 
            : Project::all();

        if ($projects->isEmpty()) {
            $this->error("No projects found.");
            return;
        }

        if ($this->confirm('Do you want to clear existing knowledge base?', false)) {
            KnowledgeChunk::truncate();
            $this->info("Cleared existing knowledge base.");
        }

        $currentModel = config('ai.providers.' . config('ai.embedding_provider') . '.embedding_model');

        foreach ($projects as $project) {
            $this->info("Processing project: {$project->name}");
            $this->ingestFaqs($project, $embedder, $currentModel);
            $this->ingestDocs($project, $markdownParser, $embedder, $currentModel);
        }

        $this->info("Ingestion complete.");
    }

    private function ingestFaqs(Project $project, EmbeddingProvider $embedder, string $model)
    {
        $slugHyphen = str_replace('_', '-', $project->slug);
        
        $possiblePaths = [
            // Dedicated documentation directory
            base_path("documentation/{$project->slug}/faqs.json"),
            base_path("documentation/{$slugHyphen}/faqs.json"),
            base_path("documentation/{$slugHyphen}-faqs.json"),
        ];

        $path = null;
        foreach ($possiblePaths as $candidate) {
            if (file_exists($candidate)) {
                $path = $candidate;
                break;
            }
        }

        if (!$path) {
            $this->warn("No FAQ file found for {$project->name}");
            return;
        }

        $faqs = json_decode(file_get_contents($path), true);
        if (!$faqs) return;

        foreach ($faqs as $faq) {
            $this->createChunk(
                $project,
                'faq',
                'en',
                $faq['question'],
                $faq['answer'],
                $embedder,
                $model
            );

            if (!empty($faq['question_bn']) && !empty($faq['answer_bn'])) {
                $this->createChunk(
                    $project,
                    'faq',
                    'bn',
                    $faq['question_bn'],
                    $faq['answer_bn'],
                    $embedder,
                    $model
                );
            }
        }
        $this->info("FAQs ingested from " . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $path));
    }

    private function ingestDocs(Project $project, MarkdownParserService $parser, EmbeddingProvider $embedder, string $model)
    {
        $slugHyphen = str_replace('_', '-', $project->slug);
        $languages = ['en', 'bn'];

        foreach ($languages as $lang) {
            $possibleFiles = [
                // Dedicated documentation directory
                base_path("documentation/{$project->slug}/{$lang}.md"),
                base_path("documentation/{$slugHyphen}/{$lang}.md"),
                base_path("documentation/{$project->slug}/docs-{$lang}.md"),
                base_path("documentation/{$slugHyphen}/docs-{$lang}.md"),
                base_path("documentation/{$slugHyphen}-docs-{$lang}.md"),
                base_path("documentation/{$project->slug}-docs-{$lang}.md"),
            ];

            $file = null;
            foreach ($possibleFiles as $candidate) {
                if (file_exists($candidate)) {
                    $file = $candidate;
                    break;
                }
            }

            if (!$file) continue;

            $parsed = $parser->parse(file_get_contents($file));
            
            foreach ($parsed['sections'] as $section) {
                $this->createChunk(
                    $project,
                    'doc_section',
                    $lang,
                    $section['title'],
                    $section['content'],
                    $embedder,
                    $model
                );
            }
            $this->info("Docs ($lang) ingested from " . str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file));
        }
    }

    private function createChunk(Project $project, string $type, string $lang, string $title, string $content, EmbeddingProvider $embedder, string $model)
    {
        // Concatenate title and content for embedding
        $textToEmbed = "Q: {$title}\nA: {$content}";
        $vector = $embedder->embed($textToEmbed);
        $norm = VectorCodec::norm($vector);
        $encoded = VectorCodec::encode($vector);

        KnowledgeChunk::updateOrCreate(
            [
                'project_id' => $project->id,
                'type' => $type,
                'language' => $lang,
                'title' => $title,
            ],
            [
                'content' => $content,
                'embedding' => $encoded,
                'embedding_model' => $model,
                'embedding_norm' => $norm,
                'embedded_at' => now(),
            ]
        );
    }
}
