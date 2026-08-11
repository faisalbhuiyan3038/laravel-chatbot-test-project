<?php

namespace App\Services\Faq;

use App\Models\Project;

class ProjectInferenceService
{
    public function __construct(
        private readonly float $ambiguityThreshold = 0.1,
    ) {}

    /**
     * Infer the target project based on the query and retrieved chunks.
     *
     * @param string $query
     * @param array $matches Array of matches containing 'project_id' and 'score'
     * @return array{status: 'confident'|'ambiguous'|'none', project: ?Project, candidate_projects: array}
     */
    public function infer(string $query, array $matches): array
    {
        $projects = Project::where('is_active', true)->get();

        $queryLower = mb_strtolower($query);
        foreach ($projects as $project) {
            $aliases = $project->aliases ?? [];
            $aliases[] = $project->name;
            
            foreach ($aliases as $alias) {
                if (str_contains($queryLower, mb_strtolower($alias))) {
                    return [
                        'status' => 'confident',
                        'project' => $project,
                        'candidate_projects' => [$project]
                    ];
                }
            }
        }

        if (empty($matches)) {
            return [
                'status' => 'none',
                'project' => null,
                'candidate_projects' => []
            ];
        }

        $projectScores = [];
        foreach ($matches as $match) {
            $pid = $match['project_id'];
            if (!isset($projectScores[$pid])) {
                $projectScores[$pid] = 0;
            }
            $projectScores[$pid] += $match['score'];
        }

        arsort($projectScores);
        $topIds = array_keys($projectScores);
        
        $topProjectId = $topIds[0];
        $topScore = $projectScores[$topProjectId];
        
        if (count($projectScores) > 1) {
            $secondProjectId = $topIds[1];
            $secondScore = $projectScores[$secondProjectId];
            
            if (($topScore - $secondScore) < $this->ambiguityThreshold) {
                $candidates = $projects->whereIn('id', [$topProjectId, $secondProjectId])->values()->all();
                return [
                    'status' => 'ambiguous',
                    'project' => null,
                    'candidate_projects' => $candidates
                ];
            }
        }

        $confidentProject = $projects->firstWhere('id', $topProjectId);
        
        return [
            'status' => 'confident',
            'project' => $confidentProject,
            'candidate_projects' => [$confidentProject]
        ];
    }
}
