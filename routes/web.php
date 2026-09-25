<?php

use App\Http\Controllers\CustomerAuthController;
use App\Http\Controllers\CustomerDashboardController;
use App\Http\Controllers\OwnerActivityLogController;
use App\Http\Controllers\OwnerAuthController;
use App\Http\Controllers\OwnerCustomerController;
use App\Http\Controllers\OwnerDashboardController;
use App\Http\Controllers\OwnerDebtController;
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

// Owner Authentication

Route::post('/owner/login', [
    OwnerAuthController::class,
    'login',
])->name('owner.login.submit');

// Customer Authentication

Route::post('/customer/login', [
    CustomerAuthController::class,
    'login',
])->name('customer.login');

// Customer Dashboard

Route::middleware('customer')->group(function () {
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
});

// Owner Portal

Route::middleware('owner')->group(function () {
    Route::get('/owner/dashboard', [OwnerDashboardController::class, 'index'])->name('owner.dashboard');

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

    Route::get('/owner/debts', [OwnerDebtController::class, 'index'])->name('owner.debts');

    Route::get('/owner/customers', [OwnerCustomerController::class, 'index'])->name('owner.customers');
    Route::get('/owner/customers/list', [OwnerCustomerController::class, 'list'])->name('owner.customers.list');
    Route::post('/owner/customers', [OwnerCustomerController::class, 'store'])->name('owner.customers.store')->middleware('throttle:20,1');
    Route::put('/owner/customers/{customer}', [OwnerCustomerController::class, 'update'])->name('owner.customers.update')->middleware('throttle:20,1');
    Route::delete('/owner/customers/{customer}', [OwnerCustomerController::class, 'destroy'])->name('owner.customers.destroy')->middleware('throttle:20,1');
    Route::post('/owner/customers/{customer}/payments', [OwnerCustomerController::class, 'payment'])->name('owner.customers.payment')->middleware('throttle:20,1');

    Route::get('/owner/activity-logs', [OwnerActivityLogController::class, 'index'])->name('owner.activity-logs');
    Route::get('/owner/activity-logs/list', [OwnerActivityLogController::class, 'list'])->name('owner.activity-logs.list');
});
