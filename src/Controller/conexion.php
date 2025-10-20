<?php

$host = '127.0.0.1';
$db = 'db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    echo "Conexión exitosa";
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

/*class Conexion {
    private $con;

    public function __construct() {
        $this->con = new mysqli('localhost', 'root', '', 'basededatos');
    }

    public function getUsers(){

        $query = $this->con->query("SELECT * FROM usuarios");
        $data = [];

        $i = 0;
        while($fila = $query->fetch_assoc()){
            $data[$i] = $fila;
            $i++;
        }

        return $data;   
    
    }

    public function getLogs(){

        $query = $this->con->query("SELECT * FROM logs");
        $data = [];

        $i = 0;
        while($fila = $query->fetch_assoc()){
            $data[$i] = $fila;
            $i++;
        }

        return $data;   
    
    }

}*/

?>

