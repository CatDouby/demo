
#### Info
- 官网 https://go-zero.dev/
- 当前使用的 gozero 的 goctl 版本为 1.6.6

### 快速搭建单服务 API 和 CRUD 示例
```sh
# 先安装好 goctl
cd /project
goctl api new fooserver
cd fooserver
goctl model mysql datasource --table room_record --url 'root:root@tcp(127.0.0.1:3306)/testdb' --dir model/testdb
go mod init
go mod tidy

# 生成文件结构后主要是处理配置和数据库连接。改动文件
# fooserver.go
# internal/config/config.go
# internal/logic/fooserverlogic.go
```

```go
// ---------  fooserver.go
import (
	"fooserver/internal/config"
	"fooserver/internal/handler"
	"fooserver/internal/svc"

	"github.com/zeromicro/go-zero/rest"
)
func main() {
    // 使用 config.GetConfig("") 统一获取配置，初始时传入配置文件路径
    c := config.GetConfig("etc/fooserver-api.yaml")
	server := rest.MustNewServer(c.RestConf)
	defer server.Stop()

	ctx := svc.NewServiceContext(*c)
	handler.RegisterHandlers(server, ctx)

	fmt.Printf("Starting server at %s:%d...\n", c.Host, c.Port)
	server.Start()

    // 默认注册的路由 /from/:name 在文件 internal/handler/routes.go
}

// ---------  internal/config/config.go
type Config struct {
	rest.RestConf
	MysqlDsn string // 增加一项数据库配置
}
var c = Config{}
// 配置文件初始化
func _init(cfgFile string) {
	var f = flag.String("f", cfgFile, "the config file")
	conf.MustLoad(*f, &c)
}
// 获取配置
func GetConfig(cfgFile string) *Config {
	if c.Host == "" {
		_init(cfgFile)
	}
	return &c
}

// ---------  internal/logic/fooserverlogic.go
import (
    "math/rand"
    
	"fooserver/internal/config"
	"fooserver/internal/types"
	"fooserver/model/testdb"

	"github.com/zeromicro/go-zero/core/stores/sqlx"
)
func (l *Apiv1Logic) Apiv1(req *types.Request) (resp *types.Response, err error) {
    resp = &types.Response{}
    // 获取数据库配置，构造 db 连接
	conn := sqlx.NewMysql(config.GetConfig("").MysqlDsn + "/testdb?charset=utf8mb4&parseTime=true")
    // 使用模型并查询一条数据，写入到响应
    i := rand.Intn(2) + 1
	user := testdb.NewUserModel(conn)
	res, err := user.FindOne(l.ctx, int64(i))
	if err != nil {
		return nil, err
	}
	resp.Message = res.Name
    resp.Time = res.CreatedAt.Format("2006-01-02 15:04:06")

	return
}
```

相关 SQL
```sql
CREATE TABLE `user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(30) NOT NULL DEFAULT '',
  `level` tinyint(4) NOT NULL DEFAULT '1',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4;
INSERT INTO log_db.`user` (id, name, `level`, created_at) VALUES(1, 'cole', 1, '2024-07-08 18:04:01');
INSERT INTO log_db.`user` (id, name, `level`, created_at) VALUES(2, 'tee', 1, '2024-07-08 20:39:01');
```

配置文件 fooserver/etc/fooserver-api.yml
```yml
Name: fooserver-api
Host: 0.0.0.0
Port: 9001
MysqlDsn: root:root@tcp(127.0.0.1:3306)
```