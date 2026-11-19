(()=>{
const L=window.PL||[]; if(!L.length) return;
const $=s=>document.querySelector(s);
const root=document.createElement('div'); root.id='mp';
root.innerHTML='<span id="mpmini"></span><button id="mpd" title="音乐">♪</button><div id="mpb"><div class="mpt"><b id="mpn"></b><small id="mpa"></small></div><div id="mpl"></div><div class="mpc"><button id="mpv">⏮</button><button id="mpp">▶</button><button id="mpx">⏭</button></div><input type="range" id="mps" min="0" max="1000" value="0"><div class="mpm"><span id="mpcur">0:00</span><button id="mpmode" title="切换播放模式">🔁</button><span id="mpdur">0:00</span></div></div>';
document.body.appendChild(root);
const au=new Audio(); au.preload='metadata';
const modes=[['🔁','列表循环'],['🔂','单曲循环'],['🔀','随机播放']];
let idx=0,mode=0,lines=[],cur=-1,seeking=false,lastSave=0;
const fmt=t=>isFinite(t)?Math.floor(t/60)+':'+String(Math.floor(t%60)).padStart(2,'0'):'0:00';
const store=(k,v)=>{try{localStorage.setItem('mp_'+k,v)}catch(e){}};
const load_=k=>{try{return localStorage.getItem('mp_'+k)}catch(e){return null}};
function parse(s){const out=[];(s||'').split(/\r?\n/).forEach(l=>{const re=/\[(\d+):(\d+(?:[.:]\d+)?)\]/g;let m,ts=[];while((m=re.exec(l)))ts.push(+m[1]*60+parseFloat(m[2].replace(':','.')));const x=l.replace(/\[[^\]]*\]/g,'').trim();if(ts.length&&x)ts.forEach(t=>out.push({t,x}));});return out.sort((a,b)=>a.t-b.t);}
function setSong(i,t){
  idx=(i+L.length)%L.length; const s=L[idx];
  au.src=s.src; if(t>0) au.addEventListener('loadedmetadata',()=>{au.currentTime=t},{once:true});
  $('#mpn').textContent=s.t; $('#mpa').textContent=s.a||'';
  lines=parse(s.lrc); cur=-1;
  const box=$('#mpl'); box.innerHTML='';
  if(!lines.length){const p=document.createElement('p');p.className='none';p.textContent='♪ 暂无歌词 ♪';box.appendChild(p);}
  lines.forEach((l,k)=>{const p=document.createElement('p');p.textContent=l.x;p.onclick=()=>{au.currentTime=l.t;};box.appendChild(p);});
  $('#mpmini').classList.toggle('has',!!lines.length); $('#mpmini').textContent='';
  store('idx',idx);
  if('mediaSession' in navigator) navigator.mediaSession.metadata=new MediaMetadata({title:s.t,artist:s.a||''});
}
function next(auto){
  if(mode==2&&L.length>1){let n;do{n=Math.floor(Math.random()*L.length)}while(n==idx);setSong(n)}
  else setSong(idx+1);
  au.play().catch(()=>{});
}
function prev(){ if(au.currentTime>3){au.currentTime=0;return} setSong(idx-1); au.play().catch(()=>{}); }
function toggle(){ au.paused?au.play().catch(()=>{}):au.pause(); }
au.onplay=()=>{root.classList.add('on');$('#mpp').textContent='⏸'};
au.onpause=()=>{root.classList.remove('on');$('#mpp').textContent='▶'};
au.onended=()=>{ if(mode==1){au.currentTime=0;au.play()} else next(true) };
au.ontimeupdate=()=>{
  const t=au.currentTime;
  if(!seeking&&au.duration) $('#mps').value=t/au.duration*1000;
  $('#mpcur').textContent=fmt(t); $('#mpdur').textContent=fmt(au.duration);
  let c=-1; for(let k=0;k<lines.length;k++){ if(lines[k].t<=t) c=k; else break; }
  if(c!==cur){ cur=c; const ps=$('#mpl').querySelectorAll('p:not(.none)');
    ps.forEach((p,k)=>p.classList.toggle('cur',k===c));
    if(c>=0&&ps[c]){ const b=$('#mpl'); b.scrollTo({top:ps[c].offsetTop-b.clientHeight/2+ps[c].clientHeight/2,behavior:'smooth'}); $('#mpmini').textContent='♪ '+lines[c].x; } }
  if(Date.now()-lastSave>2000){ lastSave=Date.now(); store('t',t); }
};
au.onloadedmetadata=()=>{$('#mpdur').textContent=fmt(au.duration)};
$('#mpd').onclick=()=>{ root.classList.toggle('open'); store('open',root.classList.contains('open')?1:0); };
$('#mpp').onclick=toggle; $('#mpx').onclick=()=>next(); $('#mpv').onclick=prev;
$('#mpmode').onclick=()=>{ mode=(mode+1)%3; $('#mpmode').textContent=modes[mode][0]; $('#mpmode').title=modes[mode][1]; store('mode',mode); };
$('#mps').oninput=()=>{seeking=true;$('#mpcur').textContent=fmt($('#mps').value/1000*au.duration)};
$('#mps').onchange=()=>{ if(au.duration) au.currentTime=$('#mps').value/1000*au.duration; seeking=false; };
document.addEventListener('click',e=>{ if(!root.contains(e.target)) root.classList.remove('open'); });
if('mediaSession' in navigator){ const m=navigator.mediaSession; m.setActionHandler('play',toggle); m.setActionHandler('pause',toggle); m.setActionHandler('previoustrack',prev); m.setActionHandler('nexttrack',()=>next()); }
mode=+load_('mode')||0; $('#mpmode').textContent=modes[mode][0];
const si=+load_('idx')||0, st=+load_('t')||0;
setSong(si<L.length?si:0, si<L.length?st:0);
console.log('%c ✦ CYBER KAWAII PORTFOLIO %c Designed & Coded by 书未寄故人','background:linear-gradient(90deg,#ff7eb3,#9d8aff);color:#fff;padding:4px 8px;border-radius:6px 0 0 6px;font-weight:bold','background:#2b1e34;color:#ffb3d1;padding:4px 10px;border-radius:0 6px 6px 0');
})();
