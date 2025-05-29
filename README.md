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

## 📝 使用方法

### 网页界面

直接访问你的 PHP 部署好的 地址，使用友好的网页界面进行检测：

```
https://check.liushen.pp.ua
```

### API 接口

#### 🔗 检查单个 ProxyIP

```bash
# 检查带端口的 IP
curl "https://check.liushen.pp.ua/index.php?path=check&proxyip=1.2.3.4:443"

# 检查不带端口的 IP（默认使用443端口）
curl "https://check.liushen.pp.ua/index.php?path=check&proxyip=1.2.3.4"

# 检查 IPv6 地址
curl "https://check.liushen.pp.ua/index.php?path=check&proxyip=[2001:db8::1]:443"

# 检查域名
curl "https://check.liushen.pp.ua/index.php?path=check&proxyip=example.com:443"
```

#### 📄 响应格式

```json
{
  "success": true,
  "proxyIP": "1.2.3.4",
  "portRemote": 443,
  "statusCode": 400,
  "responseSize": 1234,
  "timestamp": "2025-01-20T10:30:00.000Z"
}
```

#### 🔧 参数说明

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| `proxyip` | string | ✅ | 要检测的代理IP地址，支持IPv4、IPv6和域名 |

#### 📊 响应字段

| 字段 | 类型 | 说明 |
|------|------|------|
| `success` | boolean | 代理IP是否可用 |
| `proxyIP` | string | 检测的IP地址（失败时为 -1） |
| `portRemote` | number | 使用的端口号（失败时为 -1） |
| `statusCode` | number | HTTP状态码 |
| `responseSize` | number | 响应数据大小（字节） |
| `timestamp` | string | 检测时间戳 |

## 📄 许可证

本项目采用 MIT 许可证 - 查看 [LICENSE](LICENSE) 文件了解详情

#### 🙏 致谢

- [CMLiu](https://github.com/cmliu)
