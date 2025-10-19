<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// Solo admin
if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? 'usuario') !== 'administrador') {
  http_response_code(403);
  echo json_encode(['success'=>false,'message'=>'No autorizado']);
  exit;
}

$usersFile = '../Model/users.json';
$logsFile  = '../Model/logs.json';

$users = json_decode(@file_get_contents($usersFile), true) ?: [];
$logs  = json_decode(@file_get_contents($logsFile),  true) ?: [];

// Agrupar logs por ID 
$logsById = [];
foreach ($logs as $l) {
  $key = $l['id'] ?? '';
  if ($key === '') continue;
  $logsById[$key][] = [
    'fecha'  => $l['ts'] ?? '',
    'estado' => $l['result'] ?? ''
  ];
}

$out = [];
foreach ($users as $u) {
  $out[] = [
    'id'     => $u['id'],
    'email'  => $u['email'],
    'role'   => $u['role'] ?? 'usuario',
    'estado' => !empty($u['active']) ? 'activo' : 'bloqueado',
    'logs'   => $logsById[$u['id']] ?? []
  ];
}

echo json_encode($out, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
