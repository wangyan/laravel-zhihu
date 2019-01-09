# Laravel 开发知乎笔记

开发一个类似于知乎的站点：用户可以发布问题，提交回答，发表评论等，用户之间可以相互关注，发送私信等。

## 第一章 基础

### 1.1 开发环境

- OS： macOS High Sierra 10.13
- Homestead: 6.5    
- Vagrant box: parallels 4.0.0 
- Laravel 5.5    
- PHP 7.1.10 , MySQL 5.7.19 , Nginx 1.13.3  

### 1.2 配置国内软件源

Composer

```bash
composer config -g repo.packagist composer https://packagist.laravel-china.org
```

NodeJS

```bash
# 推荐使用 yarn 
yarn config set registry https://registry.npm.taobao.org --global && \
yarn config set disturl https://npm.taobao.org/dist --global && \
yarn config set sass-binary-site https://npm.taobao.org/mirrors/node-sass

# 二选一
npm config set registry https://registry.npm.taobao.org --global && \
npm config set disturl https://npm.taobao.org/dist --global && \
npm config set sass-binary-site https://npm.taobao.org/mirrors/node-sass

# 查看效果
yarn config list
```

Ubuntu 16.04

```bash
sed -i 's/archive.ubuntu.com/mirrors.163.com/g' /etc/apt/sources.list && \
sed -i 's/deb http:\/\/security/#deb http:\/\/security/g' /etc/apt/sources.list && \
sed -i 's/deb-src http:\/\/security/#deb-src http:\/\/security/g' /etc/apt/sources.list && \
apt-get -y update
```

### 1.3 创建 Laravel 项目

```bash
# 创建项目
composer create-project --prefer-dist laravel/laravel zhihu "5.5.*"

# 更新依赖库
yarn install && \
composer update && \
composer dump-autoload 

# 其他参考命令
yarn install --no-bin-links (windows)
yarn install --force （重构某个包）
npm rebuild node-sass

# 导入 Git 配置文件
cat .gitconfig > ~/.gitconfig
git config --global --edit
```

###  1.4 更改 User 模型位置

将 `User.php` 放置到 `app/Models/` 文件夹，命名空间改成：

```php
namespace App\Models;
```

修改 `auth.php` 配置文件：

```php
<?php
// config\auth.php
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],
```

### 1.5 数据库 User 表设计

