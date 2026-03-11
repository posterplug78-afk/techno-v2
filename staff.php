<?php
// ============================================================
//  EduQueue – Staff Queue Dashboard
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/queue.php';
require_once __DIR__ . '/includes/db.php';

requireLogin('staff');
$user = currentUser();
$pdo  = getDB();

// Get department assigned to this staff
$deptStmt = $pdo->prepare('SELECT * FROM departments WHERE assigned_staff_id = ?');
$deptStmt->execute([$user['id']]);
$dept = $deptStmt->fetch();

// Admin can pick any dept
if (!$dept && $user['role'] === 'admin') {
    $deptId = (int)($_GET['dept'] ?? 1);
    $dept   = $pdo->prepare('SELECT * FROM departments WHERE id = ?');
    $dept->execute([$deptId]);
    $dept = $dept->fetch();
}

if (!$dept) {
    die('<div style="padding:2rem">No department assigned to your account. Please contact admin.</div>');
}

$session   = getOrCreateTodaySession();
$sessionId = $session['id'];

// Stats
$stats = $pdo->prepare(
    "SELECT
        COUNT(*) AS total,
        SUM(status='waiting')  AS waiting,
        SUM(status='serving')  AS serving,
        SUM(status='done')     AS done,
        SUM(status='skipped')  AS skipped,
        SUM(status='missed')   AS missed
     FROM queues WHERE department_id = ? AND session_id = ?"
);
$stats->execute([$dept['id'], $sessionId]);
$s = $stats->fetch();

// Currently serving
$servingEntry = $pdo->prepare("SELECT q.*, u.full_name, u.student_id AS sid FROM queues q JOIN users u ON q.student_id=u.id WHERE q.department_id=? AND q.status='serving' LIMIT 1");
$servingEntry->execute([$dept['id']]);
$serving = $servingEntry->fetch();

// Waiting queue
$waitQ = $pdo->prepare(
    "SELECT q.*, u.full_name, u.student_id AS sid FROM queues q
     JOIN users u ON q.student_id = u.id
     WHERE q.department_id=? AND q.session_id=? AND q.status='waiting'
     ORDER BY q.sequence ASC"
);
$waitQ->execute([$dept['id'], $sessionId]);
$waiting = $waitQ->fetchAll();

// Done today
$doneQ = $pdo->prepare(
    "SELECT q.*, u.full_name FROM queues q JOIN users u ON q.student_id=u.id
     WHERE q.department_id=? AND q.session_id=? AND q.status IN ('done','skipped','missed')
     ORDER BY q.completed_at DESC LIMIT 20"
);
$doneQ->execute([$dept['id'], $sessionId]);
$done = $doneQ->fetchAll();

// Counter
$counter = $pdo->prepare('SELECT * FROM queue_counters WHERE department_id=?');
$counter->execute([$dept['id']]);
$ctr = $counter->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Staff Dashboard – <?= htmlspecialchars($dept['name']) ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>

<div class="topbar">
  <div class="topbar-brand">Edu<span>Queue</span> <span style="font-size:.8rem;color:var(--gray-500);font-weight:400">&nbsp;Staff</span></div>
  <div class="topbar-right">
    <?= htmlspecialchars($dept['name']) ?> &nbsp;|&nbsp;
    <?= htmlspecialchars($user['name']) ?> &nbsp;|&nbsp;
    <a href="display.php?dept=<?= $dept['id'] ?>" target="_blank">📺 Display</a> &nbsp;|&nbsp;
    <a href="logout.php">Log Out</a>
  </div>
</div>

