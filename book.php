<?php
require __DIR__.'/config.php';
$f = $_POST; $back = $_SERVER['HTTP_REFERER'] ?? 'index.php'; $back = strtok($back,'?');
if ($_SERVER['REQUEST_METHOD']!=='POST') { header('Location: index.php'); exit; }
if ($f['end_time'] <= $f['start_time'] || $f['start_time'] < '08:00' || $f['end_time'] > '17:00') {
  header("Location: $back?msg=".urlencode('Pick a time between 8:00 AM and 5:00 PM, with the end after the start.')."&err=1"); exit;
}
$q = $pdo->prepare('SELECT COUNT(*) FROM bookings WHERE room_id=? AND booking_date=? AND start_time<? AND end_time>?');
$q->execute([$f['room_id'],$f['booking_date'],$f['end_time'],$f['start_time']]);
if ($q->fetchColumn()) { header("Location: $back?msg=".urlencode('That room is already booked for part of that time. Choose another slot.')."&err=1"); exit; }
$pdo->prepare('INSERT INTO bookings (room_id,booking_date,start_time,end_time,class_name,lendee_name,lendee_id,key_lent_by) VALUES (?,?,?,?,?,?,?,?)')
    ->execute([$f['room_id'],$f['booking_date'],$f['start_time'],$f['end_time'],trim($f['class_name']),trim($f['lendee_name']),trim($f['lendee_id']),trim($f['key_lent_by'])]);
header("Location: $back?msg=".urlencode('Booking saved.'));
