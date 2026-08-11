<?php

namespace App\Services\Faq;

use App\Models\Project;
use App\Services\AI\Contracts\ChatProvider;

class ProjectInferenceService
{
    public function __construct(
        private readonly ChatProvider $chat,
        private readonly float $ambiguityThreshold = 0.1,
        private readonly float $minConfidenceScore = 0.68,
    ) {}

    /**
     * Infer the target project based on the query and retrieved chunks.
     *
     * @param string $query
     * @param array $matches Array of matches containing 'project_id' and 'score'
     * @param array $history Array of conversation history messages
     * @return array{status: 'confident'|'ambiguous'|'none', project: ?Project, candidate_projects: array}
     */
    public function infer(string $query, array $matches, array $history = []): array
    {
        $projects = Project::where('is_active', true)->get();

        if ($projects->isEmpty()) {
            return [
                'status' => 'ambiguous',
                'project' => null,
                'candidate_projects' => []
            ];
        }

        // 1. Explicit keyword/alias match in user query
        $queryLower = mb_strtolower($query);
        foreach ($projects as $project) {
            $aliases = $project->aliases ?? [];
            $aliases[] = $project->name;
            
            foreach ($aliases as $alias) {
                if (!empty($alias) && str_contains($queryLower, mb_strtolower($alias))) {
                    return [
                        'status'       => 'confident',
                        'detected_via' => 'explicit',
                        'project'      => $project,
                        'candidate_projects' => [$project]
                    ];
                }
            }
        }

        // 2. Intent classification via LLM (check for explicit unsupported project or context project)
        $classification = $this->classifyProjectViaLlm($query, $history, $projects);
        if ($classification['type'] === 'project' && $classification['project']) {
            return [
                'status'       => 'confident',
                'detected_via' => 'llm_context',
                'project'      => $classification['project'],
                'candidate_projects' => [$classification['project']]
            ];
        }

        if ($classification['type'] === 'unknown') {
            // User explicitly referred to an unsupported project/system (e.g. AV-CRM, Jira)
            return [
                'status' => 'ambiguous',
                'project' => null,
                'candidate_projects' => $projects->all()
            ];
        }

        // 3. Vector match checks (for when user didn't name any explicit supported or unsupported project)
        $topScore = $matches[0]['score'] ?? 0;
        $hasStrongMatches = !empty($matches) && $topScore >= $this->minConfidenceScore;

        if ($hasStrongMatches) {
            $projectScores = [];
            foreach ($matches as $match) {
                if ($match['score'] < $this->minConfidenceScore) {
                    continue;
                }
                $pid = $match['project_id'];
                if (!isset($projectScores[$pid])) {
                    $projectScores[$pid] = 0;
                }
                $projectScores[$pid] += $match['score'];
            }

            if (!empty($projectScores)) {
                arsort($projectScores);
                $topIds = array_keys($projectScores);
                
                $topProjectId = $topIds[0];
                $topScoreVal = $projectScores[$topProjectId];
                
                $isAmbiguous = false;
                if (count($projectScores) > 1) {
                    $secondProjectId = $topIds[1];
                    $secondScore = $projectScores[$secondProjectId];
                    
                    if (($topScoreVal - $secondScore) < $this->ambiguityThreshold) {
                        $isAmbiguous = true;
                    }
                }

                if (!$isAmbiguous) {
                    $confidentProject = $projects->firstWhere('id', $topProjectId);
                    if ($confidentProject) {
                        return [
                            'status'       => 'confident',
                            'detected_via' => 'vector',
                            'project'      => $confidentProject,
                            'candidate_projects' => [$confidentProject]
                        ];
                    }
                }
            }
        }

        // 4. Truly ambiguous
        return [
            'status' => 'ambiguous',
            'project' => null,
            'candidate_projects' => $projects->all()
        ];
    }

    /**
     * Classify project via LLM context / query inspection.
     * Returns ['type' => 'project'|'unknown'|'ambiguous', 'project' => ?Project]
     */
    private function classifyProjectViaLlm(string $query, array $history, $projects): array
    {
        $projectMap = [];
        $projectList = [];
        foreach ($projects as $p) {
            $projectMap[$p->id] = $p;
            $projectList[] = "{$p->name} (ID: {$p->id})";
        }
        $projectListStr = implode(", ", $projectList);

        $systemPrompt = <<<PROMPT
You are an intent classification assistant for customer support.
Identify which software project the user is referring to based on the conversation history and the latest query.
Supported active projects: {$projectListStr}.

Rules:
- If the user is referring to one of the supported projects (even implicitly via context or synonyms), respond with ONLY the numeric ID of that project.
- If the user explicitly mentions or asks about a project, software, or system that is NOT in the active projects list (e.g. AV-CRM, Jira, Salesforce, custom tools), respond with ONLY UNKNOWN.
- If no project is mentioned or implied at all, respond with ONLY AMBIGUOUS.
- Do not include any other text, quotes, or punctuation.
PROMPT;

        $formattedHistory = [];
        $maxChars = 8000;
        $currentChars = 0;
        foreach (array_reverse($history) as $msg) {
            if (isset($msg['role'], $msg['content']) && in_array($msg['role'], ['user', 'assistant'])) {
                $len = mb_strlen($msg['content']);
                if ($currentChars + $len > $maxChars) break;
                $currentChars += $len;
                array_unshift($formattedHistory, ['role' => $msg['role'], 'content' => $msg['content']]);
            }
        }

        $messages = array_merge(
            [['role' => 'system', 'content' => $systemPrompt]],
            $formattedHistory,
            [['role' => 'user', 'content' => $query]]
        );

        try {
            $response = trim($this->chat->completeMessages($messages));
            
            if (is_numeric($response) && isset($projectMap[(int)$response])) {
                return ['type' => 'project', 'project' => $projectMap[(int)$response]];
            }

            if (strtoupper($response) === 'UNKNOWN') {
                return ['type' => 'unknown', 'project' => null];
            }
        } catch (\Throwable $e) {
            // Ignore errors for this fallback classification call
        }

        return ['type' => 'ambiguous', 'project' => null];
    }
}
