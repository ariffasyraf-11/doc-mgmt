<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Enums\DocumentWorkflowStatus;
use App\Filament\Resources\Documents\DocumentResource;
use Filament\Resources\Pages\EditRecord;

class EditDocument extends EditRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if ($this->record->workflow_status !== DocumentWorkflowStatus::Draft) {
            unset(
                $data['title'], $data['description'], $data['category_id'],
                $data['document_type_id'], $data['department_id'], $data['visibility'],
                $data['effective_date'], $data['expiry_date'], $data['next_review_date'],
            );
        }

        return $data;
    }
}
