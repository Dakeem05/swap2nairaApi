<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/images/{image}', function ($image) {
    $basePath = public_path() . '/uploads/images/';

    if (File::exists($basePath . 'brandImages/' . $image)) {
        return response()->file($basePath . 'brandImages/'. $image);
    } else if (File::exists($basePath . 'profileImages/' . $image)) {
        return response()->file($basePath . 'profileImages/'. $image);
    }  else if (File::exists($basePath . 'physicalCards/' . $image)) {
        return response()->file($basePath . 'physicalCards/'. $image);
    }  else if (File::exists($basePath . 'rejectionImages/' . $image)) {
        return response()->file($basePath . 'rejectionImages/'. $image);
    } else {
        return response('Not found', 404);
    }
});