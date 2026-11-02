<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DocumentOverviewStats;
use App\Filament\Widgets\RecentDocuments;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Document Management';

    public function getWidgets(): array
    {
        return [
            DocumentOverviewStats::class,
            PendingReviewQueue::class,
            RecentDocuments::class,
            RecentAuditActivity::class,
        ];
    }
}
