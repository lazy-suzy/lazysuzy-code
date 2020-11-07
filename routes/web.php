<?php

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



// mail template checking route

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/mail-test', 'MailerController@send_catalogue')->name('catalogue');


Route::get('/', 'HomeController@index')->name('index');
Route::get('/products/{dept}/{cat?}', 'ProductController@index')->name('products');
Route::get('/products/{dept}', 'ProductController@index')->name('categories');
Route::get('/product/{sku}', 'PdpController@index')->name('index');
Route::get('/login', 'Auth\LoginController@index')->name('signup');
Route::get('/wishlist', function () {
    return view('pages.wishlist');
});
Route::get('/search', function () {
    return view('pages.search');
});
Route::get('/privacypolicy', function () {
    return view('pages.privacypolicy');
});
Route::get('/termsofservice', function () {
    return view('pages.terms');
});
Route::get('/aboutus', function () {
    return view('pages.aboutus');
});
Route::get('/brand/{brand_name}', function () {
    return view('pages.brands');
});
Route::get('/category', function () {
    return view('pages.category');
});


Auth::routes();

// Route::get('/logout', 'Auth\LoginController@logout');
Route::get('/home', 'HomeController@index')->name('home');
Route::get('redirect/{driver}', 'Auth\LoginController@redirectToProvider')
    ->name('login.provider')
    ->where('driver', implode('|', config('auth.socialite.drivers')));
Route::get('login/{driver}/callback', 'Auth\LoginController@handleProviderCallback')
    ->name('login.callback')
    ->where('driver', implode('|', config('auth.socialite.drivers')));


/*
*   API ROUTES
*/

// user routes
Route::post('/api/login', 'Auth\UserController@login')->name('login');
Route::post('/api/register', 'Auth\UserController@register');
Route::post('/api/user/update', 'Auth\UserController@update')->middleware('auth:api');
Route::post('/api/user/details/update', 'Auth\UserController@details_update')->middleware('auth:api');
Route::get('/api/logout', 'Auth\UserController@logout')->middleware('auth:api');
Route::get('/api/user/keepalive', 'Auth\UserController@keepAlive');
Route::get('/api/get-user', 'API@get_user')->middleware(['cors', 'auth:api'])->name('get-user');

// this will take username and send the user details object in response
Route::get('/api/user/details', 'API@user_details')->middleware(['cors', 'auth:api'])->name('user-details');


// deals
Route::get('/api/deals', 'API@get_deals')->name('get-deals');

Route::get('/api/collections', 'API@get_all_collections')->middleware('auth:api')->name('get-collections');
Route::get('/api/collection', 'API@get_collection_details')->middleware('auth:api')->name('get-collection-details');

Route::get('/api/brand/{key?}', 'API@get_all_brands')->middleware('auth:api')->name('get_all_brands');
Route::get('/api/categories/{dept}', 'API@get_categories')->middleware('auth:api')->name('cat-api');
Route::get('/api/all-departments', 'DepartmentController@index')->middleware('auth:api')->name('get_all_departments');
Route::get('/api/departments/{dept}', 'DepartmentController@get_department')->middleware('auth:api')->name('get_department');
Route::get('/api/categories', 'CategoryController@get_all_categories')->middleware('auth:api')->name('get_category');
Route::get('/api/products/{dept}/{cat?}/{subCat?}', 'API@filter_products')->middleware('auth:api')->name('get-products');
Route::get('/api/products/{dept}/{cat}', 'API@filter_products')->middleware('auth:api')->name('category');
Route::get('/api/product/{sku}', 'API@get_product_details')->middleware(['auth:api', 'cached'])->name('get-product-details');

// has a filter attached. request has format attribute_1=<val>&attribute_2=<val> and so on...
Route::get('/api/variation/product/{sku}', 'API@get_product_variations')->name('get-product-variations');

// takes a swatch image url as input ?swatch=<url>
Route::get('/api/filters/variation/product/{sku}', 'API@get_swatch_filter')->name('get-product-attribute-filters');

