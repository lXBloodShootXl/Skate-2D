<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "sql300.infinityfree.com"; // tu servidor MySQL correcto
$usuario = $_POST['usuario'];
$contrasena = $_POST['contrasena'];
$database = "if0_40083106_Fox_DB";

$conn = @new mysqli($servername, $usuario, $contrasena, $database);

if ($conn->connect_error) {
  echo "<h2 style='color:red;text-align:center;'>❌ Error de conexión: datos incorrectos.</h2>";
  echo "<p style='text-align:center;'><a href='login.html'>Volver</a></p>";
  exit;
} else {
  session_start();
  $_SESSION['usuario'] = $usuario;
  $_SESSION['contrasena'] = $contrasena;
  header("Location: crud.php");
  exit;
}
?>