> [文档：《Laravel 的数据库迁移 Migrations》](https://d.laravel-china.org/docs/5.5/migrations)

设计 `User` 表结构

```php
<?php
// database/migrations/2014_10_12_000000_create_users_table.php

    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->string('email')->unique();
            $table->string('password');
            $table->string('avatar');
            $table->string('confirmation_token');
            $table->smallInteger('is_active')->default(0);
            $table->string('questions_count')->default(0);
            $table->string('answers_count')->default(0);
            $table->string('comments_count')->default(0);
            $table->string('favorites_count')->default(0);
            $table->string('likes_count')->default(0);
            $table->string('followers_count')->default(0);
            $table->string('following_count')->default(0);
            $table->json('settings')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }
```

修改 `.env` 配置信息

运行数据库迁移

```bash
php artisan migrate
```

## 第二章 用户注册后发送验证邮件

### 2.1 阿里云邮件推送服务

修改 `composer.json` 然后 `composer update`

```json
"require": {
    "wang_yan/directmail": "dev-master"
},
```

或者在项目目录下执行

```bash
composer require wang_yan/directmail:dev-master
```

然后修改 `config/app.php`，添加服务提供者

```php
<?php
'providers' => [
   // 添加这行
    WangYan\DirectMail\DirectMailTransportProvider::class,
];
```

最后在 `.env` 中配置你的密钥， 并修改邮件驱动为 `directmail`

```bash
MAIL_DRIVER=directmail

DIRECT_MAIL_KEY=     # AccessKeyId
DIRECT_MAIL_SECRET=  # AccessSecret
```

### 2.2 发送注册验证邮件

初始化 Laravel 认证模块

```bash
php artisan make:auth
```

修改 `RegisterController` 控制器

```php
<?php 
// app\Http\Controllers\Auth\RegisterController.php

    use App\Models\User;
    use Illuminate\Support\Facades\Mail;

    protected function create(array $data)
    {
        $user =  User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'avatar' => 'default.jpg',
            'confirmation_token' => str_random(40),
            'password' => bcrypt($data['password']),
        ]);

        $this->sendVerifyEmailTo($user);
        return $user;
    }

    private function sendVerifyEmailTo($user)
    {
        $data = [
            'name' => $user->name,
            'url'  => Route('email.verify',['token' => $user->confirmation_token])
        ];

        Mail::send('emails.register', $data, function ($message) use ($user) {
            $message->from('service@dm.mail.wangyan.org', env('APP_NAME','Laravel'));
            $message->to($user->email);
            $message->subject('请验证您的 Email 地址');
        });
    }
```

修改 `User` 模型

```php
<?php 
// app\Models\User.php
    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'confirmation_token',
    ];
```

增加邮件模板

```bash
vim resources\views\emails\register.blade.php
```

增加路由

```php
<?php
// routes\web.php
Route::get('/email/verify/{token}', 'EmailController@verify')->name('email.verify');
```

增加控制器

```bash
php artisan make:controller EmailController
```

编辑控制器

```php
<?php
// app\Http\Controllers\EmailController.php
use App\Models\User;

class EmailController extends Controller
{
    /**
     * @param $token
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    function verify($token)
    {
        $user = User::where('confirmation_token',$token)->first();

        if(is_null($user)){
            return redirect('/');
        }

        $user->is_active = 1;

        $user->confirmation_token= str_random(40);
        $user->save();

        return redirect('/home');
    }
}
```

### 2.3 Sendcloud 邮件服务

修改 `composer.json` 然后 `composer update`

```json
"require": {
    "naux/sendcloud": "^1.1",
},
```

或者在项目目录下执行

```bash
composer require naux/sendcloud
```

修改 `config/app.php`，添加服务提供者

```php
<?php
'providers' => [
   // 添加这行
    Naux\Mail\SendCloudServiceProvider::class,
];
```

在 `.env` 中配置你的密钥， 并修改邮件驱动为 `sendcloud`

```bash
MAIL_DRIVER=sendcloud

SEND_CLOUD_USER=   # 创建的 api_user
SEND_CLOUD_KEY=    # 分配的 api_key
```

## 第三章 用户注册与登录消息提示

### 3.1 引入 `laracasts/flash` 包

这个包的作用是将消息提示放到 session 中

> [项目：《Easy Flash Messages for Laravel App》](https://github.com/laracasts/flash)

用法：

- `flash('Message')->success()`: success 样式    
- `flash('Message')->error()`: error 样式    
- `flash('Message')->warning()`: warning 样式    
- `flash('Message')->overlay()`: 弹窗  
- `flash()->overlay('Modal Message', 'Modal Title')`: 有标题的弹窗        
- `flash('Message')->important()`: 有关闭按钮   
- `flash('Message')->error()->important()`: 可以关闭的错误提示    

修改 `composer.json` 然后 `composer update`

```json
"require": {
    "laracasts/flash": "^3.0"
},
```

或者在项目目录下执行

```bash
composer require laracasts/flash
```

新增服务提供者

```php
<?php
// config\app.php
'providers' => [
    Laracasts\Flash\FlashServiceProvider::class,
];
```

修改 `EmailController` 控制器

```php
<?php
// app\Http\Controllers\EmailController.php
    function verify($token)
    {
        $user = User::where('confirmation_token',$token)->first();

        if(is_null($user)){
            // Render the message as an overlay.
            flash()->overlay('邮箱验证失败！', '温馨提示');
            return redirect('/');
        }

        $user->is_active = 1;

        $user->confirmation_token= str_random(40);
        $user->save();

        flash('邮箱验证成功！')->success()->important();
        return redirect('/home');
    }
```

修改 `HomeController` 控制器

```php
<?php
// app\Http\Controllers\HomeController.php
    public function index()
    {
        flash('登入成功！')->success()->important();
        return view('home');
    }
```

修改视图模板

```php
<?php
// 使用到 overlay() 弹窗时引入
// resources\views\layouts\app.blade.php
    <script>
        $('#flash-overlay-modal').modal();
    </script>
```

```php
<?php
// 在显示消息的位置引入
// resources\views\home.blade.php
<div class="panel-body">
    @include('flash::message')
</div>
```

```php
<?php
// resources\views\welcome.blade.php
// 将下面代码放在适当位置
<link href="https://cdn.bootcss.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">

@include('flash::message')

<script src="https://cdn.bootcss.com/jquery/3.2.1/jquery.min.js"></script>
<script src="https://cdn.bootcss.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
<script>
    $('#flash-overlay-modal').modal();
</script>
```

### 3.2 设置帐号未激活不能登录

```php
<?php
// app\Http\Controllers\Auth\LoginController.php
use Illuminate\Http\Request;

protected function attemptLogin(Request $request)
{
    $credentials = array_merge($this->credentials($request),['is_active' => '1']);
    return $this->guard()->attempt(
        $credentials,$request->has('remember')
    );
}
```

## 第四章 视图文件汉化、增加中文语言包

### 4.1 简体中文语言包

-  汉化默认视图文件    
-  将Google字体换成中科大源    
-  增加 cmn-Hans 简体中文语言包    

### 4.2 更新前端资源

> [项目：《Laravel 资源任务编译器 Laravel Mix》](https://d.laravel-china.org/docs/5.5/mix)

```css
/* resources/assets/sass/app.scss */
@import url(https://fonts.lug.ustc.edu.cn/css?family=Raleway:300,400,600);
```

编译

```bash
yarn run production
```

其他详细过程，略

![laravel-zhihu-01](https://img.cdn.wangyan.org/l/laravel-zhihu-01.png)

## 第五章 实现找回密码 

详细原理（略），可从 `app\Http\Controllers\Auth\ForgotPasswordController.php` 开始理解

```php
<?php
// app\Models\User.php
use Illuminate\Support\Facades\Mail;

public function sendPasswordResetNotification($token)
{
    $data = [
        'title' => env('APP_NAME','Laravel'),
        'name'  => $this->name,
        'url'   => url('password/reset',$token)
    ];
    Mail::send('emails.reset', $data, function ($message) {
        $message->from('service@sc.mail.wangyan.org', env('APP_NAME','Laravel'));
        $message->to($this->email);
        $message->subject('重设密码');
    });
}
```

增加邮件模板

```bash
vim resources\views\emails\reset.blade.php
```

## 第六章 设计问题表 

> [文档：Laravel 的数据库迁移 Migrations](http://d.laravel-china.org/docs/5.5/migrations)

创建 `Model` 的同时，生成 `migration` 迁移文件

```bash
# 注意双反斜杆
php artisan make:model Models\\Question -m
```

修改 migrate，设计表结构

```php
<?php
// database/migrations/create_questions_table.php
    public function up()
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('body');
            $table->integer('user_id')->unsigned();
            $table->integer('comments_count')->default(0);
            $table->integer('followers_count')->default(0);
            $table->integer('answers_count')->default(0);
            $table->string('close_comment',8)->default('F');
            $table->string('is_hidden',8)->default('F');
            $table->timestamps();
        });
    }
```

运行迁移

```bash
php artisan migrate
```

## 第七章 发布问题 

### 7.1 安装 UEditor 编辑器

修改 `composer.json` 然后 `composer update`

```json
"require": {
    "overtrue/laravel-ueditor": "~1.0"
},
```

或者在项目目录下执行

```bash
composer require "overtrue/laravel-ueditor:~1.0"
```

添加下面一行到 `config/app.php` 中 `providers` 部分

```bash
Overtrue\LaravelUEditor\UEditorServiceProvider::class,
```

发布配置文件与资源

```bash
php artisan vendor:publish --provider='Overtrue\LaravelUEditor\UEditorServiceProvider'
```

附件存储（需要以管理员身份允许终端）

> [文档：Laravel 的文件系统和云存储功能集成](http://d.laravel-china.org/docs/5.5/filesystem)

```bash
php artisan storage:link
```

### 7.2 发布问题

创建 `QuestionsController` 控制器

> [项目：《Laravel 的 HTTP 控制器》](http://d.laravel-china.org/docs/5.5/controllers)

```bash
php artisan make:controller QuestionsController --resource
```

定义 `questions` 资源路由

```php
<?php
// routes/web.php
Route::resource('questions','QuestionsController');
```

修改 `QuestionsController` 控制器

```php
<?php
// app\Http\Controllers\QuestionsController.php
    use App\Models\Question;
    use Illuminate\Support\Facades\Auth;

    // 首页显示所有问题
    public function index()
    {
        $questions = Question::all();
        return $questions;
    }
 
    // 返回创建问题视图
    public function create()
    {
        return view('questions.create');
    }

    // 保存问题，然后返回该问题视图
    public function store(Request $request)
    {
        $data = [
            'title' => $request->get('title'),
            'body' => $request->get('body'),
            'user_id' => Auth::id() // 获取登陆用户 ID
        ];
        $question = Question::create($data); // 保存问题
        return redirect()->route('questions.show',$question->id); // 重定向
    }

    // 返回问题视图
    public function show($id)
    {
        $question = Question::findOrfail($id);
        return view('questions.show',compact('question'));
    }
```

修改 `Question` 模型中的 `fillable`

```php
<?php
// app\Models\Question.php
class Question extends Model
{
    protected $fillable = ['title','body','user_id'];
}
```

新建「创建问题」视图文件

```php
<?php
//resources\views\questions\create.blade.php
@extends('layouts.app')

@section('content')
    @include('vendor.ueditor.assets')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">发布问题</div>
                    <div class="panel-body">
                        @include('flash::message')
                        <form action="/questions" method="post">
                            {!! csrf_field() !!}
                            <div class="form-group">
                                <label for="title">标题</label>
                                <input type="text" name="title" class="form-control" placeholder="标题" class="title">
                            </div>
                            <script id="container" name="body" type="text/plain"></script><br>
                            <button type="submit" class="btn btn-block btn-success pull-right">发布问题</button>
                        </form>
                    </div>
                    <script type="text/javascript">
                        var ue = UE.getEditor('container');
                        ue.ready(function() {
                            ue.execCommand('serverparam', '_token', '{{ csrf_token() }}');
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
@endsection
```

提交后问题后显示的页面

```php
<?php
//resources\views\questions\show.blade.php
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">{{$question->title}}</div>
                    <div class="panel-body">
                        {!! $question->body !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
```

## 第八章 问题表单字段验证

> [文档：《Laravel 的表单验证机制详解》](http://d.laravel-china.org/docs/5.5/validation)

### 8.1 方法一：快速验证

将验证逻辑直接写到控制器中，使用 `Illuminate\Http\Request` 对象提供的 `validate` 方法进行验证。

```php
<?php
// app\Http\Controllers\QuestionsController.php
    public function store(Request $request)
    {
        $rules = [
            'title' => 'required|min:6|max:128',
            'body' => 'required|min:12',
        ];
        $messages = [
            'title.required' => '标题不能为空',
            'title.min' => '标题长度至少要6个字符',
            'title.max' => '标题最长不能超过128个字符',
            'body.required' => '内容不能为空',
            'body.min' => '内容至少要有12个字符',
        ];
        $this->validate($request,$rules,$messages);
        $data = [
            'title' => $request->get('title'),
            'body' => $request->get('body'),
            'user_id' => Auth::id()
        ];
        $question = Question::create($data);
        return redirect()->route('questions.show',$question->id);
    }
```

编辑视图文件

```php
<?php
// resources\views\questions\create.blade.php
@extends('layouts.app')

@section('content')
    @include('vendor.ueditor.assets')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">发布问题</div>
                    <div class="panel-body">
                        @include('flash::message')
                        <form action="/questions" method="post">
                            {!! csrf_field() !!}
                            <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">
                                <label for="title">标题</label>
                                <input type="text" name="title" class="form-control" placeholder="标题" value="{{ old('title') }}" required autofocus>
                                @if ($errors->has('title'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('title') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="form-group{{ $errors->has('body') ? ' has-error' : '' }}">
                                <script id="container" name="body" type="text/plain">
                                    {!! old('title') !!}
                                </script><br>
                                @if ($errors->has('body'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('body') }}</strong>
                                    </span>
                                @endif
                            </div>
                            <button type="submit" class="btn btn-block btn-success pull-right">发布问题</button>
                        </form>
                    </div>
                    <script type="text/javascript">
                        var ue = UE.getEditor('container');
                        ue.ready(function() {
                            ue.execCommand('serverparam', '_token', '{{ csrf_token() }}');
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
@endsection
```

### 8.2 方法二：表单请求验证

使用 Artisan 命令 `make:request` 创建表单请求类来处理更复杂的验证。

```bash
php artisan make:request StoreQuestionRequest
```

添加验证规则到 `rules` 方法中

> 注意：表单请求类内也包含了 authorize 方法，用来判断用户是否有权限做出此请求。

```php
<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreQuestionRequest extends FormRequest
{
    // 如果你打算在其他地方处理授权逻辑，这里返回 true
    public function authorize()
    {
        return true;
    }

    // 规则
    public function rules()
    {
        return [
            'title' => 'required|min:6|max:128',
            'body' => 'required|min:12',
        ];
    }

    // 自定义错误消息
    public function messages()
    {
        return [
            'title.required' => '标题不能为空',
            'title.min' => '标题长度至少要6个字符',
            'title.max' => '标题最长不能超过128个字符',
            'body.required' => '内容不能为空',
            'body.min' => '内容至少要有12个字符',
        ];
    }
}
```

修改控制器使用 `StoreQuestionRequest` 类替换默认的 `Request` 类

```php
<?php
// app\Http\Controllers\QuestionsController.php
    use App\Http\Requests\StoreQuestionRequest;
    public function store(StoreQuestionRequest $request)
    {
        //
    }
```

## 第九章 美化编辑器

### 9.1 登录后才能发布问题

登录后才能发布问题，`index` 和 `show` 页面例外。

```php
<?php
// app\Http\Controllers\QuestionsController.php
    public function __construct()
    {
        $this->middleware('auth')->except(['index','show']);
    }
```

### 9.2 自定义 toolbars

```html
<!-- 定义容器的高度为200 -->
<script id="container" style="height: 200px" name="body" type="text/plain">
    {!! old('title') !!}
</script>

<script type="text/javascript">
    var ue = UE.getEditor('container', {
        toolbars: [
            ['bold', 'italic', 'underline', 'strikethrough', 'blockquote', 'insertunorderedlist', 'insertorderedlist', 'justifyleft','justifycenter', 'justifyright',  'link', 'insertimage', 'fullscreen']
        ],
        elementPathEnabled: false,
        enableContextMenu: false,
        autoClearEmptyNode:true,
        wordCount:false,
        imagePopup:false,
        autotypeset:{ indent: true,imageBlockLine: 'center' }
    });
    ue.ready(function() {
        ue.execCommand('serverparam', '_token', '{{ csrf_token() }}');
    });
</script>
```

## 第十章 定义话题与问题关系

### 10.1 Topic 模型

创建「话题」模型与数据表（请留意单、复数）

>  `question_topic` 是 `questions` 和 `topics` 的多对多中间表
> 数据库表名是复数，中间表是单数

```bash
# 创建模型同时创建 topics 表（复数）
php artisan make:model Models\\Topic -m

# 注意是 create_questions_topics_table 
php artisan make:migration create_questions_topics_table --create=question_topic
```

定义「话题」数据表结构

```php
<php
// database/migrations/create_topics_table.php
    public function up()
    {
        Schema::create('topics', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('bio')->nullable();
            $table->integer('questions_count')->default(0);
            $table->integer('followers_count')->default(0);
            $table->timestamps();
        });
    }
```

定义「问题」——「话题」数据表结构

```bash
// database\migrations\create_questions_topics_table.php
    public function up()
    {
        Schema::create('question_topic', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('question_id')->unsigned()->index();
            $table->integer('topic_id')->unsigned()->index();
            $table->timestamps();
        });
    }
```

```bash
php artisan migrate
```

定义「问题」——「话题」多对多关系

> [文档：《Eloquent：关联：多对多》](https://d.laravel-china.org/docs/5.5/eloquent-relationships#many-to-many)


### 10.2 Question 模型

问题模型：一个问题属于多个话题

```php
<?php
// app/Models/Question.php
class Question extends Model
{
    protected $fillable = ['title','body','user_id'];

    // 获得此问题所属话题（复数）
    public function topics()
    {
        //  一个问题属于多个话题
        // belongsToMany('关联模型「Topic」','中间表表名','当前模型「Question」在中间表的外键','关联模型「Topic」在中间表的外键')
        // 中间表表名，Eloquent 会按照字母顺序合并两个关联模型的名称
        // question_id 默认采用的是下划线方式命名 
        return $this->belongsToMany(Topic::class)->withTimestamps();
        return $this->belongsToMany('App\Models\Topic', 'question_topic','question_id','topic_id')->withTimestamps();
    }
}
```

话题模型：一个话题下有多个问题


```php
<?php
// app\Models\Topic.php
class Topic extends Model
{
    protected $fillable = ['name','questions_count','followers_count'];

    public function questions()
    {
        // 参数也可以使用 App\Models\Qeustion
        return $this->belongsToMany(Qeustion::class)->withTimestamps();
    }
}
```

## 第十一章 使用 Select2 优化话题选择

### 11.1 编译压缩 Select2 资源

> [文档：《Laravel 的资源任务编译器 Laravel Mix》](https://d.laravel-china.org/docs/5.5/eloquent-relationships#many-to-many)

引人 `Select2` 的 JS、CSS 文件

<https://github.com/select2/select2>

> resources\assets\css\select2.min.css    
> resources\assets\js\select2.min.js    
> resources\assets\js\select2.zh-CN.js  

引入 JS

```js
// resources\assets\js\app.js
require('./select2.min');
require('./select2.zh-CN');
```

引入 css

```css
/* resources\assets\sass\app.scss */
@import "../css/select2.min";
```

禁用缓存

```js
// webpack.mix.js
mix.js('resources/assets/js/app.js', 'public/js')
   .sass('resources/assets/sass/app.scss', 'public/css')
   .version(['public/js/app.js','public/css/app.css']);
```

编译资源

```bash
yarn run production
```

修改引入文件地址

```php
<?php
// resources\views\layouts\app.blade.php
<link href="{{ mix('css/app.css') }}" rel="stylesheet">

<script src="{{ mix('js/app.js') }}"></script>
@yield('js');
```

### 11.2 多选框（静态）

<http://select2.github.io/examples.html>

```php
<?php
// resources\views\questions\create.blade.php

<div class="form-group">
    <label for="topic">话题</label>
    <select class="js-example-basic-multiple form-control" multiple="multiple">
        <option value="AL">Alabama</option>
        <option value="WY">Wyoming</option>
    </select>
</div>

@section('js')
    <script type="text/javascript">
        $(".js-example-basic-multiple").select2();
    </script>
@endsection
```

### 11.3 多选框（动态）

生成测试数据

[文档：《Laravel 数据库之：数据填充》](https://d.laravel-china.org/docs/5.5/seeding)

```bash
php artisan make:factory TopicFactory
```

```php
<?php
// database\factories\TopicFactory.php
use Faker\Generator as Faker;

$factory->define(App\Models\Topic::class, function (Faker $faker) {
    return [
        'name' => $faker->word,
        'bio' => $faker->paragraph,
    ];
});
```

修改 `Topic` 模型中的 `fillable`

```php
<?php
// app\Models\Topic.php
protected $fillable = ['name','questions_count','followers_count','bio'];
```

使用 `tinker` 填充数据

```bash
php artisan tinker
factory(App\Models\Topic::class,10)->create();
quit
```

定义 `Topic` 的 api 路由

```php
<?php
// routes\api.php
Route::get('/topics', function (Request $request) {
    $topics = App\Models\Topic::select(['id','name'])->where('name','like','%'.$request->query('q').'%')->get();
    return $topics;
});
```

修改「创建问题」视图文件

```php
<?php
// resources\views\questions\create.blade.php
<div class="form-group">
    <label for="topic">话题</label>
    <select name="topic[]" class="select2-placeholder-multiple form-control" multiple="multiple"></select>
</div>

@section('js')
    <script type="text/javascript">
        $(document).ready(function() {
            function formatTopic (topic) {
                return  "<div class='select2-result-repository clearfix'>" +
                        "<div class='select2-result-repository__meta'>" +
                        "<div class='select2-result-repository__title'>" +
                        topic.name ? topic.name : "Laravel"   +
                        "</div></div></div>";
            }

            function formatTopicSelection (topic) {
                return topic.name || topic.text;
            }

            $(".select2-placeholder-multiple").select2({
                language: "zh-CN",
                tags: true,
                placeholder: '选择相关话题',
                minimumInputLength: 1,
                ajax: {
                    url: '/api/topics',
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            q: params.term,
                        };
                    },
                    processResults: function (data) {
                        return {
                            results: data,
                        };
                    },
                    cache: true
                },
                templateResult: formatTopic,
                templateSelection: formatTopicSelection,
                escapeMarkup: function (markup) { return markup; }
            });
        });
    </script>
@endsection
```

## 第十二章 创建和显示话题

### 12.1 新建话题

> [文档：《Laravel 的集合 Collection》](https://d.laravel-china.org/docs/5.5/collections) 、[文档：《Eloquent：多对多关联》](https://d.laravel-china.org/docs/5.5/eloquent-relationships#updating-many-to-many-relationships)

```php
<?php
// app\Http\Controllers\QuestionsController.php
    use App\Models\Topic;
    public function store(StoreQuestionRequest $request)
    {
        // 返回「话题」数组
        // 如果不是数组，则「话题」($topicsArray) 为空
        $topics = $request->get('topic');
        if(is_array($topics)){
            $topicsArray = $this->normalizeTopic($topics);
        }
        $data = [
            'title' => $request->get('title'),
            'body' => $request->get('body'),
            'user_id' => Auth::id()
        ];
        $question = Question::create($data);
        // 使用 attach 方法向「问题」——「话题」中间表插入一条数据
        // 前提是 Question 模型下有 topics() 方法
        $question->topics()->attach($topicsArray);
        return view('questions.show',compact('question'));
    } 
    // collect 函数：从数组中创建一个全新的「集合」实例
    // map 方法遍历集合并将每一个值传入给定的回调
    // map 返回一个新的集合实例,它不会修改它所调用的集合
    private function normalizeTopic(array $topics)
    {
        return collect($topics)->map(function($topic){
            // 如果话题存在，则$topic返回话题的ID（数字类型），否则返回新话题名称
            if(is_numeric($topic)){
                // 该话题的问题数+1
                Topic::find($topic)->increment('questions_count');
                return (int) $topic;
            }
            // 新建话题后返回该话题ID
            $newTopic = Topic::create(['name'=>$topic,'questions_count'=>1]);
            return $newTopic->id;
        })->toArray();
    }
```

### 12.2 显示问题的所有话题

`QuestionsController` 控制器

```php
<?php
// app\Http\Controllers\QuestionsController.php
    public function show($id)
    {
        // 使用 with 方法进行预加载，减少查询次数
        // Question 模型下要有 topics() 方法
        $question = Question::where('id',$id)->with('topics')->first();
        return view('questions.show',compact('question'));
    }
```

「问题」视图

```php
<?php
// resources\views\questions\show.blade.php
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        {{$question->title}}
                        @foreach($question->topics as $topic)
                            <a class="topic btn btn-link pull-right" href="/topic/{{$topic->id}}">{{$topic->name}}</a>
                        @endforeach
                    </div>
                    <div class="panel-body">
                        {!! $question->body !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
```

css 美化

> resources\assets\css\style.css    

```css
/* resources\assets\sass\app.scss */
@import "../css/style";
```

```bash
yarn run production
```

## 第十三章 使用 Repository 模式

`Repository` 模式的目的是将一些高频操作放在一起

在  `App\Repositories` 文件夹创建 `QuestionsRepository`

```php
<?php````
// app\Repositories\QuestionsRepository.php
namespace App\Repositories;
use App\Models\Question;
class QuestionsRepository
{
    //通过「问题ID」查找带有「话题」的「问题」
    public function byIdWithTopics($id)
    {
        $questions = Question::where('id',$id)->with('topics')->first();
        return $questions;
    }

    // 根据 $attributes 数组来创建「问题」 
    public function create(array $attributes)
    {
        return Question::create($attributes);
    }
}
```

编辑 `QuestionsController` 控制器

```php
<?php
// app\Http\Controllers\QuestionsController.php
use App\Repositories\QuestionsRepository;
class QuestionsController extends Controller
{
    protected $questionRepository;
    
    public function __construct(QuestionsRepository $questionRepository)
    {
        $this->questionRepository = $questionRepository;
        $this->middleware('auth')->except(['index','show']);
    }
    
    public function store(StoreQuestionRequest $request)
    {
        // 修改 $question = Question::create($data);
        $question = $this->questionRepository->create($data);
    }
    
    public function show($id)
    {
        // 修改 $question = Question::where('id',$id)->with('topics')->first();
        $question = $this->questionRepository->byIdWithTopics($id);
    }
}
```

## 第十四章 实现编辑问题

### 14.1 编辑问题

路由情况

![route-question](https://img.cdn.wangyan.org/r/route-question.jpg)

修改 `QuestionsController` 控制器

```php
<?php
// app\Http\Controllers\QuestionsController.php
    public function edit($id)
    {
        $question = $this->questionRepository->byID($id);
        // 只有问题创建者才能编辑
        if (Auth::user()->owns($question)){
            return view('questions.edit',compact('question'));
        }
        return back();
    }
```

在 `QuestionsRepository` 中，通过ID获取问题

```php
<?php
// app\Repositories\QuestionsRepository.php
class QuestionsRepository
{
    public function byID($id)
    {
        $question = Question::findOrfail($id);
        return $question;
    }
}
```

只有问题创建者才能编辑自己的问题

```php
<?php
// app\Models\User.php
    use Illuminate\Database\Eloquent\Model;
    public function owns(Model $model)
    {
        // 判断当前用户Id是否等于问题创建者的ID
        // Auth::user()->id == $question->user_id
        return $this->id == $model->user_id;
    }
```

「编辑问题」视图文件

复制 `create.blade.php` 文件为`edit.blade.php`，并修改以下内容:

```php
<?php
// resources/views/questions/edit.blade.php
// PATCH 方法对应的是 update 路由
<form action="/questions/{{ $question->id }}" method="post">
    {{ method_field('PATCH') }}
</form>
```

### 14.2 更新问题

`QuestionsController` 控制器

> [文档：《多对多关联》](https://d.laravel-china.org/docs/5.5/eloquent-relationships#updating-many-to-many-relationships)

`StoreQuestionRequest` 是验证规则，`sync` 用于同步关联

```php
<?php
// app\Http\Controllers\QuestionsController.php
    public function update(StoreQuestionRequest $request, $id)
    {
        
        $question = $this->questionRepository->byID($id);
        $question->update([
            'title' => $request->get('title'),
            'body' => $request->get('body'),
        ]);
        // sync 方法可以接收 ID 数组，向中间表插入对应关联数据记录。
        // 所有没放在数组里的 IDs 都会从中间表里移除。
        $topics = $request->get('topic');
        if(is_array($topics)){
            $topicsArray = $this->normalizeTopic($topics);
        }
        $question->topics()->sync($topicsArray);
        return redirect()->route('questions.show',$question->id);
    }
```

「问题」视图文件

```php
<?php
// resources/views/questions/show.blade.php
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        {{$question->title}}
                        @foreach($question->topics as $topic)
                            <a class="topic btn btn-link pull-right" href="/topic/{{$topic->id}}">{{$topic->name}}</a>
                        @endforeach
                    </div>
                    <div class="panel-body">
                        {!! $question->body !!}
                    </div>
                    <div class="actions panel-footer">
                        @if(Auth::check()  && Auth::user()->owns($question))
                            <span class="edit">
                                <a href="/questions/{{$question->id}}/edit">编辑</a>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
```

## 第十五章 问题列表和删除问题

### 15.1 问题列表

编辑问题控制器

<http://php.net/manual/zh/function.compact.php>

```php
<?php
// app\Http\Controllers\QuestionsController.php
    public function index()
    {
        $questions = $this->questionRepository->getQuestionsFeed();
        return view('questions.index',compact('questions'));
    }
```

编辑 `QuestionsRepository`

> [文档：《本地作用域》](https://d.laravel-china.org/docs/5.5/eloquent#local-scopes)

本地作用域允许我们定义通用的约束集合以便在应用中复用，只需简单在对应 Eloquent 模型方法前加上一个 scope 前缀。

```php
<?php
// app\Repositories\QuestionsRepository.php
    public function getQuestionsFeed()
    {
        // 获取 is_hidden 为 false 的问题，并按更新时间排序，同时获取该问题对应的用户信息。
        return Question::published()->latest('updated_at')->with('user')->get();
    }
```

`Eloquent` 模型中任何以 `scope` 开始的方法都被当做 `Eloquent scope`

```php
<?php
// app\Models\Question.php
    public function scopePublished($query)
    {
        //  约束条件是 is_hidden 为 false
        return $query->where('is_hidden','F');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
```

问题首页视图文件

```php
<?php
// resources\views\questions\index.blade.php
@extends('layouts.app')
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                @foreach($questions as $question)
                    <div class="media">
                        <div class="media-left">
                            <a href="">
                                <!-- 头像地址：public/images/avatar.png -->
                                <img width="48" src="{{ url('images',$question->user->avatar) }}" alt="{{$question->user->name}}" >
                            </a>
                        </div>
                        <div class="media-body">
                            <h4 class="media-heading">
                                <a href="/questions/{{$question->id}}">{{$question->title}}</a>
                            </h4>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection
```

### 15.2 删除问题

视图文件

```php
<?php
// resources\views\questions\show.blade.php
<div class="actions panel-footer">
    @if(Auth::check()  && Auth::user()->owns($question))
        <span class="edit">
            <a href="/questions/{{$question->id}}/edit">编辑</a>
        </span>
        <form action="/questions/{{$question->id}}" method="post" class="delete-form">
            {{method_field('DELETE')}}
            {{csrf_field()}}
            <button class="button delete-button is-naked">删除</button>
        </form>
    @endif
</div>
```

「删除问题」控制器

```php
<?php
// app\Http\Controllers\QuestionsController.php
    public function destroy($id)
    {
        $question = $this->questionRepository->byID($id);
        if (Auth::user()->owns($question)){
            $question->delete();
            return redirect('/questions/');
        }
        abort(403,'Forbidden');
    }
```

## 第十六章 创建问题的答案 Answer 模型

```bash
# 模型名称首字母大写且是单数，自动创建的数据库表是复数
php artisan make:model models\\Answer -m
```

```php
<?php
    public function up()
    {
        Schema::create('answers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->index()->unsigned();
            $table->integer('question_id')->index()->unsigned();
            $table->text('body');
            $table->integer('votes_count')->default(0);
            $table->integer('comments_count')->default(0);
            $table->string('is_hidden',8)->default('F');
            $table->string('close_comment',8)->default('F');
            $table->timestamps();
        });
    }
```

```bash
php artisan migrate
```

Answer 模型

```php
<?php
// app\Models\Answer.php
class Answer extends Model
{
    protected $fillable = ['user_id', 'question_id', 'body'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function question()
    {
        return $this->belongsTo(Question::class);
    }
}
```

Question 模型

```php
<?php
// app\Models\Question.php
    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
```

User 模型

```php
<?php
// app\Models\User.php
    public function answers()
    {
        return $this->hasMany(Answer::class);
    }
```

## 第十七章 实现提交回答

### 17.1 创建 Answer 控制器 

```bash
# 单数
php artisan make:controller AnswerController
```

定义保存 Answer 的路由

```php
<?php
// routes\web.php
Route::post('questions/{question}/answer','AnswerController@store');
```

Answer 控制器

```php
<?php
// app\Http\Controllers\AnswerController.php
use App\Repositories\AnswerRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AnswerController extends Controller
{
    protected $answer;

    public function __construct(AnswerRepository $answer)
    {
        $this->answer = $answer;
    }

    public function store(Request $request, $question)
    {
        // ID 是当前登录用户ID
        $answer = $this->answer->create([
            'question_id' => $question,
            'user_id'     => Auth::id(),
            'body'        => $request->get('body')
        ]);
        // 问题回答数+1
        $answer->question()->increment('answers_count');
        return back();
    }
```

AnswerRepository 模式

```php
<?php
// app\Repositories\AnswerRepository.php
use App\Models\Answer;
class AnswerRepository
{
    public function create(array $attributes)
    {
        return Answer::create($attributes);
    }
}
```

### 17.2 验证 Answer 表单

新建规则文件

```bash
php artisan make:request StoreAnswerRequest
```

```php
<?php
// app\Http\Requests\StoreAnswerRequest.php
public function authorize()
{
    return true;
}
public function rules()
{
    return [
        'body' => 'required|min:12'
    ];
}
```

将规则依赖注入

```php
<?php
// app\Http\Controllers\AnswerController.php
use App\Http\Requests\StoreAnswerRequest;
public function store(StoreAnswerRequest $request, $question)
{
    //
}
```

### 17.3 视图中显示 Answer

修改 `byIdWithTopics` 为 `byIdWithTopicsAndAnswers`

```php
<?php
// app\Http\Controllers\QuestionsController.php
    public function show($id)
    {
        $question = $this->questionRepository->byIdWithTopicsAndAnswers($id);
        return view('questions.show',compact('question'));
    }
```

```php
<?php
// app\Repositories\QuestionsRepositories.php
    public function byIdWithTopicsAndAnswers($id)
    {
        $question = Question::where('id',$id)->with('topics','answers')->first();
        return $question;
    }
```

「问题」视图

```php
<?php
// resources\views\questions\show.blade.php
@extends('layouts.app')

@section('content')
    @include('vendor.ueditor.assets')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
            <!-- ...... -->
            </div>

            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        {!! $question->answers_count !!} 个答案
                    </div>
                    <div class="panel-body">
                        @foreach($question->answers as $answer)
                            <div class="media">
                                <div class="media-left">
                                    <a href="">
                                        <img width="48" src="{{ url('images',$answer->user->avatar) }}" alt="{{$answer->user->name}}" >
                                    </a>
                                </div>
                                <div class="media-body">
                                    <h4 class="media-heading">
                                        <a href="/user/{{$answer->user->name}}">
                                            {{$answer->user->name}}
                                        </a>
                                    </h4>
                                    {!! $answer->body !!}
                                </div>
                            </div>
                        @endforeach
                        <form action="/questions/{{$question->id}}/answer" method="post">
                            {!! csrf_field() !!}
                            <div class="form-group{{ $errors->has('body') ? ' has-error' : '' }}">
                                <label for="body">内容</label>
                                <script id="container" style="height: 120px" name="body" type="text/plain">
                                    {!! old('body') !!}
                                </script><br/>
                                @if ($errors->has('body'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('body') }}</strong>
                                </span>
                                @endif
                            </div>
                            <button type="submit" class="btn btn-block btn-success pull-right">提交答案</button>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@section('js')
<script type="text/javascript">
    var ue = UE.getEditor('container', {
        toolbars: [
            ['bold', 'italic', 'underline', 'strikethrough', 'blockquote', 'insertunorderedlist', 'insertorderedlist', 'justifyleft','justifycenter', 'justifyright',  'link', 'insertimage', 'fullscreen']
        ],
        elementPathEnabled: false,
        enableContextMenu: false,
        autoClearEmptyNode:true,
        wordCount:false,
        imagePopup:false,
        autotypeset:{ indent: true,imageBlockLine: 'center' }
    });
    ue.ready(function() {
        ue.execCommand('serverparam', '_token', '{{ csrf_token() }}');
    });
</script>
@endsection
```

## 第十八章 用户关注问题

### 18.1 提交回答需要登录

在前端限制

```php
<?php
// resources\views\questions\show.blade.php
@if(Auth::check())
    <form action="/questions/{{$question->id}}/answer" method="post">
    </form>
@else
    <a href="{{ url('login') }}" class="btn btn-warning btn-block">登录提交答案</a>
@endif
```

在后端限制

```php
<?php
// app/Http/Controllers/AnswerController.php
public function __construct(AnswerRepository $answer)
{
    $this->middleware('auth');
}
```

### 18.2 关注问题

实质是创建 `user_question` 表，记录了登录用户ID和所关注问题的ID，点击关注该问题便生成一条记录。

#### 18.2.1 数据库迁移

注意 `user_question` 表是复数

```php
<?php
php artisan make:migration create_user_question --create=user_question
```

注意 `user_id` `question_id` 外键是单数

```php
<?php
// database/migrations/create_user_question.php
Schema::create('user_question', function (Blueprint $table) {
    $table->increments('id');
    $table->integer('user_id')->unsigned()->index();
    $table->integer('question_id')->unsigned()->index();
    $table->timestamps();
});
```

```bash
php artisan migrate
```

#### 18.2.2 视图文件

```php
<?php
// resources\views\questions\show.blade.php
<div class="container">
        <div class="row">
            <!-- offset-2 改成 offset-1  -->
            <div class="col-md-8 col-md-offset-1">
            ......
            </div>
            <div class="col-md-3">
                <div class="panel panel-default">
                    <div class="panel-heading question-follow">
                        <h2>{{ $question->followers_count }}</h2>
                        <span>关注者</span>
                    </div>
                    <div class="panel-body">
                        <a href="/question/{{$question->id}}/follow" class="btn btn-default">
                            关注该问题
                        </a>
                        <a href="#editor" class="btn btn-primary pull-right">撰写答案</a>
                    </div>
                </div>
            </div>
            <!-- offset-2 改成 offset-1  -->
            <div class="col-md-8 col-md-offset-1">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        {!! $question->answers_count !!} 个答案
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
```

#### 18.2.3 路由

```php
<?php
// zhihu\routes\web.php
Route::get('question/{question}/follow','QuestionFollowController@follow');
```

#### 18.2.4 「关注问题」控制器

```bash
php artisan make:controller QuestionFollowController
```

```php
<?php
// app\Http\Controllers\QuestionFollowController.php
use App\Models\Question;
use Illuminate\Support\Facades\Auth;
public function __construct()
{
    // 需要登录，否则下面 Auth::user() 爆错
    $this->middleware('auth');
}

public function follow($question)
{
    // 别忘了 use Auth
    // $question 是从路由传入的问题ID
    Auth::user()->follows($question);
    Question::find($question)->increment('followers_count');
    return back();
}
```

#### 18.2.5 模型

 ```php
<?php
// app\Models\User.php
public function follows($question)
{
    return Follow::create([
        'question_id' => $question,
        'user_id' => $this->id
    ]);
}
````

```bash
php artisan make:model models\\Follow
```

```php
<?php
// app\Models\Follow.php
class Follow extends Model
{
    protected $table = 'user_question';
    protected $fillable = ['user_id', 'question_id'];
}
```

## 第十九章 用户关注问题（下）

### 19.1 使用 toggle 方法避免重复关注

```php
<?php
// app\Http\Controllers\QuestionFollowController.php
public function follow($question)
{
    Auth::user()->followThis($question);
    ......
}
```

> [文档：《多对多关联：同步关联》](https://laravel-china.org/docs/laravel/5.5/eloquent-relationships#updating-many-to-many-relationships
)

```php
<?php
// app\Models\User.php
    public function follows()
    {
        // 多对多，一个用户可以关注多个问题，一个问题也能被多个用户关注
        return $this->belongsToMany(Question::class,'user_question')->withTimestamps();
    }
    
    public function followThis($question)
    {
        // 如果给定 ID 已附加，就会被移除。
        // 同样的，如果给定 ID 已移除，就会被附加：
        return $this->follows()->toggle($question);

        // 关注成功则+1，否则反之
        if ($this->followed($question))
            Question::find($question)->increment('followers_count');
        else
            Question::find($question)->decrement('followers_count');
    }
````

### 19.2 获取关注状态

```php
<?php
// app\Models\User.php
public function followed($question)
{
    // 通过问题ID查询该用户是否关注该问题 
    return  $this->follows()->where('question_id',$question)->count();
}
````

前端

```php
<?php
// resources\views\questions\show.blade.php
<div class="panel-body">
    @if(Auth::check())
        <a href="/question/{{$question->id}}/follow"
            class="btn btn-default {{Auth::user()->followed($question->id) ? 'btn-success' : ''}}">
            {{Auth::user()->followed($question->id) ? '已关注' : '关注该问题'}}
        </a>
    @else
        <a href="/question/{{$question->id}}/follow" class="btn btn-warning">关注该问题</a>
    @endif
    <a href="#editor" class="btn btn-primary pull-right">撰写答案</a>
</div>
````

## 第二十章 使用 Vuejs 实现关注问题

在上一节使用 toggle 避免重复关注，并且使用超链接来实现关注和取消关注。而使用 Vuejs 可以避免跳转，用户体验更佳。

路由

```php
<?php
// routes/api.php

// 根据用户ID和问题ID，查询是否关注成功
Route::post('/question/follower',function (Request $request){
    $followed = \App\Models\Follow::where('question_id',$request->get('question'))
        ->where('user_id',$request->get('user'))
        ->count();
    if ($followed) {
        return response()->json(['followed' => true]);
    }
    return response()->json(['followed' => false]);

})->middleware('api');

// 关注成功后写入数据到 user_question 表
Route::post('/question/follow',function (Request $request){
    $question_id =  $request->get('question');
    $followed = \App\Models\Follow::where('question_id',$question_id)
        ->where('user_id',$request->get('user'))
        ->first();
    //若已经关注，则取消关注
    if ($followed !== null) {
        $followed->delete();
        \App\Models\Question::find($question_id)->decrement('followers_count');
        return response()->json(['followed' => false]);
    }
    //否则写入数据
    \App\Models\Follow::create([
        'question_id'=>$request->get('question'),
        'user_id'=>$request->get('user'),
    ]);
    \App\Models\Question::find($question_id)->increment('followers_count');
    return response()->json(['followed' => true]);

})->middleware('api');
```

安装 `props` 包

```bash
yarn add props
```

```js
// resources\assets\js\components\QuestionFollowButton.vue
<template>
    <button
            class="btn btn-default"
            v-bind:class="{'btn-success' : followed}"
            v-text="text"
            v-on:click="follow"
    ></button>
</template>

<script>
    export default {
        props:['question','user'],
        mounted() {
            // 查询是否已经关注
            axios.post('/api/question/follower',{'question':this.question,'user':this.user}).then(response => {
                //console.log(response.data);
                this.followed = response.data.followed
            })
        },
        data(){
            return {
                followed:false //默认值
            }
        },
        computed:{
            text(){
                return this.followed ? '已关注' : '关注该问题'
            }
        },
        methods:{
            // 关注
            follow() {
                axios.post('/api/question/follow',{'question':this.question,'user':this.user}).then(response => {
                    this.followed = response.data.followed
                })
            }
        }
    }
</script>
````

```js
// resources\assets\js\app.js
Vue.component('question-follow-button', require('./components/QuestionFollowButton.vue'));
```

视图

```html
 <div class="panel-body">
    @if(Auth::check())
        <question-follow-button question="{{$question->id}}" user="{{Auth::id()}}"></question-follow-button>
    @else
        <a href="/question/{{$question->id}}/follow" class="btn btn-warning">关注该问题</a>
    @endif
    <a href="#editor" class="btn btn-primary pull-right">撰写答案</a>
</div>
```

编译

```bash
yarn run prod
```

## 第二十一章 前后端分离 API token 认证

### 21.1 在 `users` 表新增 `api_token` 字段


```bash
php artisan make:migration add_api_token_to_users --table=users
```

```php
<?php
// database\migrations\2017_09_19_133744_add_api_token_to_users.php
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('api_token',64)->unique();
        });
    }
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['api_token']);
        });
    }
```

```bash
php artisan migrate
```

将生成的随机字符串手动粘贴到 `user` 表中

```bash
php artisan migrate
php artisan tinker
>>> str_random(60)
=> "79hiXQBXMSWwrZDqybMTxZGjbB0nkFv2DkrAqyGoQ1f9WscoHwTSaB0btktm"
=> "ry8k58UcyZoSZJtIyZtegp3M5pqJbkceEPyC2dxm8zH5NaUoWDR9ykvY1EMX"
```

注册时自动生成随机字符串

```php
<?php
// app\Http\Controllers\Auth\RegisterController.php
    protected function create(array $data)
    {
        $user = User::create([
            'api_token' => str_random(60),
        ]);
    }
```

```php
<?php
// app\Models\User.php
  protected $fillable = [
        'api_token'
    ];
```

### 21.2 获取前端生成的 `api_token` 字符串

```php
<?php
// resources\assets\js\bootstrap.js
// 注意 Authorization
let apiToken = document.head.querySelector('meta[name="api-token"]');

if (apiToken) {
    window.axios.defaults.headers.common['Authorization'] = apiToken.content;
} else {
    console.error('API token not found');
}
```

前端从数据库读取 api_token 内容

```php
<?php
// resources\views\layouts\app.blade.php
<meta name="api-token" content="{{  Auth::check() ? 'Bearer '.Auth::user()->api_token : 'Bearer ' }}">
```

### 21.3 前端安全优化

去掉 user 部分

```js
// resources\assets\js\components\QuestionFollowButton.vue
    export default {
        props:['question'],
        mounted() {
            axios.post('/api/question/follower',{'question':this.question}).then(response => {
                //
            })
        },
        methods:{
            follow() {
                axios.post('/api/question/follow',{'question':this.question}).then(response => {
                    //
                })
            }
        }
    }
```

路由部分，新增 `$user` 并且将 `$request->get('user')` 替换成 `$user->id`

```php
<?php
// routes\api.php
Route::post('/question/follower',function (Request $request){
    $user = Auth::guard('api')->user(); //新增内容
    $followed = \App\Models\Follow::where('question_id',$request->get('question'))
        ->where('user_id',$user->id) // 注意 $user->id
        ->count();
})->middleware('auth:api');

Route::post('/question/follow',function (Request $request){
    $user = Auth::guard('api')->user(); //新增内容
    $question_id =  $request->get('question');
    $followed = \App\Models\Follow::where('question_id',$question_id)
        ->where('user_id',$user->id) //注意 $user->id
        ->first();
    \App\Models\Follow::create([
        'question_id'=>$request->get('question'),
        'user_id'=>$user->id, //注意 $user->id
    ]);
})->middleware('auth:api');
```

视图

```php
<?php
// resources\views\questions\show.blade.php
// 移除了 user="{{Auth::id()}}
<question-follow-button question="{{$question->id}}"></question-follow-button>
```

编译

```bash
yarn run prod
```

### 21.4 优化路由

<https://d.laravel-china.org/docs/5.5/eloquent-relationships#updating-many-to-many-relationships>

```php
<?php
// routes\api.php
Route::post('/question/follower',function (Request $request){
    $user = Auth::guard('api')->user();
    // 原路由需要提供 user_id ，优化后将 Follow::where 封装成 $user->followed
    //  $followed = \App\Models\Follow::where('question_id',$request->get('question'))
    //   ->where('user_id',$user->id)
    //   ->count();
    $followed = $user->followed($request->get('question'));
    if ($followed) {
        return response()->json(['followed' => true]);
    }
    return response()->json(['followed' => false]);

})->middleware('auth:api');

Route::post('/question/follow',function (Request $request){
    $user = Auth::guard('api')->user();
    $question_id =  $request->get('question');
    // 原路由需要提供 user_id ，优化后将 Follow::where 封装成 $user->followThis
    // followThis 这里用到了 toggle 特性，省去了 Follow::create 步骤
    // $followed = \App\Models\Follow::where('question_id',$question_id)
    //    ->where('user_id',$user->id)
    //    ->first();
    $question = \App\Models\Question::find($question_id);
    $followed = $user->followThis($question_id);
    if (count($followed['detached']) > 0){
        $question->decrement('followers_count');
        return response()->json(['followed' => false]);
    }
    $question->increment('followers_count');
    return response()->json(['followed' => true]);

})->middleware('auth:api');
```

## 第二十二章 关注用户（上）

### 22.1 新增 followers 表

该表存放关注者 ID 和被关注者 ID

```bash
php artisan make:migration create_followers_table --create=followers
```

```php
<?php
// database/migrations/create_followers_table.php
public function up()
    {
        Schema::create('followers', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('follower_id')->unsigned()->index();
            $table->integer('followed_id')->unsigned()->index();
            $table->timestamps();
        });
    }
```

```bash
php artisan migrate
```

> 一个用户（follower）既可以关注多个用户（正在关注他人，即following），也能被多个用户（关注者，followers）所关注。

```php
<?php
// app\Models\User.php

// 用户（follower）关注了哪些人（followed）
public function following()
{
    // 用户（follower） 和已经关注的人（followed）均在 user 表，故self::class
    // 顺序：类 -- 中间表 -- 用户在中间表的外键 -- 已经关注的人在中间表的外键
    return $this->belongsToMany(self::class, 'followers', 'follower_id', 'followed_id')->withTimestamps();
}
```

### 22.2 视图

```php
<?php
// resources/views/questions/show.blade.php
<div class="container">
    <div class="row">
        ......
        <!-- start -->
        <div class="col-md-3">
            <div class="panel panel-default">
                <div class="panel-heading question-follow">
                    <h5>关于作者</h5>
                </div>
                <div class="panel-body">
                    <div class="media">
                        <div class="media-left">
                            <a href="#">
                                <img width="36" src="{{ url('images',$question->user->avatar) }}" alt="{{$question->user->name}}">
                            </a>
                        </div>
                        <div class="media-body">
                            <h4 class="media-heading"><a href="">
                                    {{ $question->user->name }}
                                </a>
                            </h4>
                        </div>
                        <div class="user-statics" >
                            <div class="statics-item text-center">
                                <div class="statics-text">问题</div>
                                <div class="statics-count">{{ $question->user->questions_count }}</div>
                            </div>
                            <div class="statics-item text-center">
                                <div class="statics-text">回答</div>
                                <div class="statics-count">{{ $question->user->answers_count }}</div>
                            </div>
                            <div class="statics-item text-center">
                                <div class="statics-text">关注者</div>
                                <div class="statics-count">{{ $question->user->followers_count }}</div>
                            </div>
                        </div>
                    </div>
                    <user-follow-button user="{{$question->user_id}}"></user-follow-button>
                    <send-message user="{{$question->user_id}}"></send-message>
                </div>
            </div>
        </div>
        <!-- end -->
    </div>
</div>
```

## 第二十三章 关注用户（下）

###  23.1 vuejs 组件

这个按钮在关注用户（上）已经添加了

```php
<?php
// resources/views/questions/show.blade.php
<user-follow-button user="{{$question->user_id}}"></user-follow-button>
```

所以，直接新增 UserFollowButton.vue

```js
// resources\assets\js\components\UserFollowButton.vue
// 页面载入时就向 /api/user/followers/ 发起get请求，查询是否关注了该问题的作者。
// 点击按钮时向 /api/user/follow 发起post请求，直接关注该问题的作者。
<template>
    <button
            class="btn btn-default"
            v-bind:class="{'btn-success': followed}"
            v-text="text"
            v-on:click="follow"
    ></button>
</template>

<script>
    export default {
        props:['user'],
        mounted() {
            axios.get('/api/user/followers/' + this.user).then(response => {
                this.followed = response.data.followed
            })
        },
        data() {
            return {
                followed: false
            }
        },
        computed: {
            text() {
                return this.followed ? '已关注' : '关注他'
            }
        },
        methods:{
            follow() {
                axios.post('/api/user/follow',{'user':this.user}).then(response => {
                    this.followed = response.data.followed
                })
            }
        }
    }
</script>
```

运行

```js
// resources\assets\js\app.js
Vue.component('user-follow-button', require('./components/UserFollowButton.vue'));
```

```bash
yarn run dev
```

### 23.2 路由

```php
<?php
// routes\api.php
//  这里的ID是该问题的作者ID 
Route::get('/user/followers/{id}','FollowersController@index');
Route::post('/user/follow','FollowersController@follow');
```

### 23.3 控制器

UserRepository

```php
<?php
namespace App\Repositories;
use App\Models\User;
class UserRepository
{
    public function byId($id)
    {
        return User::find($id);
    }
}
```

新增 FollowersController 控制器

```bash
# 控制器是复数
php artisan make:controller FollowersController
```

```php
<?php
//app\Http\Controllers\FollowersController.php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Repositories\UserRepository;
use Auth;
class FollowersController extends Controller
{
    protected $user;

    public function __construct(UserRepository $user)
    {
        $this->user = $user;
    }

    public function index($id)
    {
        $user = $this->user->byId($id);
        $followers = $user->followers()->pluck('follower_id')->toArray();
        if ( in_array(Auth::guard('api')->user()->id, $followers) ) {
            return response()->json(['followed' => true]);
        }
        return response()->json(['followed' => false]);
    }

    public function follow()
    {
        $user = $this->user->byId(request('user'));
        $followed = Auth::guard('api')->user()->follow($user->id);
        if ( count($followed['attached']) > 0 ) {
            $user->increment('followers_count');
            return response()->json(['followed' => true]);
        }
        $user->decrement('followers_count');
        return response()->json(['followed' => false]);
    }
}
```

### 23.4 用户模型

```php
<?php
// app\Models\User.php

public function following()
{
    return $this->belongsToMany(self::class, 'followers', 'follower_id', 'followed_id')->withTimestamps();
}

// 用户（follower）被哪些人（followed）所关注（关注者）
public function followers()
{
    return $this->belongsToMany(self::class, 'followers', 'followed_id', 'follower_id')->withTimestamps();
}

// 根据用户id发起关注
public function follow($user)
{
    return $this->following()->toggle($user);
}
```

## 第二十四章 站内信通知

<https://laravel-china.org/docs/laravel/5.5/notifications>

点击关注后发送通知

```php
<?php
// app\Http\Controllers\FollowersController.php
use App\Notifications\NewUserFollowNotification;
public function follow()
{
    if ( count($followed['attached']) > 0 ) {
        //......
        $user->notify(new NewUserFollowNotification());
    }
}
```

生成 notification 通知类

```bash
php artisan make:notification NewUserFollowNotification
php artisan notifications:table
php artisan migrate
```

```php
<?php
// app\Notifications\NewUserFollowNotification.php
use Auth;
public function via($notifiable)
{
    return ['database'];
}

public function toDatabase($notifiable)
{
    return [
        'name' => Auth::guard('api')->user()->name,
    ];
}
```

web 路由

```php
<?php
// routes\web.php
Route::get('notifications','NotificationsController@index');
```

控制器

```bash
php artisan make:controller NotificationsController
```

```php
<?php
// app\Http\Controllers\NotificationsController.php
use Auth;
public function index()
{
    $user = Auth::user();

    return view('notifications.index', compact('user'));
}
```

视图

```php
<?php
// resources\views\notifications\index.blade.php
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">消息通知</div>
                    <div class="panel-body">
                        @foreach($user->notifications as $notification)
                            @include('notifications.'.snake_case(class_basename($notification->type)))
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
```

```php
<?php
// resources\views\notifications\new_user_follow_notification.blade.php
<li class="notifications {{ $notification->unread() ? 'unread' : ' ' }}">
    <a href="{{ $notification->data['name'] }}">
        {{ $notification->data['name'] }}
    </a> 关注了你。
</li>
```

## 第二十五章 关注用户之邮件通知

### 25.1 Sendcloud 邮件服务

```php
<?php
// app\Notifications\NewUserFollowNotification.php
use App\Channels\SendcloudChannel;
use Mail;
public function via($notifiable)
{
    return ['database', SendcloudChannel::class];
}

public function toSendcloud($notifiable)
{
    $data = [
        'yourName' => $notifiable->name,
        'followerName' => Auth::guard('api')->user()->name,
        'url'  => 'http://zhihu.dev/user/'.Auth::guard('api')->user()->id,
    ];
    Mail::send('emails.follow', $data, function ($message) use ($data,$notifiable) {
        $message->from('service@sc.mail.wangyan.org', env('APP_NAME','Laravel'));
        $message->to($notifiable->email);
        $message->subject($data['followerName'].'关注了你');
    });
}
```

```php
<?php
// app\Channels\SendcloudChannel.php
namespace App\Channels;
use Illuminate\Notifications\Notification;
class SendcloudChannel
{
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toSendcloud($notifiable);
    }
}
```

视图略

    resources/views/emails/follow.blade.php


### 25.2 Directmail 邮件服务

```php
<?php
// .env
MAIL_DRIVER=sendcloud
SEND_CLOUD_USER=wang_yan
SEND_CLOUD_KEY=ZN1XDQ8q6jRfS3JU
```

```php
<?php
// app\Notifications\NewUserFollowNotification.php
use App\Channels\DirectmailChannel;
use Mail;
public function via($notifiable)
{
    return ['database', DirectmailChannel::class];
}

public function toDirectmail($notifiable)
{
    $data = [
        'yourName' => $notifiable->name,
        'followerName' => Auth::guard('api')->user()->name,
        'url'  => 'http://zhihu.dev/user/'.Auth::guard('api')->user()->id,
    ];
    Mail::send('emails.follow', $data, function ($message) use ($data,$notifiable) {
        $message->from('service@dm.mail.wangyan.org', env('APP_NAME','Laravel'));
        $message->to($notifiable->email);
        $message->subject($data['followerName'].'关注了你');
    });
}
```

```php
<?php
// app\Channels\DirectmailChannel.php
namespace App\Channels;
use Illuminate\Notifications\Notification;
class DirectmailChannel
{
    public function send($notifiable, Notification $notification)
    {
        $message = $notification->toDirectmail($notifiable);
    }
}
```

## 第二十六章 重构邮件通知代码

创建基类

```php
<?php
// app/Mailer/Mailer.php
namespace App\Mailer;
use Illuminate\Support\Facades\Mail;
use Naux\Mail\SendCloudTemplate;
class Mailer
{
    public function sendTo($template, $email, array $data)
    {
        $content = new SendCloudTemplate($template,$data);

        Mail::raw($content,  function ($message) use ($email) {
            $message->from('service@sc.mail.wangyan.org', env('APP_NAME','Laravel'));
            $message->to($email);
        });
    }
}
```

关注通知

```php
<?php
// app/Mailer/UserMailer.php
namespace App\Mailer;
use Auth;
class UserMailer extends Mailer
{
    public function followNotifyEmail($email,$name)
    {
        $data = [
            'yourName' => $name,
            'followerName' => Auth::guard('api')->user()->name,
            'url'  => 'http://zhihu.dev/user/'.Auth::guard('api')->user()->id,
        ];

        $this->sendTo('zhihu_new_user_follow',$email,$data);
    }
}
```

```php
<?php
// app/Notifications/NewUserFollowNotification.php
public function toSendcloud($notifiable)
{
    (new UserMailer())->followNotifyEmail($notifiable->email,$notifiable->name);
}
```

密码重置

```php
<?php
// app/Mailer/UserMailer.php
class UserMailer extends Mailer
{
    public function passwordReset($name,$email,$token)
    {
        $data = [
            'title' => env('APP_NAME','Laravel'),
            'name'  => $name,
            'url'   => url('password/reset',$token)
        ];

        $this->sendTo('zhihu_password_reset',$email,$data);
    }
}
```

```php
<?php
// app/Models/User.php
public function sendPasswordResetNotification($token)
{
    (new UserMailer())->passwordReset($this->name,$this->email,$token);
}
```

新用户注册邮件验证

```php
<?php
// app/Mailer/UserMailer.php
class UserMailer extends Mailer
{
    public function verifyEmail($name,$email,$confirmation_token)
    {
        $data = [
            'name' => $name,
            'url'  => Route('email.verify',['token' => $confirmation_token])
        ];

        $this->sendTo('zhihu_user_register',$email,$data);
    }
}
```

```php
<?php
// app/Http/Controllers/Auth/RegisterController.php
private function sendVerifyEmailTo($user)
{
    (new UserMailer())->verifyEmail($user->name,$user->email,$user->confirmation_token);
}
```

## 第二十七章 对答案进行点赞

模型

```bash
php artisan make:model Vote -m
```

```php
<?php
// database/migrations/create_votes_table.php
Schema::create('votes', function (Blueprint $table) {
    $table->increments('id');
    $table->unsignedInteger('user_id')->index();
    $table->unsignedInteger('answer_id')->index();
    $table->timestamps();
});
```

```bash
php artisan migrate
```

路由

```php
<?php
// 都是post
Route::post('/answer/{id}/votes/users','VotesController@users');
Route::post('/answer/vote','VotesController@vote');
```

控制器

```bash
php artisan make:controller VotesController
```

```php
<?php
// app/Http/Controllers/VotesController.php
namespace App\Http\Controllers;
use App\Repositories\AnswerRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VotesController extends Controller
{
    // AnswerRepository
    protected $answer;
    public function __construct(AnswerRepository $answer)
    {
        $this->answer = $answer;
    }
    
    //根据用户id查询是否已经点赞
    public function users($id)
    {
        $user = Auth::guard('api')->user();
        // hasVoteFor()
        if($user->hasVoteFor($id)){
            return response()->json(['voted' => true]);
        }
        return response()->json(['voted' => false]);
    }

    //点赞
    public function vote()
    {
        // AnswerRepository
        $answer = $this->answer->byId(request('answer'));

        // voteFor()
        $voted = Auth::guard('api')->user()->voteFor(request('answer'));
        if ( count($voted['attached']) > 0 ) {
            $answer->increment('votes_count');
            return response()->json(['voted' => true]);
        }
        $answer->decrement('votes_count');
        return response()->json(['voted' => false]);
    }
}
```

AnswerRepository

```php
<?php
public function byId($id)
{
    return Answer::find($id);
}
```

user 模型

```php
<?php
// app/Models/User.php
    // 一个用户可以点赞多个问题，一个问题可以被多个用户点赞，中间表是votes
    public function votes()
    {
        return $this->belongsToMany(Answer::class,'votes');
    }

    /**
     * @param $answer
     * @return array
     */
    public function voteFor($answer)
    {
        return $this->votes()->toggle($answer);
    }

    /**
     * 根据回答id查询是否有点赞记录
     *
     * @param $answer
     * @return bool
     */
    public function hasVoteFor($answer)
    {
        return !! $this->votes()->where('answer_id',$answer)->count();
    }
```

vuejs组件

```js
<template>
    <button
            class="btn btn-default"
            v-bind:class="{'btn-primary': voted}"
            v-text="text"
            v-on:click="vote"
    ></button>
</template>

<script>
    export default {
        props:['answer','count'],
        mounted() {
            axios.post('/api/answer/' + this.answer + '/votes/users').then(response => {
                this.voted = response.data.voted
            })
        },
        data() {
            return {
                voted: false
            }
        },
        computed: {
            text() {
                return this.count
            }
        },
        methods:{
            vote() {
                axios.post('/api/answer/vote',{'answer':this.answer}).then(response => {
                    this.voted = response.data.voted
                    response.data.voted ? this.count ++ : this.count --
                })
            }
        }
    }
</script>
```

视图

```php
<?php
<user-vote-button answer="{{$answer->id}}" count="{{$answer->votes_count}}"></user-vote-button>
```

运行

```bash
yarn run dev
```

## 第二十八章 私信功能（上）

创建数据表

```bash
php artisan make:model Modles\\Message -m
```

```php
<?php
public function up()
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('from_user_id');
            $table->unsignedInteger('to_user_id');
            $table->text('body');
            $table->string('has_read',8)->default('F');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }
```

```bash
php artisan migrate
```

> [文档：《Eloquent：关联》](https://laravel-china.org/docs/laravel/5.5/eloquent-relationships)

私信模型

```php
<?php
// app/Models/Message.php
class Message extends Model
{
    protected $table = 'messages';
    protected $fillable = ['from_user_id', 'to_user_id', 'body'];

    protected function fromUser()
    {
        // 反向一对一，私信由某个用户发送的
        return $this->belongsTo(User::class,'from_user_id');
    }

    public function toUser()
    {
        // 反向一对多，私信可以发送给多个用户
        return !! $this->belongsTo(User::class,'to_user_id');
    }
}
```

用户模型
 
```php
<?php
// app/Models/User.php
    public function messages()
    {
        //用户可以向多个用户发送私信
        return $this->hasMany(Message::class,'to_user_id');
    }
```

## 第二十九章 私信功能（下）

路由

```php
<?php
// routes/api.php
Route::post('/message/store','MessagesController@store');
```

私信控制器

```bash
php artisan make:controller MessagesController
```

```php
<?php
namespace App\Http\Controllers;

use App\Repositories\MessageRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessagesController extends Controller
{
    protected $message;
    public function __construct(MessageRepository $message)
    {
        $this->message = $message;
    }

    public function store()
    {
        $message = $this->message->create([
            'to_user_id' => request('user'),
            'from_user_id' => Auth::guard('api')->user()->id,
            'body' => request('body')
        ]);

        if ($message) {
            return response()->json(['status' => true]);
        }

        return response()->json(['status' => false]);
    }
}
```

MessageRepository

```php
<?php
// /app/Repositories/MessageRepository.php
namespace App\Repositories;
use App\Models\Message;
class MessageRepository
{
    public function create(array $attributes)
    {
        return Message::create($attributes);
    }
}
```

视图

```php
<?php
// resources/views/questions/show.blade.php
<send-message user="{{$question->user_id}}"></send-message>
```

vuejs 组件

```js
Vue.component('send-message', require('./components/SendMessage.vue'));
```

```js
<!-- resources/assets/js/components/SendMessage.vue -->
<template>
    <div>

    <button
            class="btn btn-default pull-right"
            style="margin-top: -36px;"
            @click="showSendMessageForm"
    >发送私信</button>
        <div class="modal fade" id="modal-send-message" tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button " class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title">
                            发送私信
                        </h4>
                    </div>
                    <div class="modal-body">
                        <textarea name="body" class="form-control" v-model="body" v-if="!status"></textarea>
                        <div class="alert alert-success" v-if="status">
                            <strong>私信发送成功</strong>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" @click="store">
                            发送私信
                        </button>
                        <button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props:['user'],
        data() {
            return {
                body:'',
                status: false
            }
        },
        methods:{
            store() {
                axios.post('/api/message/store',{'user':this.user,'body':this.body}).then(response => {
                    this.status = response.data.status
                    this.body = ''
                    setTimeout(function () {
                        $('#modal-send-message').modal('hide')
                    }, 2000)
                })
            },
            showSendMessageForm() {
                $('#modal-send-message').modal('show')
            }
        }
    }
</script>
```

运行

```bash
yarn run dev
```

## 第三十章 实现评论（上）

创建数据表

```bash
php artisan make:model Models\\Comment -m
```

```php
<?php
// database/migrations/create_comments_table.php
// commentable_id 和 commentable_type 用于多态关联
public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->text('body');
            $table->unsignedInteger('commentable_id');
            $table->string('commentable_type');
            $table->unsignedInteger('parent_id')->nullable();
            $table->smallInteger('level')->default(1);
            $table->string('is_hidden',8)->default('F');
            $table->timestamps();
        });
    }
```

```bash
php artisan migrate
```

多态关联

> [文档：《多态关联》](https://laravel-china.org/docs/laravel/5.5/eloquent-relationships)

```php
<?php
// app/Models/Comment.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Comment extends Model
{
    protected $table = 'comments';
    protected $fillable = ['user_id', 'body', 'commentable_id', 'commentable_type'];
    // 获得拥有此评论的模型
    public function commentable()
    {
        return $this->morphTo();
    }
}
```

获得答案的所有评论

```php
<?php
// app/Models/Answer.php
public function comments()
{
    return $this->morphMany('App\Models\Comment','commentable');
}
```

获得问题的所有评论

```php
<?php
// app/Models/Question.php
public function comments()
{
    return $this->morphMany('App\Models\Comment','commentable');
}
```

## 第三十一章 Vuejs 实现评论组件

路由

```php
<?php
// routes/api.php
Route::get('/answer/{id}/comments','CommentsController@answer');
Route::get('/question/{id}/comments','CommentsController@question');

Route::post('comment','CommentsController@store');
```

控制器

```bash
php artisan make:controller CommentsController
```

获取多态关联

> [文档：《Eloquent 预加载》](https://laravel-china.org/docs/laravel/5.5/eloquent-relationships/1333#012e7e)


```php
<?php
// app/Http/Controllers/CommentsController.php
namespace App\Http\Controllers;
use App\models\Answer;
use App\Models\Comment;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentsController extends Controller
{
    // 获取答案所以评论
    public function answer($id)
    {
        $answer = Answer::with('comments','comments.user')->where('id', $id)->first();
        return $answer->comments;
    }

    // 获取问题所有评论
    public function question($id)
    {
        $question = Question::with('comments','comments.user')->where('id', $id)->first();
        return $question->comments;

    }

    // 保存评论
    public function store()
    {
        $model = $this->getModelNameFromType(request('type'));
        $comment = Comment::create([
            'commentable_id' => request('model'),
            'commentable_type' => $model,
            'user_id' => Auth::guard('api')->user()->id,
            'body' => request('body')
        ]);
        return $comment;
    }

    private function getModelNameFromType($type)
    {
        return $type === 'question' ? 'App\Models\Question' : 'App\Models\Answer';
    }
}
```

vuejs

```js
<!-- resources/assets/js/components/Comments.vue -->
<template>
    <div>

        <button
                class="button is-naked delete-button"
                @click="showCommentsForm"
                v-text="text"
        ></button>
        <div class="modal fade" :id=dialog tabindex="-1" role="dialog">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button " class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title">
                            评论列表
                        </h4>
                    </div>
                    <div class="modal-body">
                       <div v-if="comments.length > 0">
                           <div class="media" v-for="comment in comments">
                               <div class="media-left">
                                   <a href="#">
                                       <img width="24" class="media-object" :src="'http://zhihu.test/images/' + comment.user.avatar">
                                   </a>
                               </div>
                               <div class="media-body">
                                   <h4 class="media-heading">{{comment.user.name}}</h4>
                                   {{comment.body}}
                               </div>
                           </div>
                       </div>
                    </div>
                    <div class="modal-footer">
                        <input type="text" class="form-control" v-model="body">
                        <button type="button" class="btn btn-primary" @click="store">
                            评论
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    export default {
        props:['type','model','count'],
        data() {
            return {
                body:'',
                comments: []
            }
        },
        computed:{
            dialog() {
                return 'comments-dialog-' + this.type + '-' + this.model
            },
            dialogId() {
                return '#' + this.dialog
            },
            text() {
                return this.count + '评论'
            },
            total() {
                return this.count
            }
        },
        methods:{
            store() {
                axios.post('/api/comment',{'type':this.type,'model':this.model,'body':this.body}).then(response => {
                    let comment = {
                        user:{
                            name:Zhihu.name,
                            avatar:Zhihu.avatar
                        },
                        body: response.data.body
                    }
                    this.comments.push(comment)
                    this.body = ''
                    this.total ++
                })
            },
            showCommentsForm() {
                this.getComments()
                $(this.dialogId).modal('show')
            },
            getComments() {
                axios.get('/api/' + this.type +'/' + this.model + '/comments').then(response => {
                    this.comments = response.data
                })
            }
        }
    }
</script>
```

vuejs 组件

```js
Vue.component('comments', require('./components/Comments.vue'));
```

视图

```php
<?php
// resources/views/questions/show.blade.php
<comments type="question"
    model="{{$question->id}}"
    count="{{$question->comments()->count()}}">
</comments>
// ......
@foreach($question->answers as $answer)
    // ......
    <comments type="answer"
        model="{{$answer->id}}"
        count="{{$answer->comments()->count()}}">
    </comments>
    // ......
@endforeach
```

由前端传入用户名和头像

```php
<?php
// resources/views/layouts/app.blade.php
    <script>
        @if(Auth::check())
            window.Zhihu = {
                name:"{{Auth::user()->name}}",
                avatar:"{{Auth::user()->avatar}}"
            }
        @endif
    </script>
```

## 第三十二章 Repository 模式重构代码

```php
<?php
// app/Http/Controllers/CommentsController.php
namespace App\Http\Controllers;

use App\Repositories\AnswerRepository;
use App\Repositories\CommentRepository;
use App\Repositories\QuestionsRepository;
use Illuminate\Support\Facades\Auth;

class CommentsController extends Controller
{
    protected $answer;
    protected $question;
    protected $comment;

    public function __construct(AnswerRepository $answer, QuestionsRepository $question, CommentRepository $comment)
    {
        $this->answer = $answer;
        $this->question = $question;
        $this->comment = $comment;
    }

    public function answer($id)
    {
        return $this->answer->getAnswerCommentsById($id);
    }
    
    public function question($id)
    {
        return $this->question->getQuestionCommentsById($id);
    }

    public function store()
    {
        $model = $this->getModelNameFromType(request('type'));

        return $this->comment->create([
            'commentable_id' => request('model'),
            'commentable_type' => $model,
            'user_id' => Auth::guard('api')->user()->id,
            'body' => request('body')
        ]);
    }

    private function getModelNameFromType($type)
    {
        return $type === 'question' ? 'App\Models\Question' : 'App\Models\Answer';
    }
}
```

AnswerRepository

```php
<?php
// app/Repositories/AnswerRepository.php
public function getAnswerCommentsById($id)
{
    $answer = Answer::with('comments', 'comments.user')->where('id', $id)->first();
    return $answer->comments;
}
```

QuestionsRepository

```php
<?php
// app/Repositories/QuestionsRepository.php
public function getQuestionCommentsById($id)
{
    $question = Question::with('comments','comments.user')->where('id',$id)->first();
    return $question->comments;
}
```

CommentRepository

```php
<?php
// app/Repositories/CommentRepository.php
namespace App\Repositories;
use App\Models\Comment;
class CommentRepository
{
    public function create(array $attributes)
    {
        return Comment::create($attributes);
    }
}
```

 ## 第三十三章 自定义 helper

 33.1 优化话题路由

```php
<?php
// routes/api.php
Route::get('/topics','TopicsController@index')->middleware('api');
```

 ```bash
php artisan make:Controller TopicsController
 ```

 ```php
<?php
// app/Http/Controllers/TopicsController.php
namespace App\Http\Controllers;
use App\Repositories\TopicRepository;
use Illuminate\Http\Request;
class TopicsController extends Controller
{
    protected $topics;

    /**
     * TopicsController constructor.
     * @param $topics
     */
    public function __construct(TopicRepository $topics)
    {
        $this->topics = $topics;
    }

    public function index(Request $request)
    {
        return $this->topics->getTopicsForTagging($request);
    }
}
```

 ```php
<?php
// app/Repositories/TopicRepository.php
namespace App\Repositories;
use App\Models\Topic;
use Illuminate\Http\Request;
class TopicRepository
{
    public function getTopicsForTagging(Request $request)
    {
        return Topic::select(['id','name'])
            ->where('name','like','%'.$request->query('q').'%')
            ->get();
    }
}
```

 33.2 优问题路由

 ```php
<?php
// routes/api.php
Route::post('/question/follower','QuestionFollowController@follower')->middleware('auth:api');
Route::post('/question/follow','QuestionFollowController@followThisQuestion')->middleware('auth:api');
```

 ```php
<?php
// app/Http/Controllers/QuestionFollowController.php
public function follower(Request $request)
    {
        $user = Auth::guard('api')->user();
        $followed = $user->followed($request->get('question'));
        if ($followed) {
            return response()->json(['followed' => true]);
        }
        return response()->json(['followed' => false]);
    }

public function followThisQuestion(Request $request)
    {
        $user = Auth::guard('api')->user();
        $question = $this->question->byID($request->get('question'));
        $followed = $user->followThis($question->id);
        if (count($followed['detached']) > 0){
            $question->decrement('followers_count');
            return response()->json(['followed' => false]);
        }
        $question->increment('followers_count');
        return response()->json(['followed' => true]);
    }
```

33.3 Helpers 函数

 ```php
<?php
// app/Support/Helpers.php
if ( !function_exists('user') ) {
    function user($driver = null)
    {
        if ( $driver ) {
            return app('auth')->guard($driver)->user();
        }

        return app('auth')->user();
    }
}
```


 ```php
<?php
// composer.json
"autoload": {
    "files":[
        "app/Support/Helpers.php"
    ]
}
```

```bash
composer dump-autoload
```

 ```php
<?php
// 在所有控制器中将
Auth::guard('api')->user();  
// 替换成
user('api')
```

 ## 第三十四章 私信列表

路由

 ```php
<?php
// routes/web.php
Route::get('inbox','InboxController@index');
Route::get('inbox/{userId}','InboxController@show');
```

控制器

```bash
php artisan make:controller InboxController
```

 ```php
<?php
// app/Http/Controllers/InboxController.php
namespace App\Http\Controllers;

use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InboxController extends Controller 
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $messages = Auth::user()->messages->groupBy('from_user_id');
        return view('inbox.index',compact('messages'));
    }

    public function show($userId)
    {
        $messages = Message::where('from_user_id',$userId)->get();
        return $messages;
    }
}

