<?php

use Illuminate\Support\Facades\Route;

Route::post('fill/{resource}/{resourceId?}', 'FillStepController@handle');
Route::post('validate/{resource}/{resourceId?}', 'ValidateStepController@handle');