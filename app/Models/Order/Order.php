<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Order\Order
 *
 * @property int $id
 * @property int $user_id 用户表的用户ID
 * @property string $order_sn 订单编号
 * @property int $order_status 订单状态
 * @property int|null $aftersale_status 售后状态，0是可申请，1是用户已申请，2是管理员审核通过，3是管理员退款成功，4是管理员审核拒绝，5是用户已取消
 * @property string $consignee 收货人名称
 * @property string $mobile 收货人手机号
 * @property string $address 收货具体地址
 * @property string $message 用户订单留言
 * @property float $goods_price 商品总费用
 * @property float $freight_price 配送费用
 * @property float $coupon_price 优惠券减免
 * @property float $integral_price 用户积分减免
 * @property float $groupon_price 团购优惠价减免
 * @property float $order_price 订单费用， = goods_price + freight_price - coupon_price
 * @property float $actual_price 实付费用， = order_price - integral_price
 * @property string|null $pay_id 微信付款编号
 * @property string|null $pay_time 微信付款时间
 * @property string|null $ship_sn 发货编号
 * @property string|null $ship_channel 发货快递公司
 * @property string|null $ship_time 发货开始时间
 * @property float|null $refund_amount 实际退款金额，（有可能退款金额小于实际支付金额）
 * @property string|null $refund_type 退款方式
 * @property string|null $refund_content 退款备注
 * @property string|null $refund_time 退款时间
 * @property string|null $confirm_time 用户确认收货时间
 * @property int|null $comments 待评价订单商品数量
 * @property string|null $end_time 订单关闭时间
 * @property Carbon|null $add_time 创建时间
 * @property Carbon|null $update_time 更新时间
 * @property bool|null $deleted 逻辑删除
 * @method static Builder|Order newModelQuery()
 * @method static Builder|Order newQuery()
 * @method static Builder|Order query()
 * @method static Builder|Order whereActualPrice($value)
 * @method static Builder|Order whereAddTime($value)
 * @method static Builder|Order whereAddress($value)
 * @method static Builder|Order whereAftersaleStatus($value)
 * @method static Builder|Order whereComments($value)
 * @method static Builder|Order whereConfirmTime($value)
 * @method static Builder|Order whereConsignee($value)
 * @method static Builder|Order whereCouponPrice($value)
 * @method static Builder|Order whereDeleted($value)
 * @method static Builder|Order whereEndTime($value)
 * @method static Builder|Order whereFreightPrice($value)
 * @method static Builder|Order whereGoodsPrice($value)
 * @method static Builder|Order whereGrouponPrice($value)
 * @method static Builder|Order whereId($value)
 * @method static Builder|Order whereIntegralPrice($value)
 * @method static Builder|Order whereMessage($value)
 * @method static Builder|Order whereMobile($value)
 * @method static Builder|Order whereOrderPrice($value)
 * @method static Builder|Order whereOrderSn($value)
 * @method static Builder|Order whereOrderStatus($value)
 * @method static Builder|Order wherePayId($value)
 * @method static Builder|Order wherePayTime($value)
 * @method static Builder|Order whereRefundAmount($value)
 * @method static Builder|Order whereRefundContent($value)
 * @method static Builder|Order whereRefundTime($value)
 * @method static Builder|Order whereRefundType($value)
 * @method static Builder|Order whereShipChannel($value)
 * @method static Builder|Order whereShipSn($value)
 * @method static Builder|Order whereShipTime($value)
 * @method static Builder|Order whereUpdateTime($value)
 * @method static Builder|Order whereUserId($value)
 * @mixin Eloquent
 */
class Order extends BaseModel
{
    use OrderStatusTrait;
}