```

视图

 ```php
<?php
// resources/views/inbox/index.blade.php
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">私信列表</div>
                    <div class="panel-body">
                        @foreach($messages as $messageGroup)
                            <div class="media">
                                <div class="media-left">
                                    <a href="#">
                                        <img src="{{ url('images', $messageGroup->first()->fromUser->avatar) }}" width="48" alt="">
                                    </a>
                                </div>
                                <div class="media-body">
                                    <h4 class="media-heading">
                                        <a href="#">
                                            {{ $messageGroup->first()->fromUser->name }}
                                        </a>
                                    </h4>
                                    <p>
                                        <a href="/inbox/{{ $messageGroup->first()->fromUser->id }}">
                                            {{ $messageGroup->first()->body }}
                                        </a>
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
```

## 第三十五章 私信列表（下）

解决发件人看不到已发私信

改造数据表

```bash
php artisan make:migration add_dialog_id_to_messages --table=messages
```

```php
<?php
// database/migrations/add_dialog_id_to_messages.php
    public function up()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->bigInteger('dialog_id')->default('24');
        });
    }

    public function down()
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['dialog_id']);
        });
    }
```

```php
<?php
// app/Models/Message.php
protected $fillable = ['from_user_id', 'to_user_id', 'body', 'dialog_id'];
```

```php
<?php
// app/Http/Controllers/MessagesController.php
public function store()
{
    $message = $this->message->create([
        // ......
        'dialog_id' => time().Auth::id()
    ]);
}
```

控制器

 ```php
