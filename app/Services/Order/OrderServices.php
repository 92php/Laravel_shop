<?php

namespace App\Services\Order;

use App\CodeResponse;
use App\Enums\OrderEnums;
use App\Exceptions\BusinessException;
use App\Inputs\OrderSubmitInput;
use App\Inputs\PageInput;
use App\Jobs\OrderUnpaidTimeEndJob;
use App\Models\Goods\GoodsProduct;
use App\Models\Order\Cart;
use App\Models\Order\Order;
use App\Models\Order\OrderGoods;
use App\Notifications\NewPaidOrderEmailNotify;
use App\Notifications\NewPaidOrderSMSNotify;
use App\Services\BaseServices;
use App\Services\Goods\GoodsServices;
use App\Services\Promotion\CouponServices;
use App\Services\Promotion\GrouponServices;
use App\Services\SystemServices;
use App\Services\User\AddressServices;
use App\Services\User\UserServices;
use DB;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class OrderServices extends BaseServices
{


    public function getListByStatus($userId, PageInput $filter, $status = [])
    {
        return Order::query()->where('user_id', $userId)
            ->when(!empty($status), function (Builder $query) use ($status) {
                return $query->whereIn('order_status', $status);
            })->orderBy($filter->sort, $filter->order)
            ->paginate($filter->limit, ['*'], 'page', $filter->page);
    }

    public function coverOrderVo(Order $order, $grouponOrders = [], $goodsList = [])
    {
        return [
            "id" => $order->id,
            "orderSn" => $order->order_sn,
            "actualPrice" => $order->actual_price,
            "orderStatusText" => OrderEnums::STATUS_TEXT_MAP[$order->order_status] ?? '',
            "handleOption" => $order->getCanHandelOptions(),
            "aftersaleStatus" => $order->aftersale_status,
            "isGroupin" => in_array($order->id, $grouponOrders),
            "goodsList" => $goodsList,
        ];
    }

    public function coverOrderGoodsVo(OrderGoods $orderGoods)
    {
        return [
            "id" => $orderGoods->id,
            "goodsName" => $orderGoods->goods_name,
            "number" => $orderGoods->number,
            "picUrl" => $orderGoods->pic_url,
            "specifications" => $orderGoods->specifications,
            "price" => $orderGoods->price,
        ];
    }

    /**
     * 提交订单
     * @param $userId
     * @param  OrderSubmitInput  $input
     * @return Order|void
     * @throws BusinessException
     */
    public function submit($userId, OrderSubmitInput $input)
    {
        // 验证团购规则的有效性
        if (!empty($input->grouponRulesId)) {
            GrouponServices::getInstance()->checkGrouponValid($userId, $input->grouponRulesId);
        }
        $address = AddressServices::getInstance()->getAddress($userId, $input->addressId);
        if (empty($address)) {
            return $this->throwBadArgumentValue();
        }

        // 获取购物车的商品列表
        $checkedGoodsList = CartServices::getInstance()->getCheckedCartList($userId, $input->cartId);

        // 计算商品总金额
        $grouponPrice = 0;
        $checkedGoodsPrice = CartServices::getInstance()->getCartPriceCutGroupon($checkedGoodsList,
            $input->grouponRulesId,
            $grouponPrice);

        // 获取优惠券面额
        $couponPrice = 0;
        if ($input->couponId > 0) {
            $coupon = CouponServices::getInstance()->getCoupon($input->couponId);
            $couponUser = CouponServices::getInstance()->getCouponUser($input->userCouponId);
            $is = CouponServices::getInstance()->checkCouponAndPrice($coupon, $couponUser, $checkedGoodsPrice);
            if ($is) {
                $couponPrice = $coupon->discount;
            }
        }

        // 运费
        $freightPrice = $this->getFreight($checkedGoodsPrice);

        // 计算订单金额
        $orderTotalPrice = bcadd($checkedGoodsPrice, $freightPrice, 2);
        $orderTotalPrice = bcsub($orderTotalPrice, $couponPrice, 2);
        $orderTotalPrice = max(0, $orderTotalPrice);

        $order = Order::new();
        $order->user_id = $userId;
        $order->order_sn = $this->generateOrderSn();
        $order->order_status = OrderEnums::STATUS_CREATE;
        $order->consignee = $address->name;
        $order->mobile = $address->tel;
        $order->address = $address->province.$address->city.$address->county." ".$address->address_detail;
        $order->message = $input->message;
        $order->goods_price = $checkedGoodsPrice;
        $order->freight_price = $freightPrice;
        $order->integral_price = 0;
        $order->coupon_price = $couponPrice;
        $order->order_price = $orderTotalPrice;
        $order->actual_price = $orderTotalPrice;
        $order->groupon_price = $grouponPrice;
        $order->save();

        // 写入订单商品记录
        $this->saveOrderGoods($checkedGoodsList, $order->id);

        // 清理购物车记录
        CartServices::getInstance()->clearCartGoods($userId, $input->cartId);

        // 减库存
        $this->reduceProductsStock($checkedGoodsList);

        // 添加团购记录
        GrouponServices::getInstance()->openOrJoinGroupon($userId, $order->id, $input->grouponRulesId,
            $input->grouponLinkId);

        // 设置超时任务
        dispatch(new OrderUnpaidTimeEndJob($userId, $order->id));

        return $order;
    }

    /**
     * 减库存
     * @param  Cart[]|Collection  $goodsList
     * @throws BusinessException
     */
    public function reduceProductsStock($goodsList)
    {
        $productIds = $goodsList->pluck('product_id')->toArray();
        $products = GoodsServices::getInstance()->getGoodsProductsByIds($productIds)->keyBy('id');
        foreach ($goodsList as $cart) {
            /** @var GoodsProduct $product */
            $product = $products->get($cart->product_id);
            if (empty($product)) {
                $this->throwBadArgumentValue();
            }
            if ($product->number < $cart->number) {
                $this->throwBusinessException(CodeResponse::GOODS_NO_STOCK);
            }
            $row = GoodsServices::getInstance()->reduceStock($product->id, $cart->number);
            if ($row == 0) {
                $this->throwBusinessException(CodeResponse::GOODS_NO_STOCK);
            }
        }
    }

    /**
     * @param  Cart[]  $checkGoodsList
     * @param $orderId
     */
    private function saveOrderGoods($checkGoodsList, $orderId)
    {
        foreach ($checkGoodsList as $cart) {
            $orderGoods = OrderGoods::new();
            $orderGoods->order_id = $orderId;
            $orderGoods->goods_id = $cart->goods_id;
            $orderGoods->goods_sn = $cart->goods_sn;
            $orderGoods->product_id = $cart->product_id;
            $orderGoods->goods_name = $cart->goods_name;
            $orderGoods->pic_url = $cart->pic_url;
            $orderGoods->price = $cart->price;
            $orderGoods->number = $cart->number;
            $orderGoods->specifications = $cart->specifications;
            $orderGoods->save();
        }
    }

    /**
     * 生产订单编号
     * @return mixed
     * @throws Exception
     */
    public function generateOrderSn()
    {
        //重试5次（如果有异常就会重试）
        return retry(5, function () {
            $orderSn = date('YmdHis').Str::random(6);
            if (!$this->isOrderSnUsed($orderSn)) {
                return $orderSn;
            }
            \Log::warning('订单号获取失败，orderSn:'.$orderSn);
            $this->throwBusinessException(CodeResponse::FAIL, '订单号获取失败');
        });
    }

    public function isOrderSnUsed($orderSn)
    {
        return Order::query()->where('order_sn', $orderSn)->exists();
    }

    /**
     * 获取运费
     * @param $price
     * @return float|int
     */
    public function getFreight($price)
    {
        $freightPrice = 0;
        $freightMin = SystemServices::getInstance()->getFreightMin();
        if (bccomp($freightMin, $price) == 1) {
            $freightPrice = SystemServices::getInstance()->getFreightValue();
        }
        return $freightPrice;
    }

    public function getOrderByUserIdAndId($userId, $orderId)
    {
        return Order::query()->where('user_id', $userId)->find($orderId);
    }

    /**
     * 用户手动取消
     * @param $userId
     * @param $orderId
     */
    public function userCancel($userId, $orderId)
    {
        DB::transaction(function () use ($userId, $orderId) {
            $this->cancel($userId, $orderId, 'user');
        });
    }

    /**
     * 系统自动取消
     * @param $userId
     * @param $orderId
     */
    public function systemCancel($userId, $orderId)
    {
        DB::transaction(function () use ($userId, $orderId) {
            $this->cancel($userId, $orderId, 'system');
        });
    }

    /**
     * 后台管理员取消
     * @param $userId
     * @param $orderId
     */
    public function adminCancel($userId, $orderId)
    {
        DB::transaction(function () use ($userId, $orderId) {
            $this->cancel($userId, $orderId, 'admin');
        });
    }

    public function getOrderGoodsList($orderId)
    {
        return OrderGoods::query()->where('order_id', $orderId)->get();
    }

    public function getOrderGoodsListByOrderIds(array $orderIds)
    {
        if (empty($orderIds)) {
            return collect();
        }
        return OrderGoods::query()->whereIn('order_id', $orderIds)->get();
    }

    /**
     * 订单取消
     * @param $userId
     * @param $orderId
     * @param  string  $role  支持：user/admin/system
     * @return bool
     * @throws BusinessException
     * @throws Throwable
     */
    private function cancel($userId, $orderId, $role = 'user')
    {
        $order = $this->getOrderByUserIdAndId($userId, $orderId);
        if (is_null($order)) {
            $this->throwBadArgumentValue();
        }

        if (!$order->canCancelHandle()) {
            $this->throwBusinessException(CodeResponse::ORDER_INVALID_OPERATION, '订单不能取消');
        }

        switch ($role) {
            case 'system':
                $order->order_status = OrderEnums::STATUS_AUTO_CANCEL;
                break;
            case 'admin':
                $order->order_status = OrderEnums::STATUS_ADMIN_CANCEL;
                break;
            default:
                $order->order_status = OrderEnums::STATUS_CANCEL;
        }

        // end_time视频中忘记更新了
        $order->end_time = now()->toDateTimeString();

        if ($order->cas() == 0) {
            $this->throwBusinessException(CodeResponse::UPDATED_FAIL);
        }

        $this->returnStock($orderId);
        return true;
    }

    /**
     * 订单库存返还
     * @param $orderId
     * @throws BusinessException
     */
    private function returnStock($orderId)
    {
        $orderGoods = $this->getOrderGoodsList($orderId);
        foreach ($orderGoods as $goods) {
            $row = GoodsServices::getInstance()->addStock($goods->product_id, $goods->number);
            if ($row == 0) {
                $this->throwBusinessException(CodeResponse::UPDATED_FAIL);
            }
        }
    }

    /**
     * 订单支付成功
     * @param  Order  $order
     * @param $payId
     * @return Order
     * @throws BusinessException
     * @throws Throwable
     */
    public function payOrder(Order $order, $payId)
    {
        if (!$order->canPayHandle()) {
            $this->throwBusinessException(CodeResponse::ORDER_PAY_FAIL, '订单不能支付');
        }
        $order->pay_id = $payId;
        $order->pay_time = now()->toDateTimeString();
        $order->order_status = OrderEnums::STATUS_PAY;
        if ($order->cas() == 0) {
            $this->throwBusinessException(CodeResponse::UPDATED_FAIL);
        }
        GrouponServices::getInstance()->payGrouponOrder($order->id);
        Notification::route('mail', env('MAIL_USERNAME'))
            ->notify(new NewPaidOrderEmailNotify($order->id));
        $user = UserServices::getInstance()->getUserById($order->user_id);
        $user->notify(new NewPaidOrderSMSNotify());
        return $order;
    }

    /**
     * 发货
     * @param $userId
     * @param $orderId
     * @param $shipSn
     * @param $shipChannel
     * @return Order
     * @throws BusinessException
     * @throws Throwable
     */
    public function ship($userId, $orderId, $shipSn, $shipChannel)
    {
        $order = $this->getOrderByUserIdAndId($userId, $orderId);
        if (empty($order)) {
            $this->throwBadArgumentValue();
        }

        if (!$order->canShipHandle()) {
            $this->throwBusinessException(CodeResponse::ORDER_INVALID_OPERATION, '该订单不能发货');
        }
        $order->order_status = OrderEnums::STATUS_SHIP;
        $order->ship_sn = $shipSn;
        $order->ship_channel = $shipChannel;
        $order->ship_time = now()->toDateTimeString();
        if ($order->cas() == 0) {
            $this->throwUpdateFail();
        }
        // todo 发通知
        return $order;
    }

    /**
     * 申请退款
     * @param $userId
     * @param $orderId
     * @return Order
     * @throws BusinessException
     * @throws Throwable
     */
    public function refund($userId, $orderId)
    {
        $order = $this->getOrderByUserIdAndId($userId, $orderId);
        if (empty($order)) {
            $this->throwBadArgumentValue();
        }

        if (!$order->canRefundHandle()) {
            $this->throwBusinessException(CodeResponse::ORDER_INVALID_OPERATION, '该订单不能申请退款');
        }

        $order->order_status = OrderEnums::STATUS_REFUND;
        if ($order->cas() == 0) {
            $this->throwUpdateFail();
        }
        // todo 发通知
        return $order;
    }

    /**
     * 同意退款
     * @param  Order  $order
     * @param $refundType
     * @param $refundContent
     * @return Order
     * @throws BusinessException
     * @throws Throwable
     */
    public function agreeRefund(Order $order, $refundType, $refundContent)
    {
        if (!$order->canAgreeRefundHandle()) {
            $this->throwBusinessException(CodeResponse::ORDER_INVALID_OPERATION, '该订单不能同意退款');
        }

        $now = now()->toDateTimeString();
        $order->order_status = OrderEnums::STATUS_REFUND_CONFIRM;
        $order->end_time = $now;
        $order->refund_amount = $order->actual_price;
        $order->refund_type = $refundType;
        $order->refund_content = $refundContent;
        $order->refund_time = $now;
        if ($order->cas() == 0) {
            $this->throwUpdateFail();
        }
        $this->returnStock($order->id);
        return $order;
    }

    /**
     * 获取订单的商品数量
     * @param $orderId
     * @return int
     */
    public function countOrderGoods($orderId)
    {
        return OrderGoods::whereOrderId($orderId)->count(['id']);
    }

    /**
     * 确认收货
     * @param $userId
     * @param $orderId
     * @param  bool  $isAuto
     * @return Order
     * @throws BusinessException
     * @throws Throwable
     */
    public function confirm($userId, $orderId, $isAuto = false)
    {
        $order = $this->getOrderByUserIdAndId($userId, $orderId);
        if (empty($order)) {
            $this->throwBadArgumentValue();
        }

        if (!$order->canConfirmHandle()) {
            $this->throwBusinessException(CodeResponse::ORDER_INVALID_OPERATION, '该订单不能确认收货');
        }

        $order->comments = $this->countOrderGoods($orderId);
        $order->order_status = $isAuto ? OrderEnums::STATUS_AUTO_CONFIRM : OrderEnums::STATUS_CONFIRM;
        $order->confirm_time = now()->toDateTimeString();
        if ($order->cas() == 0) {
            $this->throwUpdateFail();
        }
        return $order;
    }

    /**
     * 订单删除
     * @param $userId
     * @param $orderId
     * @throws BusinessException
     */
    public function delete($userId, $orderId)
    {
        $order = $this->getOrderByUserIdAndId($userId, $orderId);
        if (empty($order)) {
            $this->throwBadArgumentValue();
        }
        if (!$order->canDeleteHandle()) {
            $this->throwBusinessException(CodeResponse::ORDER_INVALID_OPERATION, '该订单不能删除');
        }

        $order->delete();
        // todo 售后删除
    }

    public function getTimeoutUnConfirmOrders()
    {
        $days = SystemServices::getInstance()->getOrderUnConfirmDays();
        return Order::query()->where('order_status', OrderEnums::STATUS_SHIP)
            ->where('ship_time', '<=', now()->subDays($days))
            ->where('ship_time', '>=', now()->subDays($days + 30))
            ->get();
    }


    public function autoConfirm()
    {
        Log::info('Auto confirm start.');
        $orders = $this->getTimeoutUnConfirmOrders();
        foreach ($orders as $order) {
            try {
                $this->confirm($order->user_id, $order->id, true);
            } catch (BusinessException $e) {
            } catch (Throwable $e) {
                Log::error('Auto confirm error. Error:'.$e->getMessage());
            }
        }
    }

    /**
     * 订单详情
     * @param $userId
     * @param $orderId
     * @return array
     * @throws BusinessException
     */
    public function detail($userId, $orderId)
    {
        $order = $this->getOrderByUserIdAndId($userId, $orderId);
        if (empty($order)) {
            $this->throwBadArgumentValue();
        }
        $detail = Arr::only($order->toArray(), [
            "id",
            "orderSn",
            "message",
            "addTime",
            "consignee",
            "mobile",
            "address",
            "goodsPrice",
            "couponPrice",
            "freightPrice",
            "actualPrice",
            "aftersaleStatus",
        ]);

        $detail['orderStatusText'] = OrderEnums::STATUS_TEXT_MAP[$order->order_status] ?? '';
        $detail['handleOption'] = $order->getCanHandelOptions();
        $goodsList = $this->getOrderGoodsList($orderId);

        $express = [];
        if ($order->isShipStatus()) {
            $detail['expCode'] = $order->ship_channel;
            $detail['expNo'] = $order->ship_sn;
            $detail['expName'] = ExpressServices::getInstance()->getExpressName($order->ship_channel);
            $express = ExpressServices::getInstance()->getOrderTraces($order->ship_channel, $order->ship_sn);
        }

        return [
            'orderInfo' => $detail,
            'orderGoods' => $goodsList,
            'expressInfo' => $express
        ];
    }

    private function getPayOrderInfo($userId, $orderId)
    {
        $order = $this->getOrderByUserIdAndId($userId, $orderId);
        if (empty($order)) {
            $this->throwBadArgumentValue();
        }
        if (!$order->canPayHandle()) {
            $this->throwBusinessException(CodeResponse::ORDER_PAY_FAIL, '订单不能支付');
        }
        return $order;
    }

    public function getWxPayOrder($userId, $orderId)
    {
        $order = $this->getPayOrderInfo($userId, $orderId);

        return [
            'out_trade_no' => $order->order_sn,
            'body' => '订单：'.$order->order_sn,
            'total_fee' => bcmul($order->actual_price, 100),
        ];
    }

    public function getAlipayPayOrder($userId, $orderId)
    {
        $order = $this->getPayOrderInfo($userId, $orderId);
        return [
            'out_trade_no' => $order->order_sn,
            'total_amount' => $order->actual_price,
            'subject' => '订单：'.$order->order_sn,
        ];
    }

    public function getOrderBySn($orderSn)
    {
        return Order::query()->where('order_sn', $orderSn)->first();
    }

    /**
     * @param $orderSn
     * @param $payId
     * @param $price
     * @return Order
     * @throws BusinessException
     * @throws Throwable
     */
    private function notify($orderSn, $payId, $price)
    {
        $order = $this->getOrderBySn($orderSn);
        if (is_null($order)) {
            $this->throwBusinessException(CodeResponse::ORDER_UNKNOWN);
        }

        if ($order->isHadPaid()) {
            return $order;
        }

        if (bccomp($order->actual_price, $price, 2) != 0) {
            $this->throwBusinessException(CodeResponse::FAIL,
                "支付回调，订单[{$order->id}]金额不一致,[total_amount={$price}],订单金额[actual_price={$order->actual_price}]");
        }

        return $this->payOrder($order, $payId);
    }

    /**
     * @param  array  $data
     * @return Order
     * @throws BusinessException
     * @throws Throwable
     */
    public function wxNotify(array $data)
    {
        $orderSn = $data['out_trade_no'] ?? '';
        $payId = $data['transaction_id'] ?? '';
        $price = bcdiv($data['total_fee'], 100, 2);

        return $this->notify($orderSn, $payId, $price);
    }

    /**
     * @param  array  $data
     * @return Order
     * @throws BusinessException
     * @throws Throwable
     */
    public function alipayNotify(array $data)
    {
        if (!in_array($data['trade_status'] ?? '', ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            $this->throwBadArgumentValue();
        }

        $orderSn = $data['out_trade_no'] ?? '';
        $payId = $data['trade_no'] ?? '';
        $price = floatval($data['total_amount'] ?? 0);
        return $this->notify($orderSn, $payId, $price);
    }

}
