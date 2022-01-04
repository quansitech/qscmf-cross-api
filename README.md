# qscmf-cross-api

用于管理其他系统访问的接口，类restful的接口规范（接口命名方面不完全遵守）

```text
随着业务的变更、系统的迭代，不同系统间的数据可能需要打通，为便于清晰管理系统接口的使用情况，更好的维护与开发，可以使用此扩展包来记录与管理。

情景举例：
存在系统A，系统B，系统C
三个系统均有各自管理员用户的权限体系。

现需求需要在系统A统一登录，且统一管理三个系统的用户权限，则需要系统B、系统C提供相应接口来维护权限相关数据源。

存在系统D，且其部分用户与系统B用户相关联，需要系统B的接口提供所需服务。

则可以在系统A中安装此扩展包，注册系统A可以访问的接口；在系统B中安装此扩展包，分别注册系统A、系统C可以访问的接口；

```
## 安装

```
composer require quansitech/qscmf-cross-api
```

## 用法


#### 使用数据迁移管理接口权限
```php
public function up()
{
    // 添加接口
    // $sign 使用此服务的系统标识
    // $ip 使用此服务的系统服务器IP（第一次新增时必填）
    // $name 使用此服务的系统名称（第一次新增时必填）
    $register = new \QscmfCrossApi\RegisterMethod('library_local','xxx','本地');
    
    // 接口路由信息
    // $module_name, $controller_name, $action_name
    $register->addMethod('IntranetApi', 'Index', 'gets');
    $register->addMethod('IntranetApi', 'Index', 'update');
    $register->register();

}
```

```php
public function down()
{
    // 移除接口
    // $sign 使用此服务的系统标识
    $register = new \QscmfCrossApi\RegisterMethod('library_local');
    
    // 接口路由信息
    // $module_name, $controller_name, $action_name
    $register->delMethod('IntranetApi', 'Index', 'gets');
    $register->delMethod('IntranetApi', 'Index', 'update');
    $register->register();
}
```


#### 接口例子

```php
namespace IntranetApi\Controller;

class IndexController extends \QscmfCrossApi\RestController
{
    protected $_filter = [
        ['id', 'isExists', 'Order', 404]  //自动完成Order表记录是否存在检查，如果不存在返回404
    ];

    public function gets(){
        $this->checkRequired(I('get.'), ['id' => '订单号']);  //检查提交的参数是否有id，否则会返回订单号不存在的提示
        
        $id = I('get.id');

        $order = D('Order')->getOne($id);

        $this->response('获取成功', 1, $order); //返回订单的详细的json数据
    }
    
    public function create(){
        
    }
    
    public function update(){
        
    }
    
    public function delete(){
        
    }
}
```



#### 属性设置

属性值在继承了RestController的类里进行设置

| 属性            | 说明                                       | 格式                                                         |
| :-------------- | :----------------------------------------- | :----------------------------------------------------------- |
| filter          | 过滤请求，只有通过了才能进行业务数据的访问 | 二维数组  [['id', 'isExists', 'Order', 404], ['item_id', 'isExists', 'Item', 404]] <br />目前仅支持isExists，检查数据库表有无对应的记录，没有则返回设置的http状态码 |



#### restful规范的语义化请求

1. get 表示获取信息 ，对应controller的gets方法
2. post 表示创建信息，对应controller的create方法
3. put 表示编辑信息, 对应controller的update方法
4. delete 表示删除信息，对应controller的delete方法



#### 验证请求
接口必须验证通过才能访问


#### 版本控制

通过在http请求头的accept里加入version=1.2之类的版本号来控制接口的请求路由

如get请求，在accept 的位置加入 version=1.2，那么就会匹配到controller的  gets_v1_2的方法



#### 内置方法

| 方法名                | 说明                   | 参数                                                         | 返回值                                                       |
| :-------------------- | :--------------------- | :----------------------------------------------------------- | ------------------------------------------------------------ |
| response              | 返回请求内容           | message  提示信息<br />status 类型标记<br />data 返回的具体内容<br />code http状态码，默认值 200<br />extra_res_data 额外需要返回的数据，默认为空数组 | 返回json或者xml等格式的字符串（根据请求的资源类型而定）<br />{  'info': 'message内容', 'status': 1, 'data': 'data的json格式内容', 'extra_res_data':'自定义返回内容'} |
| checkRequired         | 必填验证               | data 需要验证的数组<br />required_list 必填的字段设置，有两种格式，直接举例说明： 1. [ 'id', 'name'] 表示id, name字段都是必填，如果没有填写，自动返回"id必填"这样的错误提示。 2. [ 'title'=> '文章标题', 'type' => '文章类型'], 表示 title, type都是必填字段，后面的value值表示对应字段的中文描述，如没有传递type字段，会自动返回“文章类型必填”的错误提示，这样用户更容易理解错误信息。 | 验证不通过，直接response错误信息，否则返回true               |


#### 环境变量

环境变量在.env文件中设置

| 设置值                  | 说明           | 默认值 |
| :---------------------- | :------------- | :----- |
| QSCMF_INTRANET_API_MP_MAINTENANCE | 关闭接口的请求 |        |