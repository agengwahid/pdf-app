<?php

use App\Http\Controllers\PdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PdfController::class, 'index'])->name('home');

Route::prefix('pdf')->group(function () {
    Route::post('/thumbnails', [PdfController::class, 'renderThumbnails'])->name('pdf.thumbnails');
    Route::post('/merge', [PdfController::class, 'merge'])->name('pdf.merge');
    Route::post('/compress', [PdfController::class, 'compress'])->name('pdf.compress');
    Route::post('/split', [PdfController::class, 'split'])->name('pdf.split');
    Route::post('/manage', [PdfController::class, 'manage'])->name('pdf.manage');
    Route::get('/download/{id}', [PdfController::class, 'download'])->name('pdf.download');
    Route::delete('/history/{id}', [PdfController::class, 'deleteHistory'])->name('pdf.history.delete');
    Route::delete('/history', [PdfController::class, 'clearHistory'])->name('pdf.history.clear');
});
