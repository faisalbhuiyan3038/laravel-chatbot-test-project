<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AI\Contracts\ChatProvider;
use App\Services\AI\Contracts\EmbeddingProvider;

class TestAiProviders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Smoke-test the currently configured embedding and chat providers';

    /**
     * Execute the console command.
     */
    public function handle(EmbeddingProvider $embedder, ChatProvider $chat)
    {
        $this->info('Embedding driver: '.config('ai.embedding.driver'));
        $this->info('Chat driver: '.config('ai.chat.driver'));

        $this->line('--- Testing embedding ---');
        $vector = $embedder->embed('How do I close a support ticket?');
        $this->info('Got a vector with '. count($vector). ' dimensions');

        $this->line('--- Testing chat ---');
        $reply = $chat->complete(
            'You are a helpful assistant for a support ticketing system.',
            'In one short sentence, what does this system do?'
        );
        $this->info('Reply: '.$reply);

        return self::SUCCESS;
    }
}
