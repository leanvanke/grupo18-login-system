<?php
declare(strict_types=1);
require __DIR__ . '/logs.php';

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/session.php';
start_session();

// Guard admin
if (empty($_SESSION['user']) || (($_SESSION['user']['role'] ?? 'usuario') !== 'administrador')) {
  http_response_code(403);
  echo json_encode(['success'=>false,'message'=>'No autorizado'], JSON_UNESCAPED_UNICODE);
  exit;
}

require __DIR__ . '/../Model/conexion.php';

try {
  // === Usuarios ===
  $stmtUsers = $pdo->query("SELECT id, email, role, active FROM users ORDER BY id ASC");
  $users = $stmtUsers->fetchAll(PDO::FETCH_ASSOC) ?: [];

  // === Logs ===
  $stmtLogs = $pdo->query("SELECT id, ts, result FROM logs ORDER BY ts DESC");
  $allLogs = $stmtLogs->fetchAll(PDO::FETCH_ASSOC) ?: [];

  // Agrupa por id (id del usuario)
  $logsByUser = [];
  foreach ($allLogs as $l) {
    $uid = (string)($l['id'] ?? '');
    if ($uid === '') continue;
    $logsByUser[$uid][] = [
      'fecha'  => $l['ts'] ?? '',
      'estado' => $l['result'] ?? ''
    ];
  }

  // Salida en el formato que espera dashboard.js
  $out = [];
  foreach ($users as $u) {
    $uid = (string)$u['id'];
    $out[] = [
      'id'     => $uid,
      'email'  => $u['email'],
      'role'   => $u['role'] ?? 'usuario',
      'estado' => ((int)($u['active'] ?? 0) === 1) ? 'activo' : 'bloqueado',
      'logs'   => $logsByUser[$uid] ?? []
    ];
  }

  echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['success'=>false,'message'=>'Error al consultar usuarios o logs'], JSON_UNESCAPED_UNICODE);
}
