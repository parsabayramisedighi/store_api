<?
require('include/functions.php');
require('core/Router.php');
require('core/middlewares.php');
load_env('include/.env');
$router = new Router();


$router->middleware($admin, function($router){
    $router->add('GET', 'api/admin/products', 'ProductController@index');
    $router->add('GET', 'api/admin/products/show', 'ProductController@adminShow');
    $router->add('POST', 'api/admin/products/create', 'ProductController@create');
    $router->add('POST', 'api/admin/products/delete', 'ProductController@delete');
    $router->add('POST', 'api/admin/products/update', 'ProductController@update');
    
    $router->add('GET', 'api/admin/users', 'UserController@index');
    $router->add('GET', 'api/admin/users/show', 'UserController@show');
    $router->add('POST', 'api/admin/users/update', 'UserController@userUpdate');
    $router->add('POST', 'api/admin/users/delete', 'UserController@delete');
    
    $router->add('POST', 'api/admin/categories/create', 'CategoryController@create');
    $router->add('POST', 'api/admin/categories/delete', 'CategoryController@delete');
});

$router->middleware($auth, function($router){
    $router->add('GET', 'api/user/profile', 'UserController@profile'); 
    $router->add('POST', 'api/user/profile/update', 'UserController@profileUpdate'); 
    
    $router->add('GET', 'api/user/cart', 'CartController@index'); 
    $router->add('POST', 'api/user/cart/update', 'CartController@addOrUpdateCartItem');
    $router->add('POST', 'api/user/cart/delete', 'CartController@deleteItem');
    
    $router->add('POST', 'api/logout', 'AuthController@logout');
});


$router->add('GET', 'api/categories', 'CategoryController@index');
$router->add('GET', 'api/categories/show', 'CategoryController@show');

$router->add('GET', 'api/products', 'ProductController@publicIndex');
$router->add('GET', 'api/products/show', 'ProductController@show');

$router->add('POST', 'api/register', 'AuthController@register');
$router->add('POST', 'api/login', 'AuthController@login');

$router->run();


?>
