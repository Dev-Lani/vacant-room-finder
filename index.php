<?php
require __DIR__.'/layout.php';
require_login();
$now = now_dt(); $today = $now->format('Y-m-d');
$nowM = (int)$now->format('G')*60 + (int)$now->format('i');
function mins($t){ [$H,$M] = explode(':',$t); return $H*60+$M; }
function fm($m){ return date('g:i A', mktime(intdiv($m,60), $m%60, 0, 1, 1, 2000)); }

$rooms = $pdo->query('SELECT * FROM rooms ORDER BY floor DESC, id')->fetchAll();
$bk = $pdo->prepare("SELECT * FROM bookings WHERE status<>'cancelled' AND (booking_date=? OR status='checked-in') ORDER BY booking_date, start_time");
$bk->execute([$today]);
$by = []; $todayCount = 0;
foreach ($bk->fetchAll() as $b) { $by[$b['room_id']][] = $b; if ($b['booking_date'] === $today) $todayCount++; }
$cnt = ['available'=>0,'reserved'=>0,'in-use'=>0];
foreach ($rooms as &$r) {
  $r['bk'] = $by[$r['id']] ?? [];
  [$r['status'], $r['text']] = room_state($r['bk'], $now);
  $cnt[$r['status']]++;
} unset($r);
$labels = ['available'=>'Available','reserved'=>'Reserved','in-use'=>'In use'];

page_head('Room availability','Find a free room right now and book it in two taps.','index');

// "Free right now" banner + overdue key alerts
$freeRows = array_values(array_filter($rooms, fn($x) => $x['status'] === 'available'));
$fn = array_column($freeRows, 'name'); $n = count($fn);
$fl = $n > 1 ? implode(', ', array_slice($fn, 0, -1)).' and '.end($fn) : ($fn[0] ?? '');
echo '<section class="hero"><div><small>Free right now</small><strong>'.($n ? h($fl) : 'No rooms are free right now').'</strong><span>'
  .($n ? 'Click a free hour on a card below, or use the button to book.' : 'Check the cards below for the next free time.').'</span></div>'
  .($n ? '<button type="button" class="btn" data-book data-id="'.$freeRows[0]['id'].'" data-date="'.$today.'">Book a free room</button>' : '').'</section>';
foreach ($rooms as $x) foreach ($x['bk'] as $b) {
  if ($b['status'] === 'checked-in' && bk_end_ts($b) < $now->getTimestamp())
    echo '<section class="alert" role="alert"><div><b>Needs attention: '.h($x['name']).' key not returned</b><span>'.h($b['class_name']).' ended at '.t12($b['end_time']).' · lent to '.h($b['lendee_name']).'</span></div>'
      .'<button type="button" class="btn ghost" data-checkout data-id="'.$b['id'].'" data-name="'.h($b['lendee_name']).'">Check out &amp; return key</button></section>';
}
echo '<section class="stats">';
foreach([['Available now',$cnt['available'],'a'],['Reserved',$cnt['reserved'],'s'],['In use',$cnt['in-use'],'r'],['Bookings today',$todayCount,'i']] as [$l,$n,$c])
  echo '<div class="stat '.$c.'"><span>'.$l.'</span><strong>'.$n.'</strong></div>';
echo '</section><div class="bar"><input id="q" placeholder="Search rooms" type="search"><button class="pill on" data-f="all">All '.count($rooms).'</button>';
foreach($labels as $k=>$l) echo '<button class="pill" data-f="'.$k.'">'.$l.' '.$cnt[$k].'</button>';
echo '</div>
<div class="hint"><b>Today, hour by hour</b><span>Click a free hour to book it</span>
<span class="lg"><span><i></i>Free</span><span><i class="b"></i>Booked</span><span><i class="u"></i>In use</span></span></div>';

