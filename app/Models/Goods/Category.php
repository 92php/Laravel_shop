<?php

namespace App\Models\Goods;

use App\Models\BaseModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Goods\Category
 *
 * @property int $id
 * @property string $name 类目名称
 * @property string $keywords 类目关键字，以JSON数组格式
 * @property string|null $desc 类目广告语介绍
 * @property int $pid 父类目ID
 * @property string|null $icon_url 类目图标
 * @property string|null $pic_url 类目图片
 * @property string|null $level
 * @property int|null $sort_order 排序
 * @property Carbon|null $add_time 创建时间
 * @property Carbon|null $update_time 更新时间
 * @property bool|null $deleted 逻辑删除
 * @method static Builder|Category newModelQuery()
 * @method static Builder|Category newQuery()
 * @method static Builder|Category query()
 * @method static Builder|Category whereAddTime($value)
 * @method static Builder|Category whereDeleted($value)
 * @method static Builder|Category whereDesc($value)
 * @method static Builder|Category whereIconUrl($value)
 * @method static Builder|Category whereId($value)
 * @method static Builder|Category whereKeywords($value)
 * @method static Builder|Category whereLevel($value)
 * @method static Builder|Category whereName($value)
 * @method static Builder|Category wherePicUrl($value)
 * @method static Builder|Category wherePid($value)
 * @method static Builder|Category whereSortOrder($value)
 * @method static Builder|Category whereUpdateTime($value)
 * @mixin Eloquent
 */
class Category extends BaseModel
{
}
