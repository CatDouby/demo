
[toc]

*   Dockerfile 中的语法很类似于 shell, 目标是自动构建 Docker 镜像，它本身是一个配置文件。
*   ***docker build*** 使用 build 指令根据 Dockerfile 创建一个 image
*   **context** 可以是一个路径(Path)，也可以是一个 URL。COPY 和 ADD 都不能操作 context 之外的本机文件。
*   **.dockerignore** 排除在构建时 context 目录位置下不希望被打包的文件。
*   每运行一条 RUN 指令都会增加层。
*   **CMD, ENTRYPOINT, HEALTHCHECK** 只可以出现一次，如果写了多个，只有最后一个生效。无论是哪种方式，**执行脚本中至少要有一个前台进程保持执行状态**，否则 docker 启动之后会被认为空闲而自动退出。可以通过 tail top 循环等阻塞方式防止自动退出。
*   基础操作系统镜像 *ubuntu、debian、centos、fedora、alpine*。空白镜像 *scratch*

##### 构建 :

```sh
# build 最后的参数是一个路径，表示构建中在宿主机操作上下文的相对目录

# 本地构建
docker build -f /pathto/dockerfile -t yhua:xximage .

# 使用远程 Git repo 进行构建
docker build -t yhua:xximage https://github.com/twang2218/gitlab-ce-zh.git#:11.1

# build参数，build-arg 后指定的参数可以通过 $(repo) 方式使用
docker build --network=host --build-arg repo=myrepo -t foo:v1.1

# 使用远程 tar 包构建
docker build http://server/context.tar.gz

# 从标准输入中读取上下文压缩包进行构建
docker build - < context.tar.gz

# BuildKit 等构建工具构建
```

```sh
# 运行应用程序镜像。设置运行的环境变量，挂载数据/日志目录/配置文件。
docker run -e PASSWD=123 -e APP_ENV=local \
-v /var/log/app_logs:/proj/runtime/logs \
-d --name=xx_app xx_image:latest
```

##### 层的概念 :

> Docker的镜像是一个压缩文件，里面有多个文件层。镜像与镜像之间的层可以共享，比如镜像 A B 分别有4个、5个层，但它们基于一个共有的有3层的镜像，那系统中两个镜像实际的总层数为 4+5-3=6 层。

>  每一层只记录文件变更，在容器启动时，Docker会将镜像的各个层进行计算，最后生成一个文件系统，这个被称为 联合挂载。

##### 多阶段构建 :

>  Docker 17.05 以后，新增Dockerfile多阶段构建。所谓多阶段构建，实际上是允许一个出现多个 FROM 指令，多个 FROM 指令并不是为了生成多根的层关系，最后生成的镜像，仍以最后一条 FROM 为准，之前的 FROM 会被抛弃，多条 FROM 就是多阶段构建，虽然最后生成的镜像只能是最后一个阶段的结果，但是，能够将前置阶段中的文件拷贝到后边的阶段中。

>  最大的使用场景是将编译环境和运行环境分离，比如构建一个Go语言程序



