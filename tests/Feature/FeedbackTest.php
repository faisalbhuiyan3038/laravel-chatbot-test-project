<?php

namespace Tests\Feature;

use App\Models\ChatFeedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_submit_like_feedback(): void
    {
        $response = $this->postJson(route('chat.feedback'), [
            'question' => 'How to log ticket?',
            'answer'   => 'Click on new ticket in menu.',
            'rating'   => 'like',
        ]);

        $response->assertStatus(201)
                 ->assertJson(['success' => true]);

        $this->assertDatabaseHas('chat_feedbacks', [
            'question' => 'How to log ticket?',
            'rating'   => 'like',
        ]);
    }

    public function test_can_submit_dislike_feedback_with_comment(): void
    {
        $response = $this->postJson(route('chat.feedback'), [
            'question'      => 'Bill missing issue',
            'answer'        => 'Contact support',
            'rating'        => 'dislike',
            'feedback_text' => 'Answer was too vague and unhelpful.',
            'sources'       => [['id' => 1, 'score' => 0.65]],
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('chat_feedbacks', [
            'question'      => 'Bill missing issue',
            'rating'        => 'dislike',
            'feedback_text' => 'Answer was too vague and unhelpful.',
        ]);
    }

    public function test_admin_dashboard_loads(): void
    {
        ChatFeedback::create([
            'question' => 'Sample Question',
            'answer'   => 'Sample Answer',
            'rating'   => 'like',
        ]);

        $response = $this->get(route('admin.feedbacks'));

        $response->assertStatus(200)
                 ->assertSee('Knowledge Base Feedbacks')
                 ->assertSee('Sample Question');
    }
}
