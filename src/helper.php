<?php

use think\app\Url;
use think\facade\Route;

if(!function_exists('buildApiUrl')){
    /**
     * 生成 api url
     * @param string $url
     * @return string
     */
    function buildApiUrl(string $url)
    {
        $namespace  = empty(config('app.app_namespace')) ? 'app' : config('app.app_namespace');
        $controller = config('route.controller_layer');
        $rule       = ltrim(str_replace([$namespace, $controller, '\\', '//'], ['', '', '/', '/'], $url), "/");
        // 是否安装了多模块
        $isInstall = \Composer\InstalledVersions::isInstalled('topthink/think-multi-app');
        // 如果安装了多模块
        if($isInstall){
            $route  = explode("/", $rule);
            $module = array_shift($route);
            $action = array_pop($route);
            $rule   = $module . '/' . strtolower(implode('.', $route)) . '/' . $action;
        }
        // 返回结果
        return \think\facade\Route::buildUrl($rule)->domain(false)->suffix(config('api.api_url_suffix', false))->build();
    }
}

