<?php require __DIR__.'/lib.php';
$ok = is_admin();
$note = '';

if(isset($_GET['out'])){
  session_destroy();
  header('Location: admin.php');
  exit;
}

// 读取歌单
function get_playlist(){
  $raw = S('playlist', '');
  if($raw !== ''){
    $list = json_decode($raw, true);
    if(is_array($list)) return $list;
  }
  if(S('bgm_url') !== ''){
    return [[
      't'   => S('bgm_name') ?: 'BGM',
      'a'   => S('bgm_artist') ?: '',
      'src' => S('bgm_url'),
      'lrc' => S('bgm_lrc')
    ]];
  }
  return [];
}

function save_playlist($list){
  db()->prepare("INSERT INTO settings(skey,sval) VALUES('playlist',?) ON DUPLICATE KEY UPDATE sval=VALUES(sval)")
      ->execute([json_encode(array_values($list), JSON_UNESCAPED_UNICODE)]);
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  check();
  if(!$ok){
    if(hash_equals($C['admin_pass'], $_POST['pw'] ?? '')){
      session_regenerate_id(true);
      $_SESSION['admin'] = 1;
      header('Location: admin.php');
      exit;
    }
    sleep(1);
    $note = '密码错误';
  } else {
    $a = $_POST['a'] ?? '';
    if($a === 'settings'){
      $u = db()->prepare("INSERT INTO settings(skey,sval) VALUES(?,?) ON DUPLICATE KEY UPDATE sval=VALUES(sval)");
      $keys = ['site_name','tagline','about','nick','age','zodiac','city','tags','want','contact'];
      foreach($keys as $k) $u->execute([$k, trim($_POST[$k] ?? '')]);

      if(!empty($_FILES['avatar']['tmp_name'])){
        if($n = save_image($_FILES['avatar']['tmp_name'])){
          del_image(S('avatar'));
          $u->execute(['avatar', $n]);
        } else {
          $note = '头像格式不支持或超过20MB';
        }
      }
      $note = $note ?: '自介与网站设置已保存 ♡';
    } elseif($a === 'add_song'){
      $src = trim($_POST['song_url'] ?? '');
      // 如果上传了本地音频文件
      if(!empty($_FILES['song_file']['tmp_name'])){
        $ext = strtolower(pathinfo($_FILES['song_file']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, ['mp3','ogg','wav','m4a','flac','aac'])){
          $bgmName = 'bgm_' . bin2hex(random_bytes(6)) . '.' . $ext;
          if(move_uploaded_file($_FILES['song_file']['tmp_name'], __DIR__ . '/uploads/' . $bgmName)){
            $src = 'uploads/' . $bgmName;
          } else {
            $note = '音频保存失败，请检查 uploads 权限';
          }
        } else {
          $note = '不支持的音频格式';
        }
      }

      // 读取歌词（优先读取上传的 .lrc 文件）
      $lrc = trim($_POST['song_lrc'] ?? '');
      if(!empty($_FILES['lrc_file']['tmp_name'])){
        $rawLrc = file_get_contents($_FILES['lrc_file']['tmp_name']);
        if($rawLrc !== false){
          if(!mb_check_encoding($rawLrc, 'UTF-8')){
            $rawLrc = mb_convert_encoding($rawLrc, 'UTF-8', 'GBK,GB2312,BIG5');
          }
          $lrc = trim(preg_replace('/^\xEF\xBB\xBF/', '', $rawLrc));
        }
      }

      if(!$note && $src !== ''){
        $title = trim($_POST['song_name'] ?? '');
        // 未自定义歌名时，自动提取原文件名（兼容中文/日文及外链 URL）
        if($title === ''){
          if(!empty($_FILES['song_file']['name'])){
            $title = preg_replace('/\.[^.]+$/', '', $_FILES['song_file']['name']);
          } else {
            $urlFile = urldecode(basename(parse_url($src, PHP_URL_PATH) ?? ''));
            $title = preg_replace('/\.[^.]+$/', '', $urlFile);
          }
        }
        $list = get_playlist();
        $list[] = [
          't'   => $title ?: '未命名歌曲',
          'a'   => trim($_POST['song_artist'] ?? ''),
          'src' => $src,
          'lrc' => $lrc
        ];
        save_playlist($list);
        $note = '歌曲已加入小窝歌单 🎵';
      } elseif(!$note){
        $note = '请上传音频文件或填写音乐直链';
      }
    } elseif($a === 'del_song'){
      $idx = (int)($_POST['idx'] ?? -1);
      $list = get_playlist();
      if(isset($list[$idx])){
        if(str_starts_with($list[$idx]['src'], 'uploads/bgm_')){
          @unlink(__DIR__ . '/' . $list[$idx]['src']);
        }
        unset($list[$idx]);
        save_playlist($list);
        $note = '已从歌单移除该歌曲';
      }
    } elseif($a === 'upload'){
      $c = 0;
      foreach($_FILES['imgs']['tmp_name'] ?? [] as $i => $tmp){
        if(!$tmp) continue;
        if($n = save_image($tmp)){
          $title = trim($_POST['title']) ?: pathinfo($_FILES['imgs']['name'][$i], PATHINFO_FILENAME);
          db()->prepare("INSERT INTO works(title,descr,file) VALUES(?,?,?)")->execute([$title, trim($_POST['descr']), $n]);
          $c++;
        }
      }
      $note = "成功上传并压缩了 $c 张高清 WebP 作品";
    } elseif($a === 'delwork'){
      $r = db()->prepare("SELECT file FROM works WHERE id=?");
      $r->execute([(int)$_POST['id']]);
      if($f = $r->fetchColumn()) del_image($f);
      db()->prepare("DELETE FROM works WHERE id=?")->execute([(int)$_POST['id']]);
      $note = '作品已删除';
    } elseif($a === 'ok'){
      db()->prepare("UPDATE messages SET approved=1 WHERE id=?")->execute([(int)$_POST['id']]);
      $note = '留言已通过审核';
    } elseif($a === 'delmsg'){
      db()->prepare("DELETE FROM messages WHERE id=?")->execute([(int)$_POST['id']]);
      $note = '留言已删除';
    } elseif($a === 'ok_friend'){
      db()->prepare("UPDATE friends SET approved=1 WHERE id=?")->execute([(int)$_POST['id']]);
      $note = '访客扩列名片已上墙';
    } elseif($a === 'del_friend'){
      db()->prepare("DELETE FROM friends WHERE id=?")->execute([(int)$_POST['id']]);
      $note = '访客名片已删除';
    }
  }
}
$ok = is_admin();
$playlist = $ok ? get_playlist() : [];
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>小窝后台管理</title>
<link rel="stylesheet" href="style.css">
<style>
main{padding-top:30px;padding-bottom:60px}
.row{display:flex;gap:10px;align-items:center;margin:8px 0;flex-wrap:wrap;justify-content:space-between;border-bottom:1px dashed #ffc2da;padding-bottom:8px}
.row img{width:60px;height:60px;object-fit:cover;border-radius:10px}
label{display:block;margin:10px 0 4px;font-size:.9rem;font-weight:bold;color:#ff6fa5}
form.inl{display:inline}
.sub-tip{font-size:.8rem;color:#8c6e85;margin:-4px 0 8px}
</style>
</head>
<body>
<main>
<?php if(!$ok): ?>
  <form method="post" class="box" style="max-width:360px;margin:100px auto;text-align:center">
    <h2>✦ 后台验证</h2>
    <input type="hidden" name="t" value="<?=csrf()?>">
    <input type="password" name="pw" placeholder="请输入管理密码" required>
    <button>进入后台</button>
    <p><em><?=h($note)?></em></p>
    <p><a href="index.php" style="font-size:.85rem;color:#999">← 返回前台</a></p>
  </form>
<?php else: $T = csrf(); ?>
  <h1>✦ 后台控制中心 <a href="index.php" style="font-size:1rem">返回前台编辑排版</a> · <a href="?out=1" style="font-size:1rem">安全退出</a></h1>
  <?php if($note) echo '<p class="box" style="border-style:solid;color:#ff4d94"><b>'.h($note).'</b></p>'; ?>
  <p class="box">📊 数据统计：总访问量 <b><?=(int)S('pv')?></b> · 独立访客 <b><?=(int)S('uv')?></b></p>

  <h2>1. 上传作品（支持多选，自动转高清 WebP）</h2>
  <form method="post" enctype="multipart/form-data" class="box">
    <input type="hidden" name="t" value="<?=$T?>">
    <input type="hidden" name="a" value="upload">
    <input name="title" placeholder="作品标题（留空则自动使用图片原文件名）">
    <textarea name="descr" placeholder="作品文字说明 / 创作心得（可留空）"></textarea>
    <input type="file" name="imgs[]" accept="image/*" multiple required>
    <button>上传并智能压缩</button>
  </form>

  <h2>2. 音乐歌单管理（支持多首切歌 & LRC 滚动歌词）</h2>
  <form method="post" enctype="multipart/form-data" class="box">
    <input type="hidden" name="t" value="<?=$T?>">
    <input type="hidden" name="a" value="add_song">
    <label>🎵 歌曲名称（留空则自动使用音频文件名）</label>
    <input name="song_name" placeholder="例如：晴天物语">
    <label>🎤 歌手名</label>
    <input name="song_artist" placeholder="例如：初音ミク">
    <label>📂 上传音频文件（MP3 / OGG / WAV / M4A / FLAC）</label>
    <input type="file" name="song_file" accept="audio/*,.mp3,.ogg,.wav,.m4a,.flac">
    <label>🔗 或填写外部音频直链 URL（上传本地文件时此处可留空）</label>
    <input name="song_url" placeholder="https://...">
    <label>📄 上传 .lrc 歌词文件（可选）</label>
    <input type="file" name="lrc_file" accept=".lrc,.txt">
    <label>📜 或直接粘贴 LRC 歌词文本（可选）</label>
    <textarea name="song_lrc" style="height:100px" placeholder="[00:00.00]歌词内容..."></textarea>
    <button>添加歌曲到歌单 ✦</button>

    <?php if($playlist): ?>
      <hr style="border:1px dashed #ffc2da;margin:18px 0 10px">
      <label>当前歌单列表（共 <?=count($playlist)?> 首）：</label>
      <?php foreach($playlist as $i => $s): ?>
        <div class="row">
          <span>🎵 <b><?=h($s['t'])?></b> <?=$s['a'] ? ' - '.h($s['a']) : ''?> <?=$s['lrc'] ? '📜' : ''?></span>
          <form method="post" class="inl" onsubmit="return confirm('确定删除这首歌？')">
            <input type="hidden" name="t" value="<?=$T?>">
            <input type="hidden" name="a" value="del_song">
            <input type="hidden" name="idx" value="<?=$i?>">
            <button>移除</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </form>

  <h2>3. 自介与扩列条设置</h2>
  <form method="post" enctype="multipart/form-data" class="box">
    <input type="hidden" name="t" value="<?=$T?>">
    <input type="hidden" name="a" value="settings">
    <?php
    $fields = [
      'site_name' => '网站标题',
      'tagline'   => '顶部副标题',
      'nick'      => '扩列昵称',
      'age'       => '年龄',
      'zodiac'    => '星座',
      'city'      => '所在地',
      'tags'      => '爱好/属性标签（用逗号分隔）',
      'contact'   => '扩列联系方式'
    ];
    foreach($fields as $k => $l){
      echo "<label>$l</label><input name=\"$k\" value=\"".h(S($k))."\">";
    }
    ?>
    <label>自我介绍（显示在扩列卡内）</label>
    <textarea name="about"><?=h(S('about'))?></textarea>
    <label>扩列要求</label>
    <textarea name="want"><?=h(S('want'))?></textarea>
    <label>更换头像（不换请留空）</label>
    <input type="file" name="avatar" accept="image/*">
    <button>保存自介设置</button>
  </form>

  <h2>4. 已发布作品管理</h2>
  <div class="box">
    <?php foreach(db()->query("SELECT * FROM works ORDER BY id DESC") as $w): ?>
      <div class="row">
        <div style="display:flex;align-items:center;gap:10px">
          <img src="uploads/<?=h($w['file'])?>_t.webp">
          <span><b><?=h($w['title'])?></b></span>
        </div>
        <form method="post" class="inl" onsubmit="return confirm('确定删除该作品？')">
          <input type="hidden" name="t" value="<?=$T?>">
          <input type="hidden" name="a" value="delwork">
          <input type="hidden" name="id" value="<?=$w['id']?>">
          <button>删除</button>
        </form>
      </div>
    <?php endforeach; ?>
  </div>

  <h2>5. 访客扩列墙审核</h2>
  <div class="box">
    <?php foreach(db()->query("SELECT * FROM friends ORDER BY id DESC LIMIT 100") as $f): ?>
      <div class="row">
        <span><?=$f['approved']?'✅':'⏳'?> <b><?=h($f['nick'])?></b> [<?=h($f['contact'])?>]：<?=h($f['intro'])?></span>
        <div>
          <?php if(!$f['approved']): ?>
            <form method="post" class="inl">
              <input type="hidden" name="t" value="<?=$T?>">
              <input type="hidden" name="a" value="ok_friend">
              <input type="hidden" name="id" value="<?=$f['id']?>">
              <button>通过上墙</button>
            </form>
          <?php endif; ?>
          <form method="post" class="inl">
            <input type="hidden" name="t" value="<?=$T?>">
            <input type="hidden" name="a" value="del_friend">
            <input type="hidden" name="id" value="<?=$f['id']?>">
            <button>删除</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <h2>6. 访客留言审核</h2>
  <div class="box">
    <?php foreach(db()->query("SELECT * FROM messages ORDER BY id DESC LIMIT 100") as $m): ?>
      <div class="row">
        <span><?=$m['approved']?'✅':'⏳'?> <b><?=h($m['name'])?></b>：<?=h($m['content'])?></span>
        <div>
          <?php if(!$m['approved']): ?>
            <form method="post" class="inl">
              <input type="hidden" name="t" value="<?=$T?>">
              <input type="hidden" name="a" value="ok">
              <input type="hidden" name="id" value="<?=$m['id']?>">
              <button>通过</button>
            </form>
          <?php endif; ?>
          <form method="post" class="inl">
            <input type="hidden" name="t" value="<?=$T?>">
            <input type="hidden" name="a" value="delmsg">
            <input type="hidden" name="id" value="<?=$m['id']?>">
            <button>删除</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
</main>
</body>
</html>