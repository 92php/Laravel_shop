<?php

namespace App\Inputs;

class OrderSubmitInput extends Input
{
    public $cartId;
    public $addressId;
    public $couponId;
    public $userCouponId;
    public $message;
    public $grouponRulesId;
    public $grouponLinkId;

    public function rules()
    {
        return [
            'cartId' => 'required|integer',
            'addressId' => 'required|integer',
            'couponId' => 'required|integer',
            'userCouponId' => 'integer',
            'message' => 'string',
            'grouponRulesId' => 'integer',
            'grouponLinkId' => 'integer',
        ];
    }


}