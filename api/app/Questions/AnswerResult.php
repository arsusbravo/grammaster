<?php

declare(strict_types=1);

namespace App\Questions;

final readonly class AnswerResult
{
    /**
     * @param  list<bool>|null  $items  Per blank / per token result when the type has several parts.
     */
    public function __construct(
        public bool $isCorrect,
        public ?array $items = null,
    ) {}
}
