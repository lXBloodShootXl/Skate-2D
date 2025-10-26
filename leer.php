<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "sql300.infinityfree.com";
$usuario = "if0_40083106";
$contrasena = "rjhf12345";
$database = "if0_40083106_Fox_DB";

$conn = new mysqli($servername, $usuario, $contrasena, $database);

if ($conn->connect_error) {
    echo json_encode([]);
    exit;
}

$orden = isset($_GET['orden']) && in_array(strtoupper($_GET['orden']), ['ASC', 'DESC']) ? $_GET['orden'] : 'DESC';

$sql = "SELECT u.nombre, p.puntos AS puntuacion, p.fecha 
        FROM puntuaciones p
        INNER JOIN usuarios u ON p.usuario_id = u.id
        ORDER BY p.puntos $orden";

$result = $conn->query($sql);

$datos = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $datos[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($datos);

$conn->close();
?>
