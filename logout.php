<?php

declare(strict_types=1);

require __DIR__ . '/includes/bootstrap.php';
app_logout();
header('Location: index.php');
exit;