<?php
// app/Http/Controllers/InboxController.php
    public function index()
    {
        $messages = Message::where('to_user_id',Auth::id())
            ->orWhere('from_user_id',Auth::id())
            ->with(['fromUser','toUser'])->get();
        return view('inbox.index',['messages' => $messages->groupBy('to_user_id')]);
    }

    public function show($dialogId)
    {
        $messages = Message::where('dialog_id',$dialogId)->get();
        return $messages;
    }
```

路由

 ```php
<?php
// routes/web.php
Route::get('inbox/{dialogId}','InboxController@show');
```

视图

    Auth::id() == $key

按收件人分组后，如果当前登陆用户就是收件人，则显示发件人信息，否则显示收件人信息。

```php
<?php
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">私信列表</div>
                    <div class="panel-body">
                        @foreach($messages as $key => $messageGroup)
                            <div class="media">
                                <div class="media-left">
                                    <a href="#">
                                        @if(Auth::id() == $key)
                                            <img src="{{ url('images', $messageGroup->last()->fromUser->avatar) }}" width="48" alt="">
                                        @else
                                            <img src="{{ url('images', $messageGroup->last()->toUser->avatar) }}" width="48" alt="">
                                        @endif
                                    </a>
                                </div>
                                <div class="media-body">
                                    <h4 class="media-heading">
                                        <a href="#">
                                            @if(Auth::id() == $key)
                                                {{ $messageGroup->last()->fromUser->name }}
                                            @else
                                                {{ $messageGroup->last()->toUser->name }}
                                            @endif
                                        </a>
                                    </h4>
                                    <p>
                                        <a href="/inbox/{{ $messageGroup->last()->dialog_id }}">
                                            {{ $messageGroup->last()->body }}
                                        </a>
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
```

## 第三十六章 回复私信

路由

 ```php
