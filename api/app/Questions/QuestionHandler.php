<?php

declare(strict_types=1);

namespace App\Questions;

/**
 * Per-type behaviour for a question. `$data` is always the stored questions.data JSON.
 *
 * Student answer shapes:
 *  - multiple_choice: {"selected": [int]}
 *  - fill_blank:      {"blanks": [string]}
 *  - order_words:     {"order": [string]}
 *  - mark_error:      {"index": int}
 *  - parse_sentence:  {"roles": [string]}  (one role per token, in order)
 */
interface QuestionHandler
{
    /**
     * The data a student may see before answering. Must never reveal the correct answer.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function publicData(array $data): array;

    /**
     * Validation rules for the answer payload (keys relative to the answer object).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function answerRules(array $data): array;

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $answer  An answer that passed answerRules().
     */
    public function check(array $data, array $answer): AnswerResult;

    /**
     * The correct answer in the same shape as a student answer (plus extra context where useful).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function correctAnswer(array $data): array;
}
