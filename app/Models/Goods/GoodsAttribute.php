<?php

namespace App\Models\Goods;

use App\Models\BaseModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Goods\GoodsAttribute
 *
 * @property int $id
 * @property int $goods_id 商品表的商品ID
 * @property string $attribute 商品参数名称
 * @property string $value 商品参数值
 * @property Carbon|null $add_time 创建时间
 * @property Carbon|null $update_time 更新时间
 * @property bool|null $deleted 逻辑删除
 * @method static Builder|GoodsAttribute newModelQuery()
 * @method static Builder|GoodsAttribute newQuery()
 * @method static Builder|GoodsAttribute query()
 * @method static Builder|GoodsAttribute whereAddTime($value)
 * @method static Builder|GoodsAttribute whereAttribute($value)
 * @method static Builder|GoodsAttribute whereDeleted($value)
 * @method static Builder|GoodsAttribute whereGoodsId($value)
 * @method static Builder|GoodsAttribute whereId($value)
 * @method static Builder|GoodsAttribute whereUpdateTime($value)
 * @method static Builder|GoodsAttribute whereValue($value)
 * @mixin Eloquent
 */
class GoodsAttribute extends BaseModel
{
}