<?php
// routes/web.php
Route::post('inbox/{dialogId}/store','InboxController@store');
```

 ```php
<?php
// app/Http/Controllers/InboxController.php
<?php
    public function index()
    {
        $messages = Message::where('to_user_id',Auth::id())
            ->orWhere('from_user_id',Auth::id())
            ->with(['fromUser','toUser'])->get();
        return view('inbox.index',['messages' => $messages->unique('dialog_id')->groupBy('to_user_id')]);
    }

    public function show($dialogId)
    {
        $messages = Message::where('dialog_id',$dialogId)->latest()->get();
        return view('inbox.show',compact('messages','dialogId'));
    }

    public function store($dialogId)
    {
        $message = Message::where('dialog_id',$dialogId)->first();
        $toUserId = $message->from_user_id === Auth::id() ? $message->to_user_id : $message->from_user_id;
        Message::create([
            'from_user_id' => Auth::id(),
            'to_user_id' => $toUserId,
            'body' => request('body'),
            'dialog_id' => $dialogId
        ]);
        return back();
    }
}
```

视图

 ```php
<?php
// resources/views/inbox/show.blade.php
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">对话列表</div>
                    <div class="panel-body">
                        <form action="/inbox/{{$dialogId}}/store" method="post">
                            {{ csrf_field() }}
                            <div class="form-group">
                                <textarea name="body" class="form-control"></textarea>
                            </div>
                            <div class="form-group pull-right">
                                <button class="btn btn-success">发送私信</button>
                            </div>
                        </form>
                        <div class="messages-list">
                            @foreach($messages as $message)
                                <div class="media">
                                    <div class="media-left">
                                        <a href="#">
                                            <img src="{{ url('images',$message->fromUser->avatar) }}"  width="48" alt="">
                                        </a>
                                    </div>
                                    <div class="media-body">
                                        <h4 class="media-heading">
                                            <a href="#">
                                                {{ $message->fromUser->name }}
                                            </a>
                                        </h4>
                                        <p>
                                            {{ $message->body }} <span class="pull-right">{{ $message->created_at->format('Y-m-d') }}</span>
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
```

## 第三十七章 标记私信已读

 ```php
