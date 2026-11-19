<?php require __DIR__.'/lib.php';
$admin = is_admin();
$msg = '';
$friendMsg = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
  check();
  $act = $_POST['act'] ?? 'message';

  // 1. 站长保存扩列卡拖拽排版（AJAX 接口）
  if($act === 'save_layout'){
    header('Content-Type: application/json; charset=utf-8');
    if(!$admin){
      echo json_encode(['ok' => false, 'msg' => '未登录无权限']);
      exit;
    }
    $layoutJson = $_POST['layout'] ?? '{}';
    $decoded = json_decode($layoutJson, true);
    if(!is_array($decoded)){
      echo json_encode(['ok' => false, 'msg' => '数据格式错误']);
      exit;
    }
    db()->prepare("INSERT INTO settings(skey,sval) VALUES('card_layout',?) ON DUPLICATE KEY UPDATE sval=VALUES(sval)")
        ->execute([json_encode($decoded, JSON_UNESCAPED_UNICODE)]);
    echo json_encode(['ok' => true]);
    exit;
  }

  // 防机器人蜜罐
  if(!empty($_POST['website'])) die();

  // 2. 访客提交扩列墙名片
  if($act === 'friend'){
    $fn = trim($_POST['fnick'] ?? '');
    $ft = trim($_POST['ftags'] ?? '');
    $fc = trim($_POST['fcontact'] ?? '');
    $fi = trim($_POST['fintro'] ?? '');
    if(time() - ($_SESSION['last_f'] ?? 0) < 30){
      $friendMsg = '休息 30 秒再投递名片吧~';
    } elseif($fn === '' || $fc === '' || mb_strlen($fn) > 20 || mb_strlen($fc) > 60 || mb_strlen($fi) > 120){
      $friendMsg = '昵称和联系方式必填，且请注意字数限制哦';
    } else {
      db()->prepare("INSERT INTO friends(nick,tags,contact,intro,ip) VALUES(?,?,?,?,?)")
          ->execute([$fn, $ft, $fc, $fi, $_SERVER['REMOTE_ADDR']]);
      $_SESSION['last_f'] = time();
      $friendMsg = '扩列卡投递成功！' . h(S('nick') ?: '站长') . '审核后就会挂上墙啦 ♡';
    }
  } else {
    // 3. 访客提交普通留言
    $n = trim($_POST['name'] ?? '');
    $c = trim($_POST['content'] ?? '');
    if(time() - ($_SESSION['last'] ?? 0) < 30){
      $msg = '休息 30 秒再留言吧~';
    } elseif($n === '' || $c === '' || mb_strlen($n) > 20 || mb_strlen($c) > 300){
      $msg = '昵称和内容都要填，且不超过 300 字哦';
    } else {
      db()->prepare("INSERT INTO messages(name,content,ip) VALUES(?,?,?)")->execute([$n, $c, $_SERVER['REMOTE_ADDR']]);
      $_SESSION['last'] = time();
      $msg = '留言发送成功，' . h(S('nick') ?: '站长') . '审核后展示 ♡';
    }
  }
} else {
  db()->exec("UPDATE settings SET sval=sval+1 WHERE skey='pv'");
  if(empty($_COOKIE['seen'])){
    db()->exec("UPDATE settings SET sval=sval+1 WHERE skey='uv'");
    setcookie('seen', '1', time() + 86400, '/');
  }
}

$works   = db()->query("SELECT * FROM works ORDER BY id DESC")->fetchAll();
$friends = db()->query("SELECT * FROM friends WHERE approved=1 ORDER BY id DESC LIMIT 30")->fetchAll();
$msgs    = db()->query("SELECT name,content,created FROM messages WHERE approved=1 ORDER BY id DESC LIMIT 50")->fetchAll();
$tags    = array_filter(array_map('trim', preg_split('/[,，]/', S('tags'))));
?>
<!doctype html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=h(S('site_name'))?></title>
<meta name="author" content="Eilo Gao">
<meta name="generator" content="书未寄故人">
<link rel="stylesheet" href="style.css?v=4">
<link rel="stylesheet" href="player.css?v=1">
</head>
<body class="<?=$admin ? 'is-admin' : 'is-guest'?>">

<?php if(!$admin): ?>
<!-- 未登录访客专属赛博次元欢迎门 -->
<div id="cyber-portal">
  <div class="portal-box">
    <div class="portal-code">ACCESS // VISITOR #<?=str_pad((string)((int)S('uv') + 1), 5, '0', STR_PAD_LEFT)?></div>
    <div class="portal-av">
      <?php if(S('avatar')): ?>
        <img src="uploads/<?=h(S('avatar'))?>_t.webp" alt="avatar">
      <?php else: ?>🌸<?php endif; ?>
    </div>
    <h2>✿ <?=h(S('site_name'))?> ✿</h2>
    <p><?=h(S('tagline'))?></p>
    <button type="button" class="enter-btn" onclick="enterSite()">ENTER // 进入小窝 ✦</button>
  </div>
