<?php

namespace App\Http\Controllers\Wx;

use App\CodeResponse;
use App\Enums\OrderEnums;
use App\Exceptions\BusinessException;
use App\Inputs\OrderSubmitInput;
use App\Inputs\PageInput;
use App\Models\Order\Order;
use App\Models\Order\OrderGoods;
use App\Services\Order\OrderServices;
use App\Services\Promotion\GrouponServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Yansongda\LaravelPay\Facades\Pay;
use Yansongda\Pay\Exceptions\GatewayException;
use Yansongda\Pay\Exceptions\InvalidArgumentException;
use Yansongda\Pay\Exceptions\InvalidConfigException;
use Yansongda\Pay\Exceptions\InvalidSignException;

class OrderController extends WxController
{
    protected $except = ['wxNotify', 'alipayNotify', 'alipayReturn'];

    /**
     * 提交订单
     * @return JsonResponse
     * @throws BusinessException
     * @throws Throwable
     */
    public function submit()
    {
        $input = OrderSubmitInput::new();

        $lockKey = sprintf('order_submit_%s_%s', $this->userId(), md5(serialize($input)));
        $lock = Cache::lock($lockKey, 5); //加锁，解决重复提交问题
        if (!$lock->get()) {
            return $this->fail(CodeResponse::FAIL, '请勿重复请求');
        }

        $order = DB::transaction(function () use ($input) {
            return OrderServices::getInstance()->submit($this->userId(), $input);
        });
        return $this->success([
            'orderId' => $order->id,
            'grouponLikeId' => $input->grouponLinkId ?? 0
        ]);
    }


    /**
     * 用户主动取消订单
     * @return JsonResponse
     * @throws BusinessException
     */
    public function cancel()
    {
        $orderId = $this->verifyId('orderId');
        OrderServices::getInstance()->userCancel($this->userId(), $orderId);
        return $this->success();
    }

    /**
     * 申请退款
     * @return JsonResponse
     * @throws BusinessException
     * @throws Throwable
     */
    public function refund()
    {
        $orderId = $this->verifyId('orderId');
        OrderServices::getInstance()->refund($this->userId(), $orderId);
        return $this->success();
    }

    /**
     * 确认收货
     * @return JsonResponse
     * @throws BusinessException
     * @throws Throwable
     */
    public function confirm()
    {
        $orderId = $this->verifyId('orderId');
        OrderServices::getInstance()->confirm($this->userId(), $orderId);
        return $this->success();
    }

    /**
     * 订单删除
     * @return JsonResponse
     * @throws BusinessException
     */
    public function delete()
    {
        $orderId = $this->verifyId('orderId');
        OrderServices::getInstance()->delete($this->userId(), $orderId);
        return $this->success();
    }

    /**
     * 订单详情
     * @return JsonResponse
     * @throws BusinessException
     */
    public function detail()
    {
        $orderId = $this->verifyId('orderId');
        $detail = OrderServices::getInstance()->detail($this->userId(), $orderId);
        return $this->success($detail);
    }

    /**
     * 订单列表
     * @return JsonResponse
     * @throws BusinessException
     */
    public function list()
    {
        $showType = $this->verifyEnum('showType', 0, array_keys(OrderEnums::SHOW_TYPE_STATUS_MAP));
        $filter = PageInput::new();
        $status = OrderEnums::SHOW_TYPE_STATUS_MAP[$showType];

        $orderListWithPage = OrderServices::getInstance()->getListByStatus($this->userId(), $filter, $status);
        $orderList = collect($orderListWithPage->items());
        $orderIds = $orderList->pluck('id')->toArray();
        if (empty($orderIds)) {
            $this->successPaginate($orderListWithPage);
        }

        $grouponOrderIds = GrouponServices::getInstance()->getGrouponOrderInOrderIds($orderIds);
        $orderGoodsList = OrderServices::getInstance()->getOrderGoodsListByOrderIds($orderIds)->groupBy('order_id');
        $list = $orderList->map(function (Order $order) use ($orderGoodsList, $grouponOrderIds) {
            /** @var Collection $goodsList */
            $goodsList = $orderGoodsList->get($order->id);
            $goodsList = $goodsList->map(function (OrderGoods $orderGoods) {
                return OrderServices::getInstance()->coverOrderGoodsVo($orderGoods);
            });
            return OrderServices::getInstance()->coverOrderVo($order, $grouponOrderIds, $goodsList);
        });
        return $this->successPaginate($orderListWithPage, $list);
    }

    /**
     * 微信支付
     * @return RedirectResponse
     * @throws BusinessException
     */
    public function h5pay()
    {
        $orderId = $this->verifyId('orderId');
        $order = OrderServices::getInstance()->getWxPayOrder($this->userId(), $orderId);
        return Pay::wechat()->wap($order);
    }

    /**
     * 微信支付回调
     * @return Response
     * @throws Throwable
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     */
    public function wxNotify()
    {
        $data = Pay::wechat()->verify()->toArray();
        Log::info('wxNotify', $data);
        DB::transaction(function () use ($data) {
            OrderServices::getInstance()->wxNotify($data);
        });
        return Pay::wechat()->success();
    }

    /**
     * 支付宝支付
     * @return JsonResponse
     * @throws BusinessException
     */
    public function h5alipay()
    {
        $orderId = $this->verifyId('orderId');
        $order = OrderServices::getInstance()->getAlipayPayOrder($this->userId(), $orderId);
        return $this->success(Pay::alipay()->wap($order)->getContent());
    }

    /**
     * 支付宝支付回调
     * @return Response
     * @throws InvalidSignException
     * @throws Throwable
     * @throws InvalidConfigException
     */
    public function alipayNotify()
    {
        $data = Pay::alipay()->verify()->toArray();
        Log::info('alipayNotify', $data);
        DB::transaction(function () use ($data) {
            OrderServices::getInstance()->alipayNotify($data);
        });
        return Pay::alipay()->success();
    }

    /**
     * 支付宝同步回调
     * @return Redirector
     * @throws InvalidConfigException
     * @throws InvalidSignException
     * @throws Throwable
     * @throws GatewayException
     */
    public function alipayReturn()
    {
        $data = Pay::alipay()->find(request()->input())->toArray();
        Log::info('alipayReturn', $data);
        DB::transaction(function () use ($data) {
            OrderServices::getInstance()->alipayNotify($data);
        });
        return redirect(env('H5_URL').'/#/user/order/list/0');
    }
}
