<?php
require __DIR__ . '/session.php';
require __DIR__ . '/../Model/conexion.php';
require __DIR__ . '/logs.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_response(['success' => false, 'message' => 'Método no permitido.'], 405);
}

if (empty($_SESSION['user']['id'])) {
  json_response(['success' => false, 'message' => 'Sesión no válida. Iniciá sesión nuevamente.'], 401);
}

$current = (string)($_POST['current_password'] ?? '');
$new     = (string)($_POST['new_password'] ?? '');
$confirm = (string)($_POST['confirm_password'] ?? '');

if ($current === '' || $new === '' || $confirm === '') {
  json_response(['success' => false, 'message' => 'Completá todos los campos.'], 400);
}

if ($new !== $confirm) {
  json_response(['success' => false, 'message' => 'Las contraseñas nuevas no coinciden.'], 400);
}

if (strlen($new) < 8) {
  json_response(['success' => false, 'message' => 'La nueva contraseña debe tener al menos 8 caracteres.'], 400);
}

$userId = $_SESSION['user']['id'];

try {
  $stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
  $stmt->execute([':id' => $userId]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
  json_response(['success' => false, 'message' => 'No se pudo validar la contraseña actual.'], 500);
}

if (!$user) {
  json_response(['success' => false, 'message' => 'Usuario no encontrado.'], 404);
}

$storedPass = (string)($user['password'] ?? '');
$hasHash = password_get_info($storedPass)['algo'] !== 0;
$valid = $hasHash ? password_verify($current, $storedPass) : ($storedPass === $current);

if (!$valid) {
  add_log($pdo, $userId, 'bad_password_change');
  json_response(['success' => false, 'message' => 'La contraseña actual no es correcta.'], 401);
}

$newHash = password_hash($new, PASSWORD_DEFAULT);

try {
  $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
  $stmt->execute([
    ':password' => $newHash,
    ':id' => $userId,
  ]);
} catch (Throwable $e) {
  json_response(['success' => false, 'message' => 'No se pudo actualizar la contraseña.'], 500);
}

add_log($pdo, $userId, 'password_changed');

json_response(['success' => true, 'message' => 'Contraseña actualizada correctamente.']);
