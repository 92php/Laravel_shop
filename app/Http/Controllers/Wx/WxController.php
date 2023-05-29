<?php

namespace App\Http\Controllers\Wx;

use App\CodeResponse;
use App\Http\Controllers\Controller;
use App\Models\User\User;
use App\VerifyRequestInput;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class WxController extends Controller
{
    use VerifyRequestInput;

    protected $only; //白名单
    protected $except; //黑名单

    public function __construct()
    {
        $option = [];
        if (!is_null($this->only)) {
            $option['only'] = $this->only;
        }
        if (!is_null($this->except)) {
            $option['except'] = $this->except;
        }
        $this->middleware('auth:wx', $option);
    }

    protected function codeReturn(array $codeResponse, $data = null, $info = '')
    {
        list($errno, $errmsg) = $codeResponse;
        $ret = ['errno' => $errno, 'errmsg' => $info ?: $errmsg];
        if (!is_null($data)) {
            if (is_array($data)) {
                $data = array_filter($data, function ($item) {
                    return $item !== null;
                });
            }
            $ret['data'] = $data;
        }
        return response()->json($ret);
    }

    /**
     * @param $page
     * @param  null  $list
     * @return JsonResponse
     */
    protected function successPaginate($page, $list = null)
    {
        return $this->success($this->paginate($page, $list));
    }

    /**
     * @param  LengthAwarePaginator|array  $page
     * @param  null|array  $list
     * @return array
     */
    protected function paginate($page, $list = null)
    {
        if ($page instanceof LengthAwarePaginator) { //如果是分页对象
            $total = $page->total();
            return [
                'total' => $page->total(),
                'page' => $total == 0 ? 0 : $page->currentPage(),
                'limit' => $page->perPage(),
                'pages' => $total == 0 ? 0 : $page->lastPage(),
                'list' => $list ?? $page->items()
            ];
        }

        if ($page instanceof Collection) { //集合
            $page = $page->toArray();
        }
        if (!is_array($page)) {
            return $page;
        }

        $total = count($page);
        return [
            'total' => $total,
            'page' => $total == 0 ? 0 : 1,
            'limit' => $total,
            'pages' => $total == 0 ? 0 : 1,
            'list' => $page
        ];
    }

    protected function success($data = null)
    {
        return $this->codeReturn(CodeResponse::SUCCESS, $data);
    }

    protected function fail(array $codeResponse = CodeResponse::FAIL, $info = '')
    {
        return $this->codeReturn($codeResponse, null, $info);
    }

    /**
     * 401
     * @return JsonResponse
     */
    protected function badArgument()
    {
        return $this->fail(CodeResponse::PARAM_ILLEGAL);
    }

    /**
     * 402
     * @return JsonResponse
     */
    protected function badArgumentValue()
    {
        return $this->fail(CodeResponse::PARAM_VALUE_ILLEGAL);
    }


    protected function failOrSuccess($isSuccess, array $codeResponse = CodeResponse::FAIL, $data = null, $info = '')
    {
        if ($isSuccess) {
            return $this->success($data);
        }
        return $this->fail($codeResponse, $info);
    }

    /**
     * @return User|null
     */
    public function user()
    {
        return Auth::guard('wx')->user();
    }

    public function isLogin()
    {
        return !is_null($this->user());
    }

    public function userId()
    {
        return $this->user()->getAuthIdentifier();
    }
}
