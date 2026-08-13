<?php
function isPositiveConfirmation(string $text): bool
{
    $t = mb_strtolower(trim($text));
    return (bool) preg_match(
        '/\b(yes|confirm|ok|okay|go ahead|proceed|submit|create it|do it|sure|agreed|হ্যাঁ|হ্যা|হয়)\b/u',
        $t
    );
}

$cases = [
    "yes",
    "ok go ahead",
    "ok, go ahead",
    "yes.",
    "Yes!",
    "ok go ahead.",
    "ok go ahead!",
];
foreach($cases as $case) {
    var_dump("$case: " . (isPositiveConfirmation($case) ? 'true' : 'false'));
}
