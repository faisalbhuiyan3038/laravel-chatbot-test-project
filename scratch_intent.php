<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$detector = app(\App\Services\Issue\IssueIntentDetector::class);

$history = [
    ['role' => 'user', 'content' => 'No that was the details of the problem'],
    ['role' => 'assistant', 'content' => "Here's a summary of the issue I'm about to create for you:\n\nCategory: User ID and Password Not Found\nDate/Time: 13 Aug 2026, 06:33\nDetails: I want to die\n\nPlease reply **yes** to confirm..."]
];

$intent = $detector->detect("ok go ahead", $history);
echo "Intent: $intent\n";
