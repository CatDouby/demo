
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