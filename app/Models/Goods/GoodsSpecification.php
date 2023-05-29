<?php

namespace App\Models\Goods;

use App\Models\BaseModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Goods\GoodsSpecification
 *
 * @property int $id
 * @property int $goods_id 商品表的商品ID
 * @property string $specification 商品规格名称
 * @property string $value 商品规格值
 * @property string $pic_url 商品规格图片
 * @property Carbon|null $add_time 创建时间
 * @property Carbon|null $update_time 更新时间
 * @property bool|null $deleted 逻辑删除
 * @method static Builder|GoodsSpecification newModelQuery()
 * @method static Builder|GoodsSpecification newQuery()
 * @method static Builder|GoodsSpecification query()
 * @method static Builder|GoodsSpecification whereAddTime($value)
 * @method static Builder|GoodsSpecification whereDeleted($value)
 * @method static Builder|GoodsSpecification whereGoodsId($value)
 * @method static Builder|GoodsSpecification whereId($value)
 * @method static Builder|GoodsSpecification wherePicUrl($value)
 * @method static Builder|GoodsSpecification whereSpecification($value)
 * @method static Builder|GoodsSpecification whereUpdateTime($value)
 * @method static Builder|GoodsSpecification whereValue($value)
 * @mixin Eloquent
 */
class GoodsSpecification extends BaseModel
{
}
