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

// ===================
// Parámetros GET
// ===================
$buscar = isset($_GET['buscar']) ? $_GET['buscar'] : '';
$campo = isset($_GET['campo']) ? $_GET['campo'] : 'fecha';
$comparadorRaw = isset($_GET['comparador']) ? $_GET['comparador'] : '=';
$ordenActual = isset($_GET['orden']) ? $_GET['orden'] : 'ASC';
$nuevoOrden = ($ordenActual == 'ASC') ? 'DESC' : 'ASC';
$flecha = ($ordenActual == 'ASC') ? '⬆' : '⬇';

// normalizar comparador
$map = [
  '='    => '=',
  'igual'=> '=',
  '=='   => '=',
  '>'    => '>=',
  'mayor'=> '>=',
  '>='   => '>=',
  '<'    => '<=',
  'menor'=> '<=',
  '<='   => '<='
];
$comparador = isset($map[$comparadorRaw]) ? $map[$comparadorRaw] : '=';

// seguridad de columnas y orden
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

    if ($campo == 'nombre') {
        $where[] = "u.nombre LIKE '%$safeBuscar%'";
        $orderBy = "u.nombre";

    } elseif ($campo == 'puntos') {
        if (is_numeric($buscar)) {
            $valor = (int)$buscar;
            $where[] = "CAST(p.puntos AS SIGNED) $comparador $valor";
        } else {
            $where[] = "CAST(p.puntos AS CHAR) LIKE '%$safeBuscar%'";
        }
        $orderBy = "CAST(p.puntos AS SIGNED)";

    } elseif ($campo == 'fecha') {
        if (preg_match('/\d{4}-\d{2}-\d{2}/', $buscar)) {
            $where[] = "DATE(p.fecha) $comparador '$safeBuscar'";
        } else {
            $where[] = "p.fecha LIKE '%$safeBuscar%'";
        }
        $orderBy = "p.fecha";
    }
} else {
    // sin filtro
    if ($campo == 'fecha') $orderBy = "p.fecha";
    elseif ($campo == 'puntos') $orderBy = "CAST(p.puntos AS SIGNED)";
    else $orderBy = "u.nombre";
}

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY $orderBy $ordenActual";

