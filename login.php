<?php
require __DIR__.'/layout.php';
if (me()) { header('Location: index.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $q = $pdo->prepare('SELECT * FROM admins WHERE username=?'); $q->execute([trim($_POST['username'] ?? '')]);
  $u = $q->fetch();
  if ($u && password_verify($_POST['password'] ?? '', $u['password_hash'])) {
    session_regenerate_id(true);
    $_SESSION['admin'] = ['id' => $u['id'], 'name' => $u['display_name']];
    header('Location: index.php'); exit;
  }
  usleep(600000); // slow down password guessing
  $err = 'Wrong username or password.';
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Login · RoomFinder</title><link rel="stylesheet" href="style.css"></head>
<body class="pub"><div class="pubc login">
<div class="brand"><span class="logo">▦</span><div><b>RoomFinder</b><small>CSS Department · Admin login</small></div></div>
<?php if ($err) echo '<div class="toasts">'.toast_html($err, true).'</div>'; ?>
<form method="post" class="lf"><?= csrf_field() ?>
<label>Username<input name="username" autocomplete="username" required autofocus></label>
<label>Password<input type="password" name="password" autocomplete="current-password" required></label>
<button class="btn">Log in</button></form></div><script src="app.js"></script></body></html>