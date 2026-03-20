<?php

use Illuminate\Support\Facades\Route;

// Landing pages
Route::get('/', function () {
    return view('home');
});

Route::get('/functionalitati', function () {
    return view('functionalitati');
});

Route::get('/preturi', function () {
    return view('preturi');
});

Route::get('/despre', function () {
    return view('despre');
});

Route::get('/blog', function () {
    return view('blog');
});

Route::get('/contact', function () {
    return view('contact');
});
