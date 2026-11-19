<?php
// Edit these for your MySQL/MariaDB (XAMPP defaults shown)
$pdo = new PDO('mysql:host=127.0.0.1;dbname=roomfinder;charset=utf8mb4', 'root', '', [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
date_default_timezone_set('Asia/Manila');

const DAY_START = 8, DAY_END = 17;
const DEBUG = true;            // set to false when deployed: disables the ?now= demo time
const MAX_BOOKING_HOURS = 4;   // longest allowed booking
const NOSHOW_GRACE_MIN = 15;   // not checked in this many minutes after start -> released (no-show)
const EARLY_CHECKIN_MIN = 15;  // can check in this many minutes before start

if (session_status() === PHP_SESSION_NONE) {
  session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
  session_start();
}