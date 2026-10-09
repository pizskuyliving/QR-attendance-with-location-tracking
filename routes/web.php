<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/absen')->name('home');

// Dashboard bawaan starter kit diarahkan ke halaman absen.
Route::get('dashboard', function (Request $request) {
    return $request->user()->role === 'admin'
        ? redirect()->route('admin.harian')
        : redirect()->route('absen.scan');
})->middleware('auth')->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/absen.php';