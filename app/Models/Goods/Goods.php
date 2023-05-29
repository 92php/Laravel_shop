<?php

namespace App\Models\Goods;

use App\Models\BaseModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Goods\Goods
 *
 * @property int $id
 * @property string $goods_sn 商品编号
 * @property string $name 商品名称
 * @property int|null $category_id 商品所属类目ID
 * @property int|null $brand_id
 * @property array|null $gallery 商品宣传图片列表，采用JSON数组格式
 * @property string|null $keywords 商品关键字，采用逗号间隔
 * @property string|null $brief 商品简介
 * @property bool|null $is_on_sale 是否上架
 * @property int|null $sort_order
 * @property string|null $pic_url 商品页面商品图片
 * @property string|null $share_url 商品分享朋友圈图片
 * @property bool|null $is_new 是否新品首发，如果设置则可以在新品首发页面展示
 * @property bool|null $is_hot 是否人气推荐，如果设置则可以在人气推荐页面展示
 * @property string|null $unit 商品单位，例如件、盒
 * @property float|null $counter_price 专柜价格
 * @property float|null $retail_price 零售价格
 * @property string|null $detail 商品详细介绍，是富文本格式
 * @property Carbon|null $add_time 创建时间
 * @property Carbon|null $update_time 更新时间
 * @property bool|null $deleted 逻辑删除
 * @method static Builder|Goods newModelQuery()
 * @method static Builder|Goods newQuery()
 * @method static Builder|Goods query()
 * @method static Builder|Goods whereAddTime($value)
 * @method static Builder|Goods whereBrandId($value)
 * @method static Builder|Goods whereBrief($value)
 * @method static Builder|Goods whereCategoryId($value)
 * @method static Builder|Goods whereCounterPrice($value)
 * @method static Builder|Goods whereDeleted($value)
 * @method static Builder|Goods whereDetail($value)
 * @method static Builder|Goods whereGallery($value)
 * @method static Builder|Goods whereGoodsSn($value)
 * @method static Builder|Goods whereId($value)
 * @method static Builder|Goods whereIsHot($value)
 * @method static Builder|Goods whereIsNew($value)
 * @method static Builder|Goods whereIsOnSale($value)
 * @method static Builder|Goods whereKeywords($value)
 * @method static Builder|Goods whereName($value)
 * @method static Builder|Goods wherePicUrl($value)
 * @method static Builder|Goods whereRetailPrice($value)
 * @method static Builder|Goods whereShareUrl($value)
 * @method static Builder|Goods whereSortOrder($value)
 * @method static Builder|Goods whereUnit($value)
 * @method static Builder|Goods whereUpdateTime($value)
 * @mixin Eloquent
 */
class Goods extends BaseModel
{
    //将属性转换为常见的数据类型
    protected $casts = [
        'counter_price' => 'float',
        'retail_price' => 'float',
        'is_new' => 'boolean',
        'is_hot' => 'boolean',
        'gallery' => 'array',
        'is_on_sale' => 'boolean'
    ];
}
