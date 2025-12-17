<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Tire Management API is running',
        'status' => 'OK',
        'documentation' => request()->root() . '/api/documentation'
    ]);
});
