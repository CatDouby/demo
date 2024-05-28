


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
```

##### config.yml
```yml
log:
  # trace, debug, info, warn, error, fatal
  level: info

runner:
  # runner的注册信息存放的文件
  file: .runner
  # 执行任务的并发数
  capacity: 1
  # 执行任务时带入的环境变量
  envs:
    A_TEST_ENV_NAME_1: a_test_env_value_1
    A_TEST_ENV_NAME_2: a_test_env_value_2
  # 执行任务时带入的环境变量配置，文件不存在时自动忽略
  env_file: .env
  # 执行任务超市时间，不应该超过 Gitea 实例自身的超时时间。如果 Gitea 实例自身超时，任务也会被终止执行。
  timeout: 3h
  # Whether skip verifying the TLS certificate of the Gitea instance.
  insecure: false
  # The timeout for fetching the job from the Gitea instance.
  fetch_timeout: 5s
  # The interval for fetching the job from the Gitea instance.
  fetch_interval: 2s
  # The labels of a runner are used to determine which jobs the runner can run, and how to run them.
  # Like: "macos-arm64:host" or "ubuntu-latest:docker://gitea/runner-images:ubuntu-latest"
  # Find more images provided by Gitea at https://gitea.com/gitea/runner-images .
  # If it's empty when registering, it will ask for inputting labels.
  # If it's empty when execute `daemon`, will use labels in `.runner` file.
  labels:
    - "ubuntu-latest:docker://gitea/runner-images:ubuntu-latest"
    - "ubuntu-22.04:docker://gitea/runner-images:ubuntu-22.04"
    - "ubuntu-20.04:docker://gitea/runner-images:ubuntu-20.04"
cache:
  # Enable cache server to use actions/cache.
  enabled: true
  # The directory to store the cache data.
  # If it's empty, the cache data will be stored in $HOME/.cache/actcache.
  dir: ""
  # The host of the cache server.
  # It's not for the address to listen, but the address to connect from job containers.
  # So 0.0.0.0 is a bad choice, leave it empty to detect automatically.
  host: ""
  # The port of the cache server.
  # 0 means to use a random available port.
  port: 0
  # The external cache server URL. Valid only when enable is true.
  # If it's specified, act_runner will use this URL as the ACTIONS_CACHE_URL rather than start a server by itself.
  # The URL should generally end with "/".
  external_server: ""

container:
  # Specifies the network to which the container will connect.
  # Could be host, bridge or the name of a custom network.
  # If it's empty, act_runner will create a network automatically.
  network: ""
  # Whether to use privileged mode or not when launching task containers (privileged mode is required for Docker-in-Docker).
  privileged: false
  # And other options to be used when the container is started (eg, --add-host=my.gitea.url:host-gateway).
  options:
  # The parent directory of a job's working directory.
  # NOTE: There is no need to add the first '/' of the path as act_runner will add it automatically. 
  # If the path starts with '/', the '/' will be trimmed.
  # For example, if the parent directory is /path/to/my/dir, workdir_parent should be path/to/my/dir
  # If it's empty, /workspace will be used.
  workdir_parent:
  # Volumes (including bind mounts) can be mounted to containers. Glob syntax is supported, see https://github.com/gobwas/glob
  # You can specify multiple volumes. If the sequence is empty, no volumes can be mounted.
  # For example, if you only allow containers to mount the `data` volume and all the json files in `/src`, you should change the config to:
  # valid_volumes:
  #   - data
  #   - /src/*.json
  # If you want to allow any volume, please use the following configuration:
  # valid_volumes:
  #   - '**'
  valid_volumes: []
  # overrides the docker client host with the specified one.
  # If it's empty, act_runner will find an available docker host automatically.
  # If it's "-", act_runner will find an available docker host automatically, but the docker host won't be mounted to the job containers and service containers.
  # If it's not empty or "-", the specified docker host will be used. An error will be returned if it doesn't work.
  docker_host: ""
  # Pull docker image(s) even if already present
  force_pull: true
  # Rebuild docker image(s) even if already present
  force_rebuild: false

host:
  # The parent directory of a job's working directory.
  # If it's empty, $HOME/.cache/act/ will be used.
  workdir_parent:
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