</div>
<script>
// 同一次浏览标签页内进入过一次后，刷新或提交表单不再重复弹出
if(sessionStorage.getItem('entered_portal') === '1'){
  document.getElementById('cyber-portal').style.display = 'none';
}
</script>
<?php else: ?>
<!-- 登录后显示的顶部编辑状态栏 -->
<div class="admin-ribbon">
  <span>✦ <b><?=h(S('nick') ?: '站长')?>的编辑模式</b>：可直接拖拽下方扩列卡元素并保存到服务器</span>
  <div>
    <a href="admin.php">后台管理</a>
    <a href="admin.php?out=1">退出登录</a>
  </div>
</div>
<?php endif; ?>

<div class="cyber-grid"></div>
<div class="sky">
  <?php for($i=0;$i<16;$i++) echo '<i style="left:'.rand(0,98).'%;top:'.rand(0,95).'%;animation-delay:'.(rand(0,40)/10).'s;font-size:'.rand(12,24).'px">'.['✦','♡','☆','✧'][$i%4].'</i>'; ?>
</div>

<header>
  <div class="badge">SYS.ONLINE // PORT-2026</div>
  <h1>✿ <?=h(S('site_name'))?> ✿</h1>
  <p class="tagline"><?=h(S('tagline'))?></p>
  <nav>
    <a href="#card">自介 & 扩列条</a>
    <a href="#works">赛博作品集</a>
    <a href="#friends">访客扩列墙</a>
    <a href="#book">留言板</a>
  </nav>
</header>

