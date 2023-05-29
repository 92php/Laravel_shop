<?php

namespace App\Http\Controllers\Wx;

use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
use App\Models\Goods\Goods;
use App\Models\Promotion\GrouponRules;
use App\Services\Goods\GoodsServices;
use App\Services\Promotion\GrouponServices;
use Illuminate\Http\JsonResponse;

class GrouponController extends WxController
{
    /**
     * 团购列表
     * @return JsonResponse
     * @throws BusinessException
     */
    public function list()
    {
        $page = PageInput::new();
        //获取团购规则列表
        $list = GrouponServices::getInstance()->getGrouponRules($page);

        $rules = collect($list->items());
        $goodsIds = $rules->pluck('goods_id')->toArray(); //获取商品id,pluck获取某一列
        $goodsList = GoodsServices::getInstance()->getGoodsListByIds($goodsIds)
            ->keyBy('id');

        $voList = $rules->map(function (GrouponRules $rule) use ($goodsList) {
            /** @var Goods $goods */
            $goods = $goodsList->get($rule->goods_id);
            return [
                'id' => $goods->id,
                'name' => $goods->name,
                'brief' => $goods->brief,
                'picUrl' => $goods->pic_url,
                'counterPrice' => $goods->counter_price,
                'retailPrice' => $goods->retail_price,
                'grouponPrice' => bcsub($goods->retail_price, $rule->discount, 2),
                'grouponDiscount' => $rule->discount,
                'grouponMember' => $rule->discount_member,
                'expireTime' => $rule->expire_time
            ];
        });
        $list = $this->paginate($list, $voList);
        return $this->success($list);
    }


}
