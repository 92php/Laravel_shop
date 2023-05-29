<?php

namespace Tests\Feature;

use App\Services\User\UserServices;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function testRegisterErrCode()
    {
        $response = $this->post('wx/auth/register', [
            'username' => 'tanfan2',
            'password' => '123456',
            'mobile' => '13111111113',
            'code' => '123'
        ]);
        $response->assertJson([
            'errno' => 703,
            'errmsg' => '验证码错误'
        ]);
    }

    public function testRegister()
    {
        $code = UserServices::getInstance()->setCaptcha('13111111113');
        $response = $this->post('wx/auth/register', [
            'username' => 'tanfan2',
            'password' => '123456',
            'mobile' => '13111111113',
            'code' => $code
        ]);
        $response->assertStatus(200);
        $response->assertJson([
            'errno' => 0, 'errmsg' => '成功', 'data' => [
                // 'token' => '',
                'userInfo' => [
                    'nickName' => 'tanfan2',
                    'avatarUrl' => "https://yanxuan.nosdn.127.net/80841d741d7fa3073e0ae27bf487339f.jpg?imageView&quality=90&thumbnail=64x64"
                ]
            ]
        ]);
        $ret = $response->getOriginalContent();
        $this->assertEquals(0, $ret['errno']);
        $this->assertNotEmpty($ret['data']);
    }

    public function testRegisterMobile()
    {
        $response = $this->post('wx/auth/register', [
            'username' => 'tanfan3',
            'password' => '123456',
            'mobile' => '131111111131',
            'code' => '1234'
        ]);
        $response->assertStatus(200);
        $ret = $response->getOriginalContent();
        $this->assertEquals(707, $ret['errno']);
    }

    public function testRegCaptcha()
    {
        $response = $this->post('wx/auth/regCaptcha', ['mobile' => '13122222222']);
        $response->assertJson(['errno' => 0, 'errmsg' => '成功']);
        $response = $this->post('wx/auth/regCaptcha', ['mobile' => '13122222222']);
        $response->assertJson(['errno' => 702, 'errmsg' => '验证码未超时1分钟，不能发送']);
    }

    public function testLogin()
    {
        $response = $this->post('wx/auth/login', ['username' => 'tanfan', 'password' => '123123']);
        $response->assertJson(
            [
                "errno" => 0,
                "errmsg" => "成功",
                "data" => [
                    "userInfo" => [
                        "nickName" => "tanfan",
                        "avatarUrl" => "https://yanxuan.nosdn.127.net/80841d741d7fa3073e0ae27bf487339f.jpg?imageView&quality=90&thumbnail=64x64"
                    ]
                ]
            ]
        );
        echo $response->getOriginalContent()['data']['token'] ?? '';
        $this->assertNotEmpty($response->getOriginalContent()['data']['token'] ?? '');
    }

    public function testInfo()
    {
        $response = $this->post('wx/auth/login', ['username' => 'user123', 'password' => 'user123']);
        $token = $response->getOriginalContent()['data']['token'] ?? '';
        $response2 = $this->get('wx/auth/info', ['Authorization' => "Bearer {$token}"]);
        $user = UserServices::getInstance()->getByUsername('user123');
        $response2->assertJson([
            'data' => [
                'nickName' => $user->nickname,
                'avatar' => $user->avatar,
                'gender' => $user->gender,
                'mobile' => $user->mobile
            ]
        ]);
    }

    public function testLogout()
    {
        $response = $this->post('wx/auth/login', ['username' => 'user123', 'password' => 'user123']);
        $token = $response->getOriginalContent()['data']['token'] ?? '';
        $response2 = $this->get('wx/auth/info', ['Authorization' => "Bearer {$token}"]);
        $user = UserServices::getInstance()->getByUsername('user123');
        $response2->assertJson([
            'data' => [
                'nickName' => $user->nickname,
                'avatar' => $user->avatar,
                'gender' => $user->gender,
                'mobile' => $user->mobile
            ]
        ]);
        $response3 = $this->post('wx/auth/logout', [], ['Authorization' => "Bearer {$token}"]);
        $response3->assertJson(['errno' => 0]);
        $response4 = $this->get('wx/auth/info', ['Authorization' => "Bearer {$token}"]);
        $response4->assertJson(['errno' => 501]);
    }

    public function testRest()
    {
        $mobile = '15100000000';
        $code = UserServices::getInstance()->setCaptcha($mobile);
        $response = $this->post('wx/auth/reset',
            [
                'mobile' => $mobile,
                'password' => 'user1234',
                'code' => $code
            ]);
        $response->assertJson(['errno' => 0]);
        $user = UserServices::getInstance()->getByMobile($mobile);
        $isPass = Hash::check('user1234', $user->password);
        $this->assertTrue($isPass);
    }

    public function testProfile()
    {
        $response = $this->post('wx/auth/login', ['username' => 'user123', 'password' => 'user123']);
        $token = $response->getOriginalContent()['data']['token'] ?? '';
        $response = $this->post('wx/auth/profile',
            [
                'avatar' => '',
                'gender' => 1,
                'nickname' => 'user1234'
            ], ['Authorization' => "Bearer {$token}"]);
        $response->assertJson(['errno' => 0]);
        $user = UserServices::getInstance()->getByUsername('user123');
        $this->assertEquals('user1234', $user->nickname);
        $this->assertEquals(1, $user->gender);
    }

}
