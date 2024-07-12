<?php

use App\Http\Controllers\Api\V1\AdminController;
use App\Http\Controllers\Api\V1\AuthenticationController;
use App\Http\Controllers\Api\V1\CardController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\RequestController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::prefix('auth')->controller(AuthenticationController::class)->group(function () {
        Route::post('register', 'register');
        Route::get('resend/{email}', 'resend');
        Route::post('verify', 'verify');
        Route::post('login', 'login');
        Route::post('forgot-password', 'forgotPassword');
        Route::post('verify-forgot-password', 'verifyForgotPassword');
        Route::post('resend-forgot-password', 'resendForgotPassword');
        Route::post('change-password', 'changePassword');
    });

    Route::post('flw-webhook', [WalletController::class, 'flwWebhook']);

    Route::middleware('isAuthenticated')->group(function () {
        Route::prefix('auth')->controller(AuthenticationController::class)->group(function () {
            Route::get('user', 'getUser');
            Route::get('logout', 'logout');
        });

        Route::middleware(['isVerified'])->group(function () {
            Route::prefix('profile')->controller(ProfileController::class)->group(function () {
                Route::get('banks', 'banks');
                Route::post('resolve-account', 'resolveAccount');
                Route::post('add-account', 'addBankAccount');
                Route::post('set-pin', 'setPin');
                Route::post('change-pin', 'changePin');
                Route::post('change-password', 'changePassword');
                Route::post('update', 'updateProfile');
                Route::get('delete', 'delete');
            });

            Route::prefix('card')->controller(CardController::class)->group(function () {
                Route::get('index', 'index');
                Route::get('show/{id}', 'show');
            });

            Route::prefix('request')->controller(RequestController::class)->group(function () {
                Route::get('get-brands', 'getBrands');
                Route::post('get-categories', 'getCategories');
                Route::post('', 'store');

            });

            Route::prefix('wallet')->controller(WalletController::class)->group(function () {
                Route::get('balance', 'getUserBalance');
                Route::post('withdraw', 'withdraw');
            });

            Route::resource('notification', NotificationController::class);
            Route::get('notification/read/{id}', [NotificationController::class, 'read']);
            
            Route::group(['middleware' => 'isAdmin', 'prefix' => '/admin'], function () {
                Route::resource('card', CardController::class);
                Route::get('card-brands', [CardController::class, 'getGiftCardBrands']);
                Route::get('card-check/{brand}', [CardController::class, 'checkIfGiftCardExists']);
                Route::prefix('request')->controller(RequestController::class)->group(function () {
                    Route::get('', 'getRequests');                    
                    Route::get('pending', 'getPendingRequests');                    
                    Route::get('/{uuid}', 'getRequest');                    
                    Route::get('/{uuid}/{action}', 'confirmRequest');                    
                });
                Route::controller(AdminController::class)->group(function () {
                    Route::get('users', 'getUsers');
                    Route::get('user/{uuid}', 'getUser');
                    Route::get('transactions', 'getTransactions');                    
                    Route::get('transaction/pending', 'getPendingTransactions');                    
                    Route::get('transaction/{uuid}', 'getTransaction');      
                }); 
            });
        });
    });
});