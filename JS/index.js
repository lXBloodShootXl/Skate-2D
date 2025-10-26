const playBtn = document.getElementById('playBtn');
const scoresBtn = document.getElementById('scoresBtn');
const infoBtn = document.getElementById('infoBtn');

playBtn.addEventListener('click', () => {
  window.location.href = "juego.html";
});

scoresBtn.addEventListener('click', () => {
  window.location.href = "puntuaciones.php";
});

infoBtn.addEventListener('click', () => {
  window.location.href = "sobre.html";
});
