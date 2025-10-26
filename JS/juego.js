const canvas = document.getElementById("gameCanvas");
const ctx = canvas.getContext("2d");

// --- Variables globales ---
let gameRunning = true;
let score = 0;
let bgX = 0;
let bgSpeed = 2;
let countdownFinished = false;

// --- Cargar imágenes ---
const playerImg = new Image();
playerImg.src = "images/skater.png";

const obstacleImg = new Image();
obstacleImg.src = "images/cono.png";

const bgImg = new Image();
bgImg.src = "images/fondo.png";

// --- Jugador ---
const player = {
  x: 100,
  y: 300,
  width: 50,
  height: 50,
  dy: 0,
  gravity: 0.4,
  jumpPower: -10,
  grounded: false,
  jumping: false,
  jumpTimer: 0,
  maxJumpTime: 8
};

let obstacles = [];
let platforms = [];
let obstacleTimer = 0;
let platformTimer = 0;

// --- Crear obstáculos y plataformas ---
function createObstacle() {
  const width = 20;
  const height = 40;
  const y = canvas.height - 30 - height;
  obstacles.push({ x: canvas.width, y, width, height });
}

function createPlatform() {
  const width = 120;
  const minGap = 300;
  const maxGap = 500;
  const lastPlatform = platforms.length > 0 ? platforms[platforms.length - 1] : null;
  const x = lastPlatform ? lastPlatform.x + Math.random() * (maxGap - minGap) + minGap : canvas.width + 200;
  const y = Math.random() * 80 + 220;
  platforms.push({ x, y, width, height: 15, color: "#00ccff" });
}

// --- Controles de teclado ---
document.addEventListener("keydown", (e) => {
  if (!countdownFinished) return;
  if (e.code === "Space" && player.grounded) {
    player.dy = player.jumpPower;
    player.grounded = false;
    player.jumping = true;
    player.jumpTimer = 0;
  }
});

document.addEventListener("keyup", (e) => {
  if (e.code === "Space") player.jumping = false;
});

// --- Verificar suelo/plataformas ---
function checkGrounded() {
  player.grounded = false;
  for (const p of platforms) {
    if (
      player.x + player.width > p.x &&
      player.x < p.x + p.width &&
      player.y + player.height <= p.y + 10 &&
      player.y + player.height + player.dy >= p.y
    ) {
      player.y = p.y - player.height;
      player.dy = 0;
      player.grounded = true;
      player.jumping = false;
    }
  }

  if (player.y + player.height >= canvas.height - 30) {
    player.y = canvas.height - player.height - 30;
    player.dy = 0;
    player.grounded = true;
    player.jumping = false;
  }
}

// --- Countdown ---
let countdownNumber = 1;
const countdownEl = document.getElementById("countdown");

function startCountdown() {
  countdownEl.textContent = countdownNumber;
  const interval = setInterval(() => {
    countdownNumber--;
    if (countdownNumber > 0) countdownEl.textContent = countdownNumber;
    else if (countdownNumber === 0) countdownEl.textContent = "GO!!!";
    else {
      countdownEl.textContent = "";
      countdownFinished = true;
      clearInterval(interval);
    }
  }, 1000);
}

startCountdown();

// --- Loop principal ---
function update() {
  if (!gameRunning) return;
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  // Fondo
  bgX -= bgSpeed;
  if (bgX <= -canvas.width) bgX = 0;
  ctx.drawImage(bgImg, bgX, 0, canvas.width, canvas.height);
  ctx.drawImage(bgImg, bgX + canvas.width, 0, canvas.width, canvas.height);

  // Obstáculos
  if (++obstacleTimer > 120) { createObstacle(); obstacleTimer = 0; }
  for (let i = obstacles.length - 1; i >= 0; i--) {
    const o = obstacles[i];
    o.x -= bgSpeed;
    ctx.drawImage(obstacleImg, o.x, o.y, o.width, o.height);

    if (player.x < o.x + o.width && player.x + player.width > o.x && player.y < o.y + o.height && player.y + player.height > o.y) {
      gameOver();
    }

    if (o.x + o.width < 0) obstacles.splice(i, 1);
  }

  // Plataformas
  if (++platformTimer > 200) { createPlatform(); platformTimer = 0; }
  for (let i = platforms.length - 1; i >= 0; i--) {
    const p = platforms[i];
    p.x -= bgSpeed;
    ctx.fillStyle = p.color;
    ctx.fillRect(p.x, p.y, p.width, p.height);
    if (p.x + p.width < 0) platforms.splice(i, 1);
  }

  // Movimiento del jugador
  if (player.jumping && player.jumpTimer < player.maxJumpTime) {
    player.dy = player.jumpPower;
    player.jumpTimer++;
  }

  player.y += player.dy;
  player.dy += player.gravity;
  checkGrounded();

  // Suelo
  ctx.fillStyle = "#555";
  ctx.fillRect(0, canvas.height - 30, canvas.width, 30);

  // Dibujar jugador
  ctx.drawImage(playerImg, player.x, player.y, player.width, player.height);

  // Puntaje
  score += 0.05;
  ctx.fillStyle = "#fff";
  ctx.font = "16px Arial";
  ctx.fillText("Score: " + Math.floor(score), 20, 30);

  requestAnimationFrame(update);
}

