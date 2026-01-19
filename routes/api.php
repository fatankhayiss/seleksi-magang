<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ImportUserController;

Route::post('/import-users', [ImportUserController::class, 'store']);
Route::get('/import-users/{id}', [ImportUserController::class, 'status']);