Route::get('/api/wishlist', 'API@get_wishlist')->middleware('auth:api')->name('get-wishlist');
Route::get('/api/mark/favourite/{sku}', 'API@mark_favourite')->middleware('auth:api')->name('mark-favourite');
Route::get('/api/unmark/favourite/{sku}', 'API@unmark_favourite')->middleware('auth:api')->name('unmark-favourite');
Route::get('/api/subscribe', 'API@subscribe_user')->name('subscribe');
Route::get('/api/banners', 'API@get_banners')->name('banners');

// redundant
Route::get('/wishlist', 'ProductController@showWishList')->name('show-wishlist');

// cart and transaction apis
Route::post('/api/cart/add', 'API@add_to_cart')->middleware(['auth:api'])->name('add-to-cart');
Route::post('/api/cart/remove', 'API@remove_from_cart')->middleware(['auth:api'])->name('remove-from-cart');
Route::get('/api/cart', 'API@get_cart')->name('get-cart')->middleware(['auth:api']);

// payment routes
Route::post('/api/payment/charge', 'Payment\PaymentController@charge_client')->middleware(['auth:api'])->name('client-secret');
Route::get('/api/order', 'Payment\PaymentController@get_order')->middleware(['auth:api'])->name('get-order');

// inventory
Route::get('/api/inventory', 'API@get_inventory')->name('get-inventory');

// Board Routes
Route::get('/api/board/asset/preview', '\App\Board\Controllers\BoardController@get_asset_for_preview')->middleware(['cors']);
Route::get('/api/board/asset/{id?}', '\App\Board\Controllers\BoardController@get_asset')->middleware(['cors', 'auth:api']);
Route::post('/api/board/asset/{id?}', '\App\Board\Controllers\BoardController@update_asset')->middleware(['cors', 'auth:api']);
Route::get('/api/board/preview/{id}', '\App\Board\Controllers\BoardController@get_board_for_preview')->middleware(['cors']);
Route::get('/api/board/{id?}', '\App\Board\Controllers\BoardController@get_board')->middleware(['cors', 'auth:api']);
Route::post('/api/board/{id?}', '\App\Board\Controllers\BoardController@update_board')->middleware(['cors', 'auth:api']);
Route::post('/api/board/like/{id}', '\App\Board\Controllers\BoardController@like_board')->middleware(['cors', 'auth:api']);
Route::post('/api/board/unlike/{id}', '\App\Board\Controllers\BoardController@unlike_board')->middleware(['cors', 'auth:api']);
Route::get('/api/board/get/all', '\App\Board\Controllers\BoardController@get_all_boards')->middleware(['cors', 'auth:api']);
Route::get('/api/board/get/options', '\App\Board\Controllers\BoardController@get_all_options')->middleware(['cors', 'auth:api']);


// search keywords
Route::get('/api/search-keywords', 'SearchController@get_all')->middleware(['cors'])->name('search-keywords');

/* ==================================================BACKEND ADMIN APIS========================================== */

Route::middleware(['auth:api', 'cors', 'admin'])->group(function () {


    Route::get('/api/admin/products/{dept}/{cat?}/{subCat?}', 'Admin\Dashboard@filter_products')->name('admin-get-products');
    Route::get('/api/admin/products/{dept}/{cat}', 'Admin\Dashboard@filter_products')->name('admin-category');
    Route::get('/api/admin/product/{sku}', 'Admin\Dashboard@get_product_details')->name('admin-get-product-details');
    Route::post('/api/admin/mark/image', 'Admin\Dashboard@mark_image')->name('mark-image');


    Route::group(['prefix' => '/api/admin/new-products'], function () {
        Route::get('', 'Admin\NewProductsController@get_new_products_list')->name('new_product.list');
        Route::get('next', 'Admin\NewProductsController@get_next_new_product')->name('new_product.next');
        Route::post('update/{id}', 'Admin\NewProductsController@update_new_product')->name('new.product.update');
        Route::post('update-multiple', 'Admin\NewProductsController@update_multiple_new_product')->name('new.product.update.multiple');
        Route::get('{id}', 'Admin\NewProductsController@get_new_product')->name('new.product');
    });
    
});

Route::group([
    'prefix' => '/api/password'
], function () {
    Route::post('create', 'Auth\ResetPasswordController@create');
    Route::get('find/{token}', 'Auth\ResetPasswordController@find');
    Route::post('reset', 'Auth\ResetPasswordController@reset');
});

/* ============================================================================================================== */