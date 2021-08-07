<?php

declare(strict_types=1);

namespace think\api;

use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * Class Builder
 * @package think\api
 */
class Builder extends Command
{

    /**
     * @var array
     */
    private $config = [];

    /**
     * 参数
     */
    public function configure()
    {
        $this->config = config('api');
        $name = ($this->config)['api_name'] ?? 'Api Document';
        $this->setName('build')
            ->addOption('url', 'u', Option::VALUE_OPTIONAL, 'default api url', '')
            ->addOption('module', 'm', Option::VALUE_OPTIONAL, 'module name like index', 'index')
            ->addOption('output', 'o', Option::VALUE_OPTIONAL, 'output index file name', 'api.html')
            ->addOption('force', 'f', Option::VALUE_OPTIONAL, 'force override general file', false)
            ->addOption('name', 't', Option::VALUE_OPTIONAL, 'document api name', $name)
            ->addOption('class', 'c', Option::VALUE_OPTIONAL | Option::VALUE_IS_ARRAY, 'extend class', null)
            ->setDescription('Build Api document from controller');
    }

    public function execute(Input $input, Output $output)
    {
        $force = $input->getOption('force');
        $url = $input->getOption('url');
        $language = $input->getOption('language');

        $language = $language ? $language : 'zh-cn';

        // 目标目录
        $output_dir = app()->getRootPath() . 'public' . DIRECTORY_SEPARATOR;
        $output_file = $output_dir . $input->getOption('output');
        if (is_file($output_file) && !$force) {
            throw new Exception("api index file already exists!\nIf you need to rebuild again, use the parameter --force=true ");
        }
        // 额外的类
        $classes = $input->getOption('class');
        // 标题
        $name = $input->getOption('name');
        // 模块
        $module = $input->getOption('module');
        $config = $this->config;
        $params = [
            'api_url'      => empty($url) ? $config['api_url'] : $url,
            'api_name'     => empty($name) ? $config['api_name'] : $name,
            'api_author'   => $config['api_author'],
            'api_language' => $config['api_language'],
        ];
        try{
            $content = app('api')->getContent($module, $classes, $params);
        }catch (\Exception $e){
            print_r($e);
        }

        if (!file_put_contents($output_file, $content)) {
            throw new Exception('Cannot save the content to ' . $output_file);
        }
        $output->info("Build Successed!");
    }
}