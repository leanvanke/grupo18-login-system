<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!empty($_SESSION['user'])) {
    echo json_encode([
        'authenticated' => true,
        'id'    => $_SESSION['user']['id'],
        'email' => $_SESSION['user']['email'],
        'active' => $_SESSION['user']['active'],
        'role'  => $_SESSION['user']['role'] ?? 'usuario'
    ]);
} else {
    echo json_encode(['authenticated' => false]);
}
