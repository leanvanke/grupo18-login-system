<?php
function add_log(PDO $pdo, $userId, string $result): void {
  try {
    $stmt = $pdo->prepare("INSERT INTO logs (`id`, `result`, `ip`) VALUES (:uid, :result, :ip)");
    $stmt->execute([
      ':uid'    => $userId, 
      ':result' => $result, 
      ':ip'     => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
  } catch (Throwable $e) {
    // No hacer nada si falla
  }
}
