<?php
require __DIR__.'/layout.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
csrf_check();

$f = $_POST; $back = $f['back'] ?? 'index.php';
function fail($m){ global $pdo, $back; if ($pdo->inTransaction()) $pdo->rollBack(); flash($m, true); go($back); }
function valid_time($t){ return preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $t) === 1; }

$id   = (int)($f['id'] ?? 0);
$room = (int)($f['room_id'] ?? 0);
$date = trim($f['booking_date'] ?? '');
$st   = trim($f['start_time'] ?? '');
$en   = trim($f['end_time'] ?? '');
$prog = trim($f['program'] ?? ''); $yr = (int)($f['year_level'] ?? 0); $sec = trim($f['section'] ?? '');
$ln   = trim($f['lendee_name'] ?? '');
$lid  = trim($f['lendee_id'] ?? '');

// --- validation ---
$d = DateTime::createFromFormat('Y-m-d', $date);
if (!$d || $d->format('Y-m-d') !== $date) fail('Invalid date.');
if (!valid_time($st) || !valid_time($en)) fail('Invalid time.');
$st = substr($st,0,5).':00'; $en = substr($en,0,5).':00';
if ($st < '08:00:00' || $en > '17:00:00' || $en <= $st) fail('Pick a time between 8:00 AM and 5:00 PM, with the end after the start.');
if ((strtotime("$date $en") - strtotime("$date $st")) > MAX_BOOKING_HOURS*3600) fail('Maximum booking length is '.MAX_BOOKING_HOURS.' hours.');
$now = now_dt();
if (strtotime("$date $st") + NOSHOW_GRACE_MIN*60 < $now->getTimestamp()) fail('You cannot book a time that already passed.');
$cm = class_map();
if (!isset($cm[$prog][$yr]) || !in_array($sec, $cm[$prog][$yr], true)) fail('Please choose a valid program, year and section.');
$cls = "$prog $yr-$sec";
if ($ln === '' || $lid === '') fail('Please fill in all fields.');
if (mb_strlen($ln) > 80) fail('Lendee name is too long.');
if (!preg_match('/^\d{4}-\d{4}$/', $lid)) fail('Student ID must be in the format 0000-0000.');

try {
  $pdo->beginTransaction();
  // Lock the room row so two simultaneous bookings for the same room are processed one at a time
  $r = $pdo->prepare('SELECT id FROM rooms WHERE id=? FOR UPDATE'); $r->execute([$room]);
  if (!$r->fetch()) fail('Room not found.');

  if ($id) {
    $c = $pdo->prepare('SELECT status FROM bookings WHERE id=? FOR UPDATE'); $c->execute([$id]);
    $cur = $c->fetch();
    if (!$cur || $cur['status'] !== 'booked') fail('Only bookings that are not yet checked in can be edited.');
  }

  // Overlap check; released (no-show), cancelled and completed bookings don't block the slot
  $q = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id=? AND booking_date=? AND start_time<? AND end_time>? AND id<>?
    AND (status='checked-in' OR (status='booked' AND TIMESTAMP(booking_date,start_time) + INTERVAL ".NOSHOW_GRACE_MIN." MINUTE >= ?))");
  $q->execute([$room,$date,$en,$st,$id,$now->format('Y-m-d H:i:s')]);
  if ($q->fetchColumn()) fail('That room is already booked for part of that time. Choose another slot.');

  if ($id) {
    $pdo->prepare('UPDATE bookings SET room_id=?,booking_date=?,start_time=?,end_time=?,class_name=?,lendee_name=?,lendee_id=? WHERE id=?')
        ->execute([$room,$date,$st,$en,$cls,$ln,$lid,$id]);
    $msg = 'Booking updated.';
  } else {
    $pdo->prepare('INSERT INTO bookings (room_id,booking_date,start_time,end_time,class_name,lendee_name,lendee_id,key_lent_by) VALUES (?,?,?,?,?,?,?,?)')
        ->execute([$room,$date,$st,$en,$cls,$ln,$lid,'']);
    $msg = 'Booking saved.';
  }
  $pdo->commit();
} catch (PDOException $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  error_log($e->getMessage());
  fail('Something went wrong while saving. Please try again.');
}
flash($msg); go($back);