<?php

namespace App\Services\Faq;

class MarkdownParserService
{
    /**
     * Parses a markdown file and extracts its sections based on heading format:
     * ## [id: ..., type: ...] Title
     *
     * @param string $content
     * @return array{
     *   metadata: array,
     *   sections: array<int, array{title: string, content: string}>
     * }
     */
    public function parse(string $content): array
    {
        $metadata = [];
        $sections = [];

        // Extract frontmatter
        if (preg_match('/^---\s*(.*?)\s*---/s', $content, $matches)) {
            $frontmatter = $matches[1];
            $content = substr($content, strlen($matches[0]));

            $lines = explode("\n", $frontmatter);
            foreach ($lines as $line) {
                if (strpos($line, ':') !== false) {
                    list($key, $value) = explode(':', $line, 2);
                    $metadata[trim($key)] = trim($value);
                }
            }
        }

        // Extract sections
        // Pattern matches: ## [id: ..., type: ...] Title
        $pattern = '/^##\s+\[(.*?)\]\s*(.*?)$/m';
        $parts = preg_split($pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        
        // $parts[0] is everything before the first heading
        for ($i = 1; $i < count($parts); $i += 3) {
            $title = trim($parts[$i + 1]);
            $body = trim($parts[$i + 2]);
            
            if (!empty($title)) {
                $sections[] = [
                    'title' => $title,
                    'content' => $body,
                ];
            }
        }

        return [
            'metadata' => $metadata,
            'sections' => $sections,
        ];
    }
}
