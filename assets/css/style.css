/* ============================================================
   EduQueue – Main Stylesheet
   ============================================================ */

:root {
    --navy:       #0a0f2e;
    --navy-mid:   #111840;
    --navy-light: #1a2456;
    --gold:       #e6a817;
    --gold-light: #ffd97d;
    --teal:       #00b8a4;
    --cream:      #faf7f0;
    --red:        #e84545;
    --green:      #2bbf8e;
    --gray-100:   #f4f6fa;
    --gray-200:   #e2e6f0;
    --gray-500:   #8892b0;
    --text:       #1e2a45;
    --shadow:     0 4px 24px rgba(0,0,0,.10);
    --radius:     12px;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background: var(--gray-100);
    color: var(--text);
    min-height: 100vh;
    font-size: 15px;
}

/* ── TOPBAR ──────────────────────────────────────────────── */
.topbar {
    background: var(--navy);
    color: #fff;
    padding: 0 2rem;
    height: 58px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: var(--shadow);
}
.topbar-brand { font-size: 1.25rem; font-weight: 700; color: var(--gold); letter-spacing: -.02em; }
.topbar-brand span { color: #fff; }
.topbar-right { display: flex; align-items: center; gap: 1rem; font-size: .85rem; color: var(--gray-200); }
.topbar-right a { color: var(--gray-200); text-decoration: none; }
.topbar-right a:hover { color: var(--gold); }

/* ── LAYOUT ──────────────────────────────────────────────── */
.page-wrap { max-width: 1100px; margin: 0 auto; padding: 2rem 1.5rem; }
.page-title { font-size: 1.6rem; font-weight: 700; color: var(--navy); margin-bottom: 1.5rem; }
.page-title span { color: var(--gold); }

.grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
.grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1.2rem; }
.grid-4 { display: grid; grid-template-columns: repeat(4,1fr); gap: 1rem; }

@media(max-width:900px) { .grid-2,.grid-3,.grid-4 { grid-template-columns: 1fr 1fr; } }
@media(max-width:600px) { .grid-2,.grid-3,.grid-4 { grid-template-columns: 1fr; } }

