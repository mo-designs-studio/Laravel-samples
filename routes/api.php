<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use App\Http\Resources\Admin\UserResource;

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

Route::namespace('Api')->prefix('web')->middleware('auth:sanctum')->group(function () {
    Route::post('verifyByToken', 'AuthController@verifyByToken');
    Route::post('change-password', 'AuthController@changePassword');
    Route::apiResource('PropertiesAds', 'PropertiesAdsController')->only('store', 'update', 'show');
    Route::get('PropertiesAds/{id}', 'PropertiesAdsController@show');
    Route::get('MyPropertiesAds', 'PropertiesAdsController@MyPropertiesAds');
    Route::apiResource('GovernoratesAndDistricts', 'GovernoratesAndDistrictsController');
    Route::get('my-properties', 'PropertiesAdsController@getMyProperties');
    Route::post('report-ad', 'PropertiesAdsController@reportAd');

});

Route::namespace('Api\Web')->prefix('web')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('users', 'UsersController')->except('store');
    Route::post('toggleFavorite', 'FavoritesController@toggleFavorite');
    Route::post('syncFavorites', 'FavoritesController@syncFavorites');
    Route::get('getAllFavorites', 'FavoritesController@getAllFavorites');
    Route::put('users', 'UsersController@update');
    Route::apiResource('contacts', 'ContactsController')->except('show');
    Route::get('contactsMethods', 'ContactsController@contactsMethods');
    Route::post('mark-notifications-as-read', 'UsersController@markUserNotificationsAsRead');
    Route::get('get-user-notifications','UsersController@getUserNotifications');
});

Route::namespace('Api\Web')->prefix('web')->group(function () {
    Route::post('users', 'UsersController@store');
    Route::get('countries-codes', 'CountriesCodesController@index');
    Route::get('contacts/{id}', 'ContactsController@show');
});

Route::namespace('Api')->prefix('web')->group(function () {
    Route::apiResource('GovernoratesAndDistricts', 'GovernoratesAndDistrictsController');
    Route::post('getRefusedOrBlockedReasons', 'Admin\MasterData\RefusedOrBlockedReasonsController@getRefusedOrBlockedReasons');
    Route::get('get-last-added-ads', 'PropertiesAdsController@getLastAddedAds');
    Route::post('getLocalSavedFavorites', 'Web\FavoritesController@getLocalSavedFavorites');
});


Route::namespace('Api')->group(function () {
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('socialLogin', 'AuthController@socialLogin');
    Route::post('reset-password', 'AuthController@resetPassword');
    Route::post('reset-password', 'AuthController@resetPassword');
    Route::get('countries', 'Admin\MasterData\CountriesController@index');
    Route::get('PropertiesCategories', 'Admin\MasterData\PropertiesCategoriesController@index');
    Route::get('PropertiesCategories/{id}', 'Admin\MasterData\PropertiesCategoriesController@show');
    Route::get('PropertiesFeatures', 'Admin\MasterData\PropertiesFeaturesController@index');
    Route::get('PropertiesFeatures/{id}', 'Admin\MasterData\PropertiesFeaturesController@show');
    Route::get('PropertiesAds/{id}', 'PropertiesAdsController@show');
    Route::get('search-properties-ads', 'PropertiesAdsController@searchPropertiesAds');
    Route::get('searchable-categories', 'Admin\MasterData\PropertiesCategoriesController@getSearchableCategories');
    Route::get('advanced-searchable-categories', 'Admin\MasterData\PropertiesCategoriesController@getAdvancedSearchableCategories');
    Route::get('checkApiConnection', function () {
        return response([], 200);
    });
    Route::get('get-articles', 'Admin\MasterData\ArticlesController@getArticles');
    Route::get('get-article/{id}', 'Admin\MasterData\ArticlesController@show');
    Route::get('get-articles-by-type/{id}', 'Admin\MasterData\ArticlesController@getArticlesByType');
    Route::get('get-articles-types', 'Admin\MasterData\ArticlesController@getArticlesTypes');
    Route::get('get-last-agents','Web\UsersController@getAgents');
    Route::get('get-all-agents','Web\UsersController@getAllAgents');
    Route::get('show-agent/{id}','Web\UsersController@showAgent');
    //Route::get('update_images_names','PropertiesAdsController@updateImagesNames');
});

Broadcast::routes(['middleware' => 'auth:sanctum']);
