// 0. 访客欢迎门进入动效
function enterSite(){
  const portal = document.getElementById('cyber-portal');
  if(!portal) return;
  sessionStorage.setItem('entered_portal', '1');
  portal.classList.add('hide');
  setTimeout(() => portal.style.display = 'none', 600);
}

// 1. 作品集大图灯箱
const lb = document.getElementById('lb');
document.querySelectorAll('.item').forEach(a => a.onclick = e => {
  e.preventDefault();
  document.getElementById('lbi').src = a.href;
  document.getElementById('lbt').innerHTML = '<b></b><br><span style="font-size:.88rem;opacity:.85"></span>';
  lb.querySelector('b').textContent = a.dataset.t;
  lb.querySelector('span').textContent = a.dataset.d;
  lb.classList.add('on');
});
lb.onclick = () => lb.classList.remove('on');
document.addEventListener('keydown', e => e.key === 'Escape' && lb.classList.remove('on'));

// 2. 复制联系方式
function copyText(t){
  (navigator.clipboard ? navigator.clipboard.writeText(t) : Promise.reject())
    .then(() => alert('联系方式已复制 ♡'))
    .catch(() => prompt('请手动复制：', t));
}

// 3. 扩列卡拖拽定位（仅登录后激活）
let activeEl = null;
let hideTimer = null;

if(window.IS_ADMIN){
  document.querySelectorAll('.drag-item').forEach(el => {
    el.addEventListener('pointerdown', e => {
      e.preventDefault();
      clearTimeout(hideTimer);
      document.querySelectorAll('.drag-item').forEach(i => i.classList.remove('active-item'));
      activeEl = el;
      el.classList.add('active-item');

      try { el.setPointerCapture(e.pointerId); } catch(err) {}

      const startX = e.clientX - parseFloat(el.dataset.x || '0');
      const startY = e.clientY - parseFloat(el.dataset.y || '0');

      const onMove = ev => {
        const nx = ev.clientX - startX;
        const ny = ev.clientY - startY;
        el.dataset.x = String(nx);
        el.dataset.y = String(ny);
        el.style.left = nx + 'px';
        el.style.top = ny + 'px';
      };

      const onUp = ev => {
        try { el.releasePointerCapture(ev.pointerId); } catch(err) {}
        el.removeEventListener('pointermove', onMove);
        el.removeEventListener('pointerup', onUp);
        el.removeEventListener('pointercancel', onUp);
        hideTimer = setTimeout(() => el.classList.remove('active-item'), 800);
      };

      el.addEventListener('pointermove', onMove);
      el.addEventListener('pointerup', onUp);
      el.addEventListener('pointercancel', onUp);
    });
  });

  document.addEventListener('pointerdown', e => {
    if(!e.target.closest('.drag-item') && !e.target.closest('.style-bar')){
      clearTimeout(hideTimer);
      document.querySelectorAll('.drag-item').forEach(i => i.classList.remove('active-item'));
    }
  });
}

function changeFontSize(delta){
  if(!activeEl) return alert('请先点击扩列卡内要调整的文字块~');
  const cur = parseFloat(window.getComputedStyle(activeEl).fontSize);
  activeEl.style.fontSize = Math.max(11, Math.min(36, cur + delta)) + 'px';
}

function changeTextColor(color){
  if(!activeEl) return alert('请先点击扩列卡内要改色的文字块~');
  activeEl.style.color = color;
}

function resetPositions(){
  clearTimeout(hideTimer);
  document.querySelectorAll('.drag-item').forEach(el => {
    el.dataset.x = '0';
    el.dataset.y = '0';
    el.style.left = '0px';
    el.style.top = '0px';
    el.style.fontSize = '';
    el.style.color = '';
    el.classList.remove('active-item');
  });
  activeEl = null;
}

// 站长将当前拖拽坐标与字号颜色保存到服务器数据库
function saveLayoutToServer(token){
  const data = {};
  document.querySelectorAll('.drag-item').forEach(el => {
    const k = el.dataset.key;
    if(!k) return;
    data[k] = {
      x: parseFloat(el.dataset.x || '0'),
      y: parseFloat(el.dataset.y || '0'),
      size: el.style.fontSize || '',
      color: el.style.color || ''
    };
  });

  const fd = new FormData();
  fd.append('t', token);
  fd.append('act', 'save_layout');
  fd.append('layout', JSON.stringify(data));

  fetch('index.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
      if(res.ok) alert('排版已永久保存！所有访客现在看到的都将是这个布局 ♡');
      else alert('保存失败：' + (res.msg || '未知错误'));
    })
    .catch(() => alert('网络请求失败，请检查服务器连接'));
}

// 4. 一键导出“自介 + 扩列条”高清合成图
function saveCard(){
  if(typeof html2canvas === 'undefined'){
    return alert('截图组件加载受阻，请刷新页面后重试~');
  }
  const card = document.getElementById('kuolie');
  card.classList.add('exporting');

  html2canvas(card, {
    scale: 2,
    useCORS: true,
    allowTaint: true,
    backgroundColor: '#fff5fa',
    logging: false
  }).then(canvas => {
    card.classList.remove('exporting');
    const dataUrl = canvas.toDataURL('image/png');
    const a = document.createElement('a');
    a.href = dataUrl;
    a.download = '我的自介扩列卡.png';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
  }).catch(err => {
    card.classList.remove('exporting');
    alert('生成图片失败：' + (err && err.message ? err.message : '请直接截图保存'));
  });
}