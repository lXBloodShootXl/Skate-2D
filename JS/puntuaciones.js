let orden = "DESC"; // por defecto descendente
let campoOrden = "p.fecha"; // por defecto ordenar por fecha
let comparadorActual = "=";  // comparador para enviar al servidor
let valorBuscar = "";         // valor de búsqueda
let datosOriginales = [];

// Volver al menú
document.getElementById("volverBtn").addEventListener("click", () => {
  window.location.href = "index.html";
});

// Exportar
document.getElementById("exportarBtn").addEventListener("click", exportarExcel);

// Ordenar
document.getElementById("ordenarBtn").addEventListener("click", () => {
  orden = (orden === "DESC") ? "ASC" : "DESC";
  document.getElementById("ordenarBtn").textContent = orden === "DESC" ? "⬇ DESC" : "⬆ ASC";
  cargarDatos();
});

// Actualizar campo de orden
document.getElementById("campoSelect").addEventListener("change", () => {
  const campo = document.getElementById("campoSelect").value;
  if (campo === "puntuacion") campoOrden = "p.puntos";
  else if (campo === "fecha") campoOrden = "p.fecha";
  else campoOrden = "u.nombre";
  cargarDatos();
});

// Actualizar comparador
document.getElementById("comparadorSelect").addEventListener("change", () => {
  const cmp = document.getElementById("comparadorSelect").value;
  if (cmp === "igual") comparadorActual = "=";
  else if (cmp === "mayor") comparadorActual = ">=";
  else if (cmp === "menor") comparadorActual = "<=";
  cargarDatos();
});

// Buscar
document.getElementById("buscarBtn").addEventListener("click", () => {
  valorBuscar = document.getElementById("buscarInput").value.trim();
  cargarDatos();
});

// Cargar datos desde leer.php
async function cargarDatos() {
  try {
    const params = new URLSearchParams({
      campo: campoOrden,
      orden: orden,
      comparador: comparadorActual,
      buscar: valorBuscar
    });

    const response = await fetch(`leer.php?${params.toString()}`, { credentials: "same-origin" });
    const data = await response.json();
    datosOriginales = data;
    mostrarDatos(data);
  } catch (error) {
    console.error("Error al cargar los datos:", error);
  }
}

// Mostrar datos en la tabla
function mostrarDatos(datos) {
  const tabla = document.getElementById("tablaPuntajes");
  tabla.innerHTML = "";

  let mejor = 0;

  datos.forEach(d => {
    const tr = document.createElement("tr");
    tr.innerHTML = `
      <td>${d.nombre || 'Sin nombre'}</td>
      <td>${d.puntos || d.puntuacion}</td>
      <td>${d.fecha}</td>`;
    tabla.appendChild(tr);
    if (parseInt(d.puntos || d.puntuacion) > mejor) mejor = parseInt(d.puntos || d.puntuacion);
  });

  document.getElementById("totalJug").textContent = datos.length;
  document.getElementById("totalPart").textContent = datos.length;
  document.getElementById("mejorPunt").textContent = mejor;
}

// Exportar a CSV
function exportarExcel() {
  if (datosOriginales.length === 0) {
    alert("No hay datos para exportar");
    return;
  }

  let csv = "Jugador,Puntaje,Fecha\n";
  datosOriginales.forEach(d => {
    csv += `${d.nombre},${d.puntos || d.puntuacion},${d.fecha}\n`;
  });

  const blob = new Blob([csv], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = url;
  a.download = "puntajes_skate_dash.csv";
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

// Cargar al iniciar
cargarDatos();
