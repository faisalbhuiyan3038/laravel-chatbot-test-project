<?php

namespace App\Console\Commands;

use App\Models\ChatFeedback;
use Illuminate\Console\Command;

class FeedbackInsightsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'faq:feedback-insights {--limit=10 : Number of disliked items to display}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze user feedback and output insights for evolving the knowledge base';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('====================================================');
        $this->info('  AV-CRM AI Knowledge Base Feedback Insights');
        $this->info('====================================================');

        $total = ChatFeedback::count();
        if ($total === 0) {
            $this->warn('No user feedback recorded yet.');
            return Command::SUCCESS;
        }

        $likes = ChatFeedback::where('rating', 'like')->count();
        $dislikes = ChatFeedback::where('rating', 'dislike')->count();
        $percentage = round(($likes / $total) * 100, 1);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Rated Responses', $total],
                ['Liked Responses (👍)', $likes],
                ['Disliked Responses (👎)', $dislikes],
                ['User Satisfaction Rate', "{$percentage}%"],
            ]
        );

        $limit = (int) $this->option('limit');
        $dislikedList = ChatFeedback::where('rating', 'dislike')
            ->latest()
            ->take($limit)
            ->get();

        if ($dislikedList->isNotEmpty()) {
            $this->newLine();
            $this->error("Recent Disliked Queries (Top {$dislikedList->count()} items requiring knowledge base updates):");

            foreach ($dislikedList as $index => $fb) {
                $this->newLine();
                $this->line("<fg=yellow># " . ($index + 1) . "</fg=yellow> <options=bold>Question:</options=bold> {$fb->question}");
                $this->line("   <options=bold>AI Answer:</options=bold> " . substr($fb->answer, 0, 120) . (strlen($fb->answer) > 120 ? '...' : ''));
                if ($fb->feedback_text) {
                    $this->line("   <fg=red>User Comment:</fg=red> \"{$fb->feedback_text}\"");
                }
                if (!empty($fb->sources)) {
                    $sourceIds = collect($fb->sources)->pluck('id')->implode(', ');
                    $this->line("   <fg=gray>Retrieved FAQs:</fg=gray> [#{$sourceIds}]");
                }
                $this->line("   <fg=gray>Recorded at:</fg=gray> {$fb->created_at->toDateTimeString()}");
            }
        } else {
            $this->newLine();
            $this->info('🎉 Great news! No negative feedback has been logged.');
        }

        return Command::SUCCESS;
    }
}
