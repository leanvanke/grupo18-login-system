<?php

$host = '127.0.0.1';
$db   = 'db';          
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$port = 3307; // Puerto por defecto de XAMPP

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    throw new PDOException("Error de conexión: ".$e->getMessage(), (int)$e->getCode());
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "OK: conexión exitosa a '{$db}' en {$host}\n";
}
