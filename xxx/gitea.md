

> 1. Gitea 可以不用专门安装 MySQL 或者 Postgres，选择 Sqlite3 会自动嵌入安装。
> 2. 安装后的设置界面可以先设置好管理员的账号密码，不用走注册流程（若不设置，注册的第一个账号被视为管理员账号）。
> 3. 截至目前 v1.21.11，CI/CD 依赖于 docker 容器，实施麻烦，不如直接使用 webhook+shell 实现部署。

### 与 PostgreSql docker-compose 部署
- SSH 访问配置比较麻烦，一般不用，因为代码仓库一般情况下也是组织内部专网访问。
- 启用SSH时，容器内 22 端口映射到外部的不需要宿主机外部访问，故配成本机ip即可。

```sh

```

##### docker-compose.yml :
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

  runner:
    image: gitea/act_runner:nightly
    environment:
      CONFIG_FILE: /config.yaml
      GITEA_INSTANCE_URL: "${INSTANCE_URL}"
      GITEA_RUNNER_REGISTRATION_TOKEN: "${REGISTRATION_TOKEN}"
      GITEA_RUNNER_NAME: "${RUNNER_NAME}"
      GITEA_RUNNER_LABELS: "${RUNNER_LABELS}"
    volumes:
      - /data/docker-volume/gitea-runer:/data
      - /var/run/docker.sock:/var/run/docker.sock
      # - ./config.yaml:/config.yaml
    networks:
      - gitea
    deponds-on:
      - server
```

#### runer
```sh
# 生成 runner 配置模板
act_runner generate-config > config.yml
# runner 向 gitea 注册后，在 runner 目录会生成一个 .runner 的注册信息文件。
```

### CI/CD
> 基于 Gitea Actions 实现。在项目目录内创建 actions 文件夹，然后添加配置文件 main.yml 配置工作流。
> Gitea Actions 需要 act runner 来运行 Job。 为了避免消耗过多资源并影响Gitea实例，建议在 Gitea 以外的机器上启动 Runner。 

- 文档 https://docs.gitea.com/zh-cn/usage/actions/quickstart
- 下载 https://dl.gitea.com/act_runner/
- [workflows](https://docs.github.com/zh/actions/using-workflows/about-workflows) ::docs.github.com
- [workflow action 语法](https://docs.github.com/zh/actions/using-workflows/workflow-syntax-for-github-actions) ::docs.github.com
- runner 有全局、组织、仓库 三种级别。


##### .gitea/workflows/main.yaml
```yml
# github 对应的是在 .github/workflows 目录

name: ci-workflow

# events: push,pull_requst,workflow_run,
# on: [push,pull_request] 不用 branches 过滤时可以写多个分支
on:
  push:
    branches:
      - test
  pull_request:
    branches:
      - test

env:
  AUTHOR: foo

jobs:
  # 工作名称可以任意取，不重复即可
  deploy:
    # if: gitea.event.name == 'xxx'
    runs-on: ubuntu-latest
    # timeout-seconds: 60
    steps:
      - run: 'echo "auhtor: ${{env.AUTHOR}}"'
      - name: checkout test
        # uses语句相当于执行命令的 run，用来执行 action 功能
        uses: actions/checkout@v3
        # with语句用来指定执行 action 的参数
        with:
          path: foo-deploy
          repository: admini/mini-app-shop
          # ref可以是分支、标签、某次提交的hash
          ref: release-v0.1
          github-server-url: 'http://47.109.128.35:8083'
      - name: deploy
        uses: xxx/yyy
```

##### docker run runner
```sh
# -v $(pwd)/config.yaml:/config.yaml \
# -e CONFIG_FILE=/config.yaml \

docker run --name runner_raw --network=gitea_gitea \
    -v /var/run/docker.sock:/var/run/docker.sock \
    -v /work/project/deploy:/data \
    -e GITEA_INSTANCE_URL=172.22.0.3:3000 \
    -e GITEA_RUNNER_REGISTRATION_TOKEN=7pkysp7N4zO0k2Kpv3ia5xbudTGh8C6rHDPmPB6a \
    -e GITEA_RUNNER_NAME=docker-runner \
    -d gitea/act_runner
```

##### actions
- https://gitea.com/actions/
- https://github.com/actions 
- https://github.com/sdras/awesome-actions
- actions/checkout 切换分支
- actions/download-artifact 下载
- actions/cache 缓存部署过程中依赖的外部文件

##### 内置变量
```ini
 = ${{ gitea.actor }}
; = ${{ github.workflow }}
 = ${{ gitea.workspace }}
事件名称 = ${{ gitea.event_name }}
仓库 = ${{ gitea.repository }}


当前分支 = ${{ gitea.ref }}
工作目录 = ${{ gitea.workspace }}
任务状态 = ${{ job.status }}
```