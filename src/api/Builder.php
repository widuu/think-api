<?php

declare(strict_types=1);

namespace think\api;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\Exception;

/**
 * Class Builder
 * @package think\api
 */
class Builder extends Command
{

    /**
     * 系统配置
     * @var array
     */
    private $config = [];

    /**
     * 参数
     */
    public function configure()
    {
        $this->config = config('api', []);
        $this->setName('build')
            ->addOption('url', 'u', Option::VALUE_OPTIONAL, 'default api url', $this->config['api_url'] ?? '' )
            ->addOption('module', 'm', Option::VALUE_OPTIONAL, 'module name like index', $this->config['module_name'] ?? '')
            ->addOption('outfile', 'o', Option::VALUE_OPTIONAL, 'output index file name', 'api.html')
            ->addOption('force', 'f', Option::VALUE_OPTIONAL, 'force override general file', false)
            ->addOption('name', 't', Option::VALUE_OPTIONAL, 'document api name', $this->config['api_name'] ?? 'Api Title')
            ->addOption('class', 'c', Option::VALUE_OPTIONAL | Option::VALUE_IS_ARRAY, 'extend class', null)
            ->addOption('language', 'l', Option::VALUE_OPTIONAL, 'language', $this->config['api_language'] ?? 'zh-cn')
            ->setDescription('Thinkphp6 Build Api document');
    }

    public function execute(Input $input, Output $output)
    {
        // 强制更新
        $force = $input->getOption('force');
        // api url 地址
        $apiUrl = $input->getOption('url');
        // 语言文件
        $apiLanguage = $input->getOption('language');
        // 输出文件
        $filePath = app()->getRootPath() . 'public' .DIRECTORY_SEPARATOR. $input->getOption('outfile');
        if (is_file($filePath) && !$force) {
            throw new Exception("api file already exists!\nIf you need to rebuild again, use the parameter --force=true ");
        }
        // 额外的类
        $classes = $input->getOption('class');
        // api 标题
        $apiTitle = $input->getOption('name');
        // 模块
        $module = $input->getOption('module');

        if(empty($module) && count($classes) == 0){
            throw new Exception("module and classes cannot be empty at the same time");
        }

        try{
            $content = app('api')->getContent($module, $classes, [
                'api_url'      => $apiUrl,
                'api_name'     => $apiTitle,
                'api_author'   => $this->config['api_author'],
                'api_language' => $apiLanguage
            ]);
        }catch (\Exception $e){
            print_r($e);
        }

        if (!file_put_contents($filePath, $content)) {
            throw new Exception('Cannot save the content to ' . $filePath);
        }

        $output->info("Api Document Build Successed!");
    }
}