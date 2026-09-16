<?php

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