<?php

namespace Tests;

use App\Inputs\OrderSubmitInput;
use App\Models\Goods\GoodsProduct;
use App\Models\User\User;
use App\Services\Order\CartServices;
use App\Services\Order\OrderServices;
use App\Services\SystemServices;
use App\Services\User\AddressServices;
use App\Services\User\UserServices;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Arr;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    protected $token;

    /** @var User $user */
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->auth();
    }

    public function auth($user = null)
    {
        if (!is_null($user)) {
            $this->user = $user;
        } else {
            if (is_null($this->user)) {
                $this->user = User::factory()->create();
            }
        }
        return $this->token = \Auth::login($this->user);
    }

    public function assertLitemallApiGet($uri, $ignore = [])
    {
        return $this->assertLitemallApi($uri, 'get', [], $ignore);
    }

    public function assertLitemallApiPost($uri, $data = [], $ignore = [])
    {
        return $this->assertLitemallApi($uri, 'post', $data, $ignore);
    }

    /**
     * @param $uri
     * @param  string  $method
     * @param  array  $data
     * @throws GuzzleException
     */
    public function assertLitemallApi($uri, $method = 'get', $data = [], $ignore = [])
    {
        $this->user = UserServices::getInstance()->getUserById(1);
        $this->auth($this->user);
        $client = new Client();
        if ($method == 'get') {
            if (!empty($data)) {
                $uri .= '?'.Arr::query($data);
            }
            $response1 = $this->get($uri);
            $response2 = $client->get('http://122.112.215.32:8080/'.$uri,
                ['headers' => ['X-Litemall-Token' => $this->token]]);
        } else {
            $response1 = $this->post($uri, $data);
            $response2 = $client->post('http://122.112.215.32:8080/'.$uri,
                [
                    'headers' => ['X-Litemall-Token' => $this->token],
                    'json' => $data
                ]);
        }

        $content1 = $response1->getContent();
        echo "mcshop    =>".json_encode(json_decode($content1), JSON_UNESCAPED_UNICODE).PHP_EOL;
        $content1 = json_decode($content1, true);
        $content2 = $response2->getBody()->getContents();
        echo "litemall  =>$content2".PHP_EOL;
        $content2 = json_decode($content2, true);

        foreach ($ignore as $key) {
            Arr::forget($content1, [$key]);
            Arr::forget($content2, [$key]);
        }

        $this->assertEquals($content2, $content1);
    }

    public function getSimpleOrder($options = [[11.3, 2], [2.3, 1], [81.4, 4]])
    {
        $this->user = User::factory()->addressDefault()->create();
        $this->auth();
        $address = AddressServices::getInstance()->getDefaultAddress($this->user->id);

        foreach ($options as list($price, $num)) {
            /** @var GoodsProduct $product */
            $product = GoodsProduct::factory()->create(['price' => $price]);
            CartServices::getInstance()->add($this->user->id, $product->goods_id, $product->id, $num);
        }

        $input = OrderSubmitInput::new([
            'addressId' => $address->id,
            'cartId' => 0,
            'couponId' => 0,
            'grouponRulesId' => 0,
            'message' => '备注'
        ]);

        SystemServices::mockInstance()->shouldReceive('getFreightValue')
            ->andReturn(0);
        return OrderServices::getInstance()->submit($this->user->id, $input);
    }
}
