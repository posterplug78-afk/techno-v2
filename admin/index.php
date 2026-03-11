<?php
// ============================================================
//  EduQueue – Admin Dashboard
// ============================================================
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/queue.php';
require_once __DIR__ . '/../includes/db.php';

requireLogin('admin');
$pdo  = getDB();
$user = currentUser();

$session   = getOrCreateTodaySession();
$sessionId = $session['id'];

// Generate QR using PHP (without Composer – pure HTML data URL approach)
$qrUrl = BASE_URL . '/index.php?session=' . urlencode($session['session_token']);

// Analytics
$totals = $pdo->prepare(
    "SELECT d.name, d.prefix,
        COUNT(q.id) AS total,
        SUM(q.status='waiting')  AS waiting,
        SUM(q.status='done')     AS done,
        SUM(q.status='skipped') + SUM(q.status='missed') AS missed
     FROM departments d
     LEFT JOIN queues q ON q.department_id=d.id AND q.session_id=?
     GROUP BY d.id ORDER BY d.name"
);
$totals->execute([$sessionId]);
$analytics = $totals->fetchAll();

$grandTotal = $pdo->prepare("SELECT COUNT(*) FROM queues WHERE session_id=?");
$grandTotal->execute([$sessionId]); $grand = $grandTotal->fetchColumn();

$doneTotal = $pdo->prepare("SELECT COUNT(*) FROM queues WHERE session_id=? AND status='done'");
$doneTotal->execute([$sessionId]); $doneT = $doneTotal->fetchColumn();

$avgWait = $pdo->prepare("SELECT ROUND(AVG(TIMESTAMPDIFF(MINUTE,joined_at,served_at))) FROM queues WHERE session_id=? AND served_at IS NOT NULL");
$avgWait->execute([$sessionId]); $avgW = $avgWait->fetchColumn() ?? 0;

// Departments
$depts = $pdo->query('SELECT d.*, u.full_name AS staff_name FROM departments d LEFT JOIN users u ON d.assigned_staff_id=u.id')->fetchAll();
$staffList = $pdo->query("SELECT id, full_name, email FROM users WHERE role IN ('staff','admin') AND is_active=1")->fetchAll();

// Recent archive
$archive = $pdo->prepare(
    "SELECT q.*, u.full_name, u.student_id AS sid, d.name AS dept_name
     FROM queues q JOIN users u ON q.student_id=u.id JOIN departments d ON q.department_id=d.id
     WHERE q.session_id=? ORDER BY q.joined_at DESC LIMIT 30"
);
$archive->execute([$sessionId]);
$archiveRows = $archive->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin – <?= SCHOOL_NAME ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<!-- QR code generator library (CDN) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>

<div class="topbar">
  <div class="topbar-brand">Edu<span>Queue</span> <span style="font-size:.8rem;color:var(--gray-500);font-weight:400">&nbsp;Admin</span></div>
  <div class="topbar-right">
    <?= htmlspecialchars($user['name']) ?> &nbsp;|&nbsp;
    <a href="<?= BASE_URL ?>/display.php" target="_blank">📺 Live Board</a> &nbsp;|&nbsp;
    <a href="<?= BASE_URL ?>/staff.php">🖥️ Staff View</a> &nbsp;|&nbsp;
    <a href="<?= BASE_URL ?>/logout.php">Log Out</a>
  </div>
</div>

