<?php

declare(strict_types=1);

namespace think\api;

use think\facade\Route;

/**
 * Class Annotations
 * @package think\api
 */
class Annotations
{
    /**
     * 注解类缓存
     * @var array
     */
    protected static $annotations = [];

    /**
     * 解析类库
     * @param array $classes
     * @param array $filter
     * @return array
     * @throws \ReflectionException
     */
    public function getApiClassAnnotations(array $classes, array $filter = [])
    {
        $annotations = ['class' => [], 'method' => []];
        foreach ($classes as $class){
            // 类不存在，直接跳过
            if(!class_exists($class)) continue;
            // 反射类
            $reflectionClass = new \ReflectionClass($class);
            // 解析反射文档
            $classAnnotations = $this->getClassAnnotationsByReflection($reflectionClass);
            // 内部文档隐藏的跳过
            if(isset($classAnnotations['ApiInternal']) && $classAnnotations['ApiInternal'][0] == true) continue;
            // 文档排序
            if(!isset($classAnnotations['ApiWeigh'])) $classAnnotations['ApiWeigh'][0] = 0;
            // Api 类标题
            $classAnnotations['ApiTitle'][0]  = $this->parseTitle($classAnnotations, $reflectionClass->getDocComment(), $reflectionClass->getName());
            // 去除空类库
            $methodsAnnotations = $this->getMethodAnnotationsByReflectionClass($reflectionClass, $classAnnotations, $filter);
            if($methodsAnnotations){
                $annotations['class'][$class]  = $classAnnotations;
                $annotations['method'][$class] = $methodsAnnotations;
            }
        }
        return $annotations;
    }

    /**
     * 获取方法级别
     * @param \ReflectionClass $reflectionClass
     * @param array $filter
     * @return array
     */
    public function getMethodAnnotationsByReflectionClass(\ReflectionClass $reflectionClass, $classAnnotations = [], array $filter = [])
    {
        $reflectionMethods = $reflectionClass->getMethods();
        $className        = $reflectionClass->getName();
        $annotations      = [];
        foreach ($reflectionMethods as $reflection){
            if($reflection->isPublic() && !$reflection->isConstructor()) {
                $methodName = $reflection->getName();
                if(!isset(self::$annotations[$className][$methodName])){
                    $methodAnnotations = $this->parseAnnotations($reflection->getDocComment() ?: '') ?: [];
                    // 跳过内部方法
                    if(isset($methodAnnotations['ApiInternal']) && $methodAnnotations['ApiInternal'][0] == true) continue;
                    // 路由地址
                    $methodAnnotations['ApiRoute'][0]  = isset($methodAnnotations['ApiRoute']) ? $methodAnnotations['ApiRoute'][0] : buildApiUrl($className .'/'. $methodName);
                    // 解析 api 名称
                    $methodAnnotations['ApiTitle'][0]  = isset($methodAnnotations['ApiTitle']) ? trim($methodAnnotations['ApiTitle'][0]) : $methodName;
                    // 解析 api 方法
                    $methodAnnotations['ApiMethod'][0] = isset($methodAnnotations['ApiMethod']) ? strtoupper($methodAnnotations['ApiMethod'][0]) : 'GET';
                    // 权重
                    $methodAnnotations['ApiWeigh'][0]  = isset($methodAnnotations['ApiWeigh']) ? intval($methodAnnotations['ApiWeigh'][0]) : 0;
                    // 解析分组
                    if (!isset($methodAnnotations['ApiSector'])) {
                        $methodAnnotations['ApiSector'] = isset($classAnnotations['ApiSector']) ? $classAnnotations['ApiSector'] : $classAnnotations['ApiTitle'];
                    }
                    self::$annotations[$className][$methodName] = $methodAnnotations;
                }else{
                    $methodAnnotations = self::$annotations[$className][$methodName];
                }
                $annotations[$methodName] = $methodAnnotations;
            }
        }
        return $annotations;
    }

    /**
     * 获取类注解
     * @param $className
     * @param array $methodFilter
     * @return array|mixed
     * @throws \ReflectionException
     */
    public function getClassAndMethodsAnnotations($className, $methodFilter = []): array
    {
        $name = 'class_method_' . $className;
        if(!class_exists($className)) return [];
        if(!isset(self::$annotations[$name])){
            $annotations  = [];
            $reflection    = new \ReflectionClass($className);
            // 获取类注解
            $annotations['class'] = $this->parseAnnotations($reflection->getDocComment() ?: '');
            // 如果没有类注解
            !isset(self::$annotations[$className]) && self::$annotations[$className] = $annotations['class'];
            // 获取到所有的方法
            $classMethods = $reflection->getMethods();
            foreach ($classMethods as $method){
                // 方法名称
                $methodName = $method->getName();
                // 过滤掉方法
                if(in_array($methodName, $methodFilter) || !$method->isPublic() || $method->isConstructor()) continue;
                // 解析方法注解
                $annotations['methods'][$methodName] = $this->parseAnnotations($method->getDocComment() ?: '') ?: [];
            }
            self::$annotations[$name] = $annotations;
        }
        return self::$annotations[$name];
    }

