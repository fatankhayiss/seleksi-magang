<?php declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/import', function () {
    return view('import');
});

Route::get('/users', [UserController::class, 'index']);
