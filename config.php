<?php
// Edit these for your MySQL/MariaDB (XAMPP defaults shown)
$pdo = new PDO('mysql:host=127.0.0.1;dbname=roomfinder;charset=utf8mb4', 'root', '', [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
date_default_timezone_set('Asia/Manila');
const DAY_START = 8, DAY_END = 17;
