[TOC]

demo
> application/ component demos


---

### kafka
[kafka quickstart](https://kafka.apache.org/quickstart)

docker 中 kraft 部署
```sh
docker pull apache/kafka:3.7.0
docker run --name kafka -p 9092:9092 -p 9093:9093 -itd apache/kafka:3.7.0
# 容器内的 kafka 可执行文件在 /opt/kafka/bin
cd /opt/kafka/bin
./kafka-topics.sh --bootstrap-server localhost:9092 --list
```

---

### rocketmq 5.2 - php
- [rocketmq quickstart](https://rocketmq.apache.org/docs/quick-start/)
- 开源版本的 RocketMQ sdk 地址： https://github.com/apache/rocketmq-clients/blob/master/
- 需要 jdk1.8 以上，生产环境 8-32G 内存。本机开发调试限制内存使用，修改 runserver.sh, runbroker.sh `Xms512m -Xmx512m -Xmn256m`。
- 默认端口 9876，accessKey 和 accessSecret 在 conf/plain_acl.yml 中配置。
- **注：开源版本的 RocketMQ 的 SDK 与阿里云的 SDK 不一定能互相兼容使用，当前 5.2.0 的 proxy 采用的是 gRPC 通信，而阿里云的 SDK 采用的是 HTTP 通信**


部署
```sh
dnf install java-11-openjd
java --version

wget https://dist.apache.org/repos/dist/release/rocketmq/5.2.0/rocketmq-all-5.2.0-bin-release.zip

nohup sh bin/mqnamesrv &
less ~/logs/rocketmqlogs/namesrv.log

nohup sh bin/mqbroker -n localhost:9876 --enable-proxy &
less ~/logs/rocketmqlogs/proxy.log

sh bin/mqshutdown broker    # broker|namesrv
```

PHP 中引入 SDK
```sh
# grpc 协议的 sdk 需要 PHP 安装 grpc 扩展
apt install -y php7.3-grpc
# composer require rocketmq/rocketmq-php-sdk
composer install
```

---

### rabbitmq - go
[rabbitmq quickstart](https://www.rabbitmq.com/tutorials)

```sh
touch main.go
go mod init github.com/CatDouby/demo/rocketmq5-go
# git clone https://github.com/apache/rocketmq-clients
# git checkout v5.0.1-rc2-golang
# copy code from rocketmq-clients/golang/example/producer/normal/main.go
go mod tidy
# modify config : topic, endpoint.
# Notice that the endpoint use mq server gRPC port (default 8081, not 9876, grpcServerPort configured in rmq-proxy.json), and port must be visitable from client.
go run .
```