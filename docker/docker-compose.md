
> **docker-compose.yml**  多个容器相互配合来完成某项任务。Compose  是 Docker 官方的开源项目，使用 Python 编写。  
> 默认管理对象是项目，通过子命令对项目中的一组容器进行便捷地生命周期管理。

- **docker-composer** 需要单独安装。
- **service** 一个应用的容器，实际上可以包括若干运行相同镜像的容器实例。
- **project** 由一组关联的应用容器组成的一个完整业务单元，在 docker-compose.yml 文件中定义。
- **depends_on** 可以用来控制部署顺序，被依赖的服务会先安装


&nbsp;
```yml
version: "3.3"
services:
    nginx:
        image: nginx:latest
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

#### php & nginx & redis
```yml
version: "3.3"
networks:
  php-work:
    driver: bridge
services:
    redis:
        container_name: redis7.2
        image: redis:7.2
        ports:
            - 6379:6379
        volumes:
            - "/work/conf/redis.conf:/etc/redis/conf/redis.conf"
            - "/data/storage/redis/data:/data"
        environment:
            - REDIS_PASSWORD="123abc678"
        command: ["redis-server", "/etc/redis/conf/redis.conf"]
        networks:
            - php-work
    php:
        container_name: php7.4fpm
        image: php:7.4-fpm
        ports:
            - 9000:9000
        volumes:
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
        ports:
            - 8080:80
        volumes:
            - "/work/conf/nginx.conf.d:/etc/nginx/conf.d"
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
```