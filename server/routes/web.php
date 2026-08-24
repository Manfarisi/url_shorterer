<?php

use App\Http\Controllers\LinkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/{code}', [LinkController::class, 'redirect'])->where('code', '[a-zA-Z0-9]+');
