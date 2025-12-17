<?php
require 'db_config.php';
$conn->query("UPDATE results_publish_status SET published=1, published_at=NOW()");
header("Location: admin_publish_results.php");
?>
