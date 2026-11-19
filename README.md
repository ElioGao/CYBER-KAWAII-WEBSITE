# 🌸 CYBER KAWAII // 赛博可爱风个人主页与扩列作品集

[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-777BB4?style=flat-square&logo=php)](https://www.php.net/)
[![Database](https://img.shields.io/badge/MySQL-5.7%2B-4479A1?style=flat-square&logo=mysql)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-pink?style=flat-square)](LICENSE)
[![Responsive](https://img.shields.io/badge/Mobile-Responsive-brightgreen?style=flat-square)](style.css)

一个专为 **二次元同好、画师、摄影师、Coser 及个人创作者** 打造的高颜值、轻量级动态个人主页系统。集成了自定义可视化排版扩列卡、多图高清 WebP 自动压缩作品集、悬浮歌词播放器以及双重访客互动墙。

前端原生 JavaScript + 纯 CSS3 构建，零重型框架依赖；后端基于 PHP + MySQL，自带直观且强大的可视化控制中心。

---

## ✨ 核心特性

- 🌸 **未登录访客次元欢迎门**：带来仪式感满满的全屏欢迎卡片与访问序号提示（基于 `sessionStorage`，刷新页面不重复打扰）。
- 🎨 **可视化拖拽 & 自定义扩列卡**：
  - 站长登录后台后，前台自动激活编辑模式，支持**鼠标/触屏自由拖拽**改变名片元素位置。
  - 支持调整字号、调色盘改色，并可**一键同步保存排版至服务器数据库**。
  - 集成 `html2canvas` 引擎，访客及站长皆可**一键导出高清 PNG 扩列卡**。
- 🖼️ **智能作品集 & 高清 WebP 引擎**：
  - 支持多图批量上传，后台自动识别并纠正图片 EXIF 旋转方向。
  - 自动生成 600px 缩略图与 2000px 高清 WebP 格式大图，在确保画质的同时极大节省服务器带宽。
  - 响应式瀑布流排版与全屏大图灯箱浏览。
- 🎵 **悬浮式多歌单 & LRC 歌词同步播放器**：
  - 支持上传本地音频（MP3/OGG/WAV/FLAC等）或填入外链直链。
  - 完美支持 `.lrc` 歌词文件解析与实时滚动高亮显示。
  - 支持列表循环、单曲循环、随机播放模式及浏览记忆续播。
  - 原生接入系统 `MediaSession` API，支持后台/锁屏切歌。
- 💌 **双重访客互动系统**：
  - **访客扩列墙**：访客可投递专属扩列名片（支持属性/标签/联系方式）。
  - **审核制留言板**：防止垃圾信息，所有扩列名片与留言均需站长后台审核后方可正式上墙。
- 🛡️ **安全与防护**：
  - 全站表单配备 **CSRF Token 防伪造攻击**。
  - 内置隐藏式**蜜罐（Honeypot）**防护机制，有效拦截自动化垃圾营销机器人。
  - 包含 `robots.txt` 爬虫屏蔽规范，保护后台地址与数据库配置文件。
- 📱 **移动端全自适应**：
  - 适配各种尺寸手机屏幕，移动端手势拖拽防乱滚处理。

---

## 📁 目录结构

```text
.
├── admin.php       # 后台控制中心（作品/歌单/自介管理、审核等）
├── config.php      # 数据库与后台密码配置文件
├── index.php       # 网站前台主页（自介卡、作品集、扩列墙、留言板）
├── install.php     # 数据库一键初始化安装脚本
├── lib.php         # 核心公共函数库（数据库连接、图片压缩、拖拽样式渲染）
├── player.css      # 悬浮音乐播放器样式
├── player.js       # 悬浮音乐播放器核心逻辑
├── robots.txt      # 搜索引擎爬虫协议文件
├── script.js       # 前台交互逻辑（拖拽定位、图片导出、灯箱效果等）
├── style.css       # 全站 UI 主题与响应式排版样式
└── uploads/        # 用户上传资源目录（自动生成）
```

---

## 🚀 快速部署指南

### 环境要求
- **Web 服务器**：Nginx / Apache / IIS
- **PHP 版本**：PHP 8.0 或更高版本
- **PHP 扩展**：`PDO_MySQL`、`GD`（需支持 WebP 与 EXIF 扩展）
- **数据库**：MySQL 5.7+ / MariaDB 10.2+

### 安装步骤

1. **克隆项目到服务器环境：**
   ```bash
   git clone https://github.com/your-username/cyber-kawaii-home.cmd
   cd cyber-kawaii-home
   ```

2. **修改配置文件 `config.php`：**
   使用文本编辑器打开 `config.php`，填写宝塔面板或服务器创建的数据库连接信息，**并修改默认后台管理密码**：
   ```php
   <?php
   return [
     'host'       => '127.0.0.1',
     'name'       => '你的数据库名',
     'user'       => '你的数据库用户名',
     'pass'       => '你的数据库密码',
     'admin_pass' => '设置你的专属后台登录密码', // 务必修改！
   ];
   ```

3. **初始化数据库：**
   在浏览器中访问 `http://你的域名/install.php`。页面提示安装成功后，系统会自动创建所需数据表并生成 `install.lock` 文件。

4. **安全清理（重要！）：**
   安装完成后，请立即通过 FTP 或终端删除网站根目录下的 `install.php` 文件：
   ```bash
   rm -f install.php
   ```

5. **登录后台：**
   访问 `http://你的域名/admin.php`，输入在 `config.php` 中设置的 `admin_pass` 密码即可进入后台管理界面。或者点击前台页脚的隐藏入口 `✦` 进行登录。

---

## ⚙️ 进阶使用说明

### 1. 扩列卡排版调整
- 登录站长账号后，前台界面顶部会出现编辑状态栏。
- 在“自介 & 扩列卡”区域内，点击并按住任意文字块或头像即可随意拖动位置。
- 点击选中的文字块后，可通过下方工具栏调整字体大小与颜色。
- 调整完毕后，务必点击 **“💾 保存排版到服务器”**，排版将永久生效并展示给后续所有访客。

### 2. 歌单与歌词添加
- 进入后台系统的 **“2. 音乐歌单管理”** 板块。
- 可直接上传本地音频文件（支持 `MP3` / `OGG` / `WAV` / `FLAC` 等）或粘贴第三方音乐直链。
- 上传对应的 `.lrc` 格式歌词文件或直接粘贴 LRC 歌词文本，前端播放器即可自动进行时间轴滚动高亮。

---

## 🔒 隐私与安全建议

1. 请确保根目录下的 `uploads/` 文件夹具备写入权限（推荐 `0755`）。
2. 在上线前，请确认已删除 `install.php` 部署文件。
3. 建议在服务器 Nginx 配置中禁止外部直接访问 `.php` 配置文件及执行上传目录中的脚本：
   ```nginx
   # 禁止 uploads 目录执行 PHP 脚本
   location ~ ^/uploads/.*\.php$ {
       deny all;
   }
   ```

---

## 📄 开源许可证

本项目采用 [MIT License](LICENSE) 许可证开源，你可以自由修改、分发与二次开发。如果这个项目对你有帮助，欢迎点一个 **⭐ Star** 支持！