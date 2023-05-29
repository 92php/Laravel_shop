<?php

namespace App\Http\Controllers\Wx;

use App\CodeResponse;
use App\Enums\Constant;
use App\Exceptions\BusinessException;
use App\Inputs\GoodsListInput;
use App\Services\CollectServices;
use App\Services\CommentServices;
use App\Services\Goods\BrandServices;
use App\Services\Goods\CatalogServices;
use App\Services\Goods\GoodsServices;
use App\Services\SearchHistoryServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoodsController extends WxController
{
    protected $only = [];

    /**
     * 商品数量
     * @return JsonResponse
     */
    public function count()
    {
        $count = GoodsServices::getInstance()->countGoodsOnSale();
        return $this->success($count);
    }

    /**
     * 商品类目
     * @return JsonResponse
     * @throws BusinessException
     */
    public function category()
    {
        $id = $this->verifyId('id');

        $cur = CatalogServices::getInstance()->getCategory($id);
        if (empty($cur)) {
            return $this->fail(CodeResponse::PARAM_VALUE_ILLEGAL);
        }

        $parent = null;
        $children = null;
        if ($cur->pid == 0) {
            $parent = $cur;
            $children = CatalogServices::getInstance()->getL2ListByPid($cur->id);
            $cur = $children->first() ?? $cur;
        } else {
            $parent = CatalogServices::getInstance()->getL1ById($cur->pid);
            $children = CatalogServices::getInstance()->getL2ListByPid($cur->pid);
        }

        return $this->success([
            'currentCategory' => $cur,
            'parentCategory' => $parent,
            'brotherCategory' => $children
        ]);
    }

    /**
     * 商品列表
     * @return JsonResponse
     * @throws BusinessException
     */
    public function list()
    {
        //参数多，封装成一个对象传入
        $input = GoodsListInput::new();

        if ($this->isLogin() && !empty($keyword)) {
            SearchHistoryServices::getInstance()->save($this->userId(), $keyword, Constant::SEARCH_HISTORY_FROM_WX);
        }

        $columns = ['id', 'name', 'brief', 'pic_url', 'is_new', 'is_hot', 'counter_price', 'retail_price'];
        $goodsList = GoodsServices::getInstance()->listGoods($input, $columns);

        $categoryList = GoodsServices::getInstance()->listL2Category($input);

        $goodsList = $this->paginate($goodsList);
        $goodsList['filterCategoryList'] = $categoryList;
        return $this->success($goodsList);
    }

    /**
     * 商品详情
     * @param  Request  $request
     * @return JsonResponse
     * @throws BusinessException
     */
    public function detail(Request $request)
    {
        $id = $this->verifyId('id');

        $info = GoodsServices::getInstance()->getGoods($id);
        if (empty($info)) {
            return $this->fail(CodeResponse::PARAM_VALUE_ILLEGAL);
        }

        $attr = GoodsServices::getInstance()->getGoodsAttribute($id);
        $spec = GoodsServices::getInstance()->getGoodsSpecification($id);
        $product = GoodsServices::getInstance()->getGoodsProduct($id);
        $issue = GoodsServices::getInstance()->getGoodsIssue();
        $brand = $info->brand_id ? BrandServices::getInstance()->getBrand($info->brand_id) : (object) [];
        $comment = CommentServices::getInstance()->getCommentWithUserInfo($id);

        $userHasCollect = 0;
        if ($this->isLogin()) {
            //用户收藏
            $userHasCollect = CollectServices::getInstance()->countByGoodsId($this->userId(), $id);
            //用户足迹
            GoodsServices::getInstance()->saveFootprint($this->userId(), $id);
        }

        // todo 团购信息
        // todo 系统配置

        return $this->success([
            'info' => $info,
            'userHasCollect' => $userHasCollect,
            'issue' => $issue,
            'comment' => $comment,
            'specificationList' => $spec,
            'productList' => $product,
            'attribute' => $attr,
            'brand' => $brand,
            'groupon' => [],
            'share' => false, //是否自动生成分享图片
            'shareImage' => $info->share_url
        ]);
    }

}
