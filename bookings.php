<?php
require __DIR__.'/layout.php';
$rooms = $pdo->query('SELECT * FROM rooms ORDER BY id')->fetchAll();
$rows = $pdo->query('SELECT b.*, r.name room FROM bookings b JOIN rooms r ON r.id=b.room_id ORDER BY booking_date, start_time')->fetchAll();
page_head('All bookings','Every reservation for the CSS department rooms.','bookings');
if(isset($_GET['msg'])) echo '<p class="flash '.(isset($_GET['err'])?'bad':'').'">'.h($_GET['msg']).'</p>';
echo '<div class="tbl"><table><thead><tr><th>Room</th><th>Date</th><th>Time</th><th>Class</th><th>Lendee (student)</th><th>Key lent by</th></tr></thead><tbody>';
foreach($rows as $b) echo '<tr><td><b>'.h($b['room']).'</b></td><td>'.date('D, M j',strtotime($b['booking_date'])).'</td><td>'.t12($b['start_time']).' – '.t12($b['end_time']).'</td><td>'.h($b['class_name']).'</td><td><b>'.h($b['lendee_name']).'</b><br><small>'.h($b['lendee_id']).'</small></td><td>'.h($b['key_lent_by']).'</td></tr>';
echo '</tbody></table></div><button class="btn fab" data-book>+ New booking</button>';
booking_modal($rooms); page_foot();
