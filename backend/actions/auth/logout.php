<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/bootstrap.php';
verify_csrf();
logout_user();
header('Location: ' . page_url('home'));
exit;