<div class="page-wrap">
  <div class="page-title"><?= htmlspecialchars($dept['name']) ?> <span>Queue</span></div>

  <!-- Stats -->
  <div class="grid-4 mb-2">
    <div class="card stat-box"><div class="stat-label">Total Today</div><div class="stat-value"><?= $s['total'] ?></div></div>
    <div class="card stat-box"><div class="stat-label">Waiting</div><div class="stat-value" style="color:var(--gold)"><?= $s['waiting'] ?></div></div>
    <div class="card stat-box"><div class="stat-label">Done</div><div class="stat-value" style="color:var(--green)"><?= $s['done'] ?></div></div>
    <div class="card stat-box"><div class="stat-label">Avg. Service</div><div class="stat-value" style="color:var(--teal)"><?= $dept['avg_service_minutes'] ?>m</div></div>
  </div>

  <div class="grid-2" style="align-items:start">

    <!-- LEFT: Queue Management -->
    <div>
      <!-- Now Serving -->
      <div class="card mb-2">
        <div class="card-title">🎯 Now Serving</div>
        <?php if ($serving): ?>
        <div class="now-serving-card">
          <div class="ns-label">Currently at Window</div>
          <div class="ns-number"><?= htmlspecialchars($serving['queue_number']) ?></div>
          <div class="ns-next"><?= htmlspecialchars($serving['full_name']) ?> · <?= htmlspecialchars($serving['purpose']) ?></div>
        </div>
        <div style="display:flex;gap:8px">
          <form method="POST" action="api/mark-status.php" style="flex:1">
            <?= csrfField() ?>
            <input type="hidden" name="queue_id" value="<?= $serving['id'] ?>">
            <input type="hidden" name="status"   value="done">
            <button class="btn btn-teal btn-full">✓ Done</button>
          </form>
          <form method="POST" action="api/mark-status.php" style="flex:1">
            <?= csrfField() ?>
            <input type="hidden" name="queue_id" value="<?= $serving['id'] ?>">
            <input type="hidden" name="status"   value="skipped">
            <button class="btn btn-red btn-full">⊘ Skip</button>
          </form>
          <form method="POST" action="api/mark-status.php" style="flex:1">
            <?= csrfField() ?>
            <input type="hidden" name="queue_id" value="<?= $serving['id'] ?>">
            <input type="hidden" name="status"   value="missed">
            <button class="btn btn-outline btn-full">✗ Missed</button>
          </form>
        </div>
        <?php else: ?>
        <div class="text-center" style="padding:2rem 0;color:var(--gray-500)">No one currently being served.</div>
        <?php endif; ?>
      </div>

      <!-- Call Next -->
      <form method="POST" action="api/call-next.php" class="mb-2">
        <?= csrfField() ?>
        <input type="hidden" name="department_id" value="<?= $dept['id'] ?>">
        <button class="btn btn-primary btn-full" style="font-size:1rem;padding:14px">
          📢 Call Next Number →
        </button>
      </form>

      <!-- Waiting Queue -->
      <div class="card">
        <div class="card-title">🕐 Waiting Queue <span class="live-dot" style="margin-left:auto">Live</span></div>
        <?php if (empty($waiting)): ?>
        <p class="text-muted text-center" style="padding:1.5rem 0">Queue is empty.</p>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>#</th><th>Number</th><th>Student</th><th>Purpose</th><th>Est. Wait</th></tr></thead>
            <tbody>
            <?php foreach ($waiting as $i => $w):
                $ahead = $w['sequence'] - ($ctr['current_serving'] ?? 0) - 1;
                $wait  = max(0, $ahead) * $dept['avg_service_minutes'];
            ?>
            <tr>
              <td><?= $i+1 ?></td>
              <td><strong><?= htmlspecialchars($w['queue_number']) ?></strong></td>
              <td><?= htmlspecialchars($w['full_name']) ?><br><small class="text-muted"><?= htmlspecialchars($w['sid']) ?></small></td>
              <td><?= htmlspecialchars(mb_strimwidth($w['purpose'], 0, 30, '…')) ?></td>
              <td><?= $wait ?>m</td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- RIGHT: Completed / Stats -->
    <div>
      <div class="card">
        <div class="card-title">✅ Completed Today</div>
        <?php if (empty($done)): ?>
        <p class="text-muted text-center" style="padding:1.5rem 0">No records yet.</p>
        <?php else: ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Number</th><th>Student</th><th>Status</th><th>Time</th></tr></thead>
            <tbody>
            <?php foreach ($done as $d): ?>
            <tr>
              <td><strong><?= htmlspecialchars($d['queue_number']) ?></strong></td>
              <td><?= htmlspecialchars($d['full_name']) ?></td>
              <td><span class="badge badge-<?= $d['status'] ?>"><?= ucfirst($d['status']) ?></span></td>
              <td><?= $d['completed_at'] ? date('g:i A', strtotime($d['completed_at'])) : '—' ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

<script>
// Auto-refresh page every 15s
setTimeout(() => location.reload(), 15000);
</script>
</body>
</html>