<div class="page-wrap">
  <div class="page-title">Admin <span>Control Panel</span></div>

  <!-- ── TODAY'S QR SESSION ── -->
  <div class="grid-2 mb-2" style="align-items:start">
    <div class="card">
      <div class="card-title">📱 Today's QR Session</div>
      <div style="text-align:center;padding:1rem 0">
        <div id="qrcode" style="display:inline-block;margin-bottom:1rem"></div>
        <div style="font-weight:700;color:var(--navy);margin-bottom:4px">Session Token</div>
        <code style="font-size:.8rem;background:var(--gray-100);padding:4px 10px;border-radius:6px"><?= htmlspecialchars($session['session_token']) ?></code>
        <div style="font-size:.78rem;color:var(--gray-500);margin-top:8px">
          📅 Valid: <?= $session['session_date'] ?> &nbsp;·&nbsp; ⏰ Expires: <?= $session['expires_at'] ?>
        </div>
        <div style="font-size:.75rem;color:var(--teal);margin-top:4px;word-break:break-all"><?= htmlspecialchars($qrUrl) ?></div>
      </div>
      <div style="display:flex;gap:8px;margin-top:1rem">
        <form method="POST" action="actions.php" style="flex:1">
          <?= csrfField() ?>
          <input type="hidden" name="action" value="regenerate_qr">
          <button class="btn btn-primary btn-full">🔄 Regenerate QR</button>
        </form>
        <button class="btn btn-outline" onclick="printQR()">🖨️ Print</button>
      </div>
    </div>

    <!-- Stats -->
    <div>
      <div class="grid-2 mb-2">
        <div class="card stat-box"><div class="stat-label">Total Today</div><div class="stat-value"><?= $grand ?></div></div>
        <div class="card stat-box"><div class="stat-label">Completed</div><div class="stat-value" style="color:var(--green)"><?= $doneT ?></div></div>
        <div class="card stat-box"><div class="stat-label">Avg Wait</div><div class="stat-value" style="color:var(--teal)"><?= $avgW ?>m</div></div>
        <div class="card stat-box"><div class="stat-label">Rate</div><div class="stat-value" style="color:var(--gold)"><?= $grand > 0 ? round($doneT/$grand*100) : 0 ?>%</div></div>
      </div>
      <div class="card">
        <div class="card-title">📊 By Department</div>
        <?php foreach ($analytics as $a): ?>
        <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gray-200)">
          <div style="font-weight:600;font-size:.85rem"><?= htmlspecialchars($a['name']) ?></div>
          <div style="font-size:.78rem;color:var(--gray-500)">
            Total: <strong><?= $a['total'] ?></strong> &nbsp;
            Done: <strong style="color:var(--green)"><?= $a['done'] ?></strong> &nbsp;
            Wait: <strong style="color:var(--gold)"><?= $a['waiting'] ?></strong>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- ── DEPARTMENTS ── -->
  <div class="card mb-2">
    <div class="card-title">🏢 Departments</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Department</th><th>Prefix</th><th>Assigned Staff</th><th>Avg. Service</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($depts as $d): ?>
        <tr>
          <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
          <td><?= htmlspecialchars($d['prefix']) ?>-xxx</td>
          <td><?= htmlspecialchars($d['staff_name'] ?? '(none)') ?></td>
          <td><?= $d['avg_service_minutes'] ?> min</td>
          <td><span class="badge badge-<?= $d['is_open'] ? 'serving' : 'done' ?>"><?= $d['is_open'] ? 'Open' : 'Closed' ?></span></td>
          <td>
            <a href="dept-edit.php?id=<?= $d['id'] ?>" class="btn btn-outline btn-sm">Edit</a>
            <form method="POST" action="actions.php" style="display:inline">
              <?= csrfField() ?>
              <input type="hidden" name="action" value="toggle_dept">
              <input type="hidden" name="dept_id" value="<?= $d['id'] ?>">
              <button class="btn btn-outline btn-sm"><?= $d['is_open'] ? 'Close' : 'Open' ?></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="margin-top:1rem">
      <a href="dept-edit.php" class="btn btn-outline btn-sm">+ Add Department</a>
    </div>
  </div>

  <!-- ── ARCHIVE / AUDIT LOG ── -->
  <div class="card mb-2">
    <div class="card-title">🗂️ Today's Queue Log</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Queue #</th><th>Student</th><th>ID</th><th>Department</th><th>Purpose</th><th>Status</th><th>Joined</th></tr></thead>
        <tbody>
        <?php foreach ($archiveRows as $r): ?>
        <tr>
          <td><strong><?= htmlspecialchars($r['queue_number']) ?></strong></td>
          <td><?= htmlspecialchars($r['full_name']) ?></td>
          <td><?= htmlspecialchars($r['sid'] ?? '—') ?></td>
          <td><?= htmlspecialchars($r['dept_name']) ?></td>
          <td><?= htmlspecialchars(mb_strimwidth($r['purpose'], 0, 40, '…')) ?></td>
          <td><span class="badge badge-<?= $r['status'] ?>"><?= ucfirst($r['status']) ?></span></td>
          <td><?= date('g:i A', strtotime($r['joined_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div style="margin-top:1rem;display:flex;gap:8px">
      <a href="export.php?session_id=<?= $sessionId ?>" class="btn btn-outline btn-sm">📥 Export CSV</a>
      <a href="archive.php" class="btn btn-outline btn-sm">📁 Full Archive</a>
    </div>
  </div>

  <!-- ── STAFF ACCOUNTS ── -->
  <div class="card mb-2">
    <div class="card-title">👤 Staff Accounts</div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($staffList as $st): ?>
<tr>
  <td><?= htmlspecialchars($st['full_name'] ?? '') ?></td>
  <td><?= htmlspecialchars($st['email']     ?? '') ?></td>
  <td>
    <?php $stRole = $st['role'] ?? ''; ?>
    <?= $stRole === 'admin'
        ? '<span class="badge badge-serving">Admin</span>'
        : '<span class="badge badge-waiting">Staff</span>' ?>
  </td>
  <td><a href="user-edit.php?id=<?= $st['id'] ?? '' ?>" class="btn btn-outline btn-sm">Edit</a></td>
</tr>
<?php endforeach; ?>    
        </tbody>
      </table>
    </div>
    <div style="margin-top:1rem">
      <a href="user-edit.php" class="btn btn-outline btn-sm">+ Add Staff</a>
    </div>
  </div>

  <!-- ── DANGER ZONE ── -->
  <div class="card" style="border:1.5px solid #fdecea">
    <div class="card-title" style="color:var(--red)">⚠️ End of Day Actions</div>
    <p class="text-muted mb-2">This will archive today's queue data, reset all counters, and generate tomorrow's QR session. Run this at end of office hours.</p>
    <form method="POST" action="actions.php" onsubmit="return confirm('Run end-of-day reset? This cannot be undone.')">
      <?= csrfField() ?>
      <input type="hidden" name="action" value="end_of_day">
      <button class="btn btn-red">🔄 Run End-of-Day Reset</button>
    </form>
  </div>

</div>

<script>
// Generate QR code
new QRCode(document.getElementById("qrcode"), {
    text: "<?= addslashes($qrUrl) ?>",
    width: 180, height: 180,
    colorDark: "#0a0f2e", colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.M
});

function printQR() {
    const qrEl = document.getElementById('qrcode');
    const img  = qrEl.querySelector('img') || qrEl.querySelector('canvas');
    const win  = window.open('', '_blank');
    win.document.write(`<html><body style="text-align:center;padding:40px">
        <h2><?= addslashes(SCHOOL_NAME) ?> – Queue System</h2>
        <p>Scan to join the queue</p>
        <img src="${img.toDataURL ? img.toDataURL() : img.src}" style="width:280px"><br>
        <code style="font-size:.8rem"><?= addslashes($session['session_token']) ?></code>
        <p style="font-size:.75rem;color:#666">Valid: <?= $session['session_date'] ?></p>
    </body></html>`);
    win.print();
}
</script>
</body>
</html>
