<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\QuestionType;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['topic_id', 'level_id', 'passage_id', 'type', 'prompt', 'data', 'explanation', 'difficulty', 'is_published'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'data' => 'array',
            'difficulty' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /** @param  Builder<Question>  $query */
    public function scopePublished(Builder $query): void
    {
        $query->where('is_published', true);
    }

    /** @return BelongsTo<Topic, $this> */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /** @return BelongsTo<Level, $this> */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * The text this question is about, if any.
     *
     * @return BelongsTo<Passage, $this>
     */
    public function passage(): BelongsTo
    {
        return $this->belongsTo(Passage::class);
    }

    /** @return BelongsToMany<Exam, $this, ExamQuestion> */
    public function exams(): BelongsToMany
    {
        return $this->belongsToMany(Exam::class, 'exam_questions')
            ->using(ExamQuestion::class)
            ->withPivot(['points', 'sort_order']);
    }

    /** One line with the actual content (the prompt alone is often generic, e.g. "Zet de woorden in de goede volgorde"). */
    public function summary(): string
    {
        $data = $this->data;

        return match ($this->type) {
            QuestionType::MultipleChoice => implode(' / ', $data['options']),
            QuestionType::FillBlank => str_replace('{{blank}}', '___', $data['text']),
            QuestionType::OrderWords => implode(' ', $data['words']),
            QuestionType::MarkError => implode(' ', $data['tokens']),
            QuestionType::ParseSentence => implode(' | ', array_column($data['tokens'], 'text')),
        };
    }

    /** Students have answered it or an exam contains it: deleting would break history. */
    public function isInUse(): bool
    {
        return $this->attemptAnswers()->exists() || $this->exams()->exists();
    }

    /**
     * Titles of published exams that contain this question. Such a question must stay published,
     * otherwise students could no longer save answers for it.
     *
     * @return Collection<int, string>
     */
    public function publishedExamTitles(): Collection
    {
        return $this->exams()->where('exams.is_published', true)->pluck('exams.title');
    }

    /** @return HasMany<AttemptAnswer, $this> */
    public function attemptAnswers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }
}
