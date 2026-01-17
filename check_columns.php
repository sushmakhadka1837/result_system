<?php
require 'db_config.php';
$r = $conn->query('DESCRIBE results');
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
