<?php
require __DIR__ . '/session.php';
require __DIR__ . '/../Model/conexion.php';
require __DIR__ . '/logs.php';

function ensure_password_column_capacity(PDO $pdo, int $expectedLength): void
{
  $minLength = max($expectedLength, 72); 
  try {
    $dbStmt = $pdo->query('SELECT DATABASE() AS db');
    $schema = (string)($dbStmt->fetchColumn() ?? '');
    if ($schema === '') {
      return;
    }

    $info = $pdo->prepare(
      'SELECT CHARACTER_MAXIMUM_LENGTH
         FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = :schema
          AND TABLE_NAME = :table
          AND COLUMN_NAME = :column
        LIMIT 1'
    );
    $info->execute([
      ':schema' => $schema,
      ':table' => 'users',
      ':column' => 'password',
    ]);

    $maxLen = (int)($info->fetchColumn() ?? 0);
    if ($maxLen > 0 && $maxLen < $minLength) {
      $pdo->exec('ALTER TABLE `users` MODIFY `password` VARCHAR(255) NOT NULL');
    }
  } catch (Throwable $e) {
    // No hacer nada si falla
  }
}

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

ensure_password_column_capacity($pdo, strlen($newHash));

try {
    $pdo->beginTransaction();

  $stmt = $pdo->prepare('UPDATE users SET password = :password WHERE id = :id');
  $stmt->execute([
    ':password' => $newHash,
    ':id' => $userId,
  ]);
  $check = $pdo->prepare('SELECT password FROM users WHERE id = :id LIMIT 1');
  $check->execute([':id' => $userId]);
  $savedPass = (string)($check->fetchColumn() ?? '');

  if ($savedPass === '') {
    $pdo->rollBack();
    json_response(['success' => false, 'message' => 'No se pudo verificar la contraseña actualizada.'], 500);
  }

  if (!password_verify($new, $savedPass)) {
    $pdo->rollBack();
    json_response([
      'success' => false,
      'message' => 'No se pudo guardar la nueva contraseña. Verificá la configuración de la base de datos.',
    ], 500);
  }

  $pdo->commit();
} catch (Throwable $e) {
  if ($pdo->inTransaction()) {
    $pdo->rollBack();
  }
  json_response(['success' => false, 'message' => 'No se pudo actualizar la contraseña.'], 500);
}

add_log($pdo, $userId, 'password_changed');

json_response(['success' => true, 'message' => 'Contraseña actualizada correctamente.']);