    /**
     * 获取标题
     * @param $annotation
     * @param $document
     * @param $className
     * @return mixed|string
     */
    private function parseTitle($annotation, $document, $className)
    {
        // 存在标题直接返回
        if(isset($annotation['ApiTitle'])) return $annotation['ApiTitle'][0];
        // 解析文档
        if (!isset($annotation['ApiTitle']) && !empty($document)) {
            preg_match_all("/\*[\s]+(.*)(\\r\\n|\\r|\\n)/U", str_replace('/**', '', $document), $match);
            $title = isset($match[1]) && isset($match[1][0]) ? $match[1][0] : '';
            if(preg_match("/^[\x{4e00}-\x{9fa5}a-zA-Z0-9]+$/u", $title)) return $title;
        }
        // 如果为空
        if(isset($annotation['ApiSector'])) return $annotation['ApiSector'][0];
        // 全都没有返回类名字
        return $className;
    }

    /**
     * 通过反射类获取文档
     * @param \ReflectionClass $reflectionClass
     * @return array|mixed
     */
    public function getClassAnnotationsByReflection(\ReflectionClass $reflectionClass)
    {
        $className = $reflectionClass->getName();
        if(!isset(self::$annotations[$className])){
            self::$annotations[$className] = $this->parseAnnotations($reflectionClass->getDocComment() ?: '') ?: [];
        }
        return self::$annotations[$className];
    }

    /**
     * 返回类注解
     * @param $className
     * @return array
     * @throws \ReflectionException
     */
    public function getClassAnnotations($className): array
    {
        if(!class_exists($className)) return [];
        if(!isset(self::$annotations[$className])){
            $class = new \ReflectionClass($className);
            self::$annotations[$className] = $this->parseAnnotations($class->getDocComment() ?: '');
        }
        return self::$annotations[$className];
    }

    /**
     * 获取类方法的注解
     * @param string $className
     * @param string $method
     * @return array|mixed|void
     * @throws \ReflectionException
     */
    public function getMethodAnnotations(string $className, string $method): array
    {
        $name = $className . '@' . $method;
        // 类或者方法不存在，返回空数组
        if(!class_exists($className) || !method_exists($className, $method)) return [];
        // 不存在
        if(!isset(self::$annotations[$name])){
            $reflection = new \ReflectionMethod($className, $method);
            self::$annotations[$name] = $reflection->isPublic() ? $this->parseAnnotations($reflection->getDocComment() ?: '') : [];
        }

        return self::$annotations[$name];
    }

    /**
     * 获取函数注解
     * @param string $functionName
     * @return array|mixed|void
     * @throws \ReflectionException
     */
    public function getFunctionAnnotatios(string $functionName): array
    {
        $name = 'function@' . $functionName;
        if(!function_exists($functionName)) return [];
        if(!isset(self::$annotations[$name])){
            $reflection = new \ReflectionFunction($name);
            self::$annotations[$name] = $this->parseAnnotations($reflection->getDocComment() ?: '');
        }
        return self::$annotations[$name];
    }

    /**
     * 解析文档
     * @param string $document
     * @return array
     */
    protected function parseAnnotations(string $document): array
    {
        $annotations = [];
        // 去除注释头部和尾部 /**  */
        $document = substr($document, 3, -2);
        // 每行匹配获取注解
        if (preg_match_all('/@(?<name>[A-Za-z_-]+)[\s\t]*\((?<args>(?:(?!\)).)*)\)\r?/s', $document, $matches)) {
            // 解析分析参数
            foreach ($matches['name'] as $k => $v){
                $annotations[$v][] = isset($matches['args'][$k]) ? $this->parseArgs($matches['args'][$k]) : [];
            }
        }
        return $annotations;
    }

