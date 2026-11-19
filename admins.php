<?php
require __DIR__.'/layout.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $act = $_POST['act'] ?? '';
  if ($act === 'add') {
    $u = trim($_POST['username'] ?? ''); $n = trim($_POST['display_name'] ?? ''); $p = (string)($_POST['password'] ?? '');
    $e = $pdo->prepare('SELECT COUNT(*) FROM admins WHERE username=?'); $e->execute([$u]);
    if (!preg_match('/^[A-Za-z0-9_.-]{3,40}$/', $u)) flash('Username: 3-40 characters, letters, numbers, . _ - only.', true);
    elseif ($n === '' || mb_strlen($n) > 60) flash('Display name is required (max 60 characters).', true);
    elseif (strlen($p) < 8) flash('Password must be at least 8 characters.', true);
    elseif ($e->fetchColumn()) flash('That username already exists.', true);
    else {
      $pdo->prepare('INSERT INTO admins (username,password_hash,display_name) VALUES (?,?,?)')->execute([$u, password_hash($p, PASSWORD_DEFAULT), $n]);
      flash('Admin "'.$u.'" created.');
    }
  } elseif ($act === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $total = (int)$pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    if ($id === (int)me()['id']) flash('You cannot delete the account you are logged in with.', true);
    elseif ($total <= 1) flash('You cannot delete the last admin.', true);
    else { $pdo->prepare('DELETE FROM admins WHERE id=?')->execute([$id]); flash('Admin deleted.'); }
  }
  header('Location: admins.php'); exit;
}

$rows = $pdo->query('SELECT id,username,display_name FROM admins ORDER BY id')->fetchAll();
page_head('Admins','Accounts that can log in and manage bookings.','admins');
echo '<div class="tbl"><table><thead><tr><th>Username</th><th>Display name</th><th>Actions</th></tr></thead><tbody>';
foreach ($rows as $r) {
  echo '<tr><td><b>'.h($r['username']).'</b>'.($r['id']==me()['id']?' <small>(you)</small>':'').'</td><td>'.h($r['display_name']).'</td><td>';
  if ($r['id'] != me()['id']) echo '<form method="post" class="inl" onsubmit="return confirm(\'Delete this admin?\')">'.csrf_field().'<input type="hidden" name="act" value="delete"><input type="hidden" name="id" value="'.$r['id'].'"><button class="btn sm danger">Delete</button></form>';
  else echo '—';
  echo '</td></tr>';
}
echo '</tbody></table></div>
<form method="post" class="lf addadmin">'.csrf_field().'<input type="hidden" name="act" value="add"><h3 style="margin:0">Add admin</h3>
<label>Username<input name="username" maxlength="40" required></label>
<label>Display name (shows as "Key lent by")<input name="display_name" maxlength="60" required></label>
<label>Password (min 8 characters)<input type="password" name="password" minlength="8" autocomplete="new-password" required></label>
<button class="btn">Create admin</button></form>';
page_foot();
