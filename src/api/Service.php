<?php


namespace think\api;

use think\Service as BaseService;

class Service extends BaseService
{
    /**
     * 注册命令行
     */
    public function boot()
    {
        $this->commands([
           'api:build' => Builder::class,
        ]);
    }
}