update();

// --- Game Over ---
function gameOver() {
  gameRunning = false;
  document.getElementById("gameOver").style.display = "block";
  const finalScore = Math.floor(score);
  document.getElementById("finalScore").textContent = "Tu puntuación final: " + finalScore;

  // --- Solicitar nombre del jugador ---
  let jugador = prompt("¡Has perdido! Ingresa tu nombre:");
  if (!jugador) jugador = "Anónimo";

  // --- Enviar al servidor para guardar en la base de datos ---
  fetch('insertar.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `accion=insertar_usuario&nombre=${encodeURIComponent(jugador)}`
  })
  .then(response => response.text())
  .then(usuarioId => {
    // usuarioId es el ID generado por el insert en tabla usuarios
    // ahora insertamos la puntuación
    return fetch('insertar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `accion=insertar_puntuacion&usuario_id=${usuarioId}&puntos=${finalScore}`
    });
  })
  .then(() => {
    alert("Puntuación guardada correctamente!");
  })
  .catch(err => {
    console.error(err);
    alert("Ocurrió un error al guardar la puntuación.");
  });
}



// --- Reinicio del juego ---
function restartGame() {
  document.getElementById("gameOver").style.display = "none";
  obstacles = [];
  platforms = [];
  player.y = 300;
  player.dy = 0;
  player.jumping = false;
  score = 0;
  obstacleTimer = 0;
  platformTimer = 0;
  gameRunning = true;
  countdownNumber = 1;
  countdownFinished = false;
  startCountdown();
  update();
}

// --- Volver al menú ---
document.getElementById("btnVolver").addEventListener("click", () => {
  window.location.href = "index.html";
});

// --- Reiniciar ---
document.getElementById("restartBtn").addEventListener("click", restartGame);

// --- Pantalla completa ---
const fullscreenBtn = document.getElementById("fullscreenBtn");
if (/Mobi|Android|iPhone/i.test(navigator.userAgent)) fullscreenBtn.classList.remove("hidden");
fullscreenBtn.addEventListener("click", () => {
  const canvas = document.getElementById("gameCanvas");
  if (document.fullscreenElement) document.exitFullscreen();
  else canvas.requestFullscreen().catch(() => alert("Tu navegador no permite el modo pantalla completa"));
});

// --- Ajuste de tamaño ---
function resizeCanvas() {
  const ratio = 800 / 400;
  let width = Math.min(window.innerWidth * 0.95, 900);
  let height = width / ratio;
  if (window.innerWidth < 768 && window.innerWidth > window.innerHeight) {
    width = window.innerWidth * 0.95;
    height = window.innerHeight * 0.65;
  }
  if (window.innerHeight > window.innerWidth) {
    width = window.innerWidth * 0.95;
    height = window.innerHeight * 0.5;
  }
  canvas.style.width = width + "px";
  canvas.style.height = height + "px";
}
window.addEventListener("resize", resizeCanvas);
window.addEventListener("load", resizeCanvas);

// --- Control táctil ---
function setupTouchControls() {
  const handleJump = () => {
    if (!countdownFinished) return;
    if (player.grounded && !player.jumping) {
      player.dy = player.jumpPower;
      player.grounded = false;
      player.jumping = true;
      player.jumpTimer = 0;
    }
  };
  ["touchstart", "pointerdown"].forEach(evt => {
    canvas.addEventListener(evt, e => { e.stopPropagation(); handleJump(); });
    document.body.addEventListener(evt, e => { if (e.target !== canvas) handleJump(); });
  });
}
setupTouchControls();

// --- Orientación ---
function checkOrientation() {
  if (window.innerHeight > window.innerWidth && window.innerWidth < 768) {
    alert("Gira tu dispositivo a modo horizontal para jugar 😄");
  }
}
window.addEventListener("load", checkOrientation);
window.addEventListener("orientationchange", checkOrientation);
