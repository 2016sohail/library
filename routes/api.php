<?php

use Illuminate\Http\Request;

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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::post('login', 'UserController@login');
Route::group(['middleware' => ['jwt.verify']], function() {
    Route::post('logout', 'UserController@logout');
    Route::resource('users', 'UserController');
    Route::group(['prefix'=>'books'], function(){
        Route::post('store', 'BooksController@store');
        Route::post('add-user', 'BooksController@add_user');
        Route::get('user-books', 'BooksController@user_books');
    });

    Route::delete('books/{b_id}', 'BooksController@delete');
    Route::get('books/all', 'BooksController@show');
    Route::get('books/show/{b_id}', 'BooksController@show');
});
Route::post('users/store', 'UserController@store');
Route::delete('users/delete', 'UserController@delete');
// Verb          Path                        Action  Route Name
// GET           /users                      index   users.index
// GET           /users/create               create  users.create
// POST          /users                      store   users.store
// GET           /users/{user}               show    users.show
// GET           /users/{user}/edit          edit    users.edit
// PUT|PATCH     /users/{user}               update  users.update
// DELETE        /users/{user}               destroy users.destroy