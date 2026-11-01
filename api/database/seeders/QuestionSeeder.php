<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Models\Level;
use App\Models\Question;
use App\Models\Topic;
use App\Support\QuestionData;
use Illuminate\Database\Seeder;

/**
 * Seeds Dutch questions from database/seeders/content/{question_type}.php.
 * Idempotent: a question that already exists (same type and data) is left alone.
 */
class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        $topics = Topic::pluck('id', 'slug');
        $levels = Level::pluck('id', 'slug');

        $existing = Question::all()
            ->mapWithKeys(fn (Question $q) => [self::fingerprint($q->type, $q->data) => true]);

        foreach (QuestionType::cases() as $type) {
            foreach (require __DIR__.'/content/'.$type->value.'.php' as $row) {
                $data = QuestionData::validate($type, $row['data']);

                if ($existing->has(self::fingerprint($type, $data))) {
                    continue;
                }

                Question::create([
                    'topic_id' => $topics[$row['topic']],
                    'level_id' => $levels[$row['level']],
                    'type' => $type,
                    'prompt' => $row['prompt'],
                    'data' => $row['data'],
                    'explanation' => $row['explanation'],
                    'difficulty' => $row['difficulty'],
                    'is_published' => true,
                ]);
            }
        }
    }

    /**
     * MySQL normalizes the key order of JSON columns, so compare with keys sorted recursively.
     *
     * @param  array<string, mixed>  $data
     */
    private static function fingerprint(QuestionType $type, array $data): string
    {
        return $type->value.':'.md5(json_encode(self::sortKeys($data), JSON_UNESCAPED_UNICODE));
    }

    private static function sortKeys(array $value): array
    {
        foreach ($value as $key => $item) {
            if (is_array($item)) {
                $value[$key] = self::sortKeys($item);
            }
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return $value;
    }
}
