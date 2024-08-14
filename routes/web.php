<?php

use Illuminate\Support\Facades\Route;
use Yajra\DataTables\Facades\DataTables;


Route::get('/', function () {
    return response()->json([], 404);
});
