<?php

namespace App\Http\Controllers\Wx;

use App\CodeResponse;
use App\Services\Goods\CatalogServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends WxController
{
    protected $only = [];

    /**
     * 首页类目
     * @param  Request  $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $id = $request->input('id', 0);
        $l1List = CatalogServices::getInstance()->getL1List();
        if (empty($id)) {
            $current = $l1List->first();
        } else {
            $current = $l1List->where('id', $id)->first();
        }

        $l2List = null;
        if (!is_null($current)) {
            $l2List = CatalogServices::getInstance()->getL2ListByPid($current->id);
        }

        return $this->success(
            [
                'categoryList' => $l1List,
                'currentCategory' => $current,
                'currentSubCategory' => $l2List
            ]
        );
    }

    /**
     * 当前类目信息
     * @param  Request  $request
     * @return JsonResponse
     */
    public function current(Request $request)
    {
        $id = $request->input('id', 0);
        if (empty($id)) {
            return $this->fail(CodeResponse::PARAM_VALUE_ILLEGAL);
        }
        $category = CatalogServices::getInstance()->getL1ById($id);
        if (is_null($category)) {
            return $this->fail(CodeResponse::PARAM_VALUE_ILLEGAL);
        }

        $l2List = CatalogServices::getInstance()->getL2ListByPid($category->id);
        return $this->success(
            [
                'currentCategory' => $category,
                'currentSubCategory' => $l2List->toArray()
            ]
        );
    }
}