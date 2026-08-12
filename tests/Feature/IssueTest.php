<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\IssueCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IssueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_cannot_access_issues(): void
    {
        $response = $this->get(route('issues.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_issues_list(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('issues.index'));
        $response->assertStatus(200)
                 ->assertSee('My Created Issues');
    }

    public function test_user_can_create_new_issue_with_valid_data(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $category = IssueCategory::first();

        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->actingAs($user)->post(route('issues.store'), [
            'issue_category_id' => $category->id,
            'issue_date'        => now()->subMinutes(10)->format('Y-m-d\TH:i'),
            'details'           => 'Lost my card at the ATM yesterday.',
            'attachments'       => [$file],
        ]);

        $response->assertRedirect(route('issues.index'))
                 ->assertSessionHas('success', 'Issue created successfully!');

        $this->assertDatabaseHas('issues', [
            'user_id'           => $user->id,
            'issue_category_id' => $category->id,
            'details'           => 'Lost my card at the ATM yesterday.',
            'status'            => '0', // Default Open
        ]);

        $issue = Issue::where('user_id', $user->id)->first();
        $this->assertCount(1, $issue->attachments);
    }

    public function test_cannot_create_issue_with_future_date(): void
    {
        $user = User::factory()->create();
        $category = IssueCategory::first();

        $futureDate = now()->addDays(2)->format('Y-m-d\TH:i');

        $response = $this->actingAs($user)->post(route('issues.store'), [
            'issue_category_id' => $category->id,
            'issue_date'        => $futureDate,
            'details'           => 'Testing future date rejection.',
        ]);

        $response->assertSessionHasErrors('issue_date');
        $this->assertDatabaseMissing('issues', [
            'details' => 'Testing future date rejection.',
        ]);
    }

    public function test_user_cannot_view_or_delete_another_users_issue(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $category = IssueCategory::first();

        $issue = Issue::create([
            'user_id'           => $user1->id,
            'issue_category_id' => $category->id,
            'issue_date'        => now()->subDay(),
            'details'           => 'User 1 issue',
            'status'            => '0',
        ]);

        // User 2 tries to view User 1's issue
        $response = $this->actingAs($user2)->get(route('issues.show', $issue->id));
        $response->assertStatus(404);

        // User 2 tries to delete User 1's issue
        $response = $this->actingAs($user2)->delete(route('issues.destroy', $issue->id));
        $response->assertStatus(404);

        $this->assertDatabaseHas('issues', ['id' => $issue->id]);
    }

    public function test_user_can_delete_their_own_issue(): void
    {
        $user = User::factory()->create();
        $category = IssueCategory::first();

        $issue = Issue::create([
            'user_id'           => $user->id,
            'issue_category_id' => $category->id,
            'issue_date'        => now()->subHour(),
            'details'           => 'Issue to be deleted',
            'status'            => '0',
        ]);

        $response = $this->actingAs($user)->delete(route('issues.destroy', $issue->id));
        $response->assertRedirect(route('issues.index'));

        $this->assertDatabaseMissing('issues', ['id' => $issue->id]);
    }
}
