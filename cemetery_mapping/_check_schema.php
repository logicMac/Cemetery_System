<?php
require_once 'C:\wamp64\www\client_cemeteryV2\cemetery_mapping\config\database.php';
$s = $pdo->query('DESCRIBE plot_grids');
while ($r = $s->fetch()) {
    echo $r['Field'] . ' | ' . $r['Type'] . ' | Null=' . $r['Null'] . PHP_EOL;
}
