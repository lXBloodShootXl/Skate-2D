<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['usuario'])) { 
  header("Location: login.html"); 
  exit; 
}

$servername = "sql300.infinityfree.com";
$usuario = $_SESSION['usuario'];
$contrasena = $_SESSION['contrasena'];
$database = "if0_40083106_Fox_DB";

$conn = new mysqli($servername, $usuario, $contrasena, $database);

$tabla = $_GET['tabla'];
$id = (int)$_GET['id'];
$usuario_id = isset($_GET['usuario_id']) ? (int)$_GET['usuario_id'] : 0;

if ($tabla == 'puntuaciones' && $usuario_id) {
    // Primero eliminamos todas las puntuaciones de ese usuario
    $conn->query("DELETE FROM puntuaciones WHERE usuario_id = $usuario_id");

    // Luego eliminamos al usuario
    $conn->query("DELETE FROM usuarios WHERE id = $usuario_id");
}

header("Location: crud.php");
exit;
?>
