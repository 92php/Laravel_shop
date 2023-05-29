<?php

namespace App\Jobs;

use App\Exceptions\BusinessException;
use App\Services\Order\OrderServices;
use App\Services\SystemServices;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * 订单超时取消任务
 * Class OrderUnpaidTimeEndJob
 * @package App\Jobs
 */
class OrderUnpaidTimeEndJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $userId;
    private $orderId;

    /**
     * Create a new job instance.
     *
     * @param $userId
     * @param $orderId
     */
    public function __construct($userId, $orderId)
    {
        $this->userId = $userId;
        $this->orderId = $orderId;
        $delayTime = SystemServices::getInstance()->getOrderUnpaidDelayMinutes();
        $this->delay(now()->addMinutes($delayTime));
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            OrderServices::getInstance()->systemCancel($this->userId, $this->orderId);
        } catch (BusinessException $exception) {
        }
    }
}
