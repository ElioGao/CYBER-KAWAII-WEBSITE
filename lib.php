<?php
session_start();
$C = require __DIR__.'/config.php';

function db(){
  static $p; global $C;
  if(!$p){
    $p = new PDO(
      "mysql:host={$C['host']};dbname={$C['name']};charset=utf8mb4",
      $C['user'],
      $C['pass'],
      [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
      ]
    );
    // 自动创建访客扩列墙数据表
    $p->exec("CREATE TABLE IF NOT EXISTS friends(
      id INT AUTO_INCREMENT PRIMARY KEY,
      nick VARCHAR(40),
      tags VARCHAR(120),
      contact VARCHAR(100),
      intro VARCHAR(200),
      approved TINYINT DEFAULT 0,
      ip VARCHAR(45),
      created TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) CHARSET=utf8mb4");
  }
  return $p;
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function csrf(){ return $_SESSION['t'] ??= bin2hex(random_bytes(16)); }
function check(){ if(!hash_equals($_SESSION['t'] ?? '', $_POST['t'] ?? '')) die('Token Error'); }
function is_admin(){ return !empty($_SESSION['admin']); }

function S($k, $d=''){
  static $s;
  if($s === null) $s = db()->query("SELECT skey,sval FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
  return $s[$k] ?? $d;
}

// 输出某个可拖拽元素的已保存位置与样式
function item_style($key){
  static $layout = null;
  if($layout === null){
    $raw = S('card_layout', '{}');
    $layout = json_decode($raw, true) ?: [];
  }
  if(empty($layout[$key])) return '';
  $it = $layout[$key];
  $css = [];
  if(isset($it['x']) && $it['x'] !== '') $css[] = 'left:' . (float)$it['x'] . 'px';
  if(isset($it['y']) && $it['y'] !== '') $css[] = 'top:' . (float)$it['y'] . 'px';
  if(!empty($it['size'])) $css[] = 'font-size:' . preg_replace('/[^0-9.px]/', '', $it['size']);
  if(!empty($it['color'])) $css[] = 'color:' . preg_replace('/[^#a-zA-Z0-9(),. ]/', '', $it['color']);
  return $css ? ' style="' . implode(';', $css) . '"' : '';
}

function item_coords($key){
  static $layout = null;
  if($layout === null){
    $layout = json_decode(S('card_layout', '{}'), true) ?: [];
  }
  $x = isset($layout[$key]['x']) ? (float)$layout[$key]['x'] : 0;
  $y = isset($layout[$key]['y']) ? (float)$layout[$key]['y'] : 0;
  return " data-key=\"".h($key)."\" data-x=\"$x\" data-y=\"$y\"";
}

// 上传图片 -> 自动修正方向 -> 压缩为 600px 缩略图 + 2000px 高清 WebP 大图
function save_image($tmp){
  if(filesize($tmp) > 20 * 1024 * 1024) return null;
  $i = @getimagesize($tmp);
  if(!$i || !in_array($i[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP, IMAGETYPE_GIF])) return null;

  $src = match($i[2]){
    IMAGETYPE_JPEG => imagecreatefromjpeg($tmp),
    IMAGETYPE_PNG  => imagecreatefrompng($tmp),
    IMAGETYPE_WEBP => imagecreatefromwebp($tmp),
    default        => imagecreatefromgif($tmp)
  };
  if(!$src) return null;

  if(function_exists('imagepalettetotruecolor')) imagepalettetotruecolor($src);

  if($i[2] == IMAGETYPE_JPEG && function_exists('exif_read_data')){
    $o = @exif_read_data($tmp)['Orientation'] ?? 1;
    $src = match($o){
      3 => imagerotate($src, 180, 0),
      6 => imagerotate($src, -90, 0),
      8 => imagerotate($src, 90, 0),
      default => $src
    };
  }

  $name = bin2hex(random_bytes(8));
  $w = imagesx($src);
  $hh = imagesy($src);

  foreach(['t' => [600, 82], 'l' => [2000, 85]] as $tag => [$max, $q]){
    $nw = min($w, $max);
    $nh = (int)round($hh * $nw / $w);
    $d = imagecreatetruecolor($nw, $nh);
    imagealphablending($d, false);
    imagesavealpha($d, true);
    imagecopyresampled($d, $src, 0, 0, 0, 0, $nw, $nh, $w, $hh);
    imagewebp($d, __DIR__."/uploads/{$name}_$tag.webp", $q);
    imagedestroy($d);
  }
  imagedestroy($src);
  return $name;
}

function del_image($n){
  foreach(['t', 'l'] as $t) @unlink(__DIR__."/uploads/{$n}_$t.webp");
}