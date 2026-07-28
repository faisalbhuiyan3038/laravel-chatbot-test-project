<?php

namespace App\Services\Faq;

use Generator;

interface FaqSource
{
    /**
     * @return Generator<array{id:int, question:string, answer:string, updated_at:string}>
     */
    public function documents(): Generator;
}