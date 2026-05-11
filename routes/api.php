<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SalarySlipController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('api.token')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
    Route::get('/dashboard/chart', [DashboardController::class, 'chart']);
    Route::get('/dashboard/recent-orders', [DashboardController::class, 'recentOrders']);

    Route::post('/products/bulk-update-stock', [ProductController::class, 'bulkUpdateStock']);
    Route::apiResource('products', ProductController::class);

    Route::get('/orders/export/pdf', [OrderController::class, 'exportPdf']);
    Route::post('/orders/validate-stock', [OrderController::class, 'validateStock']);
    Route::apiResource('orders', OrderController::class);

    Route::get('/salary-slips/export/pdf', [SalarySlipController::class, 'exportPdf']);
    Route::apiResource('salary-slips', SalarySlipController::class);

    Route::post('/users/check-email', [UserController::class, 'checkEmail']);
    Route::put('/users/change-password', [UserController::class, 'changePassword']);
    Route::apiResource('users', UserController::class);
});
