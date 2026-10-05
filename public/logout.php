<?php

require_once '../config/database.php';
require_once '../core/Auth.php';
require_once '../core/Session.php';

Session::start();
$auth = new Auth($pdo);

$auth->logout();

header('Location: index.php');
exit;