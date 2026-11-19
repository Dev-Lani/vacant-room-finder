<?php
require __DIR__.'/config.php';

/* ---------- basic helpers ---------- */
// Demo time (?now=2026-11-19 09:00) only works when DEBUG is true in config.php
function now_dt(){
  $n = DEBUG ? ($_POST['now'] ?? $_GET['now'] ?? null) : null;
  if ($n) { try { return new DateTime($n); } catch (Exception $e) {} }
  return new DateTime();
}
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES); }
function t12($t){ return date('g:i A', strtotime($t)); }
function ic($d,$s=18){ return '<svg width="'.$s.'" height="'.$s.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="'.$d.'"/></svg>'; }
function pct($t){ [$H,$M] = explode(':',$t); return max(0,min(100,(($H+$M/60)-DAY_START)/(DAY_END-DAY_START)*100)); }
// Edit this to change the class options. Years 3-4 use specializations instead of sections A-C.
function class_map(){
  $spec = ['BSIT' => ['WMAD','SMP','AMG'], 'BSCS' => ['IS']];
  $m = [];
  foreach ($spec as $p => $sp)
    foreach ([1,2,3,4] as $y) $m[$p][$y] = $y >= 3 ? $sp : ['A','B','C'];
  return $m;
}

/* ---------- auth, CSRF, flash ---------- */
function me(){ return $_SESSION['admin'] ?? null; }
function require_login(){ if (!me()) { header('Location: login.php'); exit; } }
function csrf_token(){ return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(){ return '<input type="hidden" name="csrf" value="'.csrf_token().'">'; }
function csrf_check(){
  if (!hash_equals($_SESSION['csrf'] ?? '', (string)($_POST['csrf'] ?? ''))) {
    http_response_code(419); exit('Form expired or invalid. Go back and refresh the page.');
  }
}
function flash($msg, $bad = false){ $_SESSION['flash'] = [$msg, $bad]; }
// Floating notification (top-right). Auto-dismisses; app.js wires up close + timer.
function toast_html($m, $bad = false){
  $d = $bad ? 'M12 7v6M12 16.5v.01' : 'M7.5 12.5l3 3 6-6.5';
  return '<div class="toast '.($bad ? 'bad' : 'ok').'" role="'.($bad ? 'alert' : 'status').'">'
    .'<span class="ti"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5" pathLength="1"/><path d="'.$d.'" pathLength="1"/></svg></span>'
    .'<p>'.h($m).'</p><button type="button" class="tx" aria-label="Dismiss">&times;</button><i class="tbar"></i></div>';
}
function show_flash(){
  if (empty($_SESSION['flash'])) return;
  [$m,$b] = $_SESSION['flash']; unset($_SESSION['flash']);
  echo '<div class="toasts">'.toast_html($m, $b).'</div>';
}
// Where to return after a POST (whitelisted pages only)
function back_value(){
  $a = [];
  if (isset($_GET['date'])) $a['date'] = $_GET['date'];
  if (DEBUG && isset($_GET['now'])) $a['now'] = $_GET['now'];
  return basename($_SERVER['PHP_SELF']).($a ? '?'.http_build_query($a) : '');
}
function go($back = 'index.php'){
  [$p,$q] = array_pad(explode('?', (string)$back, 2), 2, '');
  if (!in_array($p, ['index.php','bookings.php','timetable.php'], true)) $p = 'index.php';
  parse_str($q, $a); $out = [];
  if (isset($a['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $a['date'])) $out['date'] = $a['date'];
  if (DEBUG && !empty($a['now'])) $out['now'] = $a['now'];
  header('Location: '.$p.($out ? '?'.http_build_query($out) : '')); exit;
}
function form_extras(){
  $o = csrf_field().'<input type="hidden" name="back" value="'.h(back_value()).'">';
  if (DEBUG && isset($_GET['now'])) $o .= '<input type="hidden" name="now" value="'.h($_GET['now']).'">';
  return $o;
}

/* ---------- booking status logic ---------- */
// booked -> checked-in -> completed | no-show | cancelled
function bk_start_ts($b){ return strtotime($b['booking_date'].' '.$b['start_time']); }
function bk_end_ts($b){ return strtotime($b['booking_date'].' '.$b['end_time']); }
// A "booked" slot that nobody checked into within the grace period counts as no-show (room is released)
function eff_status($b, $now){
  if ($b['status'] !== 'booked') return $b['status'];
  return $now->getTimestamp() > bk_start_ts($b) + NOSHOW_GRACE_MIN*60 ? 'no-show' : 'booked';
}
function status_badge($b, $now){
  $s = eff_status($b, $now);
  $l = ['booked'=>'Booked','checked-in'=>'In use','completed'=>'Completed','no-show'=>'No-show','cancelled'=>'Cancelled'][$s];
  if ($s === 'checked-in' && $now->getTimestamp() > bk_end_ts($b)) { $l = 'Overdue'; $s = 'overdue'; }
  return '<span class="bs '.$s.'">'.$l.'</span>';
}
// $bks: bookings of one room, ordered by date/start. Returns [status, text]
function room_state($bks, $now){
  $t = $now->getTimestamp(); $next = null;
  foreach ($bks as $b) {
    $s = eff_status($b, $now);
    if ($s === 'checked-in') {
      return ['in-use', $t > bk_end_ts($b)
        ? 'Overdue · key not returned · '.$b['class_name']
        : 'In use until '.t12($b['end_time']).' · '.$b['class_name']];
    }
    if ($s === 'booked' && !$next) $next = $b;
  }
  if ($next) return ['reserved', (bk_start_ts($next) <= $t ? 'Awaiting check-in · ' : 'Reserved ')
    .t12($next['start_time']).' – '.t12($next['end_time']).' · '.$next['class_name']];
  return ['available', 'Free for the rest of the day'];
}

/* ---------- page chrome ---------- */
function page_head($title, $sub, $active){
  global $pdo; $now = now_dt();
  $count = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status<>'cancelled'")->fetchColumn();
  $nav = [
    'index'=>['Dashboard','M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z'],
    'bookings'=>['Bookings','M8 3h8a1 1 0 0 1 1 1v1h2a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h2V4a1 1 0 0 1 1-1zM8 11h8M8 15h5'],
    'timetable'=>['Timetable','M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18zM12 7v5l3 2'],
    'qr'=>['QR Codes','M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h3v3h-3zM14 20h3M20 17v4'],
    'admins'=>['Admins','M16 20v-1a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v1M9.5 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7zM21 20v-1a4 4 0 0 0-3-3.9M16 4.1a3.5 3.5 0 0 1 0 6.8']];
  $name = me()['name'] ?? ''; $ini = '';
  foreach (array_slice(preg_split('/\s+/', trim($name)), 0, 2) as $w) $ini .= strtoupper(mb_substr($w,0,1));
  echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).' · RoomFinder</title><link rel="stylesheet" href="style.css"></head><body><aside class="side"><div class="brand"><span class="logo">'.ic('M3 3h7v7H3zM14 3h7v7h-7zM3 14h7v7H3zM14 14h7v7h-7z',20).'</span><div><b>RoomFinder</b><small>CSS Department</small></div></div><p class="menu">MENU</p><nav>';
  foreach($nav as $k=>[$l,$i]) echo '<a href="'.$k.'.php" class="'.($k===$active?'on':'').'">'.$l.($k==='bookings'?'<em class="badge">'.$count.'</em>':'').'</a>';
  echo '</nav>
  <div class="key"><p class="menu">STATUS KEY</p><p><i class="dot g"></i>Available now</p><p><i class="dot s"></i>Booked, awaiting check-in</p><p><i class="dot r"></i>In use (checked in)</p></div>
  <form method="post" action="logout.php" class="lo">'.csrf_field().'<button>Log out</button></form></aside>
  <main><header class="top"><div><h1>'.h($title).'</h1><p class="sub">'.h($sub).'</p></div><div class="chips"><span class="chip">'.$now->format('D, M j  ·  g:i A').'</span><span class="chip"><b class="av">'.h($ini).'</b> '.h($name).'</span></div></header>';
  show_flash();
}
function page_foot($scripts = ''){
  echo (DEBUG ? '<p class="note">Debug: add <code>?now=2026-11-19 09:00</code> to the URL to pretend it is a different time.</p>' : '')
    .'</main>'.$scripts.'<script src="app.js"></script></body></html>';
}
function booking_modal($rooms){
  $o = ''; foreach($rooms as $r) $o .= '<option value="'.$r['id'].'">'.h($r['name']).'</option>';
  $today = now_dt()->format('Y-m-d');
  $cm = class_map();
  $po = ''; foreach (array_keys($cm) as $p) $po .= '<option>'.h($p).'</option>';
  echo '<dialog id="bm" data-classes="'.h(json_encode($cm)).'"><form method="post" action="book.php">'.form_extras().'<input type="hidden" name="id" id="bm-id">
  <h2><span id="bm-h">Book</span> <span id="bm-title">a room</span></h2>
  <label>Room<select name="room_id" id="bm-room">'.$o.'</select></label>
  <label>Date<input type="date" name="booking_date" id="bm-date" min="'.$today.'" value="'.$today.'" required></label>
  <div class="two"><label>Start<input type="time" name="start_time" min="08:00" max="17:00" value="08:00" required></label><label>End<input type="time" name="end_time" min="08:00" max="17:00" value="09:00" required></label></div>
  <small>Max '.MAX_BOOKING_HOURS.' hours per booking. If nobody checks in within '.NOSHOW_GRACE_MIN.' min after the start time, the room is released.</small>
  <div class="three">
    <label>Program<select name="program" id="bm-prog" required>'.$po.'</select></label>
    <label>Year<select name="year_level" id="bm-year" required><option value="1">1st</option><option value="2">2nd</option><option value="3">3rd</option><option value="4">4th</option></select></label>
    <label><span id="bm-seclbl">Section</span><select name="section" id="bm-sec" required></select></label>
  </div>
  <div class="two"><label>Lendee (student)<input name="lendee_name" maxlength="80" required></label>
  <label>Student ID<input name="lendee_id" id="bm-lid" maxlength="9" inputmode="numeric" pattern="\d{4}-\d{4}" placeholder="0424-0000" title="Format: 0000-0000" required></label></div>
  <div class="acts"><button type="button" class="btn ghost" data-close>Cancel</button><button class="btn">Save booking</button></div></form></dialog>';
}
function checkout_modal(){
  echo '<dialog id="co"><form method="post" action="action.php">'.form_extras().'<input type="hidden" name="action" value="checkout"><input type="hidden" name="id" id="co-id">
  <h2>Check out &amp; return key</h2>
  <label>Key returned by<input name="key_returned_by" id="co-name" maxlength="60" required></label>
  <div class="acts"><button type="button" class="btn ghost" data-close>Cancel</button><button class="btn">Confirm return</button></div></form></dialog>';
}