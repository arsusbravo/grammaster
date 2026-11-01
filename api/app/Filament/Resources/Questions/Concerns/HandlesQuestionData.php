<?php

declare(strict_types=1);

namespace App\Filament\Resources\Questions\Concerns;

use App\Enums\QuestionType;
use App\Filament\Resources\Questions\Tables\QuestionsTable;
use App\Models\Question;
use App\Support\QuestionFormMapper;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

/**
 * For the create and edit pages: turns the friendly `editor` fields into questions.data before
 * saving, and shows the Dutch validation problems as a notification instead of saving.
 */
trait HandlesQuestionData
{
    /** @param  array<string, mixed>  $data */
    protected function buildQuestionData(array $data, ?Question $existing = null): array
    {
        try {
            $type = QuestionType::from($data['type'] instanceof QuestionType ? $data['type']->value : $data['type']);
            $data['data'] = QuestionFormMapper::toData($type, $data['editor'] ?? []);
        } catch (ValidationException $e) {
            Notification::make()
                ->danger()
                ->title('De vraag kan nog niet worden opgeslagen')
                ->body(implode("\n", $e->validator->errors()->all()))
                ->persistent()
                ->send();

            $this->halt();
        }

        // A question in a published exam must stay published (students could not save answers otherwise).
        if ($existing && $existing->is_published && empty($data['is_published']) && ($exams = $existing->publishedExamTitles())->isNotEmpty()) {
            QuestionsTable::unpublishBlocked(1, $exams->all());
            $this->halt();
        }

        unset($data['editor']);

        return $data;
    }
}
