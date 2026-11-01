<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Models\Exam;
use App\Models\Level;
use App\Models\Question;
use Illuminate\Database\Seeder;

/** One sample exam per level (vmbo and vwo for now), built from that level's published questions. */
class ExamSeeder extends Seeder
{
    public function run(): void
    {
        $exams = [
            'vmbo' => ['title' => 'Oefenexamen grammatica VMBO', 'time_limit_minutes' => 30, 'pass_mark_percent' => 55],
            'vwo' => ['title' => 'Oefenexamen grammatica VWO', 'time_limit_minutes' => 40, 'pass_mark_percent' => 60],
        ];

        $typeOrder = array_map(fn (QuestionType $t) => $t->value, QuestionType::cases());

        foreach ($exams as $slug => $attributes) {
            $level = Level::where('slug', $slug)->firstOrFail();

            $exam = Exam::updateOrCreate(
                ['title' => $attributes['title'], 'level_id' => $level->id],
                $attributes + ['is_published' => true],
            );

            $questions = Question::published()
                ->where('level_id', $level->id)
                ->orderBy('id')
                ->get()
                ->sortBy(fn (Question $q) => array_search($q->type->value, $typeOrder, true))
                ->values();

            $sync = [];
            foreach ($questions as $position => $question) {
                // Harder questions are worth more points.
                $sync[$question->id] = ['points' => $question->difficulty >= 3 ? 2 : 1, 'sort_order' => $position + 1];
            }

            $exam->questions()->sync($sync);
        }
    }
}
