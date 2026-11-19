<?php
require __DIR__.'/layout.php';
require_login();
$now = now_dt(); $today = $now->format('Y-m-d');
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
page_head('Room availability','See which rooms are free right now and book a slot.','index');
echo '<section class="stats">';
foreach([['Available now',$cnt['available'],'g'],['Reserved',$cnt['reserved'],'s'],['In use',$cnt['in-use'],'r'],['Bookings today',$todayCount,'i']] as [$l,$n,$c])
  echo '<div class="stat"><span>'.$l.'</span><i class="tag '.$c.'"></i><strong>'.$n.'</strong></div>';
echo '</section><div class="bar"><input id="q" placeholder="Search rooms" type="search"><button class="pill on" data-f="all">All '.count($rooms).'</button>';
foreach($labels as $k=>$l) echo '<button class="pill" data-f="'.$k.'">'.$l.' '.$cnt[$k].'</button>';
echo '</div>';
foreach(['Upper','Lower'] as $fl){
  echo '<h3 class="grp">'.strtoupper($fl).' ROOMS</h3><div class="grid">';
  foreach($rooms as $r) if($r['floor']===$fl){
    echo '<article class="room '.$r['status'].'" data-status="'.$r['status'].'" data-name="'.h(strtolower($r['name'])).'" data-id="'.$r['id'].'" tabindex="0"><div class="rh"><h4>'.h($r['name']).'</h4><span class="st">'.$labels[$r['status']].'</span></div><p>'.h($r['text']).'</p><div class="track">';
    foreach($r['bk'] as $b) {
      $s = eff_status($b, $now);
      if ($b['booking_date'] !== $today || $s === 'no-show') continue;
      echo '<u class="'.$s.'" style="left:'.pct($b['start_time']).'%;width:'.(pct($b['end_time'])-pct($b['start_time'])).'%"></u>';
    }
    echo '<s style="left:'.pct($now->format('H:i')).'%"></s></div><div class="ax"><span>8 AM</span><span>12 PM</span><span>5 PM</span></div></article>';
  }
  echo '</div>';
}
echo '<div class="pick"><span class="logo sm">▣</span><h4>Pick a room to book</h4><p>Select any room card to reserve a time slot.</p></div>';
booking_modal($rooms); page_foot();