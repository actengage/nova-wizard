<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::prefix('nova-vendor/wizard')->group(function() {
    Route::get('test', function(Request $request) { 
        $session = app('wizard.session');
        $session->restore(request());

        return view('wizard::test', [
            'request' => $request
        ]);
    });

    Route::post('test', function() {
        $session = app('wizard.session');
        $session->merge(request());
        $session->user()->associate(auth()->user());
        $session->model_id = 1;
        $session->model_type = 'App\\Polls\\Poll';
        $session->save();
    
        return redirect('nova-vendor/wizard/test?wizard-session-id='.$session->id);
    });
});


// Add these post routes to the api so we can POST, instead of the default GET method.
/*
Route::prefix('nova-api')
    ->namespace('\\Laravel\\Nova\\Http\\Controllers')
    ->group(function() {
        Route::post('/{resource}/creation-fields', 'CreationFieldController@index');
        Route::post('/{resource}/{resourceId}/update-fields', 'UpdateFieldController@index');
    });
*/

// Custom routes for the plugin.
Route::prefix('nova-vendor/wizard')
    ->namespace('\\Actengage\\Wizard\\Http\\Controllers')
    ->group(function() {
        Route::post('/fill/{resource}/{resourceId?}', 'FillStepController@handle');
        Route::post('/validate/{resource}/{resourceId?}', 'ValidateStepController@handle');
    });