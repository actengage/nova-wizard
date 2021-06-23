<?php

use Actengage\Wizard\Session;

return [

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | This value is the name of the storage disk used that is used to store
    | the uploaded files for a given session. This disk is what allows us the
    | wizard to persist uploaded files across each step without having to
    | reupload them with each request.
    |
    */
    
    'disk' => 'wizard',

    /*
    |--------------------------------------------------------------------------
    | Session Options
    |--------------------------------------------------------------------------
    |
    | This value defines the required options for the session. The `header`
    | defines the key/value pair that will store the session id. The `model`
    | stores the Eloquent model for the session. And the `ttl` is the length of
    | time a session is valid. 
    |
    */
    'session' => [
        
        'header' => 'wizard-session-id',

        'model' => Session::class,
        
        'ttl' => '1 day'

    ]
    
];