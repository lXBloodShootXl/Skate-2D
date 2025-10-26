<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['usuario'])) { header("Location: login.html"); exit; }

$servername = "sql300.infinityfree.com";
$usuario = $_SESSION['usuario'];
$contrasena = $_SESSION['contrasena'];
$database = "if0_40083106_Fox_DB";

$conn = new mysqli($servername, $usuario, $contrasena, $database);

$tabla = $_GET['tabla'];
$id = $_GET['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($tabla == 'usuarios') {
    $nombre = $_POST['nombre'];
    $conn->query("UPDATE usuarios SET nombre='$nombre' WHERE id=$id");
  } elseif ($tabla == 'puntuaciones') {
    $puntos = $_POST['puntos'];
    $conn->query("UPDATE puntuaciones SET puntos=$puntos WHERE id=$id");
  }
  header("Location: crud.php");
  exit;
}

if ($tabla == 'usuarios') {
  $row = $conn->query("SELECT * FROM usuarios WHERE id=$id")->fetch_assoc();
  $valor = $row['nombre'];
  echo "<form method='POST'>
          <h2>Editar Usuario</h2>
          <input type='text' name='nombre' value='$valor'>
          <button type='submit'>Guardar</button>
        </form>";
}

if ($tabla == 'puntuaciones') {
  $row = $conn->query("SELECT * FROM puntuaciones WHERE id=$id")->fetch_assoc();
  $valor = $row['puntos'];
  echo "<form method='POST'>
          <h2>Editar Puntuación</h2>
          <input type='number' name='puntos' value='$valor'>
          <button type='submit'>Guardar</button>
        </form>";
}
?>
