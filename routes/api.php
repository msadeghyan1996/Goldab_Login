<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::prefix('v1')->group(function () {

   require_once __DIR__.'/verification.php';

   require_once __DIR__.'/user.php';

});

