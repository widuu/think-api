<?php

namespace think;

use think\facade\Config;
use think\facade\Cache;

/**
 * Class Api
 * @package think
 */
class Api
{
    /**
     * @var \think\App
     */
    private $app;

    /**
     * Api constructor.
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app          = $app;
        $viewPath           = __DIR__ . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;
        $config              = config('view');
        $config['view_path'] = $viewPath;
        $this->app->config->set($config, 'view');
        $this->app->lang->load( __DIR__ . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . 'zh-cn.php');
    }

    /**
     * 加载设置类库
     * @return array
     */
    public function loadClasses(string $module = '', array $extendClasses = [], $cache = true): array
    {
        $classes    = $extendClasses ? $extendClasses : Config::get('api.extend_class', []);
        $moduleName = empty($module) ? Config::get('api.module_name', '') : $module;
        $cacheName  = Config::get('api.api_cache_name', '');
        // 是否安装了多模块
        $isInstall = \Composer\InstalledVersions::isInstalled('topthink/think-multi-app');
        if(!$isInstall){
            $class = $this->app->getNamespace() .'\\'. Config::get('route.controller_layer') . '\\' . $moduleName;
            return [$class];
        }
        // 如果存在缓存从缓存读取
        if(!empty($cacheName)){
            if(Cache::has($cacheName) && $cache) return Cache::get($cacheName, []);
        }
        // 定义的模块名称
        if(!empty($moduleName)){
            $moduleDir = $this->app->getAppPath() . $moduleName. DIRECTORY_SEPARATOR . Config::get('route.controller_layer');
            // 解析目录和文件
            $dirs = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($moduleDir, \RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($dirs as $file){
                array_push($classes, str_replace([$this->app->getRootPath(), '/', '.php'], ['', '\\', ''], $file->getPathname()));
            }
        }
        // 设置缓存
        !empty($cacheName) && Cache::set($cacheName, $classes);
        // 返回类库
        return $classes;
    }

    /**
     * 对应CSS样式
     * @param string $method
     * @return string
     */
    private function getCssStyle(string $method)
    {
        $css = [ 'POST' => 'success', 'GET' => 'primary', 'PUT' => 'warning', 'DELETE' => 'danger', 'PATCH' => 'default', 'OPTIONS' => 'info'];
        return $css[$method] ?? 'success';
    }

    /**
     * 解析参数
     * @param array $params
     * @return array
     */
    private function parseParams(array $params = [])
    {
        if(!$params) return [];
        $arguments = [];
        foreach ($params as $v){
            $arguments[] = [
                'name'        => $v['name'],
                'type'        => $v['type'] ?? 'string',
                'sample'      => $v['sample'] ?? '',
                'required'    => $v['required'] ?? true,
                'description' => $v['description'] ?? '',
            ];
        }
        return $arguments;
    }

    /**
     * 解析头部
     * @param array $headers
     */
    private function parseHeader(array $headers = [], $isReturn = false)
    {
        if(!$headers) return [];
        $headerLists = [];
        foreach ($headers as $params) {
            $headerslist[] = [
                'name'        => $params['name'] ?? '',
                'type'        => $isReturn ? 'string' : ($params['type'] ?? 'string'),
                'sample'      => $params['sample'] ?? '',
                'required'    => $isReturn ? (isset($params['required']) && $params['required'] ? 'Yes' : 'No') : ($params['required'] ?? false),
                'description' => $params['description'] ?? '',
            ];
        }
        return $headerslist;
    }

    /**
     * 解析所有类库
     * @param array $classes
     * @return array
     */
    public function parseClass(array $classes)
    {
        $filter             = Config::get('api.api_method_fileter', []);
        $annotations       = app('annotations')->getApiClassAnnotations($classes, $filter);
        $classAnnotations  = $annotations['class'];

        $sectorArr = [];
        foreach ($classAnnotations as $value)
        {
            $sector = isset($value['ApiSector']) ? $value['ApiSector'][0] : $value['ApiTitle'][0];
            $sectorArr[$sector] = isset($value['ApiWeigh']) ? $value['ApiWeigh'][0] : 0;
        }
        arsort($sectorArr);

        $methodAnnotations = $annotations['method'];

        // 解析参数
        $list = [];
        $id   = 0;
        $keys = [];
        foreach ($methodAnnotations as $index => $annotation)
        {
            foreach ($annotation as $name => $method){
                if (isset($method['ApiSector'][0])) {
                    $section = $method['ApiSector'][0];
                } else {
                    $section = $index;
                }
                $list[$section][$name] = [
                    'id'            => $id,
                    'method'        => $method['ApiMethod'][0],
                    'contentType'   => isset($method['ApiContentType']) ? $method['ApiContentType'][0] : '',
                    'responseType'  => isset($method['ApiResponseType']) ? $method['ApiResponseType'][0] : '',
                    'style'         => $this->getCssStyle($method['ApiMethod'][0]),
                    'section'       => $section,
                    'url'           => $method['ApiRoute'][0],
                    'title'         => $method['ApiTitle'][0],
                    'description'   => isset($method['ApiDescription']) && is_array($method['ApiDescription']) ? $method['ApiDescription'][0] : '',
                    'body'          => isset($method['ApiBody']) && is_array($method['ApiBody']) ? $method['ApiBody'][0] : '',
                    'headers'       => $this->parseHeader($method['ApiHeaders'] ?? []),
                    'params'        => $this->parseParams($method['ApiParams'] ?? []),
                    'returnParams'  => $this->parseParams($method['ApiReturnParams'] ?? []),
                    'returnHeaders' => $this->parseHeader($method['ApiReturnHeaders'] ?? [], true),
                    'sort'          => $method['ApiWeigh'][0],
                    'return'        => isset($method['ApiReturn']) && is_array($method['ApiReturn']) ? $method['ApiReturn'][0] : '',
                ];
                $keys[$section] = 0;
                $id++;
            }
        }

        // 排序
        foreach ($list as $index => &$methods)
        {
            $sortArr = [];
            foreach ($methods as $name => $method){
                $sortArr[$name] = isset($method['sort']) ? $method['sort'] : 0;
            }
            arsort($sortArr);
            $methods = array_merge(array_flip(array_keys($sortArr)), $methods);
            unset($sortArr);
        }

        // 去重
        $key = array_diff_key($sectorArr, $keys);
        if($key){
            $sectorArr = array_filter($sectorArr, function ($k, $v) use ($key){
               return !array_key_exists($v, $key);
            }, ARRAY_FILTER_USE_BOTH);
        }

        // 分组排序
        $list = array_merge(array_flip(array_keys($sectorArr)), $list);
        unset($sectorArr);
        return $list;
    }

    /**
     * 输出末班
     * @return mixed
     */
    public function render()
    {
        $lists  = $this->parseClass($this->loadClasses());
        $config = Config::get('api', []);
        return $this->app->view->fetch('index', ['lists' => $lists, 'config' => $config]);
    }

    /**
     * @param $module
     * @param array $classes
     * @param $config
     * @return mixed
     */
    public function getContent($module, $classes = [], $config = [])
    {
        $lists  = $this->parseClass($this->loadClasses($module, $classes, false));
        return $this->app->view->fetch('index', ['lists' => $lists, 'config' => $config]);
    }
}