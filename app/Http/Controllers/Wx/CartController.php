<?php

namespace App\Http\Controllers\Wx;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Services\Goods\GoodsServices;
use App\Services\Order\CartServices;
use App\Services\Order\OrderServices;
use App\Services\Promotion\CouponServices;
use App\Services\User\AddressServices;
use Exception;
use Illuminate\Http\JsonResponse;

class CartController extends WxController
{
    /**
     * 购物车列表信息
     * @return JsonResponse
     * @throws Exception
     */
    public function index()
    {
        $list = CartServices::getInstance()->getValidCartList($this->userId());
        $goodsCount = 0;
        $goodsAmount = 0;
        $checkedGoodsCount = 0;
        $checkedGoodsAmount = 0;
        foreach ($list as $item) {
            $goodsCount += $item->number;
            $amount = bcmul($item->price, $item->number, 2);
            $goodsAmount = bcadd($goodsAmount, $amount, 2);
            if ($item->checked) {
                $checkedGoodsCount += $item->number;
                $checkedGoodsAmount = bcadd($checkedGoodsAmount, $amount, 2);
            }
        }
        return $this->success(
            [
                'cartList' => $list->toArray(),
                'cartTotal' => [
                    'goodsCount' => $goodsCount,
                    'goodsAmount' => (double) $goodsAmount,
                    'checkedGoodsCount' => $checkedGoodsCount,
                    'checkedGoodsAmount' => (double) $checkedGoodsAmount,
                ]
            ]
        );
    }

    /**
     * 立即购买
     * @return JsonResponse
     * @throws BusinessException
     */
    public function fastadd()
    {
        $goodsId = $this->verifyId('goodsId', 0);
        $productId = $this->verifyId('productId', 0);
        $number = $this->verifyPositiveInteger('number', 0);
        $cart = CartServices::getInstance()->fastadd($this->userId(), $goodsId, $productId, $number);
        return $this->success($cart->id);
    }

    /**
     * 加入购物车
     * @return JsonResponse
     * @throws BusinessException
     */
    public function add()
    {
        $goodsId = $this->verifyId('goodsId', 0);
        $productId = $this->verifyId('productId', 0);
        $number = $this->verifyPositiveInteger('number', 0);
        CartServices::getInstance()->add($this->userId(), $goodsId, $productId, $number);
        $count = CartServices::getInstance()->countCartProduct($this->userId());
        return $this->success($count);
    }

    /**
     * 获取购物车商品件数
     * @return JsonResponse
     */
    public function goodscount()
    {
        $count = CartServices::getInstance()->countCartProduct($this->userId());
        return $this->success($count);
    }


    /**
     * 更新购物车数量
     * @return JsonResponse
     * @throws BusinessException
     */
    public function update()
    {
        $id = $this->verifyId('id', 0);
        $goodsId = $this->verifyId('goodsId', 0);
        $productId = $this->verifyId('productId', 0);
        $number = $this->verifyPositiveInteger('number', 0);
        $cart = CartServices::getInstance()->getCartById($this->userId(), $id);
        if (is_null($cart)) {
            return $this->badArgumentValue();
        }
        if ($cart->goods_id != $goodsId || $cart->product_id != $productId) {
            return $this->badArgumentValue();
        }

        $goods = GoodsServices::getInstance()->getGoods($goodsId);
        if (is_null($goods) || !$goods->is_on_sale) {
            return $this->fail(CodeResponse::GOODS_UNSHELVE);
        }

        $product = GoodsServices::getInstance()->getGoodsProductById($productId);
        if (is_null($product) || $product->number < $number) {
            return $this->fail(CodeResponse::GOODS_NO_STOCK);
        }

        $cart->number = $number;
        $ret = $cart->save();
        return $this->failOrSuccess($ret);
    }

    /**
     * 购物车删除商品
     * @return JsonResponse
     * @throws BusinessException
     * @throws Exception
     */
    public function delete()
    {
        $productIds = $this->verifyArrayNotEmpty('productIds', []);
        CartServices::getInstance()->delete($this->userId(), $productIds);
        return $this->index();
    }

    /**
     * 选中或取消商品
     * @return JsonResponse
     * @throws BusinessException
     * @throws Exception
     */
    public function checked()
    {
        $productIds = $this->verifyArrayNotEmpty('productIds', []);
        $isChecked = $this->verifyBoolean('isChecked');
        CartServices::getInstance()->updateChecked(
            $this->userId(),
            $productIds,
            $isChecked == 1);
        return $this->index();
    }

    /**
     * 下单前信息确认
     * @return JsonResponse
     * @throws BusinessException
     * @throws Exception
     */
    public function checkout()
    {
        $cartId = $this->verifyInteger('cartId');
        $addressId = $this->verifyInteger('addressId');
        $couponId = $this->verifyInteger('couponId');
        $grouponRulesId = $this->verifyInteger('grouponRulesId');

        // 获取地址
        $address = AddressServices::getInstance()->getAddressOrDefault($this->userId(), $addressId);
        $addressId = $address->id ?? 0;

        // 获取购物车的商品列表
        $checkedGoodsList = CartServices::getInstance()->getCheckedCartList($this->userId(), $cartId);

        // 计算商品总金额
        $grouponPrice = 0;
        $checkedGoodsPrice = CartServices::getInstance()->getCartPriceCutGroupon($checkedGoodsList, $grouponRulesId,
            $grouponPrice);

        // 获取优惠券信息
        $availableCouponLength = 0;
        $couponUser = CouponServices::getInstance()->getMostMeetPriceCoupon($this->userId(), $couponId,
            $checkedGoodsPrice, $availableCouponLength);
        // 兼容litemall的返回
        if ($couponId == -1 || is_null($couponId)) {
            // if (is_null($couponUser)) {
            $couponId = -1;
            $userCouponId = -1;
            $couponPrice = 0;
        } else {
            $couponId = $couponUser->coupon_id ?? 0;
            $userCouponId = $couponUser->id ?? 0;
            $couponPrice = CouponServices::getInstance()->getCoupon($couponId)->discount ?? 0;
        }

        // 运费
        $freightPrice = OrderServices::getInstance()->getFreight($checkedGoodsPrice);

        // 计算订单金额
        $orderPrice = bcadd($checkedGoodsPrice, $freightPrice, 2);
        $orderPrice = bcsub($orderPrice, $couponPrice, 2);
        $orderPrice = max(0, $orderPrice);

        return $this->success([
            "addressId" => $addressId,
            "couponId" => $couponId,
            "userCouponId" => $userCouponId,
            "cartId" => $cartId,
            "grouponRulesId" => $grouponRulesId,
            "grouponPrice" => $grouponPrice,
            "checkedAddress" => $address,
            "availableCouponLength" => $availableCouponLength,
            "goodsTotalPrice" => $checkedGoodsPrice,
            "freightPrice" => $freightPrice,
            "couponPrice" => $couponPrice,
            "orderTotalPrice" => $orderPrice,
            "actualPrice" => $orderPrice,
            "checkedGoodsList" => $checkedGoodsList->toArray(),
        ]);
    }
}