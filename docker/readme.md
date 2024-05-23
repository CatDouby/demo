
[toc]


- *host.docker.internal* 容器访问宿主机

### 配置

##### /etc/docker/daemon.json 镜像源等配置
```json
// /etc/docker/daemon.json
{
  "registry-mirrors": [
    "https://docker.mirrors.ustc.edu.cn",
    "https://reg-mirror.qiniu.com",
    "https://dockerhub.azk8s.cn"
  ]
}
```

##### 允许其它 docker 客户端远程到当前 docker 主机进行管理
- https://docs.docker.com/reference/cli/dockerd/

被连接的 docker 当前主机服务配置修改
```ini
; /lib/systemd/system/docker.service
; 增加服务启动的命令参数 -H tcp://0.0.0.0:2375 

ExecStart=/usr/bin/dockerd -H tcp://0.0.0.0:2375 

; 然后重启 docker 服务 systemctl daemon-reload
; systemctl restart docker
```

远程 docker 客户端连接
```sh
docker -H host:2375 <command>
docker -H host:2375 ps -a
# 或者直接通过设置环境变量，这样可以像操作本地 docker 一样
export DOCKER_HOST="tcp://host:2375"
docker ps 
```


### Info
- hub.docker.com #docker官方的镜像
- https://docs.docker.com/get-started/#docker-concepts
- [这可能是最为详细的Docker入门总结](http://dockone.io/article/8350) ::dockone.io
- [docker中文](http://www.docker.org.cn/) ::docker.org.cn
- [PHP 开发者的 Docker 之旅](http://guide.daocloud.io/dcs/php-docker-9153862.html) ::guide.daocloud.io
- docker 配置文件: Dockerfile  中记录了容器构建过程，可在集群中实现快速分发和快速部署。
- docker 不只是 docker，它还指它的容器生态系统
- docker 后台配置文件 **/etc/docker/daemon.json** 修改后需要重启。systemctl daemon-reload && systemctl restart docker。
- **容器**: *registry, nexus, linuxkit, busybox, alpine, scratch, ubuntu*
- `docker history xxImage` 查看镜像构建过程，类似 Dockerfile 格式

#### Image 镜像 :

> 镜像可以看作是一个特殊的文件系统，除了提供容器运行时所需的程序、库、资源、配置等文件外，还包含了一些为运行时准备的一些配置参数（如匿名卷、环境变量、用户等）。  
镜像不包含任何动态数据，其内容在构建之后也不会被改变。**镜像（Image）就是一堆只读层**（read-only layer）的统一视角。  
为了加速镜像构建、重复利用资源，Docker 会利用  **中间层镜像**。所以在使用一段时间后，可能会看到一些依赖的中间层镜像。默认的 docker image ls 列表中只会显示顶层镜像，如果希望显示包括中间层镜像在内的所有镜像加 -a 参数

#### Container 容器 :
> 实际上，**容器 = 镜像 + 顶层的读写层**。

#### Repository 仓库 :
> 一个 Docker Registry 中可以包含多个仓库（Repository），每个仓库可以包含多个标签（Tag），每个标签对应着一个镜像，一般以版本作为 Tag。可以通过<仓库名>:<标签> 来指定具体镜像的版本。如果不给出标签，将以 Latest 作为默认标签。  
仓库分为 Public, Private 两种。

#### volume 数据卷 :
- 数据卷 可以在容器之间共享和重用
- 对 数据卷 的修改会立马生效，不会影响镜像
- 数据卷 默认会一直存在，即使容器被删除
- 数据卷 的使用，类似于 Linux 下对目录或文件进行 mount，镜像中的被指定为挂载点的目录中的文件会隐藏掉，能显示看的是挂载的 数据卷。
- 命名卷，生命周期不受挂载到的容器影响，有明确的主机存储路径。 匿名卷，容器删除后随之被删除。

```
docker volume create my-vol # 创建一个数据卷(命名卷)
docker volume ls # 查看所有数据卷
docker volume inspect vol_name # 查看指定数据卷名称的挂载情况
docker volume prune # 清理无用的卷
--volumns-from 可以从指定的容器挂载
# ro只读 rw可读可写
docker run -d -it --name xxredis -v xxdir:/data/xx:ro redis
```


#### tips :
- 容器内的文件修改会反映在容器层存储层，可使用 *docker diff container* 查看
- 删除镜像必须先删除引用的容器，删除容器必须先停止这个容器

### 常用命令 :
```sh
docker ps       #查看正在运行的容器

docker rm $(docker ps -a -q) #删除所有容器
docker images   #查看所有镜像
docker rmi imgA #删除镜像
docker image rm imgA imgB #删除镜像
docker image rm repository@digest #根据摘要精确删除，digest的值可以通过 docker image ls --digests 获取到
docker image inspect xxx #查看镜像信息

docker search someimage # search的镜像默认是dockerhub里的镜像，可使用过滤参数
docker search --filter stars=100 --filter is-official=true keyword

docker stats xxx #查看容器状况
docker top xxx  # 查看容器内进程情况，类似于 top 命令

docker inspect container #查看镜像/容器详细信息
# --format '{{.Mounts}}' e1f47 
# --format '{{.Config.Volumes}}' e1f47 
# format 参数使用了 go 模板的语法。 符号 . 表示当前对象，这样可以精准的获取 inspect 内容

docker diff container #查看容器已变更文件

docker tag docker/whalesay yhuafu/whalesay #为指定镜像产生新的tag
docker login
docker logout
docker container prune
docker image prune
docker volume prune
docker system df
docker system prune


docker push yhuafu/whalesay #上传发布镜像

docker exec -it rabbitmq /bin/bash #进入容器
# docker attach containerID attach可能造成操作阻塞，不适合生产环境


docker logs containerID # 容器运行日志
# 复制容器内文件到外部主机。交互主机和容器顺序可以更换复制方向。
docker cp containerID:/filepath /host/filepath 
```

&nbsp;
#### docker update :
- 用于更新容器配置项。如：`docker update containerID --restart=always` 运行容器重启后自动运行。
- update 不能更新映射的端口。



### 构建镜像

#### docker build 构建镜像
```sh
# 见 Dockerfile
docker build -f /path/dockerfile -t foo:1.0 .
```
#### docker commit 构建镜像
> 提交容器的变更并保存，生成新的镜像。配合 docker save 将镜像保存为文件，方便传输。

```sh
docker commit -a 'authorName' -m 'comment' containerID newImageName:v1.0.0

# 保存镜像到文件
# docker save -o dockerImgFile.tar imgName:v1.0

# 将镜像文件加载还原为镜像
# docker load -i dockerImgFile.tar
```

### run 运行
- 如果 run 的时候不指定镜像对应 tag，默认会把 tag 认为 latest。如果本地已经具有 stable 版，而没有 latest，那么 docker 仍然回去下载 latest 的镜像。
- --link 指定的源容器名和容器别名都可以在运行的本容器中作为 hostname 访问。
- --privileged 获取主机的完整权限，包括 root权限。
```
-d                  #让容器运行在后台
-e PASSWORD=996996  #设置环境变量
-p 127.0.0.1:5678:80            #端口映射 主机IP:主机端口:容器端口
-p 10.0.2.12::8081 # 将主机任意端口绑定到容器 8081 
-v /var/nginx/www/:/var/www/    #目录映射
-w --workdir        #容器工作目录
--name cs_nginx     #容器名称
--link cs_phpfpm:phpfpm         #容器之间建立联系，这样可以在当前容器使用另一个容器的服务 cs_phpfpm是容器名，phpfpm是别名
--restart           #重启 docker 时自动重启容器
-t                  #在容器里面生产一个伪终端
-i                  #对容器内的标准输入（STDIN）进行交互
-m 200M | --memory 300M #设置内存使用限额
--rm                #容器停止运行后，自动删除容器文件
--network netA

docker run -it --name nginx-t1 -d nginx:latest /bin/bash

# 指定时区环境变量
docker run -e TZ=Asia/Shanghai xx-image
```

---

#### 管理 :
- docker-compose , docker swarm, kubernets 都是管理工具
- docker-compose 多容器支持, 明显的缺点是**只能管理当前主机**。docker-compose.yml 配置文件, 配置指明了多个容器的协作关系、依赖等。对单个容器指明了镜像、容器如何运行(build/其他)、环境变量、网络端口


&nbsp;
#### 容器通信 :


##### docker network
```sh
# 创建一个局域网
docker network create webnet

# 运行容器 A 并加入到局域网
docker run -d --network webnet --network-alias neta containerA
# 运行容器 B 并加入到局域网
docker run -d --network webnet --network-alias netb containerB
#  在容器 A 中 ping netb 可以正常响应，在 B 中 ping neta 也可。
```

&nbsp;
##### --link :
- **link** 关联的容器相当于在它们之间创建了一个虚拟通道，而不用映射它们的端口到宿主机上。
- 互联的容器之间是可以 ping 通的。
- 除了环境变量之外，Docker 还添加 host 信息到父容器的 /etc/hosts 文件
```sh
docker run --link containerName:linkAlias

docker network create -d bridge netA # 创建一个网络
docker run -it --name box1 --network netA mybox sh # 运行时指定网络
docker run -it --name box2 --network netA mybox sh # 不同容器加入共同网络

run --hostname=HOSTNAME # hostname 写到容器内的 /etc/hostname 和 /etc/hosts

# DNS 可在 daemon.json 中进行配置
run --dns=8.8.8.8 # dns写到容器 /etc/resolv.conf
```

#### alpine 容器的 apk 包管理
> 基于 alpine 的容器默认使用 apk 作为包管理工具

##### 设置包国内镜像源
```sh
# https://mirrors.aliyun.com/alpine/v3.13/main
# https://mirrors.aliyun.com/alpine/v3.13/community
# https://mirrors.ustc.edu.cn/alpine/v3.4/main

# 安装包时指定镜像源
apk add xx --update-cache --repository https://mirrors.ustc.edu.cn/alpine/v3.4/main --allow-untrusted

# 直接修改镜像源配置文件
sed -i 's@dl-cdn.alpinelinux.org@mirrors.aliyun.com@g' /etc/apk/repositories
```

常用命令
```sh
apk search memcache
apk info
apk update      # 更新本地镜像源
apk upgrade     # 更新升级软件
apk add xx      # 安装xx软件
apk add -u xx   # 更新指定软件
```

### 安装 

#### 通过 get-docker.sh
```sh
curl -fsSL https://get.docker.com -o get-docker.sh

wget https://get.docker.com/gpg
rpmkeys --import ./gpg

sh get-docker.sh

# 安装后可能服务没有启动
# systemctl restart docker.service
# systemctl daemon-reload

# 安装后的配置文件需要手动添加
vi /etc/docker/daemon.json
```

#### 使用阿里云 repo 安装
- https://developer.aliyun.com/mirror/docker-ce

```sh
yum install -y yum-utils device-mapper-persistent-data lvm2
yum-config-manager --add-repo https://mirrors.aliyun.com/docker-ce/linux/centos/docker-ce.repo
sed -i 's+download.docker.com+mirrors.aliyun.com/docker-ce+' /etc/yum.repos.d/docker-ce.repo
yum makecache fast
yum -y install docker-ce
service docker start
```