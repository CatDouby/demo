

### 私有仓库

#### 部署
```sh
# 部署仓库直接用官方的 registry 镜像
docker pull registry
docker run -d -p 5000:5000 \
-v /data/docker-volume/registry:/var/lib/registry \
--privileged=true --name repo-registry registry


# 为 registry 仓库增加账户认证，认证成功的才能进行 push/pull
# 用 htpasswd 为给定的账户密码生成密码文件
# htpasswd -Bb -c /data/storage/htpasswd/userfoo userfoo abefg123
# -v /data/storage/htpasswd/userfoo:/htpasswd
# -e REGISTRY_AUTH=htpasswd \
# -e REGISTRY_AUTH_HTPASSWD_REALM=xx \
# -e REGISTRY_AUTH_HTPASSWD_PATH=xx \
# docker login 172.21.32.65:5000

# registry 服务添加到配置后打标签推送
# 打标签的方式必须要的格式： 私有仓库地址/镜像信息
docker tag alpine:latest 172.21.32.65:5000/alpine:latest
docker push 172.21.32.65:5000/alpine:latest
```

```js
// 将本机启动的 registry 服务加入到 insecure-registries 配置项
// /etc/docker/daemon.json 
{
  "registry-mirrors": [
    "https://registry.cn-hangzhou.aliyuncs.com",
    "https://hub-mirror.c.163.com",
    "https://docker.mirrors.ustc.edu.cn"
  ],
  "insecure-registries": [
    "172.21.32.65:5000"
  ]
}
```

