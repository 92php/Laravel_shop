<?php


namespace App\Services;


use App\CodeResponse;
use App\Exceptions\BusinessException;
use Mockery;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;

class BaseServices
{
    protected static $instance = [];

    /**
     * @return static
     */
    public static function getInstance()
    {
        if ((static::$instance[static::class] ?? null) instanceof static) {
            return static::$instance[static::class];
        }
        return static::$instance[static::class] = new static();
    }

    /**
     * @return LegacyMockInterface|MockInterface
     */
    public static function mockInstance()
    {
        return static::$instance[static::class] = Mockery::mock(static::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
    }

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * @param  array  $codeResponse
     * @param  string  $info
     * @throws BusinessException
     */
    public function throwBusinessException(array $codeResponse, $info = '')
    {
        throw new BusinessException($codeResponse, $info);
    }

    /**
     * @throws BusinessException
     */
    public function throwBadArgumentValue()
    {
        $this->throwBusinessException(CodeResponse::PARAM_VALUE_ILLEGAL);
    }

    /**
     * @throws BusinessException
     */
    public function throwUpdateFail()
    {
        $this->throwBusinessException(CodeResponse::UPDATED_FAIL);
    }
}