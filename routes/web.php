<?php

use Illuminate\Support\Facades\Route;

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

Route::get('/', function () {
    //return 1;
    return view('welcome');
});

//Route::get("/home/hello","HomeController@hello");
//Route::match(['get','post'],"HomeController@hello");



Route::get('here', function () {
    return "重定向前";
});
Route::get('three', function () {
    return "重定向后";
});
//301  永久重定向
Route::permanentRedirect("here","three");
//302   临时重定向
Route::redirect("here","three");

//Route::any("getOrder","HomeController@getOrder");

Route::get("getOrder/{id}","HomeController@getOrder");
