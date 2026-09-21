<?php

use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\OwnerAuthController;
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

Route::post('/owner/login', [
    OwnerAuthController::class,
    'login',
])->name('owner.login.submit');

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

Route::get('/owner/dashboard', function (\Illuminate\Http\Request $request) {
    if (! $request->session()->has('owner_id')) {
        return redirect()->route('landing', ['#login']);
    }

    return view('owner.dashboard');
})->name('owner.dashboard');

Route::post('/owner/logout', [
    OwnerAuthController::class,
    'logout',
])->name('owner.logout');