<?php
// app/Http/Controllers/InboxController.php
public function show($dialogId)
{
    $messages->markAsRead();
}
```

 ```php
<?php
// app/Models/Message.php
 public function markAsRead()
    {
        if(is_null($this->read_at)) {
            $this->forceFill(['has_read' => 'T','read_at' => $this->freshTimestamp()])->save();
        }
    }

    public function newCollection(array $models = [])
    {
        return new MessageCollection($models);
    }
```


 ```php
<?php
// /app/Models/MessageCollection.php
namespace App\Models;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
class MessageCollection extends Collection
{
    public function markAsRead()
    {
        $this->each(function($message) {
            if($message->to_user_id === Auth::id() ){
                $message->markAsRead();
            }
        });
    }
}
```

## 第三十八章 显示未读私信

 ```php
<?php
// app/Http/Controllers/InboxController.php
// 根据 dialog_id 会话分组（一个会话有多条私信），倒序（每个会话中最后的私信排最前）。
    public function index()
    {
        $messages = Message::where('to_user_id',Auth::id())
            ->orWhere('from_user_id',Auth::id())
            ->with(['fromUser','toUser'])->latest()->get();
        return view('inbox.index',['messages' => $messages->groupBy('dialog_id')]);
    }

    public function show($dialogId)
    {
        $messages = Message::where('dialog_id',$dialogId)->latest()->get();
        $messages->markAsRead();
        return view('inbox.show',compact('messages','dialogId'));
    }
