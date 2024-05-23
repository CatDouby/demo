
- **docker-compose.yml**  多个容器相互配合来完成某项任务。Compose  是 Docker 官方的开源项目，使用 Python 编写。默认管理对象是项目，通过子命令对项目中的一组容器进行便捷地生命周期管理。
- **docker-composer** 需要单独安装。
- **service** 一个应用的容器，实际上可以包括若干运行相同镜像的容器实例。
- **depends_on** 可以用来控制部署顺序，被依赖的服务会先安装

```sh
# ls ./docker-compose.yml
docker-compose up -d
docker-compose down
docker-compose ps
docker-compose logs -f
docker-compose exec appx bash # bash|sh
```


```yml
# 目前的版本又 3.3 3.8 3.9
version: "3.3"
services:
    nginx:
        image: nginx:latest
        restart: always
        ports:
            - 80:80
        links:
            - appx
        volumes:
            - "/www/conf/nginx.conf.d:/etc/nginx/conf.d"
    appx:
        image: xx/app
        depends_on:
            nginx

# nginx 添加 appx 的连接后，访问 appx 只需要在 nginx.conf 配置:
# proxy pass http://appx:8080/;
```

### docker-compose 直接编排 php & nginx & redis
```sh
mkdir -p /work/conf/nginx.conf.d/ /work/conf/php/ /work/project/
touch /work/conf/php/php.ini
touch /work/conf/redis.conf
```

```yml
version: "3.3"
services:
    redis:
        container_name: redis7.2
        image: redis:7.2
        user: "root"
        ports:
            - 6379:6379
        volumes:
            - "/work/conf/redis.conf:/usr/local/etc/redis.conf"
            - "/data/storage/redis/data:/data"
        environment:
            - REDIS_PASSWORD="123abc678-x_y^z"
        command: ["redis-server", "/usr/local/etc/redis.conf"]
        networks:
            - php-work
    php:
        container_name: php8.2fpm
        image: php:8.2-fpm
        user: "root"
        ports:
            - 9000:9000
        volumes:
            - "/work/conf/php/php.ini:/usr/local/etc/php/php.ini"
            - "/work/project:/work/project"
        environment:
            - TZ="Asia/Shanghai"
        links:
            - redis
        depends_on:
            - redis
        networks:
            - php-work
    nginx:
        container_name: nginx1.20
        image: nginx:1.20
        user: "root"
        ports:
            - 8080:80
        volumes:
            - "/work/conf/nginx.conf.d:/etc/nginx/conf.d"
            - "/work/log/nginx:/var/log/nginx"
            - "/work/project:/work/project"
        environment:
            - TZ="Asia/Shanghai"
        links:
            - php
        depends_on:
            - php
        command: ["nginx", "-g", "daemon off;"]
        networks:
            - php-work
networks:
    php-work:
        driver: bridge
```

```sh
docker cp php7.4fpm:/usr/local/etc/php/php.ini-production /work/conf/php/php.ini
chmod -R a+w /work/project/abc.com/storage
```

```ini
; /usr/local/etc/redis.conf
bind 0.0.0.0
requirepass 123abc678-x_y^z
```


### docker-compose 配合 Dockerfile 编排
```sh
# 准要挂载的数据和配置目录或文件
mkdir -p /data/storage/redis /data/storage/mysql
mkdir -p /work/conf/nginx.conf.d/ /work/conf/php/
mkdir /work/project/

# 创建要加入到镜像的各软件目录
mkdir ./php ./nginx ./redis ./mysql
# 在各自软件目录下编辑对应的 Dockerfile
```

```yml
version: "3.8"
services:
    redis:
        ...
    mysql:
        build: ./mysql
        volumes:
            - "/data/storage/mysql:/var/lib/mysql"
        ports:
         - 3306:3306
    nginx:
        build: ./nginx
    ...
```

### gitea 部署
> POSTGRES_DB 这个是 postgres 的数据库运行实例名。 GITEA__database__NAME 是指具体的 shcema。
> postgres 的默认 数据库名是 postgres，schema需要先创建好。 所以需要的数据库信息为 POSTGRES_DB > GITEA__database__NAME
> 部署成功后，配置文件在容器的 /data/gitea/conf/app.ini


```yml
version: "3"

networks:
  gitea:
    external: false

services:
  server:
    image: gitea/gitea:1.21.11
    container_name: gitea
    environment:
      - USER_UID=1000
      - USER_GID=1000
      - GITEA__database__DB_TYPE=postgres
      - GITEA__database__HOST=db:5432
      - GITEA__database__NAME=gitea
      - GITEA__database__USER=foo
      - GITEA__database__PASSWD=foo_pwd123&okok
    restart: always
    networks:
      - gitea
    volumes:
      - /data/docker-volume/gitea:/data
      - /etc/timezone:/etc/timezone:ro
      - /etc/localtime:/etc/localtime:ro
    ports:
      - "3000:3000"
      - "127.0.0.1:222:22"
    depends_on:
      - db

  db:
    image: postgres:16
    container_name: postgres16
    restart: always
    environment:
      - POSTGRES_USER=foo
      - POSTGRES_PASSWORD=foo_pwd123&okok
      - POSTGRES_DB=gitea
    networks:
      - gitea
    volumes:
      - /data/docker-volume/postgres:/var/lib/postgresql/data
    ports:
        - "5432:5432"
```