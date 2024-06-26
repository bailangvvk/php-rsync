# PHP 文件同步工具

此项目是一个基于PHP的文件同步工具，能够从远程服务器获取文件信息并与本地目录进行对比，同步新增、修改和删除文件。~~它还会在本地目录中删除不存在的文件夹~~，并记录同步日志。

## 功能

- 从远程服务器获取文件列表及其修改时间
- 递归扫描本地目录以获取文件信息
- 比较本地和远程文件，进行文件的新增、修改和~~删除~~操作
- ~~删除本地不存在的空文件夹~~
- 返回文件同步日志

## 使用方法

1. 将项目克隆到本地或直接下载到本地目录。
2. 配置远程JSON数据的URL和日志文件路径。
3. ~~定时或者写个脚本间隔请求运行`sync_client.php`脚本进行文件同步。~~host要自己填写捏
4. 可以使用url http://待备份的主机/sync_client.php?password=修改成你的密码&host=下发备份文件主机

### 文件说明

- `sync_client.php`：主要的同步脚本，执行文件同步操作并记录日志。改：为原版添加了密码、白名单 有些虚拟主机没权限 目录遍历使用__DIR__替代 
- `sync_server.php`：在远程服务器上用于生成文件列表及其修改时间还有下载指定文件的脚本。改：为原版添加了密码 添加了白名单文件

### 注意事项

1. ~~若同步量较大，建议先手动进行文件同步后再运行脚本~~先同步一台机子 后续通过请求url请求将url的host=改成备份模范机
2. ~~推荐设置定时器每10秒请求一次同步脚本~~host要自己填写捏
3. ~~设置php最大脚本运行时间300秒以上效果更佳~~实测美国(客户端) 香港(服务端)全量同步10M左右项目文件大概需要5s
4. sync_server.php下载指定文件功能可被坏人利用，请修改文件名

### 实现原理

1.网站管理员请求url http://待备份的主机/sync_client.php?password=修改成你的密码&host=下发备份文件主机
2.待备份的主机(客户端)向 下发备份文件主机(服务端)请求服务端目录遍历后的json数据 客户端遍历目录下并转码json数据 比对
3.比对成功 且服务端的文件时间大于客户端 且在白名单内 则向服务端请求文件下载文件 有则覆写
