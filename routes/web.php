<?php

use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\OwnerAuthController;
use App\Http\Controllers\OwnerProductController;
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
Route::get('/owner/products', [
    OwnerProductController::class,
    'index',
])->name('owner.products');

Route::get('/owner/products/list', [
    OwnerProductController::class,
    'list',
])->name('owner.products.list');

Route::post('/owner/products', [
    OwnerProductController::class,
    'store',
])
    ->name('owner.products.store')
    ->middleware('throttle:20,1');

Route::put('/owner/products/{product}', [
    OwnerProductController::class,
    'update',
])
    ->name('owner.products.update')
    ->middleware('throttle:20,1');

Route::delete('/owner/products/{product}', [
    OwnerProductController::class,
    'destroy',
])
    ->name('owner.products.destroy')
    ->middleware('throttle:20,1');
