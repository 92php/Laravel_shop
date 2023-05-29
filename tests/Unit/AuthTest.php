<?php

namespace Tests\Unit;

use App\CodeResponse;
use App\Exceptions\BusinessException;
use App\Services\User\UserServices;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function testCheckMobileSendCaptchaCount()
    {
        $mobile = '13111111111';
        foreach (range(0, 9) as $i) {
            $isPass = UserServices::getInstance()->checkMobileSendCaptchaCount($mobile);
            $this->assertTrue($isPass);
        }
        $isPass = UserServices::getInstance()->checkMobileSendCaptchaCount($mobile);
        $this->assertFalse($isPass);
        $countKey = 'register_captcha_count_'.$mobile;
        Cache::forget($countKey);
        $isPass = UserServices::getInstance()->checkMobileSendCaptchaCount($mobile);
        $this->assertTrue($isPass);
    }

    public function testCheckCaptcha()
    {
        $mobile = '13111111111';
        $code = UserServices::getInstance()->setCaptcha($mobile);
        $isPass = UserServices::getInstance()->checkCaptcha($mobile, $code);
        $this->assertTrue($isPass);

        $this->expectExceptionObject(new BusinessException(CodeResponse::AUTH_CAPTCHA_UNMATCH));
        UserServices::getInstance()->checkCaptcha($mobile, $code);
    }

}
