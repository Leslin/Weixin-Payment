<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\Route;
Route::resource(':version/pay','api/:version.Pay');
Route::rule(':version/swiftpass/submitOrderInfo','api/:version.swiftpass/submitOrderInfo','GET'); 
Route::rule(':version/test/index','api/:version.test/index','POST'); 
Route::rule(':version/inputpay/createOrder','api/:version.inputpay/createOrder');
Route::rule(':version/inputpay/test','api/:version.inputpay/test','GET');
Route::rule(':version/inputpay/testpay','api/:version.inputpay/testpay','GET');
Route::rule(':version/inputpay/index','api/:version.inputpay/index','GET');
Route::rule(':version/topay/pay/:token','api/:version.Topay/pay','GET'); 
Route::rule(':version/notify/index','api/:version.Notify/index','POST'); 
Route::rule(':version/inputnotify/index','api/:version.Inputnotify/index','POST');  //输入金额回调
Route::rule(':version/payment/ok','api/:version.payment/ok','GET'); 
Route::rule(':version/token/gettoken','api/:version.Token/gettoken','POST');
Route::miss('Error/index');
return [
    '__pattern__' => [
        'name' => '\w+',
    ],   
];
