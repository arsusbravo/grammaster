<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\QuestionType;
use App\Http\Resources\QuestionResource;
use App\Models\Level;
use App\Models\Question;
use Illuminate\Support\Collection;

/**
 * The few questions anyone may try on the landing page without an account: per level the first published question of
 * each simple type (so at most three per level). The same set is used to show them and to accept answers for them, so
 * the public check endpoint cannot be used to read the solutions of the whole question bank.
 */
class SampleQuestions
{
    /** Types the landing page can render and check without the app. */
    /** Marks a gap in the text of a fill_blank question. */
    public const BLANK = '{{blank}}';

    public const TYPES = [QuestionType::MultipleChoice, QuestionType::FillBlank, QuestionType::MarkError];

    /** @return Collection<int, Question> */
    private function questions(): Collection
    {
        return Question::published()
            ->with(['topic', 'level'])
            ->whereIn('type', array_map(fn (QuestionType $type) => $type->value, self::TYPES))
            ->orderBy('id')
            ->get()
            ->unique(fn (Question $question) => $question->level_id.'|'.$question->type->value)
            ->values();
    }

    public function isSample(Question $question): bool
    {
        return $this->questions()->contains('id', $question->id);
    }

    /**
     * Levels that have samples, in menu order, each with its public questions (never with answers or explanations).
     *
     * @return list<array{level: array<string, mixed>, questions: list<array<string, mixed>>}>
     */
    public function byLevel(): array
    {
        $questions = $this->questions();
        $order = array_flip(array_map(fn (QuestionType $type) => $type->value, self::TYPES));

        return Level::orderBy('sort_order')->get()
            ->map(fn (Level $level) => [
                'level' => ['id' => $level->id, 'slug' => $level->slug, 'name' => $level->name],
                'questions' => $questions
                    ->where('level_id', $level->id)
                    ->sortBy(fn (Question $question) => $order[$question->type->value])
                    ->map(fn (Question $question) => (new QuestionResource($question))->resolve())
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $group) => $group['questions'] !== [])
            ->values()
            ->all();
    }
}