foreach(['Upper','Lower'] as $fl){
  echo '<h3 class="grp">'.strtoupper($fl).' ROOMS</h3><div class="grid">';
  foreach($rooms as $r) if($r['floor']===$fl){
    // active bookings today (minutes)
    $act = []; $stuck = false;
    foreach ($r['bk'] as $b) {
      $s = eff_status($b, $now);
      if ($s === 'checked-in' && $b['booking_date'] !== $today) { $stuck = true; continue; }
      if ($b['booking_date'] !== $today) continue;
      if ($s === 'checked-in' || $s === 'booked') $act[] = [mins($b['start_time']), mins($b['end_time']), $s, $b['id']];
    }
    // hour cells
    $cells = '';
    $cs = [];
    for ($h = DAY_START; $h < DAY_END; $h++) {
      $a = $h*60; $z = $a+60; $st = ''; $bid = 0;
      foreach ($act as [$bs,$be,$s,$ib]) if ($bs < $z && $be > $a) { $bid = $ib; if ($s === 'checked-in') { $st = 'inuse'; break; } $st = 'booked'; }
      if (!$st) $st = ($a + NOSHOW_GRACE_MIN < $nowM) ? 'past' : 'free';
      $cs[$h] = [$st, $bid];
    }
    // Connectedness: hours of the same booking are joined into one bar
    for ($h = DAY_START; $h < DAY_END; $h++) {
      [$st, $bid] = $cs[$h];
      $jn = $bid ? ((($cs[$h-1][1] ?? 0) === $bid ? ' jl' : '').(($cs[$h+1][1] ?? 0) === $bid ? ' jr' : '')) : '';
      $lab = date('g A', strtotime("$h:00"));
      if ($st === 'free')
        $cells .= '<button type="button" class="hc free" data-book data-id="'.$r['id'].'" data-date="'.$today.'" data-start="'.sprintf('%02d:00',$h).'" data-end="'.sprintf('%02d:00',$h+1).'" aria-label="Book '.h($r['name']).' at '.$lab.'"></button>';
      else
        $cells .= '<span class="hc '.$st.$jn.'"></span>';
    }
    // footer text
    $cur = null; $next = null; $over = $stuck;
    foreach ($act as [$bs,$be,$s]) {
      if ($s === 'checked-in') { if ($be <= $nowM) $over = true; $cur = max($cur ?? 0, $be); }
      elseif ($s === 'booked' && $bs <= $nowM && $nowM < $be) $cur = max($cur ?? 0, $be);
      elseif ($bs > $nowM && ($next === null || $bs < $next)) $next = $bs;
    }
    if ($cur !== null) { do { $ext = false; foreach ($act as [$bs,$be,$s]) if ($bs <= $cur && $be > $cur) { $cur = $be; $ext = true; } } while ($ext); }
    if ($over)                    $ft = 'Next free · key not returned';
    elseif ($nowM >= DAY_END*60)  $ft = 'Closed for today';
    elseif ($cur !== null)        $ft = 'Next free · '.fm($cur);
    elseif ($next !== null)       $ft = 'Free now until '.fm($next);
    else                          $ft = 'Next free · now';

    echo '<article class="room '.$r['status'].'" data-status="'.$r['status'].'" data-name="'.h(strtolower($r['name'])).'" data-id="'.$r['id'].'">
      <div class="rh"><h4>'.h($r['name']).'</h4><span class="st">'.($over ? 'Overdue' : $labels[$r['status']]).'</span></div>
      <p>'.h($r['text']).'</p>
      <div class="hrs">'.$cells.(($nowM >= DAY_START*60 && $nowM < DAY_END*60) ? '<s class="nl" style="left:'.pct($now->format('H:i')).'%"></s>' : '').'</div>
      <div class="ax"><span>8 AM</span><span>12 PM</span><span>5 PM</span></div>
      <div class="rf"><b>'.h($ft).'</b><button type="button" class="btn'.($r['status'] === 'available' ? '' : ' ghost').'" data-book data-id="'.$r['id'].'" data-date="'.$today.'">'.($r['status'] === 'in-use' ? 'Book later' : 'Book').'</button></div>
    </article>';
  }
  echo '</div>';
}
booking_modal($rooms); checkout_modal(); page_foot();