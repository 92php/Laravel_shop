<?php

namespace App\Models;

use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Comment
 *
 * @property int $id
 * @property int $value_id 如果type=0，则是商品评论；如果是type=1，则是专题评论。
 * @property int $type 评论类型，如果type=0，则是商品评论；如果是type=1，则是专题评论；
 * @property string $content 评论内容
 * @property string $admin_content 管理员回复内容
 * @property int $user_id 用户表的用户ID
 * @property int|null $has_picture 是否含有图片
 * @property array|null $pic_urls 图片地址列表，采用JSON数组格式
 * @property int|null $star 评分， 1-5
 * @property Carbon|null $add_time 创建时间
 * @property Carbon|null $update_time 更新时间
 * @property bool|null $deleted 逻辑删除
 * @method static Builder|Comment newModelQuery()
 * @method static Builder|Comment newQuery()
 * @method static Builder|Comment query()
 * @method static Builder|Comment whereAddTime($value)
 * @method static Builder|Comment whereAdminContent($value)
 * @method static Builder|Comment whereContent($value)
 * @method static Builder|Comment whereDeleted($value)
 * @method static Builder|Comment whereHasPicture($value)
 * @method static Builder|Comment whereId($value)
 * @method static Builder|Comment wherePicUrls($value)
 * @method static Builder|Comment whereStar($value)
 * @method static Builder|Comment whereType($value)
 * @method static Builder|Comment whereUpdateTime($value)
 * @method static Builder|Comment whereUserId($value)
 * @method static Builder|Comment whereValueId($value)
 * @mixin Eloquent
 */
class Comment extends BaseModel
{
    protected $casts = [
        'pic_urls' => 'array'
    ];
}
