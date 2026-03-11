<?php
// ============================================================
//  EduQueue – Public Live Queue Display Board
//  Designed for office TV/monitor (kiosk mode)
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/queue.php';
require_once __DIR__ . '/includes/db.php';

$pdo   = getDB();
$board = getLiveBoardData();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Live Queue Board – <?= SCHOOL_NAME ?></title>
<style>
  :root {
    --navy:#0a0f2e; --gold:#e6a817; --teal:#00b8a4; --green:#2bbf8e;
    --red:#e84545; --gray:#8892b0;
  }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { background:var(--navy); color:#fff; font-family:'Segoe UI',Arial,sans-serif; min-height:100vh; }

  header {
    display:flex; align-items:center; justify-content:space-between;
    padding:14px 2rem; border-bottom:2px solid rgba(255,255,255,.08);
    background:rgba(0,0,0,.3);
  }
  header h1 { font-size:1.4rem; font-weight:800; color:var(--gold); }
  header h1 span { color:#fff; }
  #clock { font-size:1.2rem; font-weight:700; color:var(--teal); }
  #date-str { font-size:.85rem; color:var(--gray); }

  .board { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:1.2rem; padding:1.5rem; }

  .dept-card { background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.1); border-radius:16px; overflow:hidden; }
  .dept-head { background:rgba(255,255,255,.08); padding:12px 18px; display:flex; align-items:center; justify-content:space-between; }
  .dept-head .dname { font-size:.9rem; font-weight:700; color:var(--gold); }
  .live-dot { display:inline-flex;align-items:center;gap:5px;font-size:.7rem;color:var(--teal); }
  .live-dot::before{content:'';width:7px;height:7px;border-radius:50%;background:var(--teal);animation:blink .8s infinite;}
  @keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}

  .now-num { text-align:center; padding:1.5rem 1rem; }
  .now-num small { display:block; font-size:.65rem; letter-spacing:.14em; text-transform:uppercase; color:var(--gray); margin-bottom:6px; }
  .now-num .big { font-size:4.5rem; font-weight:900; line-height:1; color:var(--teal); }

  .next-row { padding:0 1rem 1rem; }
  .next-title { font-size:.65rem; text-transform:uppercase; letter-spacing:.1em; color:var(--gray); margin-bottom:8px; }
  .next-item { display:flex;align-items:center;justify-content:space-between;padding:8px 10px;background:rgba(255,255,255,.04);border-radius:8px;margin-bottom:6px; }
  .next-item .nnum { font-weight:700; font-size:.95rem; }
  .next-item .nname { font-size:.75rem; color:var(--gray); }
  .next-item .nwait { font-size:.7rem; color:var(--gold); }

  footer { text-align:center; padding:10px; font-size:.72rem; color:var(--gray); border-top:1px solid rgba(255,255,255,.06); }
</style>
</head>
<body>

<header>
  <h1>Edu<span>Queue</span> &nbsp;·&nbsp; <span style="color:#fff;font-weight:400;font-size:1rem"><?= htmlspecialchars(SCHOOL_NAME) ?></span></h1>
  <div style="text-align:right">
    <div id="clock">--:--:--</div>
    <div id="date-str"></div>
  </div>
</header>

<div class="board" id="board">
<?php foreach ($board as $d): ?>
<div class="dept-card" data-dept-id="<?= $d['id'] ?>">
  <div class="dept-head">
    <span class="dname"><?= htmlspecialchars($d['name']) ?></span>
    <span class="live-dot">Live</span>
  </div>
  <div class="now-num">
    <small>Now Serving</small>
    <div class="big now-serving-num"><?= htmlspecialchars($d['current_serving']) ?></div>
  </div>
  <div class="next-row">
    <div class="next-title">Next in Line</div>
    <div class="next-list">
    <?php if (empty($d['next'])): ?>
    <div style="text-align:center;padding:10px 0;font-size:.8rem;color:var(--gray)">Queue is empty</div>
    <?php else: ?>
    <?php foreach (array_slice($d['next'], 0, 4) as $n): ?>
    <div class="next-item">
      <div>
        <div class="nnum"><?= htmlspecialchars($n['queue_number']) ?></div>
        <div class="nname"><?= htmlspecialchars($n['full_name']) ?></div>
      </div>
      <div class="nwait">~<?= max(0,$n['est_wait']) ?>m</div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>

<footer>
  Auto-refreshing every 5 seconds &nbsp;·&nbsp; <?= htmlspecialchars(SCHOOL_NAME) ?> Inquiry Queue System
</footer>

<script>
function updateClock() {
  const now = new Date();
  document.getElementById('clock').textContent = now.toLocaleTimeString('en-PH');
  document.getElementById('date-str').textContent = now.toLocaleDateString('en-PH',{weekday:'long',year:'numeric',month:'long',day:'numeric'});
}
setInterval(updateClock, 1000); updateClock();

function refreshBoard() {
  fetch('<?= BASE_URL ?>/api/board-data.php')
    .then(r => r.json())
    .then(data => {
      data.forEach(d => {
        const card = document.querySelector('[data-dept-id="' + d.id + '"]');
        if (!card) return;
        card.querySelector('.now-serving-num').textContent = d.current_serving;
        const list = card.querySelector('.next-list');
        if (!d.next.length) {
          list.innerHTML = '<div style="text-align:center;padding:10px 0;font-size:.8rem;color:var(--gray)">Queue is empty</div>';
        } else {
          list.innerHTML = d.next.slice(0,4).map(n => `
            <div class="next-item">
              <div><div class="nnum">${n.queue_number}</div><div class="nname">${n.full_name}</div></div>
              <div class="nwait">~${Math.max(0,n.est_wait)}m</div>
            </div>`).join('');
        }
      });
    });
}
setInterval(refreshBoard, 5000);
</script>
</body>
</html>
