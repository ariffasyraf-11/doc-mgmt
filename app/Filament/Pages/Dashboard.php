<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DocumentOverviewStats;
use App\Filament\Widgets\PendingReviewQueue;
use App\Filament\Widgets\RecentAuditActivity;
use App\Filament\Widgets\RecentDocuments;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Document Management';

    public function getWidgets(): array
    {
        return [
            DocumentOverviewStats::class,\n            ExpiryWatchlist::class,
            PendingReviewQueue::class,
            RecentDocuments::class,
            RecentAuditActivity::class,
        ];
    }
}
