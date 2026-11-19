<?php
require __DIR__.'/layout.php';
require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit; }
csrf_check();

$back = $_POST['back'] ?? 'bookings.php';
$act  = $_POST['action'] ?? '';
$id   = (int)($_POST['id'] ?? 0);
$now  = now_dt(); $t = $now->getTimestamp(); $nowS = $now->format('Y-m-d H:i:s');
function stop($m){ global $pdo, $back; if ($pdo->inTransaction()) $pdo->rollBack(); flash($m, true); go($back); }

try {
  $pdo->beginTransaction();
  $q = $pdo->prepare('SELECT * FROM bookings WHERE id=? FOR UPDATE'); $q->execute([$id]);
  $b = $q->fetch();
  if (!$b) stop('Booking not found.');

  if ($act === 'checkin') {
    if ($b['status'] !== 'booked') stop('This booking cannot be checked in.');
    $s = bk_start_ts($b);
    if ($t < $s - EARLY_CHECKIN_MIN*60) stop('Too early. Check-in opens '.EARLY_CHECKIN_MIN.' min before the start time.');
    if ($t > $s + NOSHOW_GRACE_MIN*60) stop('Grace period passed. This booking was released as a no-show. Make a new booking.');
    $o = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE room_id=? AND status='checked-in' AND id<>?");
    $o->execute([$b['room_id'], $id]);
    if ($o->fetchColumn()) stop('That room still has an unreturned key. Check out the previous booking first.');
    $pdo->prepare("UPDATE bookings SET status='checked-in', checked_in_at=?, key_lent_by=? WHERE id=?")->execute([$nowS, me()['name'], $id]);
    $msg = 'Checked in. Key lent.';
  } elseif ($act === 'checkout') {
    if ($b['status'] !== 'checked-in') stop('This booking is not checked in.');
    $by = trim($_POST['key_returned_by'] ?? '') ?: $b['lendee_name'];
    if (mb_strlen($by) > 60) stop('Name is too long.');
    $pdo->prepare("UPDATE bookings SET status='completed', checked_out_at=?, key_returned_by=? WHERE id=?")->execute([$nowS, $by, $id]);
    $msg = 'Checked out. Key returned.';
  } elseif ($act === 'cancel') {
    if ($b['status'] !== 'booked') stop('Only bookings that are not yet checked in can be cancelled.');
    $pdo->prepare("UPDATE bookings SET status='cancelled' WHERE id=?")->execute([$id]);
    $msg = 'Booking cancelled.';
  } else stop('Unknown action.');
  $pdo->commit();
} catch (PDOException $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  error_log($e->getMessage());
  stop('Something went wrong. Please try again.');
}
flash($msg); go($back);
