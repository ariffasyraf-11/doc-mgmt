<?php

namespace App\Filament\Widgets;

use App\Enums\DocumentWorkflowStatus;
use App\Filament\Resources\Documents\DocumentResource;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DocumentOverviewStats extends BaseWidget
{
    protected function getStats(): array
    {
        $documents = DocumentResource::getEloquentQuery();
        $user = auth()->user();

        return [
            Stat::make('Accessible documents', (clone $documents)->count())
                ->description('Documents available to your account')
                ->icon('heroicon-o-document-text'),
            Stat::make('Published', (clone $documents)->where('workflow_status', DocumentWorkflowStatus::Published->value)->count())
                ->description('Currently published documents')
                ->icon('heroicon-o-check-circle')
                ->color('success'),
            Stat::make('Pending reviews', $user?->assignedReviews()->where('status', 'pending')->count() ?? 0)
                ->description('Reviews assigned to you')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('warning'),
            Stat::make('Expired', (clone $documents)->whereNotNull('expiry_date')->whereDate('expiry_date', '<=', today())->count())
                ->description('Accessible documents past their expiry date')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger'),
            Stat::make('Expiring in 30 days', (clone $documents)
                ->whereNotNull('expiry_date')
                ->whereBetween('expiry_date', [today(), today()->addDays(30)])
                ->count())
                ->description('Accessible documents approaching expiry')
                ->icon('heroicon-o-clock')
                ->color('warning'),
        ];
    }
}
