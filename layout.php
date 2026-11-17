<?php
require __DIR__.'/config.php';
// Demo: add ?now=2026-11-17 10:30 to any page to pretend it's that time.
function now_dt(){ try { return new DateTime($_GET['now'] ?? 'now'); } catch (Exception $e) { return new DateTime(); } }
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES); }
function t12($t){ return date('g:i A', strtotime($t)); }
function pct($t){ [$H,$M] = explode(':',$t); return max(0,min(100,(($H+$M/60)-DAY_START)/(DAY_END-DAY_START)*100)); }
function page_head($title, $sub, $active){
  global $pdo; $now = now_dt();
  $count = $pdo->query('SELECT COUNT(*) FROM bookings')->fetchColumn();
  $nav = ['index'=>['Dashboard','▦'],'bookings'=>['Bookings','▣'],'timetable'=>['Timetable','◔']];
  echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.h($title).' · RoomFinder</title><link rel="stylesheet" href="style.css"></head><body><aside class="side"><div class="brand"><span class="logo">▦</span><div><b>RoomFinder</b><small>CSS Department</small></div></div><p class="menu">MENU</p><nav>';
  foreach($nav as $k=>[$l,$i]) echo '<a href="'.$k.'.php" class="'.($k===$active?'on':'').'"><span>'.$i.'</span>'.$l.($k==='bookings'?'<em class="badge">'.$count.'</em>':'').'</a>';
  echo '<a class="dim"><span>▥</span>Reports<em class="soon">Soon</em></a><a class="dim"><span>⚙</span>Settings<em class="soon">Soon</em></a></nav>
  <div class="key"><p class="menu">STATUS KEY</p><p><i class="dot g"></i>Available now</p><p><i class="dot s"></i>Reserved, not yet in use</p><p><i class="dot r"></i>In use / ongoing class</p></div></aside>
  <main><header class="top"><div><h1>'.h($title).'</h1><p class="sub">'.h($sub).'</p></div><div class="chips"><span class="chip">'.$now->format('D, M j  ·  g:i A').'</span><span class="chip">🔔</span><span class="chip"><b class="av">CS</b> CSS Admin</span></div></header>';
}
function page_foot(){ echo '<p class="note">Add <code>?now=2026-11-17 10:30</code> to the URL to pretend it is a different time.</p></main><script src="app.js"></script></body></html>'; }
function booking_modal($rooms){
  $o=''; foreach($rooms as $r) $o.='<option value="'.$r['id'].'">'.h($r['name']).'</option>';
  echo '<dialog id="bm"><form method="post" action="book.php"><h2>Book <span id="bm-title">a room</span></h2>
  <label>Room<select name="room_id" id="bm-room">'.$o.'</select></label>
  <label>Date<input type="date" name="booking_date" id="bm-date" value="'.now_dt()->format('Y-m-d').'" required></label>
  <div class="two"><label>Start<input type="time" name="start_time" min="08:00" max="17:00" value="08:00" required></label><label>End<input type="time" name="end_time" min="08:00" max="17:00" value="09:00" required></label></div>
  <label>Class<input name="class_name" placeholder="BSIT 1-A" required></label>
  <div class="two"><label>Lendee (student)<input name="lendee_name" required></label><label>Student ID<input name="lendee_id" placeholder="2025-0001" required></label></div>
  <label>Key lent by<input name="key_lent_by" value="Admin Lim" required></label>
  <div class="acts"><button type="button" class="btn ghost" data-close>Cancel</button><button class="btn">Save booking</button></div></form></dialog>';
}
