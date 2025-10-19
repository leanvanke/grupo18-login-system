<?php

require __DIR__ . '/session.php';
start_session();

// Json de usuarios, momentaneo
$usersFile = "../Model/users.json";

// Cargar usuarios
$users = [];
if (file_exists($usersFile)) {
  $raw = file_get_contents($usersFile);
  $users = json_decode($raw, true) ?: [];
}

// Solo POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_response(['success' => false, 'message' => 'Método no permitido'], 405);
}

$id       = trim($_POST['id'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role     = $_POST['role'] ?? 'usuario'; // coincide con <select> del front

// Validaciones simples
if ($id === '' || $email === '' || $password === '' || $role === '') {
  json_response(['success' => false, 'message' => 'Completa todos los campos.'], 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  json_response(['success' => false, 'message' => 'Formato de correo inválido.'], 400);
}
// Regla simple: 8+ chars, al menos una mayúscula, un número y un símbolo.
if (!(strlen($password) >= 8 && preg_match('/[A-Z]/',$password) && preg_match('/\d/',$password) && preg_match('/[^A-Za-z0-9]/',$password))) {
  json_response(['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres, una mayúscula, un número y un símbolo.'], 400);
}


// Duplicados
foreach ($users as $u) {
  if ($u['id'] === $id) {
    json_response(['success' => false, 'message' => 'El ID ya existe.'], 409);
  }
  if (strtolower($u['email']) === strtolower($email)) {
    json_response(['success' => false, 'message' => 'El email ya está registrado.'], 409);
  }
}

// Guardar usuario (sin hash)
$users[] = [
  'id' => $id,
  'email' => $email,
  'password' => $password,  
  'role' => $role,          // "usuario" | "administrador" 
  'created_at' => date('Y-m-d H:i:s'),
  'active' => true
];

file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

json_response(['success' => true, 'message' => 'Usuario registrado correctamente.']);

