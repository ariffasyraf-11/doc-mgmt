<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Policies\DocumentPolicy;
use App\Policies\DocumentVersionPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(DocumentVersion::class, DocumentVersionPolicy::class);
    }
}
