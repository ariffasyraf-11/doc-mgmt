<?php

namespace App\Filament\Resources\ReviewPolicies\Pages;

use App\Filament\Resources\ReviewPolicies\DocumentTypeReviewPolicyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateReviewPolicy extends CreateRecord
{
    protected static string $resource = DocumentTypeReviewPolicyResource::class;
}
