<?php

// A simple test to see if session is saved after a StreamedResponse
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// We can just look at Laravel's StartSession middleware.
