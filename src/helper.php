<?php

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
        // 返回结果
        return url($rule)->domain(false)->suffix(config('api.api_url_suffix', false))->build();
    }
}

