<?php
function add_log(PDO $pdo, $userId, string $result): void {
  try {
    $stmt = $pdo->prepare("INSERT INTO logs (`id`, `result`, `ip`) VALUES (:uid, :result, :ip)");
    $stmt->execute([
      ':uid'    => $userId, // el mismo id que en users.id (string o int)
      ':result' => $result, // ej: 'login success', 'bad_password', 'user_blocked', 'user_not_found', 'blocked', 'unblocked', 'deleted'
      ':ip'     => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
  } catch (Throwable $e) {
    // silencioso: no romper el flujo si falla el log
  }
}
