<?php

namespace App\Http\Middleware;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  Request  $request
     * @return string|null
     */
    protected function redirectTo($request)
    {
        //登陆失败会跳转 /login
        if (!$request->expectsJson()) {
            return route('login');
        }
    }

    /**
     * @param  Request  $request
     * @param  array  $guards
     * @throws BusinessException
     * @throws AuthenticationException
     */
    protected function unauthenticated($request, array $guards)
    {
        //获取用户身份认证失败，需要做些处理，返回统一格式
        if ($request->expectsJson() || in_array('wx', $guards)) {
            throw new BusinessException(CodeResponse::UN_LOGIN);
        }
        parent::unauthenticated($request, $guards);
    }
}
