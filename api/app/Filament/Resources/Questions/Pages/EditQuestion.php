<?php

declare(strict_types=1);

namespace App\Filament\Resources\Questions\Pages;

use App\Filament\Resources\Questions\Concerns\HandlesQuestionData;
use App\Filament\Resources\Questions\QuestionResource;
use App\Filament\Resources\Questions\Tables\QuestionsTable;
use App\Support\QuestionFormMapper;
use Filament\Resources\Pages\EditRecord;

class EditQuestion extends EditRecord
{
    use HandlesQuestionData;

    protected static string $resource = QuestionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            QuestionsTable::deleteAction(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['type'] = $this->record->type->value;
        $data['editor'] = QuestionFormMapper::toForm($this->record->type, $this->record->data);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->buildQuestionData($data, $this->record);
    }
}