##### 指令 :
```Dockerfile
# 指定镜像源
FROM nginx:v1.18.0
#   给镜像源起一个别名，在 copy from 的时候可以使用这个别名
FROM nginx:v1.18.0 AS nginx1.18 
# 特殊的镜像 scratch 它是一个虚拟的空白的镜像
FROM scratch

# WORKDIR 是指生成容器的目录，如该目录不存在会建立目录
# 进入不同的层后，会默认回到 WORKDIR
# 当使用 docker run -it 进入到容器后会默认进入到这个目录
WORKDIR /app

# COPY 上下文目录中文件/目录  镜像指定位置
COPY hom* /mydir/
COPY hom?.txt /mydir/
COPY --chown=55:mygroup files* /mydir/
COPY --from=nginx:latest /etc/nginx/nginx.conf /nginx.conf # 从镜像复制文件
# COPY 操作，源文件的读、写、执行权限、文件变更时间等各种元数据都会保留。

# ADD 指令格式和性质基本与 COPY 一致
# ADD 如果添加的是压缩文件，则会自动完成解压
# ADD 的上下文源路径可以是 URL。docker 自动去下载，并赋予 600 权限
# 官方建议尽可能的使用 COPY

# CMD 指令用于指定默认的容器主进程的启动命令
# CMD 内容是在 docker run 的时候执行，而 RUN 的内容是在 docker build 的时候执行
# CMD 的好处时在运行容器时可以通过指定执行的命令覆盖在 Dockerfile 中预设的CMD命令
# shell 格式：CMD <命令>
# exec 格式：CMD ["可执行文件", "参数1", "参数2"...]
CMD ["/usr/bin/wc", "--help"]


# ENV 设置环境变量
ENV key value
ENV TZ=Asia/Shanghai
ENV key1=value1 key2=value2

# ARG 构建参数
# ARG 定义的参数默认值可以在构建命令 docker build 中用 --build-arg <参数>=<值> 来覆盖
ARG VER=1.19-alpine3.16

# VOLUME 定义匿名卷
# 事先指定某些目录挂载为匿名卷，这样在运行时如果用户不指定挂载，其应用也可以正常运行，不会向容器存储层写入大量数据
VOLUME /data
# /data 目录在运行时自动挂载为匿名卷，写入 /data 的任何信息都不会记录进容器存储层，从而保证了容器存储层的无状态化

# EXPOSE 声明端口
EXPOSE 80 8080
# 声明的端口与 -p 参数指定的端口不相关，只在 -P 参数使用随机端口映射时使用

# ENTRYPOINT 格式与 RUN 一样，指定 ENTRYPOINT 后，CMD指令就不会直接执行，而是作为参数传递给 ENTRYPOINT
# 与 CMD 比较，CMD不能追加命令但是 ENTRYPOINT 可以，两种都是在 docker run 时执行
ENTRYPOINT ["app-init.sh"]
# 也支持以下写法，中括号方式不支持使用变量作为参数
ENTRYPOINT /path/httpserver "-p" ${PORT}

# USER 指定当前用户
USER redis
USER www:www

# HEALTHCHECK 健康检查
HEALTHCHECK --interval=5s --timeout=3s CMD curl -fs http://localhost/ || exit 1

# ONBUILD ...
# 以当前镜像为基础镜像，去构建下一级镜像的时候才会被执行。指令可多条。
ONBUILD COPY ./package.json /app
ONBUILD RUN [ "npm", "install" ]
ONBUILD COPY . /app/
```

##### 示例 :
```Dockerfile
FROM centos7

WORKDIR /app    #设定工作目录，之后操作的相对路径都相对于此目录。

ENV http_port=8080

EXPOSE ${http_port}             #暴露服务端口号

COPY start.sh /root/start.sh    #复制，从主机到容器。
COPY check* /testdir/           #通配符支持

# CMD 指令
RUN mkdir fbuild && echo 123 > fwrite.txt
RUN ls -la

MAINTAINER docker_user docker_user@email.com #维护者信息


# ENTRYPOINT  #执行指令，使用此命令时 RUN 命令不再执行，而只作为此命令的参数
ENTRYPOINT "/pathto/xx.sh"
```

##### 将本地代码包打包到镜像 :
```Dockerfile
    FROM centos7
    WORKDIR /app/mywebapp
    ENV LOGPATH=/app/webapp/logs WEBAPP_VER=V1.0 MAINTAINER=foo
    # 从 docker build 指定的相对目录 copy 到 workdir 的相对目录
    COPY ./ ./
    CMD ["ls", "-la"]

    # 假设当前目录下有一个项目目录 myweb
    # Dockerfile 放在 myweb 内
    # 执行构建 docker build -f myweb/Dockerfile -t foo:v1.0 myweb 
```

##### 编译 go 程序 :
```Dockerfile
FROM golang:1.10.3

# 将源码拷贝到镜像中
COPY server.go /build/

WORKDIR /build

# 编译镜像时，运行 go build 编译生成 server 程序
RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 GOARM=6 go build -ldflags ‘-w -s’ -o server

# 指定容器运行时入口程序 server
ENTRYPOINT ["/build/server"]

# 基础镜像 golang:1.10.3 是非常庞大的，因为其中包含了所有的Go语言编译工具和库
#，而运行时候我们仅仅需要编译后的 server 程序就行了，不需要编译时的编译工具
```

##### 已经编译好的 go 程序打包到镜像 :

