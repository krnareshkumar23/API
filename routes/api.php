<?php


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| All API routes use the api middleware group
|
*/

Route::group(['prefix' => 'v1'], function () {

    Route::group(['prefix' => 'news'], function () {
        Route::get('/', 'API\V1\NewsController@getAll' );
        Route::get('/single', 'API\V1\NewsController@getById' );
    });

    Route::group(['prefix' => 'notifications'], function() {
        Route::get('/', 'API\V1\NotificationController@all');
        Route::put('/mark-read', 'API\V1\NotificationController@markAllRead');
        Route::put('/{id}/mark-read', 'API\V1\NotificationController@markRead');
    });
});
