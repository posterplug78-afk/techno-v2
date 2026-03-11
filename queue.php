<?php
// ============================================================
//  EduQueue – Student Queue Form & Ticket
// ============================================================
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/queue.php';
require_once __DIR__ . '/includes/db.php';

requireLogin('student');
startSecureSession();

$pdo  = getDB();
$user = currentUser();

// Get or create today's session
$session      = getOrCreateTodaySession();
$sessionId    = $session['id'];
$sessionToken = $session['session_token'];

// Check for existing active queue
$existing = $pdo->prepare(
    "SELECT q.*, d.name AS dept_name FROM queues q
     JOIN departments d ON q.department_id = d.id
     WHERE q.student_id = ? AND q.session_id = ? AND q.status IN ('waiting','serving')"
);
$existing->execute([$user['id'], $sessionId]);
$activeQueue = $existing->fetch();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$activeQueue) {
    verifyCsrf();

    $deptId  = (int)($_POST['department_id'] ?? 0);
    $purpose = sanitize($_POST['purpose']    ?? '');
    $phone   = sanitize($_POST['phone']      ?? '');
    $captcha = $_POST['g-recaptcha-response'] ?? '';

    if (!$deptId || !$purpose) {
        $error = 'Please fill in all required fields.';
    } elseif (!verifyCaptcha($captcha)) {
        $error = 'CAPTCHA verification failed. Please try again.';
    } elseif (!checkRateLimit(getClientIP())) {
        $error = 'Too many requests. Please wait a few minutes.';
    } else {
        recordRateLimit(getClientIP());
        $result = generateQueueNumber($sessionId, $user['id'], $deptId, $purpose, $phone);
        if ($result['success']) {
            // Reload to show ticket
            header('Location: ' . BASE_URL . '/queue.php');
            exit;
        }
        $error = $result['message'];
    }
}

// Load departments
$depts = $pdo->query('SELECT * FROM departments WHERE is_open = 1 ORDER BY name')->fetchAll();

// Load student info
$studentInfo = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$studentInfo->execute([$user['id']]);
$student = $studentInfo->fetch();

