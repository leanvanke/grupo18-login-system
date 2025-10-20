<?php
session_start();
$_SESSION['user'] = ['id'=>'test_admin','email'=>'admin@test','role'=>'administrador','active'=>1];

$_POST['id'] = '12';
$_POST['action'] = 'block';

require_once __DIR__ . '/admin_update_user.php';
