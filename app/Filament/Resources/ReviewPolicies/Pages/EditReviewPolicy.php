<?php

namespace App\Filament\Resources\ReviewPolicies\Pages;

use App\Filament\Resources\ReviewPolicies\DocumentTypeReviewPolicyResource;
use Filament\Resources\Pages\EditRecord;

class EditReviewPolicy extends EditRecord
{
    protected static string $resource = DocumentTypeReviewPolicyResource::class;
}
