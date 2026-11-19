<?php
require __DIR__.'/layout.php';
require_login();
$rooms = $pdo->query('SELECT * FROM rooms ORDER BY floor DESC, id')->fetchAll();
page_head('QR codes','Print one per room and stick it on the door. Scanning shows live room status.','qr');
echo '<div class="bar"><input id="qr-base" type="url" placeholder="Base URL"><button class="btn" id="qr-print" type="button">Print</button></div>
<p class="sub qrhelp">Para gumana sa phone, ilagay sa Base URL ang IP ng server PC (hal. <code>http://192.168.1.10/vacant-room-finder/room.php?id=</code>). Hindi gagana ang <code>localhost</code> sa ibang device.</p><div class="qrs">';
foreach ($rooms as $r)
  echo '<article class="qr" data-id="'.$r['id'].'"><b>'.h($r['name']).'</b><small>'.h($r['floor']).' floor</small><div class="qrimg"></div><small>Scan for room status</small><code></code></article>';
echo '</div>';
page_foot('<script src="qrcode.js"></script>');
