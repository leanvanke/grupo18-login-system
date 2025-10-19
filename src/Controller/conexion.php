<?php

class Conexion {
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

}

?>

