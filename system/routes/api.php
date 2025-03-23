<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;


Route::post('/login',[AuthController::class,'login']);
// Route::get('/setup',[SetupController::class,'setup']);


Route::group(['middleware' => ['auth:sanctum']], function () {
    Route::get('/validate', [AuthController::class,'validate_token']);
    Route::get('/logout', [AuthController::class,'logout']);

    // role management routes
    Route::post('/new-role', [RoleController::class,'store']);
    Route::post('/update-role/{id}', [RoleController::class,'update']);
    Route::get('/roles/{id?}', [RoleController::class,'read']);
    Route::delete('/role/{id}', [RoleController::class,'delete']);
    
    // product management routes
    Route::post('/new-product', [ProductController::class,'store']);
    Route::post('/update-product/{id}', [ProductController::class,'update']);
    Route::get('/products/{page?}/{offset?}', [ProductController::class,'read']);
    Route::get('/product-details/{id}', [ProductController::class,'details']);
    Route::delete('/product/{id}', [ProductController::class,'delete']);
   
    // client management routes
    Route::post('/new-client', [ClientController::class,'store']);
    Route::post('/update-client/{id}', [ClientController::class,'update']);
    Route::get('/clients/{page?}/{offset?}', [ClientController::class,'read']);
    Route::get('/client-details/{id}', [ClientController::class,'details']);
    Route::delete('/client/{id}', [ClientController::class,'delete']);
    
    // vehicle management routes
    Route::post('/new-vehicle', [VehicleController::class,'store']);
    Route::post('/update-vehicle/{id}', [VehicleController::class,'update']);
    Route::get('/vehicles/{page?}/{offset?}', [VehicleController::class,'read']);
    Route::get('/all-vehicles', [VehicleController::class,'readAll']);
    Route::get('/vehicle-details/{id}', [VehicleController::class,'details']);
    Route::delete('/vehicle/{id}', [VehicleController::class,'delete']);

    // user management routes
    Route::post('/new-user', [UserController::class,'store']);
    Route::post('/update-user/{id}', [UserController::class,'update']);
    Route::get('/users/{page?}/{offset?}', [UserController::class,'read']);
    Route::post('/user-details/{id}', [UserController::class,'details']);
    Route::delete('/user/{id}', [UserController::class,'delete']);
    Route::get('/toggle-user-status/{id}',[UserController::class,'toggleStatus']);
    Route::get('/toggle-user-block-status/{id}',[UserController::class,'toggleBlock']);
    Route::get('/update-user-phone/{id}/{phone}', [UserController::class,'changePhone']);
    Route::get('/update-user-email/{id}/{email}', [UserController::class,'changeEmail']);
    Route::post('/update-user-password', [UserController::class,'changePassword']);


    // transaction management routes
    Route::post('/new-transaction', [TransactionController::class,'store']);
    Route::post('/update-transaction/{id}', [TransactionController::class,'update']);
    Route::get('/transactions/{page?}/{offset?}', [TransactionController::class,'read']);
    Route::get('/transaction-details/{id}', [TransactionController::class,'details']);
    Route::delete('/transaction/{id}', [TransactionController::class,'delete']);
    Route::post('/search-transactions/{page?}/{offset?}', [TransactionController::class,'search']);
    route::post('/export-transactions', [TransactionController::class,'export']);

    Route::post('/dashboard-analytics', [App\Http\Controllers\DashboardController::class,'getAnalytics']);
    Route::post('/user-payroll',[App\Http\Controllers\PayrollController::class,'downloadPayroll']);

    
});

