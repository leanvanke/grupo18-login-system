<?php
require __DIR__ . '/session.php';
require __DIR__ . '/../Model/conexion.php';
require __DIR__ . '/logs.php';

function too_many_attempts($logs, $id, $ip) {
  $now = time(); $fails = 0;
  foreach (array_reverse($logs) as $ev) {
    if (strtotime($ev['ts']) < $now - 600) break; // últimos 10 min
    if ($ev['result'] === 'bad_password' || $ev['result'] === 'user_not_found') {
      if ($ev['id'] === $id || ($ev['ip'] ?? '') === $ip) $fails++;
    }
  }
  return $fails >= 5;
}

start_session();

// Json de logs, momentáneo (se mantiene); usuarios ahora vienen de la BD
$logsFile  = "../Model/logs.json";
$logs  = json_decode(@file_get_contents($logsFile), true) ?: [];

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_response(['success' => false, 'message' => 'Método no permitido'], 405);
}

$id       = trim($_POST['id'] ?? '');
$password = $_POST['password'] ?? '';

if ($id === '' || $password === '') {
  json_response(['success' => false, 'message' => 'Completa todos los campos.'], 400);
}

if (too_many_attempts($logs, $id, $_SERVER['REMOTE_ADDR'] ?? '')) {
  json_response(['success'=>false,'message'=>'Demasiados intentos. Probá en unos minutos.'], 429);
}

// ====== CAMBIO: buscar usuario por ID en la BD (en lugar de users.json) ======
try {
  $stmt = $pdo->prepare("SELECT id, email, password, role, active FROM users WHERE id = :id LIMIT 1");
  $stmt->execute([':id' => $id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  // Error de servidor al consultar usuario
  json_response(['success' => false, 'message' => 'Error de servidor al consultar usuario.'], 500);
}

if (!$user) {
  // Log intento fallido
  add_log($pdo, $id, 'user_not_found');
  json_response(['success' => false, 'message' => 'Usuario no encontrado.'], 401);
}

// ====== CAMBIO: interpretar 'active' desde la BD (1=activo, otro= bloqueado) ======
$isActive = (int)($user['active'] ?? 0) === 1;

// Si el usuario existe y NO está activo => está bloqueado (misma semántica que antes)
if (!$isActive) {
  add_log($pdo, $id, 'user_blocked');
  json_response(['success' => false, 'message' => 'Usuario bloqueado.'], 423);
}

// Comparación simple (soporta hash y texto plano para compatibilidad)
$storedPass = (string)($user['password'] ?? '');
$hasHash = password_get_info($storedPass)['algo'] !== 0;
$valid = $hasHash ? password_verify($password, $storedPass) : ($storedPass === $password);

if (!$valid) {
  add_log($pdo, $id, 'bad_password');
  json_response(['success' => false, 'message' => 'Contraseña incorrecta.'], 401);
}

// OK: crear sesión (igual que antes)
$_SESSION['user'] = [
  'id'     => $user['id'],
  'email'  => $user['email'],
  'role'   => $user['role'],  // "usuario" o "administrador"
  'active' => $isActive
];
session_regenerate_id(true);

add_log($pdo, $id, 'login success');

// El front espera role;
json_response([
  'success' => true,
  'message' => 'Login OK',
  'role'    => $user['role'],
  'allowed_roles' => ['usuario','administrador']
]);
