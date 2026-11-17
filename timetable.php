<?php
require __DIR__.'/layout.php';
$now = now_dt(); $date = $_GET['date'] ?? $now->format('Y-m-d');
$d = new DateTime($date); $prev = (clone $d)->modify('-1 day')->format('Y-m-d'); $next = (clone $d)->modify('+1 day')->format('Y-m-d');
$rooms = $pdo->query('SELECT * FROM rooms ORDER BY floor DESC, id')->fetchAll();
$q = $pdo->prepare('SELECT * FROM bookings WHERE booking_date=?'); $q->execute([$d->format('Y-m-d')]);
$by=[]; foreach($q->fetchAll() as $b) $by[$b['room_id']][]=$b;
$isToday = $d->format('Y-m-d')===$now->format('Y-m-d'); $nt=$now->format('H:i:s');
page_head('Timetable','All rooms across the day, 8:00 AM to 5:00 PM.','timetable');
if(isset($_GET['msg'])) echo '<p class="flash '.(isset($_GET['err'])?'bad':'').'">'.h($_GET['msg']).'</p>';
echo '<div class="nav"><a class="btn ghost" href="?date='.$prev.'">‹</a><span class="chip">'.$d->format('l, F j, Y').'</span><a class="btn ghost" href="?date='.$next.'">›</a><a class="btn ghost" href="timetable.php">Today</a>
<span class="legend"><i class="tag r"></i>In use now <i class="tag s"></i>Booked <i class="tag"></i>Free</span></div><div class="tt"><div class="tt-h"><span>ROOM</span><div>';
for($h=8;$h<17;$h++) echo '<span>'.date('g A',strtotime("$h:00")).'</span>';
echo '</div></div>';
foreach($rooms as $r){
  echo '<div class="tt-r"><a class="tt-n" href="#" data-book data-id="'.$r['id'].'" data-date="'.$d->format('Y-m-d').'"><b>'.h($r['name']).'</b><small>'.$r['floor'].' · tap to book</small></a><div class="tt-c">';
  foreach($by[$r['id']] ?? [] as $b){
    $live = $isToday && $b['start_time']<=$nt && $nt<$b['end_time'];
    echo '<div class="blk '.($live?'live':'').'" style="left:'.pct($b['start_time']).'%;width:'.(pct($b['end_time'])-pct($b['start_time'])).'%"><b>'.h($b['class_name']).'</b><span>'.h($b['lendee_name']).'</span></div>';
  }
  if($isToday) echo '<s class="nowline" style="left:'.pct($now->format('H:i')).'%"></s>';
  echo '</div></div>';
}
echo '</div><p class="note">The indigo line marks the current time. Click a room name to book it for this date.</p>';
booking_modal($rooms); page_foot();
