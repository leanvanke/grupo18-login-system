<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!empty($_SESSION['user'])) {
    echo json_encode([
        'authenticated' => true,
        'id'         => $_SESSION['user']['id'],
        'name'       => $_SESSION['user']['name'] ?? null,
        'email'      => $_SESSION['user']['email'],
        'birth_date' => $_SESSION['user']['birth_date'] ?? null,
        'active'     => $_SESSION['user']['active'],
        'role'       => $_SESSION['user']['role'] ?? 'usuario'
    ]);
} else {
    echo json_encode(['authenticated' => false]);
}