```

视图

 ```php
<?php
// resources/views/inbox/index.blade.php
// 每个会话显示一个列表，如果登陆用户就是发件人，那么显示收件人头像，否则显示发件人头像。
// $messageGroup->first() 指回话中的最后一条私信记录（messages已经倒序）
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">私信列表</div>
                    <div class="panel-body">
                        @foreach($messages as $messageGroup)
                            <div class="media {{ $messageGroup->first()->shouldAddUnreadClass() ? 'unread' : '' }}">
                                <div class="media-left">
                                    <a href="#">
                                        @if(Auth::id() == $messageGroup->last()->from_user_id)
                                            <img src="{{ url('images', $messageGroup->last()->toUser->avatar) }}" width="48" alt="">
                                        @else
                                            <img src="{{ url('images', $messageGroup->last()->fromUser->avatar) }}" width="48" alt="">
                                        @endif
                                    </a>
                                </div>
                                <div class="media-body">
                                    <h4 class="media-heading">
                                        <a href="#">
                                            @if(Auth::id() == $messageGroup->last()->from_user_id)
                                                {{ $messageGroup->last()->toUser->name }}
                                            @else
                                                {{ $messageGroup->last()->fromUser->name }}
                                            @endif
                                        </a>
                                    </h4>
                                    <p>
                                        <a href="/inbox/{{ $messageGroup->first()->dialog_id }}">
                                            {{ $messageGroup->first()->body }}
                                        </a>
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
```

模型

 ```php
<?php
// app/Models/Message.php
    public function read()
    {
        return $this->has_read === 'T';
    }

    public function unread()
    {
        return $this->has_read === 'F';
    }

    public function shouldAddUnreadClass()
    {
        // 如果登陆用户就是发件人，那么始终未读
        if(Auth::id() === $this->from_user_id) {
            return false;
        }
        return $this->unread();
    }
```

优化

 ```php
<?php
// app/Http/Controllers/InboxController.php
public function index()
    {
        $messages = Message::where('to_user_id',Auth::id())
            ->orWhere('from_user_id',Auth::id())
            ->with(['fromUser' => function ($query){
                return $query->select(['id','name','avatar']);
            },'toUser' => function ($query){
                return $query->select(['id','name','avatar']);
            }])->latest()->get();
        return view('inbox.index',['messages' => $messages->groupBy('dialog_id')]);
    }
    public function show($dialogId)
    {
        $messages = Message::where('dialog_id',$dialogId)->with(['fromUser' => function ($query){
                return $query->select(['id','name','avatar']);
            },'toUser' => function ($query){
                return $query->select(['id','name','avatar']);
            }])->latest()->get();
        $messages->markAsRead();
        return view('inbox.show',compact('messages','dialogId'));
    }
```

## 第三十九章 私信实现 Repository 模式

```php
<?php
// app/Repositories/MessageRepository.php
namespace App\Repositories;

use App\Models\Message;
use Illuminate\Support\Facades\Auth;

class MessageRepository
{
    public function create(array $attributes)
    {
        return Message::create($attributes);
    }

    public function getAllMessages()
    {
        return $messages = Message::where('to_user_id',Auth::id())
            ->orWhere('from_user_id',Auth::id())
            ->with(['fromUser' => function ($query){
                return $query->select(['id','name','avatar']);
            },'toUser' => function ($query){
                return $query->select(['id','name','avatar']);
            }])->latest()->get();
    }

    public function getDialogMessagesBy($dialogId)
    {
        return Message::where('dialog_id',$dialogId)->with(['fromUser' => function ($query){
            return $query->select(['id','name','avatar']);
        },'toUser' => function ($query){
            return $query->select(['id','name','avatar']);
        }])->latest()->get();
    }

    public function getStingleMessageBy($dialogId)
    {
        return Message::where('dialog_id',$dialogId)->first();
    }
}
```

控制器

```php
<?php
// app/Http/Controllers/InboxController.php
namespace App\Http\Controllers;

use App\Repositories\MessageRepository;
use Illuminate\Support\Facades\Auth;

class InboxController extends Controller
{
    protected $message;

    public function __construct(MessageRepository $message)
    {
        $this->middleware('auth');
        $this->message = $message;
    }

    public function index()
    {
        $messages = $this->message->getAllMessages();
        return view('inbox.index',['messages' => $messages->groupBy('dialog_id')]);
    }

    public function show($dialogId)
    {
        $messages = $this->message->getDialogMessagesBy($dialogId);
        $messages->markAsRead();
        return view('inbox.show',compact('messages','dialogId'));
    }