// If active queue, get status
$queueStatus = null;
if ($activeQueue) {
    $queueStatus = getQueueStatus($activeQueue['id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Queue Form – <?= SCHOOL_NAME ?></title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY !== 'YOUR_RECAPTCHA_SITE_KEY'): ?>
<script src="https://www.google.com/recaptcha/api.js?render=<?= RECAPTCHA_SITE_KEY ?>"></script>
<?php endif; ?>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
  <div class="topbar-brand">Edu<span>Queue</span></div>
  <div class="topbar-right">
    Welcome, <?= htmlspecialchars($user['name']) ?> &nbsp;|&nbsp;
    <a href="display.php" target="_blank">📺 Live Board</a> &nbsp;|&nbsp;
    <a href="logout.php">Log Out</a>
  </div>
</div>

<div class="page-wrap">
  <div class="page-title">Student <span>Queue Portal</span></div>

  <div class="grid-2" style="align-items:start">

    <!-- LEFT COLUMN -->
    <div>
      <?php if ($activeQueue): ?>
      <!-- ── ACTIVE TICKET ── -->
      <div class="card mb-2">
        <div class="card-title">🎟️ Your Queue Ticket</div>
        <div class="alert alert-success">
          You already have an active queue number for today.
        </div>
        <div class="ticket">
          <div class="ticket-header">
            <div class="school">🏫 <?= htmlspecialchars(SCHOOL_NAME) ?></div>
            <div class="num" id="ticket-number"><?= htmlspecialchars($activeQueue['queue_number']) ?></div>
            <div class="dept"><?= htmlspecialchars($activeQueue['dept_name']) ?></div>
          </div>
          <div class="ticket-body">
            <div class="ticket-row"><span class="tl">Name</span><span class="tv"><?= htmlspecialchars($student['full_name']) ?></span></div>
            <div class="ticket-row"><span class="tl">Student ID</span><span class="tv"><?= htmlspecialchars($student['student_id'] ?? '—') ?></span></div>
            <div class="ticket-row"><span class="tl">Date</span><span class="tv"><?= date('F j, Y') ?></span></div>
            <div class="ticket-row"><span class="tl">Status</span>
              <span id="ticket-status">
                <span class="badge badge-<?= $activeQueue['status'] ?>"><?= ucfirst($activeQueue['status']) ?></span>
              </span>
            </div>
            <div class="ticket-row"><span class="tl">Now Serving</span><span class="tv" id="now-serving">—</span></div>
            <div class="ticket-row"><span class="tl">People Ahead</span><span class="tv" id="people-ahead">—</span></div>
          </div>
          <div class="ticket-footer">
            ⏱️ Est. Wait: <strong id="est-wait">calculating...</strong>
          </div>
        </div>
      </div>

      <?php else: ?>
      <!-- ── INQUIRY FORM ── -->
      <div class="card">
        <div class="card-title">📋 Inquiry Form</div>

        <?php if ($error): ?>
        <div class="alert alert-danger">⚠️ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" id="queue-form">
          <?= csrfField() ?>

          <div class="grid-2">
            <div class="form-group">
              <label>Full Name</label>
              <input class="form-control" type="text" value="<?= htmlspecialchars($student['full_name']) ?>" readonly>
            </div>
            <div class="form-group">
              <label>Student ID</label>
              <input class="form-control" type="text" value="<?= htmlspecialchars($student['student_id'] ?? '') ?>" readonly>
            </div>
          </div>

          <div class="form-group">
            <label>Email</label>
            <input class="form-control" type="email" value="<?= htmlspecialchars($student['email']) ?>" readonly>
          </div>

          <div class="form-group">
            <label for="department_id">Department to Visit *</label>
            <select class="form-control" id="department_id" name="department_id" required>
              <option value="">— Select a Department —</option>
              <?php foreach ($depts as $d): ?>
              <option value="<?= $d['id'] ?>" <?= (isset($_POST['department_id']) && $_POST['department_id'] == $d['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($d['name']) ?> (<?= $d['prefix'] ?>-001 format)
              </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group">
            <label for="purpose">Purpose of Inquiry *</label>
            <textarea class="form-control" id="purpose" name="purpose" rows="3"
              placeholder="e.g. Request for Transcript of Records" required><?= htmlspecialchars($_POST['purpose'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label for="phone">Phone Number (for SMS notification, optional)</label>
            <input class="form-control" type="tel" id="phone" name="phone"
              placeholder="09XXXXXXXXX" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
          </div>

          <!-- reCAPTCHA hidden field -->
          <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">

          <button type="submit" class="btn btn-gold btn-full mt-2" id="submit-btn">
            🎟️ Get My Queue Number
          </button>
        </form>
      </div>
      <?php endif; ?>
    </div>

    <!-- RIGHT COLUMN – Live Queue Status -->
    <div>
      <div class="card">
        <div class="card-title">📊 Live Queue <span class="live-dot" style="margin-left:auto">Live</span></div>
        <div id="live-board">
          <?php foreach ($depts as $d): ?>
          <?php
            $cs = $pdo->prepare('SELECT current_serving, last_issued FROM queue_counters WHERE department_id = ?');
            $cs->execute([$d['id']]);
            $counter = $cs->fetch();
            $serving = $counter ? ($d['prefix'] . '-' . str_pad($counter['current_serving'], 3,'0',STR_PAD_LEFT)) : '—';
            $waiting = $counter ? max(0, $counter['last_issued'] - $counter['current_serving']) : 0;
          ?>
          <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--gray-200)">
            <div>
              <div style="font-weight:700;font-size:.9rem"><?= htmlspecialchars($d['name']) ?></div>
              <div style="font-size:.75rem;color:var(--gray-500)">Now serving: <strong><?= $serving ?></strong></div>
            </div>
            <div style="text-align:right">
              <div style="font-size:.75rem;color:var(--gray-500)"><?= $waiting ?> waiting</div>
              <span class="badge badge-<?= $d['is_open'] ? 'serving' : 'done' ?>"><?= $d['is_open'] ? 'Open' : 'Closed' ?></span>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <div class="text-muted mt-2" style="font-size:.75rem">Updates every 10 seconds</div>
      </div>
    </div>

  </div><!-- /grid -->
</div>

<script>
// ── reCAPTCHA v3 ─────────────────────────────────────────────
<?php if (defined('RECAPTCHA_SITE_KEY') && RECAPTCHA_SITE_KEY !== 'YOUR_RECAPTCHA_SITE_KEY'): ?>
document.getElementById('queue-form')?.addEventListener('submit', function(e) {
    e.preventDefault();
    grecaptcha.ready(() => {
        grecaptcha.execute('<?= RECAPTCHA_SITE_KEY ?>', {action: 'submit'}).then(token => {
            document.getElementById('g-recaptcha-response').value = token;
            e.target.submit();
        });
    });
});
<?php endif; ?>

// ── POLLING for queue status ──────────────────────────────────
<?php if ($activeQueue): ?>
const QUEUE_ID = <?= $activeQueue['id'] ?>;
function pollStatus() {
    fetch('<?= BASE_URL ?>/api/queue-status.php?queue_id=' + QUEUE_ID)
        .then(r => r.json())
        .then(data => {
            if (data.error) return;
            document.getElementById('now-serving').textContent   = data.current_serving ?? '—';
            document.getElementById('people-ahead').textContent  = data.ahead + ' people';
            document.getElementById('est-wait').textContent      = data.est_wait > 0 ? data.est_wait + ' minutes' : 'Your turn soon!';
            const statusEl = document.getElementById('ticket-status');
            if (statusEl) {
                statusEl.innerHTML = `<span class="badge badge-${data.status}">${data.status.charAt(0).toUpperCase()+data.status.slice(1)}</span>`;
            }
            // Browser notification when near
            if (data.ahead <= 3 && data.ahead >= 0 && 'Notification' in window) {
                Notification.requestPermission().then(p => {
                    if (p === 'granted') {
                        new Notification('EduQueue Alert', {
                            body: `Queue ${data.queue_number} — You are almost up at the ${data.dept_name}!`,
                            icon: '<?= BASE_URL ?>/assets/img/icon.png'
                        });
                    }
                });
            }
        });
}
pollStatus();
setInterval(pollStatus, 10000);
<?php endif; ?>

// ── Refresh live board every 10s ─────────────────────────────
function refreshBoard() {
    fetch('<?= BASE_URL ?>/api/board-data.php')
        .then(r => r.json())
        .then(data => {
            const el = document.getElementById('live-board');
            if (!el || !data.length) return;
            el.innerHTML = data.map(d => `
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--gray-200)">
                  <div>
                    <div style="font-weight:700;font-size:.9rem">${d.name}</div>
                    <div style="font-size:.75px;color:var(--gray-500)">Now serving: <strong>${d.current_serving}</strong></div>
                  </div>
                  <div style="text-align:right">
                    <div style="font-size:.75rem;color:var(--gray-500)">${d.waiting_count} waiting</div>
                  </div>
                </div>
            `).join('');
        });
}
setInterval(refreshBoard, 10000);
</script>
</body>
</html>
