<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\PembayaranController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\API\StudentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1.2')->group(function () {
    Route::post('/users/register', [AuthController::class, 'register']);
    Route::post('/users/login', [AuthController::class, 'login']);

    Route::get('/students', [StudentController::class, 'index']);
    Route::post('/students', [StudentController::class, 'addStudent']);
    Route::post('/students/import', [StudentController::class, 'importStudents']);
    Route::get('/students/show/{id}', [StudentController::class, 'showStudent']);
    Route::post('/students/update/{id}', [StudentController::class, 'updateStudent']);
    Route::delete('/students/delete/{id}', [StudentController::class, 'deleteStudent']);
    
    Route::post('/pembayaran/store', [PembayaranController::class, 'submitPembayaran']);
    Route::post('notification/handler', [PembayaranController::class, 'notificationHandler']);

    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/posts', [PostController::class, 'store']);
    Route::get('/posts/show/{id}', [PostController::class, 'show']);
    Route::post('/posts/update/{id}', [PostController::class, 'update']);
    Route::delete('/posts/delete/{id}', [PostController::class, 'destroy']);
});
