<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AttemptMode;
use App\Models\Attempt;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProgressService
{
    /** A topic counts as weak with at least this many scored answers... */
    public const WEAK_MIN_ANSWERS = 3;

    /** ...and an accuracy below this percentage. */
    public const WEAK_BELOW_PERCENT = 70;

    public const WEAK_LIMIT = 3;

    public const HISTORY_LIMIT = 20;

    /** @return array<string, mixed> */
    public function for(User $user): array
    {
        // Only scored answers count: unsubmitted exam answers have is_correct = null.
        $stats = DB::table('attempt_answers as aa')
            ->join('attempts as a', 'a.id', '=', 'aa.attempt_id')
            ->join('questions as q', 'q.id', '=', 'aa.question_id')
            ->where('a.user_id', $user->id)
            ->whereNotNull('aa.is_correct')
            ->groupBy('q.topic_id')
            ->selectRaw('q.topic_id, count(*) as answered, sum(aa.is_correct) as correct')
            ->get()
            ->keyBy('topic_id');

        $topics = Topic::orderBy('sort_order')->get()->map(function (Topic $topic) use ($stats): array {
            $answered = (int) ($stats[$topic->id]->answered ?? 0);
            $correct = (int) ($stats[$topic->id]->correct ?? 0);

            return [
                'topic' => ['id' => $topic->id, 'slug' => $topic->slug, 'name' => $topic->name],
                'answered' => $answered,
                'correct' => $correct,
                'accuracy' => $answered > 0 ? round($correct / $answered * 100, 1) : null,
            ];
        });

        $weak = $topics
            ->filter(fn (array $t) => $t['answered'] >= self::WEAK_MIN_ANSWERS && $t['accuracy'] < self::WEAK_BELOW_PERCENT)
            ->sortBy([['accuracy', 'asc'], ['answered', 'desc']])
            ->take(self::WEAK_LIMIT)
            ->values();

        $answered = (int) $topics->sum('answered');
        $correct = (int) $topics->sum('correct');

        return [
            'overall' => [
                'answered' => $answered,
                'correct' => $correct,
                'accuracy' => $answered > 0 ? round($correct / $answered * 100, 1) : null,
            ],
            'topics' => $topics->values(),
            'weak_topics' => $weak,
            // The rule behind weak_topics, so a client can explain it without repeating the numbers.
            'weak_criteria' => ['min_answers' => self::WEAK_MIN_ANSWERS, 'below_percent' => self::WEAK_BELOW_PERCENT],
            'history' => $this->history($user),
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function history(User $user)
    {
        return Attempt::where('user_id', $user->id)
            ->whereNotNull('finished_at')
            ->with(['exam' => fn ($q) => $q->select('id', 'title', 'pass_mark_percent')->withCount('questions')])
            ->withCount(['answers as answered', 'answers as correct' => fn ($q) => $q->where('is_correct', true)])
            ->latest('finished_at')
            ->latest('id')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->map(fn (Attempt $a) => [
                'id' => $a->id,
                'mode' => $a->mode->value,
                'exam' => $a->mode === AttemptMode::Exam && $a->exam
                    ? ['id' => $a->exam->id, 'title' => $a->exam->title]
                    : null,
                'score' => (float) $a->score,
                'passed' => $a->mode === AttemptMode::Exam && $a->exam
                    ? (float) $a->score >= $a->exam->pass_mark_percent
                    : null,
                'answered' => $a->answered,
                'correct' => $a->correct,
                // What "correct" is measured against: all questions of an exam (unanswered ones count as wrong),
                // or the answered questions of a practice session.
                'total' => $a->mode === AttemptMode::Exam && $a->exam ? $a->exam->questions_count : $a->answered,
                'started_at' => $a->started_at->toIso8601String(),
                'finished_at' => $a->finished_at->toIso8601String(),
            ]);
    }
}
