
### 端口转发

#### tcp 转发
> stream 与 http 模块同级，是一种流量转发。需要安装 stream 模块。多数情况下 http 转发的场景，用 tcp 转发也是可以的。
> proxy_pass 转发语句的时候与 http 不同，不需要带上协议。
```
stream {
    upstream gitea {
        server 127.0.0.1:3000;
    }
    server {
        listen 8083;
        proxy_pass gitea;
    }
}
```

#### http 转发
```
http {
    upstrem webapi {
        server 192.168.1.5:80;
    }
    server {
        listen 80;
        location / {
            proxy_pass http://webapi;
            proxy_set_header Host $host;
            proxy_set_header X-Real-IP $remote_addr;
            proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
            proxy_set_header X-Forwarded-Proto $scheme;
        }
    }
}
```


### Nginx 转发到 PHP 的几种方式
```
fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
fastcgi_pass 127.0.0.1:9000;
fastcgi_pass php:9000;  # docker
```


### Laravel 项目配置
```
server {

    # listen 80;
    index index.php index.html;
    root /work/project/abc.com/public;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
 
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass php:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```