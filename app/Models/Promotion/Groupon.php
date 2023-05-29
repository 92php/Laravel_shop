<?php

namespace App\Models\Promotion;

use App\Models\BaseModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\Promotion\Groupon
 *
 * @property int $id
 * @property int $order_id 关联的订单ID
 * @property int|null $groupon_id 如果是开团用户，则groupon_id是0；如果是参团用户，则groupon_id是团购活动ID
 * @property int $rules_id 团购规则ID，关联litemall_groupon_rules表ID字段
 * @property int $user_id 用户ID
 * @property string|null $share_url 团购分享图片地址
 * @property int $creator_user_id 开团用户ID
 * @property string|null $creator_user_time 开团时间
 * @property int|null $status 团购活动状态，开团未支付则0，开团中则1，开团失败则2
 * @property Carbon $add_time 创建时间
 * @property Carbon|null $update_time 更新时间
 * @property bool|null $deleted 逻辑删除
 * @method static Builder|Groupon newModelQuery()
 * @method static Builder|Groupon newQuery()
 * @method static Builder|Groupon query()
 * @method static Builder|Groupon whereAddTime($value)
 * @method static Builder|Groupon whereCreatorUserId($value)
 * @method static Builder|Groupon whereCreatorUserTime($value)
 * @method static Builder|Groupon whereDeleted($value)
 * @method static Builder|Groupon whereGrouponId($value)
 * @method static Builder|Groupon whereId($value)
 * @method static Builder|Groupon whereOrderId($value)
 * @method static Builder|Groupon whereRulesId($value)
 * @method static Builder|Groupon whereShareUrl($value)
 * @method static Builder|Groupon whereStatus($value)
 * @method static Builder|Groupon whereUpdateTime($value)
 * @method static Builder|Groupon whereUserId($value)
 * @mixin Eloquent
 */
class Groupon extends BaseModel
{
}
