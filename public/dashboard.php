<?php

require_once __DIR__ . '/../config/session.php';

if (!isLoggedIn()) {
    header("Location: /simak_app/public/index.php");
    exit();
}

redirectToDashboard();
exit();
?>