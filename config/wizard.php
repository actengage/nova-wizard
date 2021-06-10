<?php

use Actengage\Wizard\Session;

return [

    'disk' => 'wizard',

    'session' => [
        
        'header' => 'wizard-session-id',

        'model' => Session::class

    ]
    
];