    /**
     * 解析参数
     * @param string $argements
     * @return array|bool|int|mixed|string
     */
    protected function parseArgs(string $argements)
    {
        // 换行处理
        $argements = preg_replace('/^\s*\*/m', '', $argements);
        // 解析后的参数
        $params = [];
        // 字符串长度
        $len    = strlen($argements);
        // 字符游标
        $cursor = 0;
        // 参数和值
        $var    = $val = '';
        // 多层嵌套解析
        $level  = 1;
        // 当前的标识符
        $delimiter     = null;
        // 上一个标识符
        $prevDelimiter = '';
        // 下一个标识符
        $nextDelimiter = '';
        // 参数和值结构标识
        $structure     = false;
        // 解析类型
        $type          = 'plain';
        // 是否去除空格
        $isTrim        = false;
        // 开合闭区间
        $flag = ['"', '"', '{', '}', ',', '='];
        // 循环字符串
        while ($cursor <= $len){
            $prev_char = substr($argements, $cursor -1, 1);
            $char = substr($argements, $cursor++, 1);
            // 譬如 "params" 来解析参数开闭合
            if($prev_char !== '\\' && $char == '"'){
                $delimiter = $char;
                // " 开区间
                if (!$structure && empty($prevDelimiter) && empty($nextDelimiter)) {
                    $prevDelimiter = $nextDelimiter = $delimiter;
                    $val           = '';
                    $structure     = true;
                    $isTrim        = true;
                }else{
                    // 验证是否在闭合区间内
                    if($char != $nextDelimiter){
                        throw new \InvalidArgumentException(sprintf(
                            "Parse Error: enclosing error -> expected: [%s], given: [%s]",
                            $nextDelimiter, $char
                        ));
                    }

                    // 验证数据是不是解析完，没解析完抛出异常
                    if ($cursor < $len) {
                        if (',' !== substr($argements, $cursor, 1)) {
                            throw new \InvalidArgumentException(sprintf(
                                "Parse Error: missing comma separator near: ...%s<--",
                                substr($argements, ($cursor-10), $cursor)
                            ));
                        }
                    }

                    $prevDelimiter = $nextDelimiter = '';
                    $structure     = false;
                    $delimiter     = null;
                }
            }elseif (!$structure && in_array($char, $flag)){
                switch ($char){
                    case '=' :
                        // 参数后的 = 参数
                        $prevDelimiter = $nextDelimiter = '';
                        $level         = 2;
                        $structure     = false;
                        $type          = 'equality';
                        $isTrim = false;
                        break;
                    case ',':
                        // 使用,分割两个参数 api=params,test=test
                        $level = 3;
                        // 必须标签中，并且还没有上一个或者下一个闭合标签 推出异常
                        if ($structure === true && !empty($prevDelimiter) && !empty($nextDelimiter)) {
                            throw new \InvalidArgumentException(sprintf(
                                "Parse Error: enclosing error -> expected: [%s], given: [%s]",
                                $nextDelimiter, $char
                            ));
                        }
                        // 重置分隔符，因为','来分割两个参数
                        $prevDelimiter = $nextDelimiter = '';
                        break;
                    case '{' :
                        // 解析{}参数解析
                        // 数据
                        $content = '';
                        // 必须标识
                        $close   = false;
                        // 继续循环剩下的数据
                        while ($cursor <= $len){
                            $char = substr($argements, $cursor++, 1);
                            // 解析混乱 存在闭合标签的时候
                            if (isset($delimiter) && $char === $delimiter) {
                                throw new \InvalidArgumentException(sprintf(
                                    "Parse Error: Composite variable is not enclosed correctly."
                                ));
                            }
                            // 一直到 } 循环结束
                            if($char == '}'){
                                $close = true;
                                break;
                            }
                            $content .= $char;
                        }
                        // 到结尾都没找到必须标签，错误
                        if(!$close){
                            break;
                            throw new \InvalidArgumentException(sprintf(
                                'no closed label errors were found. near %s', $content
                            ));
                        }
                        // 解析参数
                        $val = $this->parseArgs($content);
                        break;
                }
            }else{
                if($level == 1){
                    $var .= $char;
                }elseif ($level == 2){
                    $val .= $char;
                }
            }
            // 当层级为3 主要 {} 的结构和循环结束时 解析值
            if ($level === 3 || $cursor === $len) {
                if ($type == 'plain' && $cursor === $len) {
                    $params = $this->parseValue($var);
                } else {
                    $params[trim($var)] = $this->parseValue($val, !$isTrim);
                }
                // 初始化数值
                $level     = 1;
                $var       = $val = '';
                $structure = false;
                $isTrim    = false;
            }
        }
        return $params;
    }

    /**
     * 解析值
     * @param $val
     * @param false $trim
     * @return bool|int|mixed|string
     */
    private function parseValue($val, $trim = false)
    {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $val[$k] = $this->parseValue($v);
            }
        } elseif (is_string($val)) {
            // 去除空字符
            if ($trim) {
                $val = trim($val);
            }
            // 解析 false 或者 true
            $tmp = strtolower($val);
            if ($tmp === 'false' || $tmp === 'true') {
                $val = $tmp === 'true';
            } elseif (is_numeric($val)) {
                return intval($val);
            }
            unset($tmp);
        }
        return $val;
    }
}