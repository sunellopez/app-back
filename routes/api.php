<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ExpenseController;

// 🧾 Rutas públicas
Route::post('sign-up', [UserController::class, 'store']);
Route::post('login', [UserController::class, 'login']);

// 🔐 Rutas protegidas
Route::middleware('auth:sanctum')->group(function () {
    
    // Perfil de usuario
    Route::get('profile', [UserController::class, 'show']);
    Route::put('update-profile', [UserController::class, 'updateProfile']);
    Route::post('logout', [UserController::class, 'logout']);

    // Gastos personales
    Route::prefix('expenses')->group(function () {
        Route::get('/', [ExpenseController::class, 'index']);        
        Route::post('/', [ExpenseController::class, 'store']);        
        Route::get('/summary', [ExpenseController::class, 'summary']);
        Route::get('/highest-this-week', [ExpenseController::class, 'getHighestExpenseThisWeek']);
        Route::get('/monthly', [ExpenseController::class, 'getMonthlyExpenses']);
    });
});