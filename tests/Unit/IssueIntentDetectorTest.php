<?php

namespace Tests\Unit;

use App\Services\AI\Contracts\ChatProvider;
use App\Services\Issue\IssueIntentDetector;
use Tests\TestCase;

class IssueIntentDetectorTest extends TestCase
{
    public function test_how_to_question_is_classified_as_faq(): void
    {
        $mockChat = $this->createMock(ChatProvider::class);
        $mockChat->expects($this->once())
            ->method('completeMessages')
            ->willReturn('faq');

        $detector = new IssueIntentDetector($mockChat);
        $result = $detector->detect('How to log a new ticket in AV-CRM?');

        $this->assertEquals('faq', $result);
    }

    public function test_create_issue_request_classified_as_issue_create(): void
    {
        $mockChat = $this->createMock(ChatProvider::class);
        $mockChat->expects($this->once())
            ->method('completeMessages')
            ->willReturn('issue_create');

        $detector = new IssueIntentDetector($mockChat);
        $result = $detector->detect('I want to create a new support ticket for my login issue');

        $this->assertEquals('issue_create', $result);
    }

    public function test_query_issues_classified_as_issue_query(): void
    {
        $mockChat = $this->createMock(ChatProvider::class);
        $mockChat->expects($this->once())
            ->method('completeMessages')
            ->willReturn('issue_query');

        $detector = new IssueIntentDetector($mockChat);
        $result = $detector->detect('What is the status of my latest issue?');

        $this->assertEquals('issue_query', $result);
    }
}
