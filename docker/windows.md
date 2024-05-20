
[toc]

- docker-desktop 安装时选择推荐的 WSL2；启动后设置 Resources > WSL integration 选项中启用已经安装的 Ubunt 子系统

#### WSL 下 docker-desktop 使用
> docker-desktop 在休眠模式下时， wsl 子系统中不能识别 docker 命令，重新打开 docker-desktop 工作界面或者运行一个容器即可避免休眠。

```sh
# 修改 docker 镜像源
# 修改 docker 在 wsl 的默认存储位置

# docker 安装后默认创建两个子系统
wsl -l -v
#docker-desktop：内是docker相关程序
#docker-desktop-data: 保存的镜像
# 备份为tar包到当前目录
wsl --export docker-desktop docker-desktop.tar
wsl --export docker-desktop-data docker-desktop-data.tar
# 删除原来的
wsl --unregister docker-desktop
wsl --unregister docker-desktop-data
# 导入tar包到新的路径 D:\data\docker\wsl
wsl --import docker-desktop D:\data\docker\wsl\docker-desktop docker-desktop.tar
wsl --import docker-desktop-data D:\data\docker\wsl\docker-desktop-data docker-desktop-data.tar
```

```sh
# 指定挂载路径时，宿主机的挂载路径可以直接使用windows上的盘符路径
# docker run --name micro01 -v D:\dev\www\hyperf-micro:/data/project -p 9511:9501 --privileged -u root -it --entrypoint /bin/sh hyperf/hyperf:8.0-alpine-v3.15-swoole
docker run --name hpf01 -v D:\dev\www\hyperf-micro:/data/project -p 9501:9501 --privileged -u root -it --entrypoint /bin/sh hyperf/hyperf:7.4-alpine-v3.11-swoole
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

```sh
# consul 部署
docker run -d -p 8500:8500 -v D:\data\consul:/consul/data --restart=always --name=consul consul agent -server -bootstrap -ui -client='0.0.0.0'
```