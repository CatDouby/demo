
- https://aria2.github.io/manual/en/html/aria2c.html  
- [windows 下 aria2配置]()
- https://www.moerats.com/tag/Aria2/  
- [如何使用 aria2 及 webui-aria2 下载百度云资源](http://moflying.com/2016/06/05/%E5%A6%82%E4%BD%95%E4%BD%BF%E7%94%A8aria2%E5%8F%8Awebui-aria2%E4%B8%8B%E8%BD%BD%E7%99%BE%E5%BA%A6%E4%BA%91%E8%B5%84%E6%BA%90/)
- Aria2 的 release 包 [https://github.com/aria2/aria2/releases](https://github.com/aria2/aria2/releases)  , https://github.com/persepolisdm/persepolis/releases  
- 加速bt或磁力下载的 trackerslist: https://github.com/ngosang/trackerslist

##### 管理界面
- http://aria2c.com/
- 图形版: https://persepolisdm.github.io/  


#### 安装
```sh
touch aria2.log aria2.conf aria2.session
```

#### 命令行使用
```sh
# --conf-path     #指定配置文件路径 
# --no-conf       #禁止使用配置文件
# --gid           #指定任务gid

# 从多个主机下载同一文件
aria2c -P "http://{host1,host2,host3}/file.iso"

# 按一定规律下载
aria2c -Z -P "http//host/image[000-100].png"
aria2c -Z -P "http//host/image[A-Z:2].png"

# 限速下载
aria2c --max-download-limit=100K file.metalink

# 验证校验和
aria2c --checksum=sha-1=0192ba11326fe2298c8cb4de616f4d4140213837 http://example.org/file

# 只下载.torrent文件
aria2c --follow-torrent=false "http://host/file.torrent"

#使用cookie
aria2c --load-cookies = cookies.txt "http://host/file.zip"

```

```cmd
// windows下后台执行:
CreateObject("WScript.Shell").Run "F:\dev\services\aria2\aria2c.exe --conf-path=aria2.conf",0

// windows下关闭:
Taskkill /F /IM aria2c.exe

// 运行文件:
start Start.vbs

// 命令行指定下载
aria2c.exe --conf-path=D:\dev\services\aria2\aria2.conf
```

&nbsp;
##### DHT支持
Unless the legacy file paths *$HOME/.aria2/dht.dat* and *$HOME/.aria2/dht6.dat* are pointing to existing files, the routing table of IPv4 DHT is saved to the path *$XDG_CACHE_HOME/aria2/dht.dat* and the routing table of IPv6 DHT is saved to the path *$XDG_CACHE_HOME/aria2/dht6.dat*.

&nbsp;
##### Netrc支持
Netrc support is enabled by default for HTTP(S)/FTP/SFTP. To disable netrc support, specify --no-netrc option. Your .netrc file should have correct permissions(600).

If machine name starts ., aria2 performs domain-match instead of exact match. This is an extension of aria2. For example of domain match, imagine the following .netrc entry:

```
machine .example.org login myid password mypasswd
```

&nbsp;
##### Input File
The input file can contain a list of URIs for aria2 to download. You can specify multiple URIs for a single entity: separate URIs on a single line using the TAB character.
```
http://server/file.iso http://mirror/file.iso
  dir=/iso_images
  out=file.img
http://foo/bar
```
If aria2 is executed with -i uri.txt -d /tmp options, then file.iso is saved as /iso_images/file.img and it is downloaded from http://server/file.iso and http://mirror/file.iso. The file bar is downloaded from http://foo/bar and saved as /tmp/bar

```ini
dir=F:\down
log=F:\dev\services\aria2\aria2.log
input-file=F:\dev\services\aria2\aria2.session
save-session=F:\dev\services\aria2\aria2.session

save-session-interval=60
force-save=true
log-level=error

# see --split option
max-concurrent-downloads=5
continue=true
max-overall-download-limit=0
max-overall-upload-limit=50K
max-upload-limit=20

# Http/FTP options
connect-timeout=120
lowest-speed-limit=10K
max-connection-per-server=10
max-file-not-found=2
min-split-size=1M
split=5
check-certificate=false
http-no-cache=true

# FTP Specific Options

# BT/PT Setting
bt-enable-lpd=true
#bt-max-peers=55
follow-torrent=true
enable-dht=true
enable-dht6=false
enable-peer-exchange=true
bt-seed-unverified
rpc-save-upload-metadata=true
bt-hash-check-seed
bt-remove-unselected-file
bt-request-peer-speed-limit=100K
seed-ratio=0.0

# Metalink Specific Options

# RPC Options
enable-rpc=true
pause=false
rpc-allow-origin-all=true
rpc-listen-all=true
rpc-save-upload-metadata=true
rpc-secure=false

# Advanced Options
daemon=true
disable-ipv6=true
enable-mmap=true
file-allocation=falloc
max-download-result=120
#no-file-allocation-limit=32M
force-sequential=true
parameterized-uri=true

bt-tracker=udp://tracker.open-internet.nl:6969/announce,udp://tracker.coppersurfer.tk:6969/announce,udp://exodus.desync.com:6969/announce,udp://tracker.opentrackr.org:1337/announce,udp://tracker.internetwarriors.net:1337/announce,udp://9.rarbg.to:2710/announce,udp://public.popcorn-tracker.org:6969/announce,udp://tracker.vanitycore.co:6969/announce,udp://mgtracker.org:6969/announce,udp://tracker.tiny-vps.com:6969/announce,udp://tracker.torrent.eu.org:451/announce,udp://tracker.cypherpunks.ru:6969/announce,udp://thetracker.org:80/announce,udp://open.stealth.si:80/announce,udp://bt.xxx-tracker.com:2710/announce,udp://tracker.uw0.xyz:6969/announce,udp://tracker.iamhansen.xyz:2000/announce,udp://retracker.lanta-net.ru:2710/announce,http://t.nyaatracker.com:80/announce,http://retracker.telecom.by:80/announce
```