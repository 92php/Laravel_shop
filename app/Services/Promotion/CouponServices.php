<?php

namespace App\Services\Promotion;

use App\CodeResponse;
use App\Enums\CouponEnums;
use App\Enums\CouponUserEnums;
use App\Exceptions\BusinessException;
use App\Inputs\PageInput;
use App\Models\Promotion\Coupon;
use App\Models\Promotion\CouponUser;
use App\Services\BaseServices;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;

class CouponServices extends BaseServices
{
    public function getUsableCoupons($userId)
    {
        return CouponUser::query()->where('user_id', $userId)
            ->where('status', CouponUserEnums::STATUS_USABLE)
            ->get();
    }

    public function getCouponUser($id, $columns = ['*'])
    {
        return CouponUser::query()->find($id, $columns);
    }

    public function getCoupon($id, $columns = ['*'])
    {
        return Coupon::query()->find($id, $columns);
    }

    public function getCoupons(array $ids, $columns = ['*'])
    {
        return Coupon::query()->whereIn('id', $ids)->get($columns);
    }

    public function countCoupon($couponId)
    {
        return CouponUser::query()->where('coupon_id', $couponId)->count('id');
    }

    public function countCouponByUserId($userId, $couponId)
    {
        return CouponUser::query()->where('coupon_id', $couponId)->where('user_id', $userId)->count('id');
    }

    public function list(PageInput $page, $columns = ['*'])
    {
        return Coupon::query()->where('type', CouponEnums::TYPE_COMMON)
            ->where('status', CouponEnums::STATUS_NORMAL)
            ->orderBy($page->sort, $page->order)
            ->paginate($page->limit, $columns, 'page', $page->page);
    }

    public function mylist($userId, $status, PageInput $page, $columns = ['*'])
    {
        return CouponUser::query()->where('user_id', $userId)
            ->when(!is_null($status), function (Builder $query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderBy($page->sort, $page->order)
            ->paginate($page->limit, $columns, 'page', $page->page);
    }

    /**
     * @param $userId
     * @param $couponId
     * @return bool
     * @throws BusinessException
     */
    public function receive($userId, $couponId)
    {
        $coupon = CouponServices::getInstance()->getCoupon($couponId);
        if (is_null($coupon)) {
            $this->throwBusinessException(CodeResponse::PARAM_ILLEGAL);
        }

        if ($coupon->total > 0) {
            $fetchedCount = CouponServices::getInstance()->countCoupon($couponId);
            if ($fetchedCount >= $coupon->total) {
                $this->throwBusinessException(CodeResponse::COUPON_EXCEED_LIMIT);
            }
        }

        if ($coupon->limit > 0) {
            $userFetchedCount = CouponServices::getInstance()->countCouponByUserId($userId, $couponId);
            if ($userFetchedCount >= $coupon->limit) {
                $this->throwBusinessException(CodeResponse::COUPON_EXCEED_LIMIT, '优惠券已经领取过');
            }
        }

        if ($coupon->type != CouponEnums::TYPE_COMMON) {
            $this->throwBusinessException(CodeResponse::COUPON_RECEIVE_FAIL, '优惠券类型不支持');
        }

        if ($coupon->status == CouponEnums::STATUS_OUT) {
            $this->throwBusinessException(CodeResponse::COUPON_EXCEED_LIMIT);
        }
        if ($coupon->status == CouponEnums::STATUS_EXPIRED) {
            $this->throwBusinessException(CodeResponse::COUPON_RECEIVE_FAIL, '优惠券已经过期');
        }

        $couponUser = CouponUser::new();
        if ($coupon->time_type == CouponEnums::TIME_TYPE_TIME) {
            $startTime = $coupon->start_time;
            $endTime = $coupon->end_time;
        } else {
            $startTime = Carbon::now();
            $endTime = $startTime->copy()->addDays($coupon->days);
        }
        $couponUser->fill([
            'coupon_id' => $couponId,
            'user_id' => $userId,
            'start_time' => $startTime,
            'end_time' => $endTime
        ]);
        return $couponUser->save();
    }

    /**
     * 验证当前价格是否可以使用这张优惠券
     * @param  Coupon  $coupon
     * @param  CouponUser  $couponUser
     * @param  double  $price
     * @return bool
     * @throws Exception
     */
    public function checkCouponAndPrice($coupon, $couponUser, $price)
    {
        if (empty($couponUser)) {
            return false;
        }
        if (empty($coupon)) {
            return false;
        }
        if ($couponUser->coupon_id != $coupon->id) {
            return false;
        }
        if ($coupon->status != CouponEnums::STATUS_NORMAL) {
            return false;
        }

        if ($coupon->goods_type != CouponEnums::GOODS_TYPE_ALL) {
            return false;
        }

        if (bccomp($coupon->min, $price) == 1) {
            return false;
        }

        $now = now();
        switch ($coupon->time_type) {
            case CouponEnums::TIME_TYPE_TIME:
                $start = Carbon::parse($coupon->start_time);
                $end = Carbon::parse($coupon->end_time);
                if ($now->isBefore($start) || $now->isAfter($end)) {
                    return false;
                }
                break;
            case CouponEnums::TIME_TYPE_DAYS:
                $expired = Carbon::parse($couponUser->add_time)->addDays($coupon->days);
                if ($now->isAfter($expired)) {
                    return false;
                }
                break;
            default:
                return false;
        }
        return true;
    }

    public function getMeetPriceCouponAndSort($userId, $price)
    {
        $couponUsers = CouponServices::getInstance()->getUsableCoupons($userId);
        $couponIds = $couponUsers->pluck('coupon_id')->toArray();
        $coupons = CouponServices::getInstance()->getCoupons($couponIds)->keyBy('id');
        return $couponUsers->filter(function (CouponUser $couponUser) use ($coupons, $price) {
            /** @var Coupon $coupon */
            $coupon = $coupons->get($couponUser->coupon_id);
            return CouponServices::getInstance()->checkCouponAndPrice($coupon, $couponUser, $price);
        })->sortByDesc(function (CouponUser $couponUser) use ($coupons) {
            /** @var Coupon $coupon */
            $coupon = $coupons->get($couponUser->coupon_id);
            return $coupon->discount;
        });
    }

    public function getCouponUserByCouponId($userId, $couponId)
    {
        return CouponUser::query()->where('user_id', $userId)->where('coupon_id', $couponId)
            ->orderBy('id')->first();
    }

    /**
     * @param $userId
     * @param $couponId
     * @param $price
     * @param  int  $availableCouponLength
     * @return CouponUser|null
     * @throws Exception
     */
    public function getMostMeetPriceCoupon($userId, $couponId, $price, &$availableCouponLength = 0)
    {
        $couponUsers = $this->getMeetPriceCouponAndSort($userId, $price);
        $availableCouponLength = $couponUsers->count();

        if (is_null($couponId) || $couponId == -1) {
            return null;
        }

        if (!empty($couponId)) {
            $coupon = $this->getCoupon($couponId);
            $couponUser = $this->getCouponUserByCouponId($userId, $couponId);
            $is = $this->checkCouponAndPrice($coupon, $couponUser, $price);
            if ($is) {
                return $couponUser;
            }
        }
        return $couponUsers->first();
    }

}
