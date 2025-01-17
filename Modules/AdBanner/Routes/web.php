<?php

use Illuminate\Support\Facades\Route;
use Modules\AdBanner\Http\Controllers\AdBannerController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['prefix' => 'app', 'as' => 'backend.', 'middleware' => ['auth','admin']], function () {

    Route::group(['prefix' => '/adbanner', 'as' => 'adbanner.'], function () {
        Route::get('/index_list', [AdBannerController::class, 'index_list'])->name('index_list');
        Route::get('/index_data', [AdBannerController::class, 'index_data'])->name('index_data');
        Route::get('export', [AdBannerController::class, 'export'])->name('export');
        Route::get('/trashed', [AdBannerController::class, 'trashed'])->name('trashed');
        Route::post('bulk-action', [AdBannerController::class, 'bulk_action'])->name('bulk_action');
        Route::post('update-status/{id}', [AdBannerController::class, 'update_status'])->name('update_status');
        Route::post('restore/{id}', [AdBannerController::class, 'restore'])->name('restore');
        Route::delete('force-delete/{id}', [AdBannerController::class, 'forceDelete'])->name('force_delete');
    });
    Route::resource('adbanner', AdBannerController::class)->names('adbanner');

});