```Dockerfile
# 不需要Go语言编译环境
FROM scratch

# 将编译结果拷贝到容器中
COPY server /server

# 指定容器运行时入口程序 server
ENTRYPOINT ["/server"]
```

##### 合并编译和运行 go 程序的 Dockerfile :

```Dockerfile
# 结合上面的编译运行多阶段案例
FROM golang:1.10.3 as builder
COPY server.go /build/
WORKDIR /build
RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 GOARM=6 go build -ldflags '-w -s' -o server

FROM scratch
COPY --from=builder /build/server /
ENTRYPOINT ["/server"]


# 另一个合并编译运行的多阶段案例
#   docker build --build-arg PORT=80 指定端口
FROM golang:1.19-alpine3.16 AS builder
WORKDIR /app
COPY . .
RUN go build -o httpserver server.go

FROM alpine3.16
WORKDIR /app/webapp
ARG PORT=80 #变量默认值
ENV PORT=${PORT} LOGPATH=/app/webapp/logs WEBAPP_VER=V1.0 MAINTAINER=foo
COPY --from=builder /app/server .
EXPOSE ${PORT}
CMD ["nohup", "./httpserver", ">>request.log", "&"]
```

```go
// server.go
package main

import (
  "os"
  "log"
  "net/http"
  "time"
)

func main() {
    http.HandleFunc("/", func(w http.ResponseWriter, r *http.Request) {
        t := time.Now().Format("2006-01-02 15:04:05")
        log.Println(t)
        w.Write([]byte("got request at "+t))
    })
    http.ListenAndServe(os.Getenv("PORT"), nil)
}
```

##### 将编译后端 http_server 打包为最小的镜像
> scratch 为空容器，没有 bash cd mkdir 等命令，所以把可执行文件直接放在根目录，或者将事先建立好的目录拷贝到容器。

```Dockerfile
FROM scratch

MAINTAINER yhfoo
WORKDIR /

COPY ./http_server /

EXPOSE 8081

ENTRYPOINT ["/http_server"]
```

```sh
# 编译得到可执行文件
GOOS=linux GOARCH=amd64 CGO_ENABLED=0 go build -ldflags '-w -s' -o http_server http_server.go

# 打包得到 docker 镜像
docker build -t fserver:pure -f http_server_Dockerfile .

# 运行容器，得到监听端口的 server
docker run -itd --name ff -p 8081:8081 fserver:pure
```

### Note

##### `entrypoint.sh`
> `entrypoint.sh`文件一般放在 /usr/local/bin

```sh
#!/bin/bash
set -e
# source ~/.bashrc
# ..
```


#### redis_exporter 添加到 redis 镜像
```Dockerfile
FROM redis:7.2

COPY ./redis_exporter /bin
COPY ./foo-entrypoint.sh /usr/local/bin

ARG REDIS_PASSWORD=
ENV REDIS_PASSWORD=$REDIS_PASSWORD
ENV TZ=Asia/Shanghai

EXPOSE 6379
EXPOSE 8080

# Note: redis-server 是前台运行的
# 不使用密码
# ENTRYPOINT redis-server & && /bin/redis_exporter -web.listen-address 127.0.0.1:8080
# ENTRYPOINT redis-server /etc/redis.conf & && redis_exporter -redis.addr 127.0.0.1:6379 -redis.password $REDIS_PASSWORD -web.listen-address 0.0.0.0:8080
ENTRYPOINT foo-entrypoint.sh
```

/usr/local/bin/foo-entrypoint.sh
```sh
#!/bin/bash
set -e
redis-server /etc/redis.conf &
redis_exporter -redis.addr 127.0.0.1:6379 -redis.password $REDIS_PASSWORD -web.listen-address 0.0.0.0:8080
```

构建镜像和运行容器
```sh
docker build -f xxDockerfile -t foo-redis7.2:exporter .

# redis.conf 文件必须先创建好，否则docker会当作目录挂载

docker run -d --name foo-redis -p 6379:6379 -p 8080:8080 \
-v /data/storage/redis:/data \
-v /data/docker-etc/redis.conf:/etc/redis.conf \
-e TZ=Asian/Shanghai \
-e REDIS_PASSWORD=666 \
fooredis7.2:exporter
```