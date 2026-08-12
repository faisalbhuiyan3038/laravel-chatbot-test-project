<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FaqChatController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return redirect()->route('chat.index');
});

// Public Chat routes
Route::get('/chat', [FaqChatController::class, 'index'])->name('chat.index');
Route::post('/chat/ask', [FaqChatController::class, 'ask'])
    ->middleware('throttle:30,1')
    ->name('chat.ask');
Route::post('/chat/feedback', [FeedbackController::class, 'store'])->name('chat.feedback');

// Guest Auth Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/admin/feedbacks', [FeedbackController::class, 'dashboard'])->name('admin.feedbacks');
    Route::delete('/admin/feedbacks/{id}', [FeedbackController::class, 'destroy'])->name('admin.feedbacks.destroy');
});