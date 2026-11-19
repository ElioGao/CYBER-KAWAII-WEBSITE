<?php
require __DIR__.'/lib.php';
if(file_exists(__DIR__.'/install.lock')) die('已安装完成，请删除 install.php');
$p = db();
$p->exec("CREATE TABLE IF NOT EXISTS works(
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(100),
  descr TEXT,
  file VARCHAR(32),
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARSET=utf8mb4");

$p->exec("CREATE TABLE IF NOT EXISTS messages(
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(40),
  content TEXT,
  approved TINYINT DEFAULT 0,
  ip VARCHAR(45),
  created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARSET=utf8mb4");

$p->exec("CREATE TABLE IF NOT EXISTS settings(
  skey VARCHAR(40) PRIMARY KEY,
  sval TEXT
) CHARSET=utf8mb4");

$def = [
  'site_name'  => 'CYBER KAWAII // 小窝',
  'tagline'    => '✦ 欢迎来到我的二次元赛博空间 ♡',
  'about'      => '这里是我的个人简介~ 喜欢画画、摄影、折腾代码与二次元文化，欢迎来扩列交流！',
  'nick'       => '你的昵称',
  'age'        => '20',
  'zodiac'     => '双鱼座',
  'city'       => '坐标/城市',
  'tags'       => '二次元,插画,摄影,游戏,设计',
  'want'       => '扩列要求：同好欢迎 / 话痨欢迎 / 拒雷点',
  'contact'    => 'QQ：123456789 / VX：your_id',
  'avatar'     => '',
  'bgm_name'   => '示例BGM - 晴天物语',
  'bgm_artist' => 'Kawaii Synth',
  'bgm_url'    => '',
  'bgm_lrc'    => "[00:00.00]在后台填入音乐MP3直链与LRC歌词即可自动滚动~\n[00:05.00]切换网页板块音乐也不会中断哦 ♡",
  'pv'         => '0',
  'uv'         => '0'
];

$s = $p->prepare("INSERT IGNORE INTO settings VALUES(?,?)");
foreach($def as $k => $v) $s->execute([$k, $v]);

@mkdir(__DIR__.'/uploads', 0755, true);
file_put_contents(__DIR__.'/install.lock', '1');
echo '安装成功！请立即删除 install.php，然后访问首页点击页脚 ✦ 登录后台。';