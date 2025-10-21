<?php

if (!function_exists('start_session')) {
  function start_session() {
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
}

if (!function_exists('json_response')) {
  function json_response($arr, $code = 200) {
    http_response_code($code);
    if (function_exists('ob_get_length') && ob_get_length()) {
      @ob_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
  }
}
