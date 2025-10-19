<?php
// src/php/admin_update_user.php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user']) || ($_SESSION['user']['role'] ?? 'usuario') !== 'administrador') {
  http_response_code(403);
  echo json_encode(['success'=>false,'message'=>'No autorizado']);
  exit;
}

$logsFile  = "../../data/logs.json";
$logs  = json_decode(@file_get_contents($logsFile), true) ?: [];

$usersFile = __DIR__ . '/../../data/users.json';
$users = json_decode(@file_get_contents($usersFile), true) ?: [];

$id     = $_POST['id']     ?? '';
$action = $_POST['action'] ?? '';

if ($id === '' || $action === '') {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>'Parámetros faltantes']);
  exit;
}

$found = false;
foreach ($users as $i => $u) {
  if ($u['id'] === $id) {
    $found = true;
    if ($action === 'block') {
      $users[$i]['active'] = false;
        $logs[] = ['ts'=>date('Y-m-d H:i:s'),'id'=>$id,'result'=>'user_blocked','ip'=>$_SERVER['REMOTE_ADDR'] ?? ''];
        file_put_contents($logsFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    } elseif ($action === 'unblock') {
        $logs[] = ['ts'=>date('Y-m-d H:i:s'),'id'=>$id,'result'=>'user_unblocked','ip'=>$_SERVER['REMOTE_ADDR'] ?? ''];
        file_put_contents($logsFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
      $users[$i]['active'] = true;
    } elseif ($action === 'delete') {
        $logs[] = ['ts'=>date('Y-m-d H:i:s'),'id'=>$id,'result'=>'user_deleted','ip'=>$_SERVER['REMOTE_ADDR'] ?? ''];
        file_put_contents($logsFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
      array_splice($users, $i, 1);
    } else {
      http_response_code(400);
      echo json_encode(['success'=>false,'message'=>'Acción inválida']);
      exit;
    }
    break;
  }
}

if (!$found) {
  http_response_code(404);
  echo json_encode(['success'=>false,'message'=>'Usuario no encontrado']);
  exit;
}

file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo json_encode(['success'=>true]);
