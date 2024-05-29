

### VSCode
*   文档 <https://code.visualstudio.com/docs#vscode>
*   快捷键 <https://code.visualstudio.com/shortcuts/keyboard-shortcuts-windows.pdf>

##### 插件 :

*   **Auto Close Tag** , html 或 xml 自动闭合标签。
*   **PHP Intelephense** PHP代码提示工具，支付代码提示、查找定义、类搜索等功能
*   **PHP Debug Adapter for Visual Studio Code** , 基于 XDebug 的 PHP 调试扩展。
*   **Go for Visual Studio Code** , Golang 语法提示。
*   **REST client**
*   **Draw\.io**
*   **Windows opacity**
*   **Chinese (Simplified) Language Pack** , 中文语言包。
*   **ChatGPT GPT-4 - Bito AI Code Assistant** AI代码生成
*   **TONGYI Lingma** 阿里AI代码
*   **Remote SSH** 打开远程文件夹进行编辑

```js
// setting.json
{
    "editor.wordWrap": "off",
    "editor.indentSize": "tabSize",
    "editor.tabSize": 4,
    "php.validate.executablePath": "D:/dev/install/phpstudy_pro//php.exe",
    "php-docblocker.extra": [
        "@api {get} /mch_api/order/",
    ],
    "files.autoSave": "off",
    "winopacity.opacity": 220,
    "eslint.autoFixOnSave": true,
}
```

```js
{
    "php-docblocker.extra": [
        "@copyright",
        "@author fuyh <fuyh@jvtd.cn>",
        "@datetime $CURRENT_YEAR-$CURRENT_MONTH-$CURRENT_DATE $CURRENT_HOUR:$CURRENT_MINUTE",
        
        "@api {get} /mch_api/integralmall/integralmall/",
        "@apiDescription desc",
        "@apiGroup  Merchant-Integralmall",
        "@apiPermission merchant",
        "@apiVersion 2.0.0",
        "@apiParam {string} id",
        "@apiSuccessExample {json} Success-Response: \n{}"
    ]
}
```

新建 api.http 文件，文件内编写需要执行 rest client 插件发送的请求 :
```
    ### rest 方式请求
    GET {{host}}{{base}}/slides?store_id=1 HTTP/1.1

    ### curl 方式请求。请求间以 3 个 # 分割
    curl -X POST {{host}}{{base}}/user-save
    content-type: {{contentType}}

    {
        "name":"Hendry",
        "salary":"61888",
        "age":"26"
    }
```
##### 下载缓慢问题 :

> 在下载页面获取到的地址为 `https://az764295.vo.msecnd.net/stable/xx.zip
> 将域名 az764295.vo.msecnd.net 替换为 vscode.cdn.azure.cn`

---

### Sublime Text 3

#### 插件：
- HTML相关插件 **Emmet**
- 选区增强插件 **MultiEditUtils** 
- 代码格式化 **CodeFormatter** / **SublimeAStyleFormatter** 
- 语言分析增强 **ApplySyntax**
- Doc注释 **DocBlocker**
- Git实时改动对比 **GitGutter**
- 打开cmd命令窗 **SublimeCodeIntel**
- 代码提示、自动补全 **Ctags**
- 调色盘 **ColorPicker**
- 半透明插件 **SublimeTextTrans**
- Go 代码提示、运行 **GoSublime** https://github.com/DisposaBoy/GoSublime
- Go 编译、运行 **Golang Build**

#### 配置：
```js
// 个性化设置 : Preference.user.default
{
    "color_scheme": "Monokai.sublime-color-scheme",
    "atomic_save": true,
    "auto_complete_cycle": true,
    "auto_complete_with_fields": false,
    "font_size": 12,
    "line_numbers": true,
    "highlight_line": true,
    "save_on_focus_lost": true,
    "tab-size": 4,
    "translate_tabs_to_spaces": true,
    "index_files": true,
    "folder_exclude_patterns": [".svn",".git",".hg","CVS","bin","phpserver","setup"],
    "index_exclude_patterns": ["*.log","*.css","*.phtml","D:\\dev\\xxx\\vendor\\**"],
}
// 以上几项 patterns 的配置 路径都是相对于当前打开的目录
//   在项目文件较多时，排除不需要建立索引的文件和目录可以降低CPU消耗。


// DocBlocker :
{
    "jsdocs_extra_tags":["@Author yinhua.fu","@DateTime {{datetime}}","@copyright"]
}

// GitGutter :
{
	"git_binary":"D:/dev/install/Git/cmd/git.exe"
}

// Terminal.sublime-settings:
{
    // 不给参数时默认调用 Windows的 PowerShell
    "terminal": "C:\\Windows\\System32\\cmd.exe",
}

// build system 配置:
{
    "cmd": ["F:/dev/install/conda/python.exe","-u","$file"],
    "env":{
        "PYTHONPATH": "$project_path"
    },
    "file_regex": "^[ ]*File \"(...*?)\", line ([0-9]*)",
    "selector": "source.python",
    "encoding": "utf-8"
}
{
    "shell_cmd": "export PYTHONPATH=\"$project_path\"; python -u \"$file\"",
    "windows":
    {
        "shell_cmd": "set \"PYTHONPATH=$project_path\" & python -u \"$file\"",
    },
    "file_regex": "^[ ]*File \"(...*?)\", line ([0-9]*)",
    "selector": "source.python"
}
```

#### snippet
> 编辑后的代码片段目录在："插件包目录/User" 下，后缀 ".sublime-snippet"
```xml
<!-- thinkphp ajax return -->
<snippet>
    <content><![CDATA[
\$ret = array('status'=>0, 'info'=>'');
$1
\$this->ajaxReturn(\$ret, 'json');
]]></content>
    <tabTrigger>ret</tabTrigger>
    <scope>source.php</scope>
    <description>ajaxReturn</description>
</snippet>


<!-- thinkphp if condition template -->
<snippet>
    <content><![CDATA[<if condition="${1}">
<else/>
</if>
]]></content>
    <tabTrigger>ifc</tabTrigger>
    <scope>text.html</scope>
    <description>if condition=""</description>
</snippet>
```

```xml
<!-- Golang for range -->
<snippet>
    <content><![CDATA[for k,v := range "${1}" {
        
}
]]></content>
    <tabTrigger>forr</tabTrigger>
    <scope>source.go</scope>
    <description>if condition=""</description>
</snippet>
```
