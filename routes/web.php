<?php

use App\Http\Controllers\DocumentVersionController;
use App\Http\Controllers\DocumentWorkflowController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin');

Route::middleware('auth')->group(function () {
    Route::get('/documents/{document}/workflow', function (\App\Models\Document $document) {
        return redirect()->to(route('filament.admin.resources.documents.workflow', ['record' => $document]));
    })->name('documents.workflow.show');
    Route::get('/documents/{document}/versions/create', [DocumentVersionController::class, 'create'])
        ->name('documents.versions.create');
    Route::post('/documents/{document}/versions', [DocumentVersionController::class, 'store'])
        ->name('documents.versions.store');
    Route::post('/documents/{document}/versions/{version}/verify-integrity', [DocumentWorkflowController::class, 'verifyIntegrity'])
        ->name('documents.versions.verify-integrity');
    Route::post('/documents/{document}/versions/{version}/submit', [DocumentWorkflowController::class, 'submit'])
        ->name('documents.versions.submit');
    Route::post('/documents/{document}/versions/{version}/reviews/{review}/decision', [DocumentWorkflowController::class, 'decide'])
        ->name('documents.versions.reviews.decide');
    Route::post('/documents/{document}/versions/{version}/publish', [DocumentWorkflowController::class, 'publish'])
        ->name('documents.versions.publish');
    Route::post('/documents/{document}/archive', [DocumentWorkflowController::class, 'archive'])
        ->name('documents.archive');
    Route::get('/documents/{document}/versions/{version}/download', [DocumentVersionController::class, 'download'])
        ->name('documents.versions.download');
});
