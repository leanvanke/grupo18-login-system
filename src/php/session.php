<?php
// session.php
function start_session() {
  if (session_status() === PHP_SESSION_NONE) {
    session_start();
  }
}

function json_response($arr, $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}