// Generar link de ordenamiento conservando todos los parámetros
$ordenLink = "?campo=$campo&orden=$nuevoOrden";
if ($buscar !== '') $ordenLink .= "&buscar=" . urlencode($buscar);
if ($comparadorRaw !== '') $ordenLink .= "&comparador=" . urlencode($comparadorRaw);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Panel CRUD - Puntuaciones</title>
<style>
body { font-family: Arial, sans-serif; background: linear-gradient(to right, #222, #444); color: #fff; text-align: center; padding-bottom: 50px; }
h1 { color: #00FFAA; margin-top: 20px; }
table { width: 90%; margin: 20px auto; border-collapse: collapse; cursor: pointer; background-color: #1a1a1a; border-radius: 8px; overflow: hidden; }
th, td { border: 1px solid #555; padding: 10px; text-align: center; }
th { background-color: #333; }
tr:nth-child(even) { background-color: #2a2a2a; }
tr:hover { background-color: #005577; }
tr.seleccionado { background-color: #0099CC; }
button, a { background: #008CBA; color: #fff; padding: 8px 15px; border: none; border-radius: 5px; text-decoration: none; margin: 3px; }
button:hover, a:hover { background: #006f94; cursor: pointer; }
input, select { padding: 5px; margin: 5px; border-radius: 5px; border: 1px solid #555; background-color: #222; color: #fff; }
#acciones { display: none; margin-top: 20px; }
.barra-busqueda { margin-top: 20px; }
</style>
</head>
<body>

<h1>Panel CRUD - Puntuaciones</h1>

<div class="barra-busqueda">
  <form method="GET" action="">
    <input type="text" name="buscar" placeholder="Buscar..." value="<?= htmlspecialchars($buscar) ?>">
    <select name="campo" id="campoSelect">
      <option value="nombre" <?= $campo=='nombre'?'selected':'' ?>>Nombre</option>
      <option value="puntos" <?= $campo=='puntos'?'selected':'' ?>>Puntuación</option>
      <option value="fecha" <?= $campo=='fecha'?'selected':'' ?>>Fecha</option>
    </select>
    <select name="comparador" id="comparadorSelect">
      <option value="=" <?= $comparadorRaw=='='|| $comparadorRaw=='igual' ? 'selected':'' ?>>Igual a</option>
      <option value=">=" <?= $comparadorRaw=='>='|| $comparadorRaw=='>'|| $comparadorRaw=='mayor' ? 'selected':'' ?>>Mayor o igual que</option>
      <option value="<=" <?= $comparadorRaw=='<='|| $comparadorRaw=='<'|| $comparadorRaw=='menor' ? 'selected':'' ?>>Menor o igual que</option>
      <option value=">" style="display:none">Mayor que (oculto)</option>
      <option value="<" style="display:none">Menor que (oculto)</option>
    </select>
    <button type="submit">Buscar</button>
    <a href="<?= $ordenLink ?>" style="padding: 8px 15px;"><?= $flecha ?></a>
    <a href="exportar_excel.php?campo=<?= $campo ?>&buscar=<?= urlencode($buscar) ?>&orden=<?= $ordenActual ?>" style="background:#4CAF50;">Exportar a Excel</a>
  </form>
</div>

<table id="tablaDatos">
  <tr><th>#</th><th>Nombre</th><th>Puntuación</th><th>Fecha</th></tr>
  <?php
  $result = $conn->query($query);
  $contador = 1;
  if ($result && $result->num_rows > 0) {
      while ($row = $result->fetch_assoc()) {
          echo "<tr data-id='{$row['id']}' data-usuario='{$row['usuario_id']}'>
                  <td>$contador</td>
                  <td>" . htmlspecialchars($row['nombre']) . "</td>
                  <td>" . htmlspecialchars($row['puntos']) . "</td>
                  <td>" . htmlspecialchars($row['fecha']) . "</td>
                </tr>";
          $contador++;
      }
  } else {
      echo "<tr><td colspan='4'>No hay registros encontrados</td></tr>";
  }
  ?>
</table>

<div id="acciones">
  <button id="btnEditar">Modificar</button>
  <button id="btnEliminar">Eliminar</button>
</div>

<br>
<a href="cerrar_sesion.php">Cerrar sesión</a>

<script>
// Selección de fila y acciones
let filaSeleccionada = null;
const filas = document.querySelectorAll("#tablaDatos tr");
const acciones = document.getElementById("acciones");
filas.forEach((fila, i) => {
  if (i === 0) return;
  fila.addEventListener("click", () => {
    filas.forEach(f => f.classList.remove("seleccionado"));
    fila.classList.add("seleccionado");
    filaSeleccionada = fila;
    acciones.style.display = "block";
  });
});

document.getElementById("btnEditar").addEventListener("click", () => {
  if (!filaSeleccionada) return;
  const usuarioId = filaSeleccionada.getAttribute("data-usuario");
  window.location.href = `editar.php?tabla=usuarios&id=${usuarioId}`;
});

document.getElementById("btnEliminar").addEventListener("click", () => {
  if (!filaSeleccionada) return;
  const id = filaSeleccionada.getAttribute("data-id");
  const usuarioId = filaSeleccionada.getAttribute("data-usuario");
  if (confirm("¿Seguro que deseas eliminar este registro y su usuario?")) {
    window.location.href = `eliminar.php?tabla=puntuaciones&id=${id}&usuario_id=${usuarioId}`;
  }
});

// Mostrar/ocultar comparador dinámicamente
const campoSelect = document.getElementById('campoSelect');
const comparadorSelect = document.getElementById('comparadorSelect');
function actualizarComparador() {
  const campo = campoSelect.value;
  if (campo === 'puntos' || campo === 'fecha') {
    comparadorSelect.style.display = 'inline-block';
  } else {
    comparadorSelect.style.display = 'none';
  }
}
campoSelect.addEventListener('change', actualizarComparador);
actualizarComparador();
</script>

</body>
</html>
