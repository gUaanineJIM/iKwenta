<?php

use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('landing');
})->name('landing');

Route::get('/customer/login', function () {
    return redirect()->route('landing', '#login');
})->name('customer.login');

Route::get('/owner/login', function () {
    return redirect()->route('landing', '#login');
})->name('owner.login');

// Customer Dashboard

Route::post('/customer/login', [
    CustomerAuthController::class,
    'login',
])->name('customer.login');

Route::get('/customer/dashboard', [
    CustomerDashboardController::class,
    'index',
])->name('customer.dashboard');

Route::get('/customer/dashboard/section/{section}', [
    CustomerDashboardController::class,
    'section',
])
    ->whereIn('section', ['overview', 'debts', 'items', 'payments'])
    ->name('customer.dashboard.section');

Route::post('/customer/logout', [
    CustomerAuthController::class,
    'logout',
])->name('customer.logout');

// Owner Dashboard

Route::get('/owner/dashboard', function () {
    return view('owner.dashboard');
})->name('owner.dashboard');
