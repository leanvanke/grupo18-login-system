<?php
require __DIR__ . '/session.php';
start_session();

// Json de usuarios y logs, momentaneo
$usersFile = "../../data/users.json";
$logsFile  = "../../data/logs.json";

$users = json_decode(file_get_contents($usersFile), true) ?: [];
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

// Buscar usuario por id
$user = null;
foreach ($users as $u) {
  if ($u['id'] === $id) { $user = $u; break; }
}

if (!$user) {
  // Log intento fallido
  $logs[] = ['ts'=>date('Y-m-d H:i:s'),'id'=>$id,'result'=>'user_not_found','ip'=>$_SERVER['REMOTE_ADDR'] ?? ''];
  file_put_contents($logsFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  json_response(['success' => false, 'message' => 'Usuario no encontrado.'], 401);
}

// Comparación simple (sin hash)
if ($user['password'] !== $password) {
  $logs[] = ['ts'=>date('Y-m-d H:i:s'),'id'=>$id,'result'=>'bad_password','ip'=>$_SERVER['REMOTE_ADDR'] ?? ''];
  file_put_contents($logsFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
  json_response(['success' => false, 'message' => 'Contraseña incorrecta.'], 401);
}

// OK: crear sesión
// $_SESSION['user'] contiene los datos del usuario autenticado:
// - id: string, identificador único del usuario
// - email: string, correo electrónico del usuario
// - role: string, "usuario" o "administrador"
// - active: bool/int, indica si el usuario está activo
$_SESSION['user'] = [
  'id' => $user['id'],
  'email' => $user['email'],
  'role' => $user['role'],            // "usuario" o "administrador"
  'active' => $user['active']
];

$logs[] = ['ts'=>date('Y-m-d H:i:s'),'id'=>$id,'result'=>'success','ip'=>$_SERVER['REMOTE_ADDR'] ?? ''];
file_put_contents($logsFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// El front espera role;
json_response([
  'success' => true,
  'message' => 'Login OK',
  'role'    => $user['role'],
  'allowed_roles' => ['usuario','administrador']
]);
