<?php


namespace think\api;

use think\Api;
use think\Route;
use think\Service as BaseService;

class Service extends BaseService
{
    /**
     * 绑定服务
     * @var string[]
     */
    public $bind = [
        'api'         => Api::class,
        'annotations' => Annotations::class
    ];

    /**
     * 注册命令行
     */
    public function boot()
    {
        // 注册路由
        $rule = $this->app->config->get('api.api_route', '');
        if(!empty($rule)){
            $domain = $this->app->config->get('api.api_route_domain', '');
            $this->registerRoutes(function (Route $route) use ($rule, $domain){
                $r = $route->rule($rule, '\\think\\Api@render');
                !empty($domain) && $r->domain($domain);
            });
        }

        // 注册命令行
        $this->commands([
           'api:build' => Builder::class,
        ]);
    }
}