/* ── CARD ─────────────────────────────────────────────────── */
.card {
    background: #fff;
    border-radius: var(--radius);
    padding: 1.5rem;
    box-shadow: var(--shadow);
}
.card-title {
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .1em;
    color: var(--navy);
    border-bottom: 2px solid var(--gold);
    padding-bottom: 8px;
    margin-bottom: 1.2rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ── FORM ─────────────────────────────────────────────────── */
.form-group { margin-bottom: 1rem; }
.form-group label { display: block; font-size: .8rem; font-weight: 600; color: var(--navy); margin-bottom: 5px; }
.form-control {
    width: 100%;
    padding: 9px 14px;
    border: 1.5px solid var(--gray-200);
    border-radius: 8px;
    font-size: .9rem;
    color: var(--text);
    background: #fff;
    transition: border-color .2s;
    outline: none;
}
.form-control:focus { border-color: var(--teal); }
.form-control[readonly] { background: var(--gray-100); color: var(--gray-500); }

/* ── BUTTONS ─────────────────────────────────────────────── */
.btn {
    display: inline-block;
    padding: 9px 22px;
    border-radius: 8px;
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
    border: none;
    transition: all .2s;
    text-decoration: none;
    text-align: center;
}
.btn-primary   { background: var(--navy); color: #fff; }
.btn-primary:hover { background: var(--navy-light); }
.btn-gold      { background: var(--gold); color: var(--navy); }
.btn-gold:hover { background: var(--gold-light); }
.btn-teal      { background: var(--teal); color: #fff; }
.btn-teal:hover { filter: brightness(1.1); }
.btn-red       { background: var(--red); color: #fff; }
.btn-outline   { background: transparent; border: 1.5px solid var(--gray-200); color: var(--text); }
.btn-outline:hover { border-color: var(--teal); color: var(--teal); }
.btn-sm        { padding: 5px 14px; font-size: .78rem; }
.btn-full      { width: 100%; display: block; }
.btn:disabled  { opacity: .55; cursor: not-allowed; }

/* ── BADGE ───────────────────────────────────────────────── */
.badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: .7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .06em;
}
.badge::before { content:''; width:7px; height:7px; border-radius:50%; }
.badge-waiting  { background: #fff8e6; color: #b07d00; } .badge-waiting::before  { background:#e6a817; animation:blink 1.4s infinite; }
.badge-serving  { background: #e6faf7; color: #007a6e; } .badge-serving::before  { background:var(--teal); animation:blink .9s infinite; }
.badge-done     { background: #e6faf3; color: #1a8a5f; } .badge-done::before     { background:var(--green); }
.badge-skipped  { background: #fdecea; color: #b52020; } .badge-skipped::before  { background:var(--red); }
.badge-missed   { background: #f0f0f0; color: #666;    } .badge-missed::before   { background:#aaa; }
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }

/* ── TABLE ───────────────────────────────────────────────── */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: .85rem; }
thead { background: var(--navy); }
thead th { padding: 10px 14px; text-align: left; color: #fff; font-weight: 600; font-size: .75rem; text-transform: uppercase; letter-spacing: .08em; white-space: nowrap; }
tbody tr { border-bottom: 1px solid var(--gray-200); transition: background .15s; }
tbody tr:hover { background: var(--gray-100); }
tbody td { padding: 10px 14px; vertical-align: middle; }

/* ── STAT BOX ────────────────────────────────────────────── */
.stat-box { padding: 1.2rem; }
.stat-label { font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: var(--gray-500); margin-bottom: 6px; }
.stat-value { font-size: 2.2rem; font-weight: 800; color: var(--navy); line-height: 1; }
.stat-sub   { font-size: .72rem; color: var(--gray-500); margin-top: 4px; }

/* ── ALERT ───────────────────────────────────────────────── */
.alert { padding: 11px 16px; border-radius: 8px; font-size: .85rem; margin-bottom: 1rem; display: flex; gap: 8px; align-items: flex-start; }
.alert-info    { background: #e8f4fd; border-left: 4px solid #2196f3; color: #0d47a1; }
.alert-success { background: #e6faf3; border-left: 4px solid var(--green); color: #1a6b47; }
.alert-warning { background: #fff8e6; border-left: 4px solid var(--gold); color: #7a5200; }
.alert-danger  { background: #fdecea; border-left: 4px solid var(--red); color: #8b0000; }

/* ── QUEUE TICKET ────────────────────────────────────────── */
.ticket {
    background: #fff;
    border: 2px dashed var(--gray-200);
    border-radius: 16px;
    overflow: hidden;
    max-width: 340px;
    margin: 0 auto;
    box-shadow: 0 8px 32px rgba(0,0,0,.12);
}
.ticket-header {
    background: var(--navy);
    padding: 1.5rem;
    text-align: center;
    color: #fff;
}
.ticket-header .school { font-size: .72rem; letter-spacing: .12em; text-transform: uppercase; color: var(--gold); margin-bottom: 8px; }
.ticket-header .num    { font-size: 5rem; font-weight: 900; line-height: 1; }
.ticket-header .dept   { font-size: .9rem; color: var(--gray-200); margin-top: 4px; }
.ticket-body { padding: 1.2rem; }
.ticket-row  { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: .82rem; }
.ticket-row .tl { color: var(--gray-500); }
.ticket-row .tv { font-weight: 600; color: var(--navy); }
.ticket-footer { background: var(--gray-100); padding: 1rem; text-align: center; font-size: .72rem; color: var(--gray-500); border-top: 1px dashed var(--gray-200); }

/* ── NOW SERVING BOARD ───────────────────────────────────── */
.now-serving-card {
    background: var(--navy);
    border-radius: var(--radius);
    padding: 1.5rem;
    text-align: center;
    margin-bottom: 1.2rem;
}
.ns-label  { font-size: .7rem; letter-spacing: .12em; text-transform: uppercase; color: var(--gray-500); margin-bottom: 8px; }
.ns-number { font-size: 4rem; font-weight: 900; color: var(--teal); line-height: 1; }
.ns-next   { font-size: .82rem; color: var(--gray-400, #999); margin-top: 8px; }

/* ── MISC ────────────────────────────────────────────────── */
.live-dot { display: inline-flex; align-items: center; gap: 5px; font-size: .7rem; color: var(--teal); }
.live-dot::before { content:''; width:7px; height:7px; border-radius:50%; background:var(--teal); animation:blink .8s infinite; }
.divider { border: none; border-top: 1px solid var(--gray-200); margin: 1.2rem 0; }
.text-center { text-align: center; }
.text-muted  { color: var(--gray-500); font-size: .82rem; }
.mt-1 { margin-top: .5rem; } .mt-2 { margin-top: 1rem; } .mt-3 { margin-top: 1.5rem; }
.mb-1 { margin-bottom: .5rem; } .mb-2 { margin-bottom: 1rem; }
.flex { display: flex; } .items-center { align-items: center; } .justify-between { justify-content: space-between; }
.gap-1 { gap: .5rem; } .gap-2 { gap: 1rem; }

/* ── LOGIN PAGE ──────────────────────────────────────────── */
.login-wrap { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: var(--navy); }
.login-card { background: #fff; border-radius: 16px; padding: 2.5rem; width: 100%; max-width: 400px; box-shadow: 0 16px 48px rgba(0,0,0,.25); }
.login-logo { text-align: center; margin-bottom: 2rem; }
.login-logo h1 { font-size: 2rem; font-weight: 900; color: var(--navy); }
.login-logo h1 span { color: var(--gold); }
.login-logo p { color: var(--gray-500); font-size: .85rem; margin-top: 4px; }
