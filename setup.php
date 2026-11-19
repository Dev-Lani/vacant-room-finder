<?php
// First-run page: only works while the admins table is empty.
require __DIR__.'/layout.php';
if ($pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() > 0) { header('Location: login.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $u = trim($_POST['username'] ?? ''); $n = trim($_POST['display_name'] ?? ''); $p = (string)($_POST['password'] ?? '');
  if (!preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $u)) $err = 'Username: 3-40 characters, letters, numbers, . _ - only.';
  elseif ($n === '' || mb_strlen($n) > 60) $err = 'Display name is required (max 60 characters).';
  elseif (strlen($p) < 8) $err = 'Password must be at least 8 characters.';
  else {
    $pdo->prepare('INSERT INTO admins (username,password_hash,display_name) VALUES (?,?,?)')->execute([$u, password_hash($p, PASSWORD_DEFAULT), $n]);
    $id = $pdo->lastInsertId(); session_regenerate_id(true);
    $_SESSION['admin'] = ['id' => (int)$id, 'name' => $n];
    header('Location: index.php'); exit;
  }
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Setup · RoomFinder</title><link rel="stylesheet" href="style.css"></head>
<body class="pub"><div class="pubc login">
<div class="brand"><span class="logo">▦</span><div><b>RoomFinder</b><small>Create the first admin account</small></div></div>
<?php if ($err) echo '<p class="flash bad">'.h($err).'</p>'; ?>
<form method="post" class="lf"><?= csrf_field() ?>
<label>Username<input name="username" maxlength="40" required autofocus></label>
<label>Display name<input name="display_name" maxlength="60" required></label>
<label>Password (min 8 characters)<input type="password" name="password" minlength="8" autocomplete="new-password" required></label>
<button class="btn">Create admin &amp; log in</button></form></div></body></html>
