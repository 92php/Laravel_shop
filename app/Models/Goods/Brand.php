<?php

namespace App\Models\Goods;

use App\Models\BaseModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Goods\Brand
 *
 * @property int $id
 * @property string $name 品牌商名称
 * @property string $desc 品牌商简介
 * @property string $pic_url 品牌商页的品牌商图片
 * @property int|null $sort_order
 * @property float|null $floor_price 品牌商的商品低价，仅用于页面展示
 * @property Carbon|null $add_time 创建时间
 * @property Carbon|null $update_time 更新时间
 * @property bool|null $deleted 逻辑删除
 * @method static Builder|Brand newModelQuery()
 * @method static Builder|Brand newQuery()
 * @method static Builder|Brand query()
 * @method static Builder|Brand whereAddTime($value)
 * @method static Builder|Brand whereDeleted($value)
 * @method static Builder|Brand whereDesc($value)
 * @method static Builder|Brand whereFloorPrice($value)
 * @method static Builder|Brand whereId($value)
 * @method static Builder|Brand whereName($value)
 * @method static Builder|Brand wherePicUrl($value)
 * @method static Builder|Brand whereSortOrder($value)
 * @method static Builder|Brand whereUpdateTime($value)
 * @mixin Eloquent
 */
class Brand extends BaseModel
{
    protected $casts = [
        'floor_price' => 'float'
    ];
}
