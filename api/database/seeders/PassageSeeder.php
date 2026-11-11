<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Models\Level;
use App\Models\Passage;
use App\Models\Topic;
use App\Support\QuestionData;
use Illuminate\Database\Seeder;

/**
 * Seeds texts with their questions from database/seeders/content/passages.php (topic "tekstbegrip").
 * Idempotent: a text is found by its title, a question by its prompt within that text.
 * Needs the levels and topics, so run it after LevelSeeder and TopicSeeder.
 */
class PassageSeeder extends Seeder
{
    public function run(): void
    {
        $topic = Topic::where('slug', 'tekstbegrip')->firstOrFail();
        $levels = Level::pluck('id', 'slug');

        foreach (require __DIR__.'/content/passages.php' as $row) {
            $passage = Passage::updateOrCreate(['title' => $row['title']], ['body' => $row['body']]);
            $existing = $passage->questions()->pluck('prompt')->all();

            foreach ($row['questions'] as $question) {
                if (in_array($question['prompt'], $existing, true)) {
                    continue;
                }

                $type = QuestionType::from($question['type']);

                $passage->questions()->create([
                    'topic_id' => $topic->id,
                    'level_id' => $levels[$question['level']],
                    'type' => $type,
                    'prompt' => $question['prompt'],
                    'data' => QuestionData::validate($type, $question['data']),
                    'explanation' => $question['explanation'],
                    'difficulty' => $question['difficulty'],
                    'is_published' => true,
                ]);
            }
        }
    }
}
