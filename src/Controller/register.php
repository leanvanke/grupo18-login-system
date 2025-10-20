<?php
require __DIR__ . '/session.php';
require __DIR__ . '/../Model/conexion.php';
require __DIR__ . '/logs.php';
start_session();

/* Helper JSON si no existe */
if (!function_exists('json_response')) {
  function json_response(array $payload, int $status = 200): void {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
  }
}

/* Solo POST */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
  json_response(['success' => false, 'message' => 'Método no permitido'], 405);
}

/* Input */
$id       = trim($_POST['id'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');
$role     = (string)($_POST['role'] ?? 'usuario');

/* Validaciones simples (mismas que tenías) */
if ($id === '' || $email === '' || $password === '' || $role === '') {
  json_response(['success' => false, 'message' => 'Completa todos los campos.'], 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_response(['success' => false, 'message' => 'Formato de correo inválido.'], 400);
}
// 8+ chars, al menos 1 mayúscula, 1 número y 1 símbolo
if (!(strlen($password) >= 8 && preg_match('/[A-Z]/', $password) && preg_match('/\d/', $password) && preg_match('/[^A-Za-z0-9]/', $password))) {
  json_response(['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un símbolo.'], 400);
}

/* Duplicados en BD (equivalente a tu loop en JSON) */
try {
  // Chequear ID
  $stmt = $pdo->prepare("SELECT 1 FROM users WHERE id = :id LIMIT 1");
  $stmt->execute([':id' => $id]);
  if ($stmt->fetch()) {
    json_response(['success' => false, 'message' => 'El ID ya existe.'], 409);
  }

  // Chequear Email (case-insensitive)
  $stmt = $pdo->prepare("SELECT 1 FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1");
  $stmt->execute([':email' => $email]);
  if ($stmt->fetch()) {
    json_response(['success' => false, 'message' => 'El email ya está registrado.'], 409);
  }

  // Insert
  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("
    INSERT INTO users (id, email, password, role, active, created_at)
    VALUES (:id, :email, :password, :role, :active, :created_at)
  ");
  $ok = $stmt->execute([
    ':id'         => $id,
    ':email'      => $email,
    ':password'   => $hash,
    ':role'       => $role,
    ':active'     => 1,
    ':created_at' => date('Y-m-d H:i:s'),
  ]);

  if (!$ok) {
    json_response(['success' => false, 'message' => 'No se pudo registrar el usuario.'], 500);
  }

add_log($pdo, $id, 'register success');

} catch (PDOException $e) {
  // Si tenés UNIQUE en BD y cae aquí por duplicados
  // Podés mapear el código 1062 (MySQL) a 409:
  if ((int)$e->errorInfo[1] === 1062) {
    // Determinar por qué campo (si querés afinar):
    $msg = (stripos($e->getMessage(), 'for key \'PRIMARY\'') !== false || stripos($e->getMessage(), 'for key \'users.PRIMARY\'') !== false)
      ? 'El ID ya existe.'
      : 'El email ya está registrado.';
    json_response(['success' => false, 'message' => $msg], 409);
  }
  json_response(['success' => false, 'message' => 'Error de servidor al registrar.'], 500);
} catch (Throwable $e) {
  json_response(['success' => false, 'message' => 'Error inesperado.'], 500);
}
