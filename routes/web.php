<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Landing page (public)
Route::get('/', function () {
    return view('landing');
})->name('home');

// Static pages (public)
Route::get('/about', function () {
    return view('pages.about');
})->name('about');

Route::get('/contact', function () {
    return view('pages.contact');
})->name('contact');

Route::get('/privacy', function () {
    return view('pages.privacy');
})->name('privacy');

Route::get('/terms', function () {
    return view('pages.terms');
})->name('terms');

Route::get('/cookies', function () {
    return view('pages.cookies');
})->name('cookies');

// Login route - redirects to the React SPA
Route::get('/login', function () {
    return view('app');
})->name('login');

// SPA catch-all - all other routes serve the React app
Route::get('/{any}', function () {
    return view('app');
})->where('any', '.*');
