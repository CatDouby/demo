[toc]

*   [实战多阶段构建 Laravel 镜像](https://yeasy.gitbooks.io/docker_practice/image/multistage-builds/laravel.html) ::yeasy.gitbooks.io

#### 使用空镜像 :
```Dockerfile
FROM scratch
```

#### 容器互通 :
```sh
# 创建一个数据库容器
docker run -itd --name db --env MYSQL_ROOT_PASSWORD=example  mariadb
# 创建一个web容器并将它连接到db容器
docker run -itd -P --name web --link db:db nginx:latest
# 此处运行的 web 容器为父容器，db为子容器
```

#### hyperf-dleno 镜像构建

```Dockerfile
FROM registry.cn-hangzhou.aliyuncs.com/dleno-server/php:alp3.12-php7.4-n-sw4.8.13
LABEL maintainer="foo" version="2.0"

# ---------- env settings ----------
# --build-arg timezone=Asia/Shanghai APP_ENV=prod
ARG timezone
ARG APP_ENV

#环境信息
ENV APP_ENV=${APP_ENV:-"local"}
ENV TIMEZONE=${timezone:-"Asia/Shanghai"}

#设置最大句柄数
#RUN echo $(grep MemTotal /proc/meminfo |awk '{printf("%d",$2/2)}') > /proc/sys/fs/file-max \
#    && echo $(grep MemTotal /proc/meminfo |awk '{printf("%d",$2/2/4)}') > /proc/sys/fs/nr_open

# composer 设置
#  如果是本地开发的公用容器就不需要执行 composer install --no-dev -o #  cd 到具体的开发项目手动执行就可以了
RUN composer config -g repos.packagist composer https://mirrors.cloud.tencent.com/composer/ \
     && composer config -g repos.packagist composer https://mirrors.aliyun.com/composer/

# update
RUN ln -sf /usr/share/zoneinfo/${TIMEZONE} /etc/localtime \
    && echo "${TIMEZONE}" > /etc/timezone

EXPOSE 9501 9502 9504

CMD ["sh", "/opt/www/docker/docker-entrypoint.sh"]

# 热重载
#php bin/hyperf.php server:watch
# 强制结束所有进程
#kill -9 $(ps -ef|grep Server|grep -v grep|awk '{print $2}')
```
构建镜像 & 运行容器
```sh
docker build -f ./Dockerfile -t dnoapp:v1 .

docker run --name dnoapp -p 9501:9501 -p 9502:9502 \
-v /data/workplace/hyperf-apps/:/data/project \
--privileged -u root -itd dnoapp:v1  /bin/bash
```

#### 使用 mysql 5.7
```sh
docker pull mysql:5.7

docker run -p 3306:3306 --name mysql5.7 -d \
-v /data/storage/mysql5.7/data:/var/lib/mysql \
-v /data/storage/mysql5.7/my.cnf:/etc/mysql/my.cnf \
-v /data/storage/mysql5.7/log:/var/log/mysql \
-e MYSQL_ROOT_PASSWORD=root \
mysql:5.7
```

#### 使用 redis 7.2
```sh
docker run -d --name my-redis -p 6379:6379 \
-v /data/storage/redis:/data \
-e REDIS_PASSWORD=ZHKPctsnugxgRcPm8Ezwbt3n \
redis:7.2
```

####  使用 composer 创建项目或安装依赖
> 容器中的 composer 不依赖于 PHP 执行文件，只需在 composer.json 中指定 PHP 版本即可。

```js
{
  "config": {
    "platform": {
      "php": "7.4",
      "ext-something": "MAJOR.MINOR.PATCH"
    }
  }
}
```

```sh
# composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/
# composer config -g repo.packagist composer https://mirrors.cloud.tencent.com/composer/

touch /work/proj/xx-proj/composer.json
echo '{"config":{"platform":{"php":"7.4"}}}' > /work/proj/xx-proj/composer.json

docker run --rm --interactive --tty \
  --env COMPOSER_CACHE_DIR=/cache \ # 包缓存目录
  -v /work/proj/xx-proj:/app \
  -v /work/cache/composer:/cache \
  composer command

# composer commands:
# composer require predis/predis
# composer install
#   --ignore-platform-reqs --no-scripts 
# composer create-project laravel/laravel:^8.0 example-app
#   cd example-app && php artisan serve
```


#### 使用 consul
```sh
docker run -d -p 8500:8500 --restart=always --name=consul \
  -v D:\data\consul:/consul/data \
  consul agent -server -bootstrap -ui -client='0.0.0.0'
```

#### 使用 hperf, consul 镜像搭建微服务

##### 发布者、消费者共同的 hyperf 扩展和服务注册发现
```bash
# 增加配置项 config/autoload/services.php 
# service提供者增加注解属性 publisTo="consul"

# 指定挂载路径时，宿主机的挂载路径可以直接使用windows上的盘符路径
docker run --name hpf01 -v D:\dev\www\hyperf-micro:/data/project -p 9501:9501 \
--privileged -u root -it --entrypoint /bin/sh hyperf/hyperf:7.4-alpine-v3.11-swoole

composer config -g repo.packagist composer https://mirrors.aliyun.com/composer
# 进入容器后使用 composer 创建项目
cd /data/project && composer create-project hyperf/hyperf-skeleton hyperf_01
composer require hyperf/rpc
composer require hyperf/json-rpc
composer require hyperf/service-governance-consul
# 创建项目后可以直接复制hyperf_01，创建多个项目 hyperf_02, hyperf_03 然后给每个复制的项目创建对应的容器，这样不用每个容器都用 composer 创建一遍
# 注册中心项目增加依赖 composer require hyperf/service-governance-consul
# 启动项目
php bin/hyperf.php start
docker exec -it hyperf /bin/sh
```


```php
// config/autoload/services.php
return [
	'enable' => [
        'discovery' => true,
        'register' => true,
    ],
    'drivers' => [
        'consul' => [
            'uri' => 'http://192.168.0.30:8500',
            'token' => '',
        ]
    ]
];

// 服务提供者
namespace App\JsonRpc;
use Hyperf\RpcServer\Annotation\RpcService;
/**
 * @RpcService(name="Calculator", protocol="jsonrpc-http", server="jsonrpc-http", publishTo="consul")
 */
class CalculatorService implements CalculatorServiceInterface
{
	public function add(int $a, int $b) :int
	{
		return $a + $b;
	}
}
```

##### hyperf 的服务提供者和服务消费者
```sh
# 服务提供者
# app/JsonRpc/
# app/JsonRpc/CalculatorService.php
# app/JsonRpc/CalculatorServiceInterface.php
# 配置 config/autoload/server.php
#   在 servers 项内增加 jsonrpc-http相关，作为提供服务的调用端口


# 服务调用者/消费者
# app/Rpc/
# app/Rpc/CalculatorServiceInterface.php 除了命名空间，文件内容与服务提供者对应接口一致
# 在业务控制器内通过 Hyperf\Di\Annotation\Inject 注入到一个声明变量，然后远程调用。
# 配置 config/autoload/services.php
#   在 consumers 项内增加服务相关信息(服务名称/服务接口/服务调用协议/服务注册中心信息或指定的服务提供者地址)
```

config/autoload/server.php

```php
return [
    // 这里省略了该文件的其它配置
    'servers' => [
        [
            'name' => 'jsonrpc-http',
            'type' => Server::SERVER_HTTP,
            'host' => '0.0.0.0',
            'port' => 9511,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_REQUEST => [\Hyperf\JsonRpc\HttpServer::class, 'onRequest'],
            ],
        ],
    ],
];
```

config/autoload/services.php

```php
return [
	'consumers' => [
            'name' => 'Calculator',// 与提供者发布的服务名相同
            'service' => \App\JsonRpc\CalculatorServiceInterface::class, // 服务接口名
            // 服务提供者的服务协议，默认值为 jsonrpc-http [jsonrpc-http jsonrpc jsonrpc-tcp-length-check]
            'protocol' => 'jsonrpc-http',
            // 负载均衡算法，可选，默认值为 random
            'load_balancer' => 'random',
            // 服务中心节点，如不配置则不会从服务中心获取节点信息
            'registry' => [
                'protocol' => 'consul',
                'address' => 'http://127.0.0.1:8500',
            ],
            // 如果没有指定上面的 registry 配置，即为直接对指定的节点进行消费
            'nodes' => [
                ['host' => '127.0.0.1', 'port' => 9511],
            ],
	],
];
```