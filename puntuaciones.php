<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.html");
    exit;
}

// Conexión a la DB
$servername = "sql300.infinityfree.com";
$usuario = $_SESSION['usuario'];
$contrasena = $_SESSION['contrasena'];
$database = "if0_40083106_Fox_DB";

$conn = new mysqli($servername, $usuario, $contrasena, $database);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

// ===================
// Parámetros GET
// ===================
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';
$campo = isset($_GET['campo']) ? $_GET['campo'] : 'fecha';
$comparadorRaw = isset($_GET['comparador']) ? $_GET['comparador'] : '=';
$ordenActual = isset($_GET['orden']) ? $_GET['orden'] : 'DESC';
$nuevoOrden = ($ordenActual == 'ASC') ? 'DESC' : 'ASC';
$flecha = ($ordenActual == 'ASC') ? '⬆' : '⬇';

// Normalizar comparador
$map = [
    '=' => '=',
    'igual' => '=',
    '>=' => '>=',
    'mayor' => '>=',
    '<=' => '<=',
    'menor' => '<='
];
$comparador = isset($map[$comparadorRaw]) ? $map[$comparadorRaw] : '=';

// Columnas permitidas
$allowedCampos = ['nombre','puntos','fecha'];
$campo = in_array($campo, $allowedCampos) ? $campo : 'fecha';
$ordenActual = ($ordenActual === 'DESC') ? 'DESC' : 'ASC';

// ===================
// Construir query
// ===================
$query = "SELECT p.id, u.id AS usuario_id, u.nombre, p.puntos, p.fecha
          FROM puntuaciones p
          JOIN usuarios u ON p.usuario_id = u.id";

$where = [];
$orderBy = '';
if (trim($buscar) !== '') {
    $safeBuscar = $conn->real_escape_string($buscar);

    if ($campo === 'nombre') {
        $where[] = "u.nombre LIKE '%$safeBuscar%'";
        $orderBy = "u.nombre";
    } elseif ($campo === 'puntos') {
        if (is_numeric($buscar)) {
            $valor = (int)$buscar;
            $where[] = "CAST(p.puntos AS SIGNED) $comparador $valor";
        } else {
            $where[] = "CAST(p.puntos AS CHAR) LIKE '%$safeBuscar%'";
        }
        $orderBy = "CAST(p.puntos AS SIGNED)";
    } elseif ($campo === 'fecha') {
        if (preg_match('/\d{4}-\d{2}-\d{2}/', $buscar)) {
            $where[] = "DATE(p.fecha) $comparador '$safeBuscar'";
        } else {
            $where[] = "p.fecha LIKE '%$safeBuscar%'";
        }
        $orderBy = "p.fecha";
    }
} else {
    // Sin filtro
    if ($campo === 'fecha') $orderBy = "p.fecha";
    elseif ($campo === 'puntos') $orderBy = "CAST(p.puntos AS SIGNED)";
    else $orderBy = "u.nombre";
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY $orderBy $ordenActual";

$result = $conn->query($query);

// Estadísticas
$totalJugadores = 0;
$totalPartidas = 0;
$mejorPuntaje = 0;
$datosTabla = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $datosTabla[] = $row;
        $totalJugadores++;
        $totalPartidas++;
        if ((int)$row['puntos'] > $mejorPuntaje) $mejorPuntaje = (int)$row['puntos'];
    }
}

// ===================
// Generar link de orden
// ===================
$ordenLink = "?campo=$campo&orden=$nuevoOrden";
if ($buscar !== '') $ordenLink .= "&buscar=" . urlencode($buscar);
if ($comparadorRaw !== '') $ordenLink .= "&comparador=" . urlencode($comparadorRaw);
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Puntajes - Skate Dash 2D</title>
<link rel="stylesheet" href="css/puntuaciones.css">
</head>
<body>
<div class="background"></div>

<header class="header">
  <h1>🏆 Puntajes</h1>
