<?php

namespace App\Services\Promotion;

use App\CodeResponse;
use App\Enums\GrouponEnums;
use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
use App\Models\Promotion\Groupon;
use App\Models\Promotion\GrouponRules;
use App\Services\BaseServices;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\AbstractFont;
use Intervention\Image\Facades\Image;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

use function route;

class GrouponServices extends BaseServices
{

    public function getGrouponRules(PageInput $page, $columns = ['*'])
    {
        return GrouponRules::whereStatus(GrouponEnums::RULE_STATUS_ON)
            ->orderBy($page->sort, $page->order)
            ->paginate($page->limit, $columns, 'page', $page->page);
    }

    public function getGrouponRulesById($id, $columns = ['*'])
    {
        return GrouponRules::query()->find($id, $columns);
    }

    /**
     * 获取参团人数
     * @param  int  $openGrouponId  开团团购活动Id
     * @return int
     */
    public function countGrouponJoin($openGrouponId)
    {
        return Groupon::query()->whereGrouponId($openGrouponId)
            ->where('status', '!=', GrouponEnums::STATUS_NONE)
            ->count(['id']);
    }

    /**
     * 用户是否参与或开启某个团购活动
     * @param $userId
     * @param $grouponId
     * @return bool
     */
    public function isOpenOrJoin($userId, $grouponId)
    {
        return Groupon::query()->whereUserId($userId)
            ->where(function (Builder $builder) use ($grouponId) {
                return $builder->where('groupon_id', $grouponId)
                    ->orWhere('id', $grouponId);
            })->where('status', '!=', GrouponEnums::STATUS_NONE)->exists();
    }

    /**
     * 校验用户是否可以开启或参与某个团购活动
     * @param $userId
     * @param $ruleId
     * @param  null  $linkId
     * @throws BusinessException
     */
    public function checkGrouponValid($userId, $ruleId, $linkId = null)
    {
        if ($ruleId == null || $ruleId <= 0) {
            return;
        }
        //获取团购规则
        $rule = $this->getGrouponRulesById($ruleId);
        if (is_null($rule)) {
            $this->throwBusinessException(CodeResponse::PARAM_ILLEGAL);
        }

        if ($rule->status == GrouponEnums::RULE_STATUS_DOWN_EXPIRE) {
            $this->throwBusinessException(CodeResponse::GROUPON_EXPIRED);
        }

        if ($rule->status == GrouponEnums::RULE_STATUS_DOWN_ADMIN) {
            $this->throwBusinessException(CodeResponse::GROUPON_OFFLINE);
        }

        if ($linkId == null || $linkId <= 0) {
            return;
        }

        if ($this->countGrouponJoin($linkId) >= ($rule->discount_member - 1)) {
            $this->throwBusinessException(CodeResponse::GROUPON_FULL);
        }

        if ($this->isOpenOrJoin($userId, $linkId)) {
            $this->throwBusinessException(CodeResponse::GROUPON_JOIN);
        }
        return;
    }

    public function getGroupon($id, $columns = ['*'])
    {
        return Groupon::query()->find($id, $columns);
    }

    /**
     * 生成开团或参团记录
     * @param $userId
     * @param $orderId
     * @param $ruleId
     * @param  null  $linkId
     * @return int|null
     */
    public function openOrJoinGroupon($userId, $orderId, $ruleId, $linkId = null)
    {
        // 卫语句
        if ($ruleId == null || $ruleId <= 0) {
            return $linkId;
        }

        $groupon = Groupon::new();
        $groupon->order_id = $orderId;
        $groupon->user_id = $userId;
        $groupon->status = GrouponEnums::STATUS_NONE;
        $groupon->rules_id = $ruleId;

        if ($linkId == null || $linkId <= 0) {
            $groupon->creator_user_id = $userId;
            $groupon->creator_user_time = Carbon::now()->toDateTimeString();
            $groupon->groupon_id = 0;
            $groupon->save();
            return $groupon->id;
        }

        $openGroupon = $this->getGroupon($linkId);
        $groupon->creator_user_id = $openGroupon->creator_user_id;
        $groupon->groupon_id = $linkId;
        $groupon->share_url = $openGroupon->share_url;
        $groupon->save();
        return $linkId;
    }

    public function getGrouponByOrderId($orderId)
    {
        return Groupon::whereOrderId($orderId)->first();
    }

    /**
     * 支付成功，更新团购活动状态
     * @param $orderId
     * @throws BusinessException
     */
    public function payGrouponOrder($orderId)
    {
        $groupon = $this->getGrouponByOrderId($orderId);
        if (is_null($groupon)) {
            return;
        }

        $rule = $this->getGrouponRulesById($groupon->rules_id);
        if ($groupon->groupon_id == 0) {
            $groupon->share_url = $this->createGrouponShareImage($rule);
        }

        $groupon->status = GrouponEnums::STATUS_ON;
        $isSuccess = $groupon->save();
        if (!$isSuccess) {
            $this->throwBusinessException(CodeResponse::UPDATED_FAIL);
        }

        if ($groupon->groupon_id == 0) {
            return;
        }

        $joinCount = $this->countGrouponJoin($groupon->groupon_id);
        if ($joinCount < $rule->discount_member - 1) {
            return;
        }

        $row = Groupon::query()->where(function (Builder $builder) use ($groupon) {
            return $builder->where('groupon_id', $groupon->groupon_id)
                ->orWhere('id', $groupon->groupon_id);
        })->update(['status' => GrouponEnums::STATUS_SUCCEED]);

        if ($row == 0) {
            $this->throwBusinessException(CodeResponse::UPDATED_FAIL);
        }
        return;
    }

    /**
     * 创建团购分享图片
     *
     * 1.获取链接，创建二维码
     * 2.合成图片
     * 3.保存图片，返回图片地址
     * @param  GrouponRules  $rules
     * @return string
     */
    public function createGrouponShareImage(GrouponRules $rules)
    {
        // 单元测试暂时不生成图片，减少单测运行时间
        if (app()->environment('testing')) {
            return '';
        }
        $shareUrl = route('home.redirectShareUrl', ['type' => 'groupon', 'id' => $rules->goods_id]);
        $qrCode = QrCode::format('png')->margin(1)->size(290)->generate($shareUrl);

        $goodsImage = Image::make($rules->pic_url)->resize(660, 660);
        $image = Image::make(resource_path('image/back_groupon.png'))
            ->insert($qrCode, 'top-left', 460, 770)
            ->insert($goodsImage, 'top-left', 71, 69)
            ->text($rules->goods_name, 65, 867, function (AbstractFont $font) {
                $font->color(array(167, 136, 69));
                $font->size(28);
                $font->file(resource_path('ttf/msyh.ttf'));
            });
        $filePath = 'groupon/'.Carbon::now()->toDateString().'/'.Str::random().'.png';
        Storage::disk('public')->put($filePath, $image->encode());
        return Storage::url($filePath);
    }

    public function getGrouponOrderInOrderIds($orderIds)
    {
        return Groupon::query()->whereIn('order_id', $orderIds)->pluck('order_id')->toArray();
    }

}
