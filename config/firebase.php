<?php

/*
|--------------------------------------------------------------------------
| Firebase configuration
|--------------------------------------------------------------------------
|
| Configuration details for firebase service
|
*/

return [
  "base_url" => env('FIREBASE_BASE_URL'),
  "config"  => resource_path("configs/firebase.json"),
];