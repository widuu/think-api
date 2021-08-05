<?php


namespace think\api;

use think\Api;
use think\Service as BaseService;

class Service extends BaseService
{
    /**
     * 绑定服务
     * @var string[]
     */
    public $bind = [
        'api' => Api::class
    ];

    /**
     * 注册命令行
     */
    public function boot()
    {
        dump($this->app->api);
        $this->commands([
           'api:build' => Builder::class,
        ]);
    }
}