</header>

<main class="contenedor">
  <!-- Estadísticas generales -->
  <section class="stats">
    <div class="card">
      <h3>Total Jugadores</h3>
      <p id="totalJug"><?= $totalJugadores ?></p>
    </div>
    <div class="card">
      <h3>Total Partidas</h3>
      <p id="totalPart"><?= $totalPartidas ?></p>
    </div>
    <div class="card">
      <h3>Mejor Puntaje</h3>
      <p id="mejorPunt"><?= $mejorPuntaje ?></p>
    </div>
  </section>

  <!-- Controles -->
  <section class="busqueda">
    <form method="GET" action="">
      <select id="campoSelect" name="campo">
        <option value="nombre" <?= $campo=='nombre'?'selected':'' ?>>Nombre</option>
        <option value="puntos" <?= $campo=='puntos'?'selected':'' ?>>Puntuación</option>
        <option value="fecha" <?= $campo=='fecha'?'selected':'' ?>>Fecha</option>
      </select>

      <select id="comparadorSelect" name="comparador">
        <option value="igual" <?= $comparadorRaw=='igual'||$comparadorRaw=='='?'selected':'' ?>>Igual a</option>
        <option value="mayor" <?= $comparadorRaw=='mayor'||$comparadorRaw=='>='?'selected':'' ?>>Mayor o igual</option>
        <option value="menor" <?= $comparadorRaw=='menor'||$comparadorRaw=='<='?'selected':'' ?>>Menor o igual</option>
      </select>

      <input type="text" id="buscarInput" name="buscar" placeholder="🔍 Escribe tu búsqueda..." value="<?= htmlspecialchars($buscar) ?>">

      <button type="submit" id="buscarBtn">Buscar</button>

      <!-- Botón de orden asc/desc -->
      <button type="submit" name="orden" value="<?= $nuevoOrden ?>" id="ordenarBtn">
        <?= $flecha ?> <?= $ordenActual ?>
      </button>
    </form>
  </section>

  <!-- Tabla de puntajes -->
  <section class="tabla">
    <h2>Lista de Puntajes</h2>
    <table>
      <thead>
        <tr>
          <th>Jugador</th>
          <th>Puntaje</th>
          <th>Fecha</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!empty($datosTabla)): ?>
          <?php foreach($datosTabla as $d): ?>
            <tr>
              <td><?= htmlspecialchars($d['nombre']) ?></td>
              <td><?= $d['puntos'] ?></td>
              <td><?= $d['fecha'] ?></td>
            </tr>
          <?php endforeach; ?>
        <?php else: ?>
          <tr><td colspan="3">No hay registros encontrados</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </section>

  <!-- Acciones -->
  <div class="acciones">
    <!-- Botón exportar Excel -->
    <form method="GET" action="exportar_excel.php" style="display:inline;">
      <input type="hidden" name="campo" value="<?= $campo ?>">
      <input type="hidden" name="buscar" value="<?= htmlspecialchars($buscar) ?>">
      <input type="hidden" name="comparador" value="<?= $comparadorRaw ?>">
      <input type="hidden" name="orden" value="<?= $ordenActual ?>">
      <button type="submit" id="exportarBtn">📊 Exportar a Excel</button>
    </form>

    <button id="volverBtn" onclick="window.location.href='index.html'">⬅ Volver al Menú</button>
  </div>
</main>

<script>
// Mostrar/ocultar comparador según campo
const campoSelect = document.getElementById('campoSelect');
const comparadorSelect = document.getElementById('comparadorSelect');

function actualizarComparador() {
    if (campoSelect.value === 'nombre') {
        comparadorSelect.style.display = 'none';
    } else {
        comparadorSelect.style.display = 'inline-block';
    }
}

// Ejecutar al cargar y al cambiar
actualizarComparador();
campoSelect.addEventListener('change', actualizarComparador);
</script>

</body>
</html>
