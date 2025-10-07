<?php

$router->get('/', function () use ($router) {
    return response()->json([
        'success' => true,
        'message' => 'OTP Authentication API',
        'version' => $router->app->version(),
    ]);
});

$router->group(['prefix' => 'api/v1', 'middleware' => 'throttle:10,1'], function () use ($router) {
    $router->post('auth/check-mobile', 'AuthController@checkMobile');
    $router->post('auth/login', 'AuthController@login');
    $router->post('auth/request-otp', 'AuthController@requestOtp');
    $router->post('auth/register', 'AuthController@register');
});

$router->group(['prefix' => 'api/v1', 'middleware' => 'auth'], function () use ($router) {
    $router->get('auth/me', 'AuthController@me');
});

