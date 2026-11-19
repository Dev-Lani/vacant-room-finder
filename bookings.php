<?php
require __DIR__.'/layout.php';
require_login();
$now = now_dt(); $today = $now->format('Y-m-d');
$rooms = $pdo->query('SELECT * FROM rooms ORDER BY id')->fetchAll();
$rows = $pdo->query('SELECT b.*, r.name room FROM bookings b JOIN rooms r ON r.id=b.room_id ORDER BY booking_date DESC, start_time DESC')->fetchAll();
page_head('All bookings','Reservations with check-in / check-out history.','bookings');
$ex = form_extras();
echo '<div class="tbl"><table class="wide"><thead><tr><th>Room</th><th>Date</th><th>Time</th><th>Class</th><th>Lendee (student)</th><th>Key lent by</th><th>Check-in / out</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
foreach($rows as $b){
  $s = eff_status($b, $now);
  $log = '—';
  if ($b['checked_in_at']) {
    $log = 'In '.date('g:i A', strtotime($b['checked_in_at']));
    if ($b['checked_out_at']) $log .= '<br>Out '.date('g:i A', strtotime($b['checked_out_at'])).'<br><small>Key back: '.h($b['key_returned_by']).'</small>';
  }
  $act = '';
  $f = fn($a,$label,$cls='',$extra='') => '<form method="post" action="action.php" class="inl" '.$extra.'>'.$ex.'<input type="hidden" name="id" value="'.$b['id'].'"><input type="hidden" name="action" value="'.$a.'"><button class="btn sm '.$cls.'">'.$label.'</button></form>';
  if ($s === 'booked') {
    if ($b['booking_date'] === $today) $act .= $f('checkin','Check in');
    $act .= '<button type="button" class="btn sm ghost" data-edit data-id="'.$b['id'].'" data-room="'.$b['room_id'].'" data-date="'.$b['booking_date'].'" data-start="'.substr($b['start_time'],0,5).'" data-end="'.substr($b['end_time'],0,5).'" data-cls="'.h($b['class_name']).'" data-lname="'.h($b['lendee_name']).'" data-lid="'.h($b['lendee_id']).'">Edit</button>';
    $act .= $f('cancel','Cancel','danger','onsubmit="return confirm(\'Cancel this booking?\')"');
  } elseif ($s === 'checked-in') {
    $act .= '<button type="button" class="btn sm" data-checkout data-id="'.$b['id'].'" data-name="'.h($b['lendee_name']).'">Check out</button>';
  } else $act = '—';
  echo '<tr><td><b>'.h($b['room']).'</b></td><td>'.date('D, M j',strtotime($b['booking_date'])).'</td><td>'.t12($b['start_time']).' – '.t12($b['end_time']).'</td><td>'.h($b['class_name']).'</td><td><b>'.h($b['lendee_name']).'</b><br><small>'.h($b['lendee_id']).'</small></td><td>'.h($b['key_lent_by']).'</td><td>'.$log.'</td><td>'.status_badge($b,$now).'</td><td class="acts2">'.$act.'</td></tr>';
}
echo '</tbody></table></div><button class="btn fab" data-book>+ New booking</button>';
booking_modal($rooms); checkout_modal(); page_foot();