    public function store($dialogId)
    {
        $message = $this->message->getStingleMessageBy($dialogId);
        $toUserId = $message->from_user_id === Auth::id() ? $message->to_user_id : $message->from_user_id;
        $this->message->create([
            'from_user_id' => Auth::id(),
            'to_user_id' => $toUserId,
            'body' => request('body'),
            'dialog_id' => $dialogId
        ]);
        return back();
    }
}
```

## 第四十章 私信通知

```bash
 php artisan make:notification NewMessageNotification
 ```

 ```php
<?php
// app/Notifications/NewMessageNotification.php
namespace App\Notifications;

use App\Models\Message;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    public function via()
    {
        return ['database'];
    }

    public function toDatabase()
    {
         return [
             'name' => $this->message->fromUser->name,
             'dialog' => $this->message->dialog_id,
         ];
    }
}
```

控制器

 ```php
<?php
// app/Http/Controllers/InboxController.php
public function store($dialogId)
    {
        $message = $this->message->getStingleMessageBy($dialogId);
        $toUserId = $message->from_user_id === Auth::id() ? $message->to_user_id : $message->from_user_id;
        $newMessage = $this->message->create([
            'from_user_id' => Auth::id(),
            'to_user_id' => $toUserId,
            'body' => request('body'),
            'dialog_id' => $dialogId
        ]);
        $newMessage->toUser->notify(new NewMessageNotification($newMessage));
        return back();
    }
```

视图

 ```php
<?php
// resources/views/notifications/new_message_notification.blade.php
<li class="notifications">
    <a href="/inbox/{{$notification->data['dialog']}}">
        {{ $notification->data['name'] }} 给你发了一条私信
    </a>
</li>
```

## 第四十一章 notifications 已读

路由

```bash
# routes/web.php
Route::get('notifications/{notification}','NotificationsController@show');
```

控制器

 ```php
<?php
// app/Http/Controllers/NotificationsController.php
public function show(DatabaseNotification $notification)
    {
        $notification->markAsRead();
        return redirect(\Request::query('redirect_url'));
    }
```

视图

 ```php
<?php
// resources/views/notifications/new_message_notification.blade.php
<li class="notifications {{ $notification->unread() ? 'unread' : ' ' }}">
    <a href="/notifications/{{$notification->id}}?redirect_url=/inbox/{{$notification->data['dialog']}}">
        {{ $notification->data['name'] }} 给你发了一条私信
    </a>
</li>
```

## 第四十二章 上传头像组件

路由

 ```php
<?php
// routes/web.php
Route::get('avatar','UsersController@avatar');
```

控制器

```bash
php artisan make:controller UsersController
```

 ```php
<?php
// app/Http/Controllers/UsersController.php
class UsersController extends Controller
{
    public function avatar()
    {
        return view('users.avatar');
    }
}
```

视图

 ```php
<?php
// resources/views/users/avatar.blade.php
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">更换头像</div>
                    <div class="panel-body">
                        <user-avatar avatar="{{ url('images',Auth::user()->avatar) }}" ></user-avatar>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
```


vue 组件

<http://github.com/dai-siki/vue-image-crop-upload>

```bash
yarn add vue-image-crop-upload babel-polyfill
```

 ```php
<?php
// /resources/assets/js/app.js
Vue.component('user-avatar', require('./components/Avatar.vue'));
```

```js
<!-- resources/assets/js/components/Avatar.vue -->
<template>
    <div>
        <my-upload field="img"
                   @crop-success="cropSuccess"
                   @crop-upload-success="cropUploadSuccess"
                   @crop-upload-fail="cropUploadFail"
                   v-model="show"
                   :width="300"
                   :height="300"
                   url="/upload"
                   :params="params"
                   :headers="headers"
                   img-format="png"></my-upload>
        <img :src="imgDataUrl" width="48">
        <a class="btn" @click="toggleShow">设置头像</a>
    </div>
</template>

<script>
    import 'babel-polyfill'; // es6 shim
    import myUpload from 'vue-image-crop-upload';
    export default {
        props:['avatar'],
        data() {
            return {
                show: false,
                params: {
                    token: '123456798',
                    name: 'avatar'
                },
                headers: {
                    smail: '*_~'
                },
                imgDataUrl: this.avatar
            }
        },
        components: {
            'my-upload': myUpload
        },
        methods: {
            toggleShow() {
                this.show = !this.show;
            },
            cropSuccess(imgDataUrl, field){
                console.log('-------- crop success --------');
                this.imgDataUrl = imgDataUrl;
            },
            cropUploadSuccess(jsonData, field){
                console.log('-------- upload success --------');
                console.log(jsonData);
                console.log('field: ' + field);
            },
            cropUploadFail(status, field){
                console.log('-------- upload fail --------');
                console.log(status);
                console.log('field: ' + field);
            }
        }
    }
</script>
```

```bash
yarn run dev
```

## 第四十三章 头像上传到服务器

路由

 ```php
<?php
// routes/web.php
Route::get('avatar','UsersController@avatar');
```

控制器

 ```php
<?php
// app/Http/Controllers/UsersController.php
public function changeAvatar(Request $request)
    {
        $file = $request->file('img');
        $filename = md5(time().user()->id).'.'.$file->getClientOriginalExtension();
        $file->move(public_path('avatars'),$filename);
        user()->avatar = asset('avatars/'.$filename);
        user()->save();
        return ['url' => user()->avatar];
    }
```

vue 组件

```js
<!-- resources/assets/js/components/Avatar.vue -->
<template>
    <div style="text-align: center;">
        <my-upload field="img"
                   @crop-success="cropSuccess"
                   @crop-upload-success="cropUploadSuccess"
                   @crop-upload-fail="cropUploadFail"
                   v-model="show"
                   :width="300"
                   :height="300"
                   url="/avatar"
                   :params="params"
                   :headers="headers"
                   img-format="png"></my-upload>
        <img :src="imgDataUrl" style="width: 80px;">
        <div style="margin-top: 20px;">
            <button class="btn btn-default" @click="toggleShow">设置头像</button>
        </div>
    </div>
</template>

<script>
    import 'babel-polyfill';
    import myUpload from 'vue-image-crop-upload';

    export default {
        props:['avatar','token'],
        data() {
            return {
                show: false,
                params: {
                    name: 'img'
                },
                headers: {
                    smail: '*_~',
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                imgDataUrl: this.avatar
            }
        },
        components: {
            'my-upload': myUpload
        },
        methods: {
            toggleShow() {
                this.show = !this.show;
            },
            cropSuccess(imgDataUrl, field){
                this.imgDataUrl = imgDataUrl;
            },
            cropUploadSuccess(response, field){
                this.imgDataUrl = response.url
                this.toggleShow()
            },
            cropUploadFail(status, field){
                console.log('-------- upload fail --------');
                console.log(status);
                console.log('field: ' + field);
            }
        }
    }
</script>
```

```bash
yarn run dev
```

## 第四十四章 头像上传到七牛

安装扩展包

<https://github.com/overtrue/laravel-filesystem-qiniu>

```bash
composer require "overtrue/laravel-filesystem-qiniu" -vvv
```

```php
<?php
// config/app.php
'providers' => [
    // Other service providers...
    Overtrue\LaravelFilesystem\Qiniu\QiniuStorageServiceProvider::class,
],
```

```php
<?php
// config/filesystems.php
return [
   'disks' => [
        //...
        'qiniu' => [
           'driver'     => 'qiniu',
           'access_key' => env('QINIU_ACCESS_KEY', 'xxxxxxxxxxxxxxxx'),
           'secret_key' => env('QINIU_SECRET_KEY', 'xxxxxxxxxxxxxxxx'),
           'bucket'     => env('QINIU_BUCKET', 'test'),
           'domain'     => env('QINIU_DOMAIN', 'xxx.clouddn.com'), // or host: https://xxxx.clouddn.com
        ],
        //...
    ]
];
```

控制器

 ```php
<?php
// app/Http/Controllers/UsersController.php
public function changeAvatar(Request $request)
    {
        $file = $request->file('img');
        $filename = 'avatars/'.md5(time().user()->id).'.'.$file->getClientOriginalExtension();

        Storage::disk('qiniu')->writeStream($filename,fopen($file->getRealPath(),'r'));
        user()->avatar = 'http://'.config('filesystems.disks.qiniu.domain').'/'.$filename;

        user()->save();
        return ['url' => user()->avatar];
    }
```


## 第四十五章 实现修改密码

路由

 ```php
<?php
// routes/web.php
Route::get('password','PasswordController@password');
Route::post('password/update','PasswordController@update');
```

验证密码

```bash
php artisan make:request ChangePasswordRequest
```

```php
<?php
// app/Http/Requests/ChangePasswordRequest.php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class ChangePasswordRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'old_password' => 'required|min:6',
            'password' => 'required|min:6|confirmed',
        ];
    }

    public function messages()
    {
        return [
            'old_password.required' => '原始密码不能为空',
            'old_password.min' => '原始密码不能少于6个字符',
            'password.required' => '新始密码不能为空',
            'password.min' => '新密码不能少于6个字符',
            'password.confirmed' => '两次输入新密码不符',
        ];
    }
}
```

控制器

```bash
php artisan make:controller PasswordController
```

```php
<?php
// app/Http/Controllers/PasswordController.php
namespace App\Http\Controllers;
use Hash;
use App\Http\Requests\ChangePasswordRequest;
class PasswordController extends Controller
{
    public function password()
    {
        return view('users.password');
    }

    public function update(ChangePasswordRequest $request)
    {
        if(Hash::check($request->get('old_password'),user()->password)) {
            user()->password = bcrypt($request->get('password'));
            user()->save();
            flash('密码修改成功','success');
            return back();
        }
        flash('密码修改失败','danger');
        return back();
    }
}
```

视图

```php
<?php
// resources/views/users/password.blade.php
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">修改密码</div>
                    <div class="panel-body">
                        @include('flash::message')
                        <form class="form-horizontal" role="form" method="POST" action="/password/update">
                            {{ csrf_field() }}
                            <div class="form-group{{ $errors->has('old_password') ? ' has-error' : '' }}">
                                <label for="old_password" class="col-md-4 control-label">原始密码</label>
                                <div class="col-md-6">
                                    <input id="old_password" type="password" class="form-control" name="old_password" value="{{ old('old_password') }}" required>
                                    @if ($errors->has('old_password'))
                                        <span class="help-block">
                                        <strong>{{ $errors->first('old_password') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group{{ $errors->has('password') ? ' has-error' : '' }}">
                                <label for="password" class="col-md-4 control-label">输入新密码</label>
                                <div class="col-md-6">
                                    <input id="password" type="password" class="form-control" name="password" required>
                                    @if ($errors->has('password'))
                                        <span class="help-block">
                                        <strong>{{ $errors->first('password') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="password-confirm" class="col-md-4 control-label">确认新密码</label>
                                <div class="col-md-6">
                                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-6 col-md-offset-4">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        更改密码
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
```

## 第四十六章 用户个人设置

路由

 ```php
<?php
// routes/web.php
Route::get('setting','SettingController@index');
Route::post('setting','SettingController@store');
```

控制器

```bash
php artisan make:controller SettingController
```

 ```php
<?php
// app/Http/Controllers/SettingController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return view('users.setting');
    }

    public function store(Request $request)
    {
        $settings = array_merge(user()->settings,array_only($request->all(),['city','bio']));
        user()->update(['settings' => $settings]);
        return back();
    }
}
```

模型

 ```php
<?php
// app/Models/User.php
protected $fillable = [
    'settings',
];

protected $casts = [
    'settings' => 'array'
];
```

默认值

 ```php
<?php
// app/Http/Controllers/Auth/RegisterController.php
$user =  User::create([
    'settings' => ['city' => ''],
]);
```

视图

 ```php
<?php
// /resources/views/users/setting.blade.php
@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <div class="panel panel-default">
                    <div class="panel-heading">设置个人信息</div>
                    <div class="panel-body">
                        <form class="form-horizontal" role="form" method="POST" action="/setting">
                            {{ csrf_field() }}
                            <div class="form-group{{ $errors->has('city') ? ' has-error' : '' }}">
                                <label for="city" class="col-md-4 control-label">现居城市</label>
                                <div class="col-md-6">
                                    <input id="city" type="text" class="form-control" name="city" value="{{ user()->settings['city'] }}"  required>
                                    @if ($errors->has('city'))
                                        <span class="help-block">
                                        <strong>{{ $errors->first('city') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group{{ $errors->has('city') ? ' has-error' : '' }}">
                                <label for="bio" class="col-md-4 control-label">个人简介</label>
                                <div class="col-md-6">
                                    <textarea id="bio" type="text" class="form-control" name="bio"  required>{{ user()->settings['bio'] }}</textarea>
                                    @if ($errors->has('bio'))
                                        <span class="help-block">
                                        <strong>{{ $errors->first('bio') }}</strong>
                                    </span>
                                    @endif
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-6 col-md-offset-4">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        更新资料
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
```

## 第四十七章 重构用户设置

控制器

 ```php
<?php
// app/Http/Controllers/SettingController.php
public function store(Request $request)
    {
        user()->settings()->merge($request->all());
        return back();
    }
```

模型

 ```php
<?php
// app/Models/User.php
public function settings()
{
    return new Setting($this);
}
```

 ```php
<?php
namespace App\Models;
class Setting
{
    protected $allowed = ['city','bio'];
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function merge(array $attributes)
    {
        $settings = array_merge($this->user->settings,array_only($attributes,$this->allowed));

        return $this->user->update(['settings' => $settings]);
    }
}
```