<?php

use App\Http\Controllers\Api\AnswerController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\ParticipantController;
use App\Http\Controllers\OptionController;
use App\Http\Controllers\PresentationController;
use App\Http\Controllers\PresentationControlController;
use App\Http\Controllers\PresentationViewController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\QuizController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Quiz Participant Routes (Public)
Route::prefix('quiz')->group(function () {
    Route::get('/join/{presentation}', [QuizController::class, 'join'])->name('quiz.join');
    Route::get('/{presentation}', [QuizController::class, 'play'])->name('quiz.play');
});

// Presentation View Routes (Admin)
Route::get('/presentations/{presentation}/control', [PresentationViewController::class, 'control'])
    ->middleware(['auth'])
    ->name('presentations.control');

Route::get('/presentations/{presentation}/present', [PresentationViewController::class, 'present'])
    ->middleware(['auth'])
    ->name('presentations.present');

// Participant API Routes (for Vue.js AJAX calls)
Route::prefix('api')->group(function () {
    Route::post('/participants', [ParticipantController::class, 'store'])->name('api.participants.store');
    Route::post('/answers', [AnswerController::class, 'submit'])
        ->middleware('participant')
        ->name('api.answers.submit');
    Route::get('/presentations/{presentation}/leaderboard', [LeaderboardController::class, 'show'])
        ->name('api.leaderboard.show');
    Route::get('/presentations/{presentation}/participants/{participantId}/rank', [LeaderboardController::class, 'participant'])
        ->name('api.leaderboard.participant');
});

// Admin Presentation CRUD Routes
Route::middleware(['auth'])->group(function () {
    Route::resource('presentations', PresentationController::class);

    Route::post('presentations/{presentation}/questions/reorder', [QuestionController::class, 'reorder'])
        ->name('presentations.questions.reorder');

    Route::resource('presentations.questions', QuestionController::class)
        ->shallow()
        ->except(['index', 'show', 'create', 'edit']);

    Route::resource('questions.options', OptionController::class)
        ->shallow()
        ->except(['index', 'show', 'create', 'edit']);
});

// Admin Presentation Control Routes
Route::prefix('presentations')->middleware(['auth'])->group(function () {
    Route::post('/{presentation}/questions/{question}/start', [PresentationControlController::class, 'startQuestion'])
        ->name('presentations.questions.start');
    Route::post('/{presentation}/questions/{question}/end', [PresentationControlController::class, 'endQuestion'])
        ->name('presentations.questions.end');
    Route::patch('/{presentation}/status/{status}', [PresentationControlController::class, 'updateStatus'])
        ->name('presentations.status.update');
});

require __DIR__.'/settings.php';
