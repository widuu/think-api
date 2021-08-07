# think-api

> Thinkphp 6 api 文档生成系统

### 安装

```
composer require widuu/think-api
```

### 配置

```
return [
    // api 名称,生成文档的头部显示文字
    'api_name'           => '测试API',
    // api 接口请求地址
    'api_url'            => '',
    // api 作者
    'api_author'         => '',
    // 默认语言，其它语言在 vendor/widuu/think-api/src/lang 下添加对应的语言
    'api_language'       => 'zh-cn',
    // 将哪个模块作为api使用，如果没有安装多应用模块，这个是 controller 名称
    'module_name'        => 'index',
    // api 路由，注册路由显示 api 文档
    'api_route'          => '/api',
    // api 路由绑定域名，显示文档的域名
    'api_route_domain'   => '',
    // api 自动生成地址的后缀
    'api_url_suffix'      => false,
    // api 排除类中的方法，譬如你有个 init 等等
    'api_method_fileter'  => [],
    // api 缓存名称，目录缓存，如果为空缓存，如果不为空就缓存
    'api_cache_name'     => 'THINK_APIDOC_CACHE',
    // 附加类库，将其它类库显示文档
    'extend_class'       => [

    ],
];
```

### 使用

> 生成静态文档，使用 php think api 参数如下

```
  -u, --url[=URL]            default api url [default: ""]
  -m, --module[=MODULE]      module name like index [default: "index"]
  -o, --outfile[=OUTFILE]    output index file name [default: "api.html"]
  -f, --force[=FORCE]        force override general file [default: false]
  -t, --name[=NAME]          document api name [default: "测试API"]
  -c, --class[=CLASS]        extend class (multiple values allowed)
  -l, --language[=LANGUAGE]  language [default: "zh-cn"]
```

> 实时访问通过配置中定义的路由就可以直接访问了

### 注释说明

#### 类注解

|名称|说明|实例|
|:-----|:-----|:-----|
|@ApiTitle|类说明，如果不存在分组（@ApiSector）标题当成分组| @ApiTitle("测试类")|
|@ApiSector|分组，如果类内部方法没有此注解，所有函数都归属于此分组| @ApiSector("测试分组")|
|@ApiInternal|内部文档，禁止解析，使用之后此类不会被解析|@ApiInternal(true)|
|@ApiWeigh|排序，数字越大，排序越靠上|@ApiWeigh(10)|

#### 方法注解

> 注只解析 `public` 方法，并且跳过 `__construct` 方法，如果想要跳过哪些方法，可以在 `config/api.php` 中的 `api_method_fileter` 中添加方法名称来跳过注解

|名称|说明|实例|
|:-----|:-----|:-----|
|@ApiInternal|内部文档，禁止解析，使用之后此方法不会被解析|@ApiInternal (true)|
|@ApiTitle|方法说明，如果不存在，会匹配注释的文档信息匹配中文和英文信息，不能有符号，匹配不成功就是英文名称| @ApiTitle("测试方法")|
|@ApiSector|分组，如果不存在分组，则属于类内部分组| @ApiSector ("测试分组")|
|@ApiRoute|路由，如果是指定的tinkphp的控制器，可以为空自动解析，但是如果其他类或者伪静态一定要指定，路由建议用""注释,防止解析{}符号|@ApiRoute ("/index/test/{name}")|
|@ApiMethod|Api的请求方法，如果不存在就是 'GET' 方法|@ApiMethod(POST)|
|@ApiContentType| Api 的 Content-type|@ApiMethod ("multipart/form-data")|
|@ApiHeaders|请求头部信息，可以多个|@ApiHeaders (name=username, type=string, required=true, description="请求的用户名")|
|@ApiParams|请求的参数，可以多个|@ApiParams(name="name", type="string", required=true, description="方法名字")|
|@ApiReturnParams|返回参数说明，可以多个|@ApiReturnParams (name="code", type="integer", required=true, sample="0", description="返回的状态")|
|@ApiReturnHeaders|返回头部，可以多个|@ApiReturnHeaders (name="token", type="integer", required=true, sample="xxxxxxxx")|
|@ApiReturn|返回结果示例|@ApiReturn ("{'code':1,'msg':'返回成功','data':{'test':1}}")|
|@ApiBody|body正文|@ApiBody ("body")|
|@ApiWeigh|排序，数字越大，排序越靠上|@ApiWeigh(10)|


#### 示例

```
<?php


namespace app\index\controller\test;

/**
 * @ApiTitle("测试API")
 * @ApiWeigh (20)
 * Class Index
 * @package app\index\controller\test
 */
class Index
{
    /**
     * 测试方法
     *
     * @ApiTitle    (测试名称)
     * @ApiSector   (测试分组)
     * @ApiDescription   (测试描述信息)
     * @ApiRoute    ("/index/index.test/index/{name}")
     * @ApiContentType ("multipart/form-data")
     * @ApiMethod   (POST)
     * @ApiHeaders (name=username, type=string, required=true, description="请求的用户名")
     * @ApiBody   (测试正文)
     * @ApiParams   (name="name", type="string", required=true, description="用户名")
     * @ApiReturnParams (name="code", type="integer", required=true, sample="0", description="返回的状态")
     * @ApiReturnHeaders (name="token", type="integer", required=true, sample="xxxxxxxx")
     * @ApiReturn   ({
     *  'code':'1',
     *  'mesg':'返回成功'
     * })
     */
    public function index()
    {}

    /**
     * 方法测试
     */
    public function test()
    {}

    /**
     * 跳过注解的方法
     * @ApiInternal（true)
     */
    public function say()
    {}
}
```

