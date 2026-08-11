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

        $currentModel = config('ai.providers.' . config('ai.embedding_provider') . '.embedding_model');

        foreach ($projects as $project) {
            $this->info("Processing project: {$project->name}");

            // Example logic for Ansar Recruitment
            if ($project->slug === 'ansar_recruitment') {
                $this->ingestFaqs($project, $embedder, $currentModel);
                $this->ingestDocs($project, $markdownParser, $embedder, $currentModel);
            }
        }

        $this->info("Ingestion complete.");
    }

    private function ingestFaqs(Project $project, EmbeddingProvider $embedder, string $model)
    {
        $path = database_path('seeders/data/faqs.json');
        if (!file_exists($path)) return;

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
        $this->info("FAQs ingested.");
    }

    private function ingestDocs(Project $project, MarkdownParserService $parser, EmbeddingProvider $embedder, string $model)
    {
        $files = [
            'en' => base_path('ansar-recruitment-docs-en.md'),
            'bn' => base_path('ansar-recruitment-docs-bn.md'),
        ];

        foreach ($files as $lang => $file) {
            if (!file_exists($file)) continue;

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
            $this->info("Docs ($lang) ingested.");
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
