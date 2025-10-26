<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
$servername = "sql300.infinityfree.com";
$usuario = $_SESSION['usuario'];
$contrasena = $_SESSION['contrasena'];
$database = "if0_40083106_Fox_DB";

$conn = new mysqli($servername, $usuario, $contrasena, $database);

if ($_POST['accion'] == "insertar_usuario") {
    $nombre = $_POST['nombre'];
    $conn->query("INSERT INTO usuarios (nombre) VALUES ('$nombre')");
    echo $conn->insert_id; // devuelve el ID generado
    exit;
}

if ($_POST['accion'] == "insertar_puntuacion") {
    $usuario_id = $_POST['usuario_id'];
    $puntos = $_POST['puntos'];
    $conn->query("INSERT INTO puntuaciones (usuario_id, puntos) VALUES ($usuario_id, $puntos)");
    exit;
}
?>
