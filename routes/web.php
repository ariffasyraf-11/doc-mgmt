<?php

use App\Http\Controllers\DocumentVersionController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware('auth')->group(function () {
    Route::get('/documents/{document}/versions/create', [DocumentVersionController::class, 'create'])
        ->name('documents.versions.create');
    Route::post('/documents/{document}/versions', [DocumentVersionController::class, 'store'])
        ->name('documents.versions.store');
    Route::get('/documents/{document}/versions/{version}/download', [DocumentVersionController::class, 'download'])
        ->name('documents.versions.download');
});
