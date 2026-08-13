<?php
function isPositiveConfirmation(string $text): bool
{
    $t = mb_strtolower(trim($text));
    return (bool) preg_match(
        '/\b(yes|confirm|ok|okay|go ahead|proceed|submit|create it|do it|sure|agreed|হ্যাঁ|হ্যা|হয়)\b/u',
        $t
    );
}
var_dump(isPositiveConfirmation("yes"));
var_dump(isPositiveConfirmation("ok go ahead"));
