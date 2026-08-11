<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Project;
use App\Services\Faq\ProjectInferenceService;
use App\Services\AI\Contracts\ChatProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProjectInferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_explicit_project_alias(): void
    {
        Project::create([
            'slug' => 'ansar_recruitment',
            'name' => 'Ansar Recruitment',
            'aliases' => ['ansar', 'vdp'],
            'is_active' => true,
        ]);

        $mockChat = $this->createMock(ChatProvider::class);
        $service = new ProjectInferenceService($mockChat);

        $result = $service->infer('How to apply in Ansar Recruitment?', []);

        $this->assertEquals('confident', $result['status']);
        $this->assertEquals('explicit', $result['detected_via']);
        $this->assertEquals('Ansar Recruitment', $result['project']->name);
    }

    public function test_detects_unsupported_project_via_llm_classification(): void
    {
        Project::create([
            'slug' => 'ansar_recruitment',
            'name' => 'Ansar Recruitment',
            'aliases' => ['ansar', 'vdp'],
            'is_active' => true,
        ]);

        $mockChat = $this->createMock(ChatProvider::class);
        $mockChat->expects($this->once())
            ->method('completeMessages')
            ->willReturn('UNKNOWN');

        $service = new ProjectInferenceService($mockChat);

        $result = $service->infer('How to log a new ticket in AV-CRM?', []);

        $this->assertEquals('ambiguous', $result['status']);
        $this->assertNull($result['project']);
    }

    public function test_formats_supported_projects_list_naturally(): void
    {
        Project::create([
            'slug' => 'p1',
            'name' => 'Ansar Recruitment',
            'is_active' => true,
        ]);

        $mockChat = $this->createMock(ChatProvider::class);
        $retrieverMock = $this->createMock(\App\Services\Faq\FaqRetriever::class);
        $answerer = new \App\Services\Faq\RagAnswerer($retrieverMock, $mockChat, new ProjectInferenceService($mockChat));

        $this->assertEquals('Ansar Recruitment', $answerer->formatSupportedProjectsList());

        Project::create([
            'slug' => 'p2',
            'name' => 'HR Portal',
            'is_active' => true,
        ]);

        $this->assertEquals('Ansar Recruitment and HR Portal', $answerer->formatSupportedProjectsList());
    }
}
