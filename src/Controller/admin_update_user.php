<?php
// src/Controller/admin_update_user.php
declare(strict_types=1);

// === Forzar JSON y capturar errores/warnings como JSON ===
ob_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');

set_error_handler(function($sev, $msg, $file, $line) {
  http_response_code(500);
  @ob_clean();
  echo json_encode(['success'=>false,'message'=>'PHP error','detail'=>"$msg @ $file:$line"], JSON_UNESCAPED_UNICODE);
  exit;
});
register_shutdown_function(function(){
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR], true)) {
    http_response_code(500);
    @ob_clean();
    echo json_encode(['success'=>false,'message'=>'Fatal error','detail'=>$e['message']], JSON_UNESCAPED_UNICODE);
  }
});

require __DIR__ . '/session.php';
start_session();


// --- Guard admin ---
if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? 'usuario') !== 'administrador')) {
  json_response(['success'=>false,'message'=>'No autorizado'], 403);
}

require __DIR__ . '/../Model/conexion.php';
require __DIR__ . '/logs.php';

$id     = $_POST['id']     ?? '';
$action = $_POST['action'] ?? '';
if ($id === '' || $action === '') {
  json_response(['success'=>false,'message'=>'Parámetros faltantes'], 400);
}
if (!in_array($action, ['block','unblock','delete'], true)) {
  json_response(['success'=>false,'message'=>'Acción inválida'], 400);
}

// Buscar usuario destino
$stmt = $pdo->prepare("SELECT id, role, active FROM users WHERE id = :id LIMIT 1");
$stmt->execute([':id'=>$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) json_response(['success'=>false,'message'=>'Usuario no encontrado'], 404);

// Reglas
$actorId = $_SESSION['user']['id'] ?? '';
if (($user['role'] ?? '') === 'administrador') {
  json_response(['success'=>false,'message'=>'No se puede modificar un administrador'], 403);
}
if ($id === $actorId && ($action === 'block' || $action === 'delete')) {
  json_response(['success'=>false,'message'=>'No podés realizar esa acción sobre tu propio usuario'], 403);
}


// Ejecutar acción
switch ($action) {
  case 'block':
    if ((int)$user['active'] !== 0) {
      $upd = $pdo->prepare("UPDATE users SET active = 0 WHERE id = :id");
      $upd->execute([':id'=>$id]);
    }
    add_log($pdo, $id, 'user_blocked');
    json_response(['success'=>true,'message'=>'Usuario bloqueado']);

  case 'unblock':
    if ((int)$user['active'] !== 1) {
      $upd = $pdo->prepare("UPDATE users SET active = 1 WHERE id = :id");
      $upd->execute([':id'=>$id]);
    }
    add_log($pdo, $id, 'user_unblocked');
    json_response(['success'=>true,'message'=>'Usuario desbloqueado']);

  case 'delete':
    $del = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $del->execute([':id'=>$id]);
    add_log($pdo, $id, 'user_deleted');
    json_response(['success'=>true,'message'=>'Usuario eliminado']);
}

// fallback
json_response(['success'=>false,'message'=>'Acción no ejecutada'], 400);
