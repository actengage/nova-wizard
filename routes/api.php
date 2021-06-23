<?php

use Illuminate\Support\Facades\Route;

// Custom routes for the plugin.
Route::post('/validate/{resource}/{resourceId?}', 'ValidateStepController@handle');