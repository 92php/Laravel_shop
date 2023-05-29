<?php

namespace App\Models\Order;

use App\Enums\OrderEnums;
use Exception;
use Illuminate\Support\Str;
use ReflectionClass;

/**
 * Trait OrderStatusTrait
 * @package App\Models\Order
 * @method bool canAgreeRefundHandle()
 * @method bool canShipHandle()
 * @method bool canCancelHandle()
 * @method bool canDeleteHandle()
 * @method bool canPayHandle()
 * @method bool canCommentHandle()
 * @method bool canConfirmHandle()
 * @method bool canRefundHandle()
 * @method bool canRebuyHandle()
 * @method bool canAftersaleHandle()
 * @method bool isCreateStatus()
 * @method bool isPayStatus()
 * @method bool isShipStatus()
 * @method bool isConfirmStatus()
 * @method bool isCancelStatus()
 * @method bool isAutoCancelStatus()
 * @method bool isRefundStatus()
 * @method bool isRefundConfirmStatus()
 * @method bool isAutoConfirmStatus()
 */
trait OrderStatusTrait
{
    private $canHandleMap = [
        // 取消操作
        'cancel' => [
            OrderEnums::STATUS_CREATE
        ],
        // 删除操作
        'delete' => [
            OrderEnums::STATUS_CANCEL,
            OrderEnums::STATUS_AUTO_CANCEL,
            OrderEnums::STATUS_ADMIN_CANCEL,
            OrderEnums::STATUS_REFUND_CONFIRM,
            OrderEnums::STATUS_CONFIRM,
            OrderEnums::STATUS_AUTO_CONFIRM
        ],
        // 支付操作
        'pay' => [
            OrderEnums::STATUS_CREATE
        ],
        // 发货
        'ship' => [
            OrderEnums::STATUS_PAY
        ],
        // 评论操作
        'comment' => [
            OrderEnums::STATUS_CONFIRM,
            OrderEnums::STATUS_AUTO_CONFIRM
        ],
        // 确认收货操作
        'confirm' => [OrderEnums::STATUS_SHIP],
        // 取消订单并退款操作
        'refund' => [OrderEnums::STATUS_PAY],
        // 再次购买
        'rebuy' => [
            OrderEnums::STATUS_CONFIRM,
            OrderEnums::STATUS_AUTO_CONFIRM
        ],
        // 售后操作
        'aftersale' => [
            OrderEnums::STATUS_CONFIRM,
            OrderEnums::STATUS_AUTO_CONFIRM
        ],
        // 同意退款
        'agreerefund' => [
            OrderEnums::STATUS_REFUND
        ],
    ];

    public function __call($name, $arguments)
    {
        if (Str::is('can*Handle', $name)) {
            if (is_null($this->order_status)) {
                throw new Exception("order status is null when call method[$name]!");
            }
            $key = Str::of($name)->replaceFirst('can', '')
                ->replaceLast('Handle', '')
                ->lower();
            return in_array($this->order_status, $this->canHandleMap[(string) $key]);
        } elseif (Str::is('is*Status', $name)) {
            if (is_null($this->order_status)) {
                throw new Exception("order status is null when call method[$name]!");
            }
            $key = Str::of($name)->replaceFirst('is', '')
                ->replaceLast('Status', '')->snake()->upper()->prepend('STATUS_');
            $status = (new ReflectionClass(OrderEnums::class))->getConstant($key);
            return $this->order_status == $status;
        }
        return parent::__call($name, $arguments);
    }

    public function getCanHandelOptions()
    {
        return [
            'cancel' => $this->canCancelHandle(),
            'delete' => $this->canDeleteHandle(),
            'pay' => $this->canPayHandle(),
            'comment' => $this->canCommentHandle(),
            'confirm' => $this->canConfirmHandle(),
            'refund' => $this->canRefundHandle(),
            'rebuy' => $this->canRebuyHandle(),
            'aftersale' => $this->canAfterSaleHandle(),
            'ship' => $this->canShipHandle(),
            'agreerefund' => $this->canAgreeRefundHandle(),
        ];
    }

    public function isHadPaid()
    {
        return !in_array($this->order_status, [
            OrderEnums::STATUS_CREATE,
            OrderEnums::STATUS_ADMIN_CANCEL,
            OrderEnums::STATUS_AUTO_CANCEL,
            OrderEnums::STATUS_CANCEL
        ]);
    }
}
