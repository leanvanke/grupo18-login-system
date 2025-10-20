<?php
// session.php
function start_session() {
  //Sesión endurecida
  if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
      'lifetime' => 0,
      'path' => '/',
      'httponly' => true,
      'secure' => isset($_SERVER['HTTPS']),
      'samesite' => 'Lax'
    ]);
    session_start();
  }
}

function json_response($arr, $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}
