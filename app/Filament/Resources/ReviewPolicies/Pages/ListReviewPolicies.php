<?php

namespace App\Filament\Resources\ReviewPolicies\Pages;

use App\Filament\Resources\ReviewPolicies\DocumentTypeReviewPolicyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListReviewPolicies extends ListRecords
{
    protected static string $resource = DocumentTypeReviewPolicyResource::class;

    protected function getHeaderActions(): array { return [CreateAction::make()]; }
}
