# Tourze框架-HTTP模块

HTTP模块主要包括请求和返回的相关操作的实现。

## 安装

首先需要下载和安装[composer](https://getcomposer.org/)，具体请查看官网的[Download页面](https://getcomposer.org/download/)

在你的`composer.json`中增加：

    "require": {
        "tourze/base": "^1.0"
    },

或直接执行

    composer require tourze/base:"^1.0"

## 使用

### 调用外部

有时候，我们需要抓取外部数据，我们会使用snoopy或者Requests这些类库。

本组件集成了Requests类库，可以很方便地调用外部请求：

    ...
    $request = Request::factory('http://www.baidu.com');
    // $request->method = 'POST';
    // $request->post(['username' => 'admin', 'password' => 'admin']);
    // $request->query(['v' => '2015']);
    $response = $request->execute();

### 调用内部

跟调用外部类似，地址填内部的相对地址就好。

通过调用内部，可以很方便实现HMVC。
