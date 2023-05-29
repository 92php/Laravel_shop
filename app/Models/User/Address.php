<?php

namespace App\Models\User;

use App\Models\BaseModel;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * App\Models\User\Address
 *
 * @property int $id
 * @property string $name 收货人名称
 * @property int $user_id 用户表的用户ID
 * @property string $province 行政区域表的省ID
 * @property string $city 行政区域表的市ID
 * @property string $county 行政区域表的区县ID
 * @property string $address_detail 详细收货地址
 * @property string|null $area_code 地区编码
 * @property string|null $postal_code 邮政编码
 * @property string $tel 手机号码
 * @property bool $is_default 是否默认地址
 * @property Carbon|null $add_time 创建时间
 * @property Carbon|null $update_time 更新时间
 * @property bool|null $deleted 逻辑删除
 * @method static Builder|Address newModelQuery()
 * @method static Builder|Address newQuery()
 * @method static Builder|Address query()
 * @method static Builder|Address whereAddTime($value)
 * @method static Builder|Address whereAddressDetail($value)
 * @method static Builder|Address whereAreaCode($value)
 * @method static Builder|Address whereCity($value)
 * @method static Builder|Address whereCounty($value)
 * @method static Builder|Address whereDeleted($value)
 * @method static Builder|Address whereId($value)
 * @method static Builder|Address whereIsDefault($value)
 * @method static Builder|Address whereName($value)
 * @method static Builder|Address wherePostalCode($value)
 * @method static Builder|Address whereProvince($value)
 * @method static Builder|Address whereTel($value)
 * @method static Builder|Address whereUpdateTime($value)
 * @method static Builder|Address whereUserId($value)
 * @mixin Eloquent
 */
class Address extends BaseModel
{
    protected $casts = [
        'is_default' => 'boolean',
    ];

}
