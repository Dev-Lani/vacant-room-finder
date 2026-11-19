<?php
// Public page (no login): what the QR code on the room door opens.
require __DIR__.'/layout.php';
$id = (int)($_GET['id'] ?? 0);
$q = $pdo->prepare('SELECT * FROM rooms WHERE id=?'); $q->execute([$id]);
$room = $q->fetch();
if (!$room) { http_response_code(404); exit('Room not found.'); }
$now = now_dt(); $today = $now->format('Y-m-d');
$b = $pdo->prepare("SELECT * FROM bookings WHERE room_id=? AND status<>'cancelled' AND (booking_date=? OR status='checked-in') ORDER BY booking_date, start_time");
$b->execute([$id, $today]); $bks = $b->fetchAll();
[$status, $text] = room_state($bks, $now);
$labels = ['available'=>'Available','reserved'=>'Reserved','in-use'=>'In use'];
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><meta http-equiv="refresh" content="30">
<title><?= h($room['name']) ?> · RoomFinder</title><link rel="stylesheet" href="style.css"></head>
<body class="pub"><div class="pubc">
<p class="sub">RoomFinder · CSS Department</p>
<h1><?= h($room['name']) ?></h1><p class="sub"><?= h($room['floor']) ?> floor</p>
<div class="banner <?= $status ?>"><strong><?= $labels[$status] ?></strong><p><?= h($text) ?></p></div>
<h3 class="grp">TODAY'S SCHEDULE</h3>
<ul class="sched">
<?php $n = 0; foreach ($bks as $x) { if ($x['booking_date'] !== $today) continue; $n++; ?>
<li><span><?= t12($x['start_time']) ?> – <?= t12($x['end_time']) ?></span><b><?= h($x['class_name']) ?></b><?= status_badge($x, $now) ?></li>
<?php } if (!$n) echo '<li>No bookings today.</li>'; ?>
</ul>
<p class="note">Updated <?= $now->format('g:i A') ?> · refreshes every 30 seconds</p>
</div></body></html>
