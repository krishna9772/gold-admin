<?php

use Illuminate\Support\Facades\Route;
use Modules\Tag\Http\Controllers\TagController;


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

    Route::group(['prefix' => '/tags', 'as' => 'tags.'], function () {
        Route::get('/index_list', [TagController::class, 'index_list'])->name('index_list');
        Route::get('/index_data', [TagController::class, 'index_data'])->name('index_data');
        Route::get('export', [TagController::class, 'export'])->name('export');
        Route::get('/trashed', [TagController::class, 'trashed'])->name('trashed');
        Route::post('bulk-action', [TagController::class, 'bulk_action'])->name('bulk_action');
        Route::post('update-status/{id}', [TagController::class, 'update_status'])->name('update_status');
        Route::post('restore/{id}', [TagController::class, 'restore'])->name('restore');
        Route::delete('force-delete/{id}', [TagController::class, 'forceDelete'])->name('force_delete');
    });
    Route::resource('tags', TagController::class)->names('tags');

});
