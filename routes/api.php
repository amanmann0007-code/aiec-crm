<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiUserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\FollowUpController;
use App\Http\Controllers\RemarkController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TelecallerLeadController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', [ApiUserController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/customers', [CustomerController::class, 'store'])
        ->middleware('role:admin,receptionist,director');
    Route::post('/customers/{customer}/remarks', [RemarkController::class, 'store']);
    Route::post('/customers/{customer}/documents', [CustomerController::class, 'uploadDocuments']);
    Route::delete('/documents/{document}', [CustomerController::class, 'deleteDocument']);

    Route::get('/follow-ups', [FollowUpController::class, 'index']);

    Route::get('/search/customers', [SearchController::class, 'customers']);

    Route::get('/telecaller/leads', [TelecallerLeadController::class, 'index'])
        ->middleware('role:telecaller');
    Route::post('/telecaller/leads', [TelecallerLeadController::class, 'store'])
        ->middleware('role:telecaller');
    Route::post('/telecaller/customers', [CustomerController::class, 'storeTelecaller'])
        ->middleware('role:telecaller');
});
