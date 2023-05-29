<?php

namespace App\Models\Order;

use App\Models\BaseModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Order\Cart
 *
 * @property int $id
 * @property int|null $user_id 用户表的用户ID
 * @property int|null $goods_id 商品表的商品ID
 * @property string|null $goods_sn 商品编号
 * @property string|null $goods_name 商品名称
 * @property int|null $product_id 商品货品表的货品ID
 * @property float|null $price 商品货品的价格
 * @property int|null $number 商品货品的数量
 * @property string|null $specifications 商品规格值列表，采用JSON数组格式
 * @property int|null $checked 购物车中商品是否选择状态
 * @property string|null $pic_url 商品图片或者商品货品图片
 * @property Carbon|null $add_time 创建时间
 * @property Carbon|null $update_time 更新时间
 * @property bool|null $deleted 逻辑删除
 * @method static Builder|Cart newModelQuery()
 * @method static Builder|Cart newQuery()
 * @method static Builder|Cart query()
 * @method static Builder|Cart whereAddTime($value)
 * @method static Builder|Cart whereChecked($value)
 * @method static Builder|Cart whereDeleted($value)
 * @method static Builder|Cart whereGoodsId($value)
 * @method static Builder|Cart whereGoodsName($value)
 * @method static Builder|Cart whereGoodsSn($value)
 * @method static Builder|Cart whereId($value)
 * @method static Builder|Cart whereNumber($value)
 * @method static Builder|Cart wherePicUrl($value)
 * @method static Builder|Cart wherePrice($value)
 * @method static Builder|Cart whereProductId($value)
 * @method static Builder|Cart whereSpecifications($value)
 * @method static Builder|Cart whereUpdateTime($value)
 * @method static Builder|Cart whereUserId($value)
 * @mixin Eloquent
 */
class Cart extends BaseModel
{
    protected $casts = [
        'checked' => 'boolean',
        'specifications' => 'array'
    ];
}
