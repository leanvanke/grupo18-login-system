<?php
declare(strict_types=1);

require __DIR__ . '/session.php';
require __DIR__ . '/../Model/conexion.php';
require __DIR__ . '/logs.php';

start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_response(['success' => false, 'message' => 'Método no permitido.'], 405);
}

if (empty($_SESSION['user']['id'])) {
  json_response(['success' => false, 'message' => 'Sesión expirada. Iniciá sesión nuevamente.'], 401);
}

$userId = $_SESSION['user']['id'];

$email = trim((string)($_POST['email'] ?? ''));
$name = trim((string)($_POST['name'] ?? ''));
$birthInput = trim((string)($_POST['birth_date'] ?? ''));

if ($email === '' || $name === '') {
  json_response(['success' => false, 'message' => 'Completá el email y el nombre.'], 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_response(['success' => false, 'message' => 'Ingresá un email válido.'], 400);
}

$maxNameLength = 120;
if (mb_strlen($name) < 2) {
  json_response(['success' => false, 'message' => 'El nombre debe tener al menos 2 caracteres.'], 400);
}
if (mb_strlen($name) > $maxNameLength) {
  json_response(['success' => false, 'message' => 'El nombre es demasiado largo.'], 400);
}

$birthDate = null;
if ($birthInput !== '') {
  $dt = DateTime::createFromFormat('Y-m-d', $birthInput);
  $errors = DateTime::getLastErrors();
  if (!$dt || ($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0) {
    json_response(['success' => false, 'message' => 'Ingresá una fecha de nacimiento válida (AAAA-MM-DD).'], 400);
  }
  $birthDate = $dt->format('Y-m-d');
}

try {
  $dup = $pdo->prepare('SELECT id FROM users WHERE email = :email AND id <> :id LIMIT 1');
  $dup->execute([
    ':email' => $email,
    ':id' => $userId,
  ]);
  if ($dup->fetch(PDO::FETCH_ASSOC)) {
    json_response(['success' => false, 'message' => 'Ese email ya está en uso por otro usuario.'], 409);
  }
} catch (Throwable $e) {
  json_response(['success' => false, 'message' => 'No se pudo validar el email.'], 500);
}

try {
  $stmt = $pdo->prepare('UPDATE users SET email = :email, name = :name, birth_date = :birth WHERE id = :id');
  $stmt->bindValue(':email', $email);
  $stmt->bindValue(':name', $name);
  if ($birthDate === null) {
    $stmt->bindValue(':birth', null, PDO::PARAM_NULL);
  } else {
    $stmt->bindValue(':birth', $birthDate);
  }
  $stmt->bindValue(':id', $userId);
  $stmt->execute();
} catch (Throwable $e) {
  json_response(['success' => false, 'message' => 'No se pudo actualizar el perfil.'], 500);
}

$_SESSION['user']['email'] = $email;
$_SESSION['user']['name'] = $name;
$_SESSION['user']['birth_date'] = $birthDate;

add_log($pdo, $userId, 'profile_updated');

json_response([
  'success' => true,
  'message' => 'Perfil actualizado correctamente.',
  'profile' => [
    'email' => $email,
    'name' => $name,
    'birth_date' => $birthDate,
  ],
]);