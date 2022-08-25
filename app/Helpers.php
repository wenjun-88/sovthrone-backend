<?php
use Illuminate\Support\Facades\Route;

function activeRoute($route, $nested = false): string
{
    if ($nested)
    {
        return Route::is($route . '*') ? 'active' : '';
    }
    return (Route::currentRouteName() === $route) ? 'menu-item-active' : '';
}

function responseSuccess($access_token = ['access_token' => ''], $code = 200)
{
    $status = ['success' => true, 'status_code' => $code];
    $merged = array_merge($status, $access_token);
    return response()->json($merged, 200);
}

function responseFailed($access_token = ['access_token' => ''], $code = 500)
{
    $status = ['success' => false, 'status_code' => $code];
    $merged = array_merge($status, $access_token);
    return response()->json($merged, 200);
}
