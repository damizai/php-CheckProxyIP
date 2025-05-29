# PHP代理IP检测工具

## 简介

这是一个基于PHP开发的Cloudflare代理IP检测工具，可以帮助用户快速验证代理IP的有效性，并提供简洁的API接口。

## 功能特点

- ✅ 验证Cloudflare代理IP有效性
- 🔍 域名解析与批量IP检测
- 🌐 IP地理位置信息查询
- 🌓 支持暗色/亮色模式切换

## 部署方法

### 方法一：传统虚拟主机部署

1. 下载[index.php](cci:7://file:///c:/Users/BiuXin/Desktop/Files/index.php:0:0-0:0)和[proxyip.php](cci:7://file:///c:/Users/BiuXin/Desktop/Files/proxyip.php:0:0-0:0)文件
2. 上传到您的虚拟主机网站根目录
3. 修改[index.php](cci:7://file:///c:/Users/BiuXin/Desktop/Files/index.php:0:0-0:0)中的`$cfphptoken`变量
4. 通过浏览器访问您的网站

### 方法二：宝塔面板部署

1. 登录宝塔面板，创建站点
2. 上传[index.php](cci:7://file:///c:/Users/BiuXin/Desktop/Files/index.php:0:0-0:0)和[proxyip.php](cci:7://file:///c:/Users/BiuXin/Desktop/Files/proxyip.php:0:0-0:0)到网站根目录
3. 修改[index.php](cci:7://file:///c:/Users/BiuXin/Desktop/Files/index.php:0:0-0:0)中的`$cfphptoken`变量
4. 确保PHP版本≥7.4，并开启curl扩展

### 感谢CMLiu