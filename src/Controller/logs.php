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

function recent_logs(PDO $pdo, int $minutes = 10): array {
  $since = date('Y-m-d H:i:s', time() - max($minutes, 1) * 60);

  try {
    $stmt = $pdo->prepare("SELECT id, result, ip, ts FROM logs WHERE ts >= :since ORDER BY ts DESC");
    $stmt->execute([':since' => $since]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  } catch (Throwable $e) {
    return [];
  }
}