<main>
  <!-- 自介 + 扩列条二合一（站长可拖拽保存排版，访客锁定查看成品） -->
  <section id="card">
    <h2>自介 & 扩列卡</h2>
    <div class="card" id="kuolie">
      <div class="card-deco">ID.CARD // 001</div>
      <div class="av drag-item"<?=item_coords('avatar')?><?=item_style('avatar')?> title="<?=$admin?'按住可拖拽调整位置':''?>">
        <?php if(S('avatar')): ?>
          <img src="uploads/<?=h(S('avatar'))?>_t.webp" draggable="false" alt="avatar">
        <?php else: ?>🌸<?php endif; ?>
      </div>
      <div class="info">
        <h3 class="drag-item"<?=item_coords('nick')?><?=item_style('nick')?>><?=h(S('nick'))?></h3>
        <p class="chips drag-item"<?=item_coords('chips')?><?=item_style('chips')?>>
          <span>🎂 <?=h(S('age'))?>岁</span>
          <span>⭐ <?=h(S('zodiac'))?></span>
          <span>📍 <?=h(S('city'))?></span>
        </p>
        <p class="about-text drag-item"<?=item_coords('about')?><?=item_style('about')?>><?=nl2br(h(S('about')))?></p>
        <p class="chips drag-item"<?=item_coords('tags')?><?=item_style('tags')?>>
          <?php foreach($tags as $t) echo '<span class="tag">#'.h($t).'</span>'; ?>
        </p>
        <p class="want drag-item"<?=item_coords('want')?><?=item_style('want')?>>💌 <?=nl2br(h(S('want')))?></p>
        <p class="ct drag-item"<?=item_coords('contact')?><?=item_style('contact')?>><b><?=h(S('contact'))?></b></p>
      </div>
    </div>

    <div class="card-tools">
      <?php if($admin): ?>
      <!-- 仅站长登录后可见的排版工具栏 -->
      <div class="style-bar">
        <span>🎨 <?=h(S('nick') ?: '站长')?>的排版工具：</span>
        <button type="button" class="mini-btn" onclick="changeFontSize(1)">字号+</button>
        <button type="button" class="mini-btn" onclick="changeFontSize(-1)">字号-</button>
        <input type="color" id="txtColor" value="#ff6fa5" title="修改选中文字颜色" onchange="changeTextColor(this.value)">
        <button type="button" class="mini-btn" onclick="resetPositions()">重置默认</button>
        <button type="button" class="mini-btn save-db-btn" onclick="saveLayoutToServer('<?=csrf()?>')">💾 保存排版到服务器</button>
      </div>
      <?php endif; ?>

      <div class="btns">
        <button type="button" onclick="copyText(<?=h(json_encode(S('contact'), JSON_UNESCAPED_UNICODE))?>)">复制联系方式</button>
        <button type="button" class="primary-btn" onclick="saveCard()">📸 保存为高清扩列图</button>
      </div>
    </div>
  </section>

  <!-- 作品集 -->
  <section id="works">
    <h2>作品集</h2>
    <?php if(!$works): ?>
    <p class="box"><?=$admin ? '还没有上传作品，点击页脚 ✦ 进入后台发布吧~' : h(S('nick') ?: '站长') . '正在整理作品中，敬请期待~ ♡'?></p>
    <?php endif; ?>
    <div class="grid">
      <?php foreach($works as $w): ?>
        <a class="item" href="uploads/<?=h($w['file'])?>_l.webp" data-t="<?=h($w['title'])?>" data-d="<?=h($w['descr'])?>">
          <img src="uploads/<?=h($w['file'])?>_t.webp" loading="lazy" alt="<?=h($w['title'])?>">
          <span><?=h($w['title'])?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- 方案二：访客专属互动板块（访客扩列墙） -->
  <section id="friends">
    <h2>访客扩列墙</h2>
    <form method="post" action="#friends" class="box friend-form">
      <input type="hidden" name="t" value="<?=csrf()?>">
      <input type="hidden" name="act" value="friend">
      <input name="website" class="hp" tabindex="-1" autocomplete="off">
      <div class="form-row">
        <input name="fnick" maxlength="20" placeholder="你的扩列昵称 *" required>
        <input name="ftags" maxlength="40" placeholder="你的属性/成分（如：原神,画画,音游）">
      </div>
      <input name="fcontact" maxlength="60" placeholder="你的联系方式（如：QQ 123456 / 仅同好）*" required>
      <input name="fintro" maxlength="120" placeholder="一句话扩列宣言（审核通过后将展示在下方名片墙~）">
      <button type="submit">投递我的扩列名片 ✦</button>
      <?php if($friendMsg) echo '<em>'.h($friendMsg).'</em>'; ?>
    </form>

    <?php if($friends): ?>
      <div class="friend-grid">
        <?php foreach($friends as $f): ?>
          <div class="f-card">
            <div class="f-head">
              <b>✿ <?=h($f['nick'])?></b>
              <button type="button" class="mini-copy" onclick="copyText(<?=h(json_encode($f['contact'], JSON_UNESCAPED_UNICODE))?>)">复制联络</button>
            </div>
            <?php if($f['tags']): ?>
              <div class="f-tags">
                <?php foreach(array_filter(array_map('trim', preg_split('/[,，]/', $f['tags']))) as $ft): ?>
                  <span>#<?=h($ft)?></span>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
            <?php if($f['intro']): ?><p class="f-intro"><?=h($f['intro'])?></p><?php endif; ?>
            <div class="f-ct">💌 <?=h($f['contact'])?></div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="box" style="margin-top:12px;text-align:center;font-size:.9rem">扩列墙还是空的，快来投递第一张访客名片吧~</p>
    <?php endif; ?>
  </section>

  <!-- 留言板 -->
  <section id="book">
    <h2>留言板</h2>
    <form method="post" action="#book" class="box">
      <input type="hidden" name="t" value="<?=csrf()?>">
      <input type="hidden" name="act" value="message">
      <input name="website" class="hp" tabindex="-1" autocomplete="off">
      <input name="name" maxlength="20" placeholder="你的昵称" required>
      <textarea name="content" maxlength="300" placeholder="留下你的足迹吧~（审核后显示）" required></textarea>
      <button>发送留言 ♡</button>
      <?php if($msg) echo '<em>'.h($msg).'</em>'; ?>
    </form>
    <?php foreach($msgs as $m): ?>
      <div class="msg">
        <b><?=h($m['name'])?></b> <small><?=h(substr($m['created'], 0, 10))?></small>
        <p><?=nl2br(h($m['content']))?></p>
      </div>
    <?php endforeach; ?>
  </section>
</main>

<footer>
  👀 总访问 <?=(int)S('pv')?> · 独立访客 <?=(int)S('uv')?><br>
  © <?=h(S('site_name'))?> <a href="admin.php" class="secret-gate" title="管理入口">✦</a>
</footer>

<div id="lb"><div><img id="lbi" alt=""><p id="lbt"></p></div></div>
<?php
$plRaw = S('playlist', '');
$plData = $plRaw !== '' ? (json_decode($plRaw, true) ?: []) : [];
if(!$plData && S('bgm_url') !== ''){
  $plData = [[
    't'   => S('bgm_name') ?: 'BGM',
    'a'   => S('bgm_artist') ?: '',
    'src' => S('bgm_url'),
    'lrc' => S('bgm_lrc')
  ]];
}
?>
<script>
window.IS_ADMIN = <?=$admin ? 'true' : 'false'?>;
window.PL = <?=json_encode($plData, JSON_UNESCAPED_UNICODE)?>;
</script>
<script src="https://cdn.staticfile.net/html2canvas/1.4.1/html2canvas.min.js"></script>
<script>
if(typeof html2canvas === 'undefined'){
  document.write('<script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"><\/script>');
}
</script>
<script src="player.js?v=1"></script>
<script src="script.js?v=4"></script>
</body>
</html>