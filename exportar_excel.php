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
if ($conn->connect_error) {
  die("Error de conexión: " . $conn->connect_error);
}

// Cabeceras para exportar a Excel
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=Reporte_puntuaciones.xls");
header("Pragma: no-cache");
header("Expires: 0");

// Leer parámetros GET
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';
$campo = isset($_GET['campo']) ? $_GET['campo'] : 'fecha';
$orden = isset($_GET['orden']) ? $_GET['orden'] : 'ASC';

// Consulta base
$query = "SELECT u.nombre, p.puntos, p.fecha 
          FROM puntuaciones p 
          JOIN usuarios u ON p.usuario_id = u.id";

// Aplicar búsqueda
if (!empty($buscar)) {
  if ($campo == 'nombre') {
    $query .= " WHERE u.nombre LIKE '%$buscar%'";
  } elseif ($campo == 'puntos') {
    $query .= " WHERE p.puntos LIKE '%$buscar%'";
  } elseif ($campo == 'fecha') {
    $query .= " WHERE p.fecha LIKE '%$buscar%'";
  }
}

// Aplicar orden
$query .= " ORDER BY $campo $orden";

// Ejecutar consulta
$result = $conn->query($query);
echo "\xEF\xBB\xBF"; // BOM para UTF-8

// Generar tabla HTML (Excel la interpretará)
echo "<table border='1'>";
echo "<tr><th>Nombre</th><th>Puntuación</th><th>Fecha</th></tr>";

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>{$row['nombre']}</td>
            <td>{$row['puntos']}</td>
            <td>{$row['fecha']}</td>
          </tr>";
  }
} else {
  echo "<tr><td colspan='3'>No hay registros encontrados</td></tr>";
}

echo "</table>";
$conn->close();
?>

