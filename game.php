<?php
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$conn = getConnection();

// Buscar melhor pontuação do usuário
$stmt = $conn->prepare("SELECT MAX(pontos) as melhor_pontuacao FROM pontuacoes WHERE usuario_id = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$resultado = $stmt->fetch();
$melhor_pontuacao = $resultado['melhor_pontuacao'] ?? 0;

// Buscar top 10 ranking geral
$stmt = $conn->query("
    SELECT u.username, MAX(p.pontos) as pontos
    FROM pontuacoes p
    JOIN usuarios u ON p.usuario_id = u.id
    GROUP BY u.id
    ORDER BY pontos DESC
    LIMIT 10
");
$ranking = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <title>Jogo da Cobrinha</title>
</head>
<body>
    <!-- Partículas de fundo -->
    <div class="particles" id="particles"></div>

    <div class="container">
        <header class="game-header">
            <h1 class="game-title">🐍 Jogo da Cobrinha</h1>
        </header>

        <div class="game-layout">
            <!-- Espaço vazio para centralizar -->
            <div></div>

            <!-- Área principal do jogo -->
            <div class="game-container glass-card">
                <div class="player-info">
                    <span class="player-name"><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    <div class="player-actions">
                        <button id="soundToggleBtn" class="sound-btn">🔊</button>
                        <a href="logout.php" class="logout-btn">SAIR</a>
                    </div>
                </div>

                <div class="scores">
                    <div class="score-box">
                        <div class="score-label">Pontos</div>
                        <div class="score-value" id="currentScore">0</div>
                    </div>
                    <div class="score-box">
                        <div class="score-label">Recorde</div>
                        <div class="score-value" id="bestScore"><?php echo $melhor_pontuacao; ?></div>
                    </div>
                </div>

                <canvas id="gameCanvas" width="400" height="400"></canvas>

                <div class="controls">
                    <button class="btn btn-start" id="startBtn">INICIAR</button>
                    <button class="btn btn-pause" id="pauseBtn">PAUSAR</button>
                </div>

                <div class="mobile-controls">
                    <button class="arrow-btn arrow-up" data-direction="UP">▲</button>
                    <button class="arrow-btn arrow-left" data-direction="LEFT">◀</button>
                    <button class="arrow-btn arrow-right" data-direction="RIGHT">▶</button>
                    <button class="arrow-btn arrow-down" data-direction="DOWN">▼</button>
                </div>

                <div class="instructions">
                    <strong> COMO JOGAR:</strong> Use as setas do teclado (←↑→↓) ou os botões na tela para mover a cobra neon. Colete as esferas de energia para crescer e ganhar pontos!
                </div>
            </div>

            <!-- Ranking -->
            <div class="ranking-container glass-card">
                <h2 class="ranking-title">🏆 TOP 10</h2>
                <ul class="ranking-list" id="rankingList">
                    <?php if (empty($ranking)): ?>
                        <li class="ranking-item">
                            <span class="ranking-player">Nenhuma pontuação registrada.</span>
                        </li>
                    <?php else: ?>
                        <?php 
                            $classes = ['gold', 'silver', 'bronze'];
                        ?>
                        <?php foreach ($ranking as $index => $jogador): ?>
                            <li class="ranking-item <?php echo $classes[$index] ?? ''; ?>">
                                <span class="ranking-player"><?php echo ($index + 1) . '. ' . htmlspecialchars($jogador['username']); ?></span>
                                <span class="ranking-score"><?php echo $jogador['pontos']; ?> pts</span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Modal Game Over -->
    <div class="game-over-modal" id="gameOverModal">
        <div class="game-over-content">
            <h2 class="game-over-title">GAME OVER! 💀</h2>
            <p class="final-score">Pontuação: <strong id="finalScore">0</strong></p>
            <div class="game-over-actions">
                <button class="btn-restart" id="restartBtn">JOGAR NOVAMENTE</button>
                <a href="game.php" class="logout-btn">SAIR</a>
            </div>
        </div>
    </div>

    <!-- Efeitos Sonoros -->
    <audio id="blipSound" src="sounds/blip.mp3" preload="auto"></audio>
    <audio id="explosionSound" src="sounds/explosion.mp3" preload="auto"></audio>

    <script>
        // Criar partículas no fundo
        const particlesContainer = document.getElementById('particles');
        for (let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 10 + 's';
            particle.style.animationDuration = (Math.random() * 10 + 5) + 's';
            particlesContainer.appendChild(particle);
        }

        // Configuração do jogo
        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');
        const box = 20;
        const canvasSize = 400;
        const blipSound = document.getElementById('blipSound');
        const explosionSound = document.getElementById('explosionSound');

        let snake = [{x: 10 * box, y: 10 * box}];
        let direction = 'RIGHT';
        let food = generateFood();
        let score = 0;
        let bestScore = <?php echo $melhor_pontuacao; ?>;
        let game;
        let isPaused = false;
        let isMuted = false;
        let isGameOver = false;
        
        // Configurações de velocidade
        const initialInterval = 100; // Velocidade inicial (mais alto = mais lento)
        const minInterval = 40;      // Velocidade máxima (mais baixo = mais rápido)
        const speedStep = 5;         // Quanto diminuir o intervalo a cada aceleração
        let currentInterval = initialInterval;

        // Event listeners
        document.addEventListener('keydown', changeDirection);
        document.getElementById('startBtn').addEventListener('click', startGame);
        document.getElementById('pauseBtn').addEventListener('click', pauseGame);
        document.getElementById('restartBtn').addEventListener('click', restartGame);

        document.getElementById('soundToggleBtn').addEventListener('click', () => {
            isMuted = !isMuted;
            const btn = document.getElementById('soundToggleBtn');
            btn.textContent = isMuted ? '🔇' : '🔊';
        });

        // Controles mobile
        document.querySelectorAll('.arrow-btn').forEach(btn => {
            btn.addEventListener('touchstart', (e) => {
                e.preventDefault(); // Previne o evento de click fantasma e outros comportamentos
                const newDirection = btn.dataset.direction;
                if (newDirection === 'LEFT' && direction !== 'RIGHT') direction = 'LEFT';
                else if (newDirection === 'UP' && direction !== 'DOWN') direction = 'UP';
                else if (newDirection === 'RIGHT' && direction !== 'LEFT') direction = 'RIGHT';
                else if (newDirection === 'DOWN' && direction !== 'UP') direction = 'DOWN';
            });
        });

        function generateFood() {
            return {
                x: Math.floor(Math.random() * (canvasSize / box)) * box,
                y: Math.floor(Math.random() * (canvasSize / box)) * box
            };
        }

        function changeDirection(event) {
            const key = event.keyCode;
            
            // Verifica se a tecla pressionada é uma das setas (37-40)
            if ([37, 38, 39, 40].includes(key)) {
                // Impede a ação padrão do navegador (rolar a página)
                event.preventDefault();
            }

            if (key === 37 && direction !== 'RIGHT') direction = 'LEFT';
            else if (key === 38 && direction !== 'DOWN') direction = 'UP';
            else if (key === 39 && direction !== 'LEFT') direction = 'RIGHT';
            else if (key === 40 && direction !== 'UP') direction = 'DOWN';
        }

        function collision(head, array) {
            for (let i = 0; i < array.length; i++) {
                if (head.x === array[i].x && head.y === array[i].y) return true;
            }
            return false;
        }

        function drawInitialState() {
            // Fundo com gradiente animado
            const gradient = ctx.createLinearGradient(0, 0, canvasSize, canvasSize);
            gradient.addColorStop(0, '#0a0a0a');
            gradient.addColorStop(1, '#1a1a2e');
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, canvasSize, canvasSize);

            // Grade sutil
            ctx.strokeStyle = 'rgba(0, 255, 255, 0.05)';
            ctx.lineWidth = 1;
            for (let i = 0; i <= canvasSize; i += box) {
                ctx.beginPath();
                ctx.moveTo(i, 0);
                ctx.lineTo(i, canvasSize);
                ctx.stroke();
                ctx.beginPath();
                ctx.moveTo(0, i);
                ctx.lineTo(canvasSize, i);
                ctx.stroke();
            }

            // Desenhar cobra inicial
            const initialSnake = [{x: 10 * box, y: 10 * box}];
            ctx.shadowBlur = 20;
            ctx.shadowColor = '#00ffff';
            ctx.fillStyle = '#00ffff';
            ctx.fillRect(initialSnake[0].x + 1, initialSnake[0].y + 1, box - 2, box - 2);

            // Desenhar comida inicial
            ctx.shadowBlur = 30;
            ctx.shadowColor = '#ff0080';
            ctx.fillStyle = '#ff0080';
            ctx.beginPath();
            ctx.arc(food.x + box/2, food.y + box/2, (box/2 - 2), 0, 2 * Math.PI);
            ctx.fill();

            ctx.shadowBlur = 0;
        }

        function draw() {
            if (isPaused || isGameOver) return;

            // Fundo com gradiente animado
            const gradient = ctx.createLinearGradient(0, 0, canvasSize, canvasSize);
            gradient.addColorStop(0, '#0a0a0a');
            gradient.addColorStop(1, '#1a1a2e');
            ctx.fillStyle = gradient;
            ctx.fillRect(0, 0, canvasSize, canvasSize);

            // Grade sutil
            ctx.strokeStyle = 'rgba(0, 255, 255, 0.05)';
            ctx.lineWidth = 1;
            for (let i = 0; i <= canvasSize; i += box) {
                ctx.beginPath();
                ctx.moveTo(i, 0);
                ctx.lineTo(i, canvasSize);
                ctx.stroke();
                ctx.beginPath();
                ctx.moveTo(0, i);
                ctx.lineTo(canvasSize, i);
                ctx.stroke();
            }

            // Desenhar cobra com efeito neon
            for (let i = 0; i < snake.length; i++) {
                const segment = snake[i];
                
                // Sombra/glow
                ctx.shadowBlur = 20;
                ctx.shadowColor = i === 0 ? '#00ffff' : '#00ff88';
                
                // Gradiente para cada segmento
                const segmentGradient = ctx.createRadialGradient(
                    segment.x + box/2, segment.y + box/2, 0,
                    segment.x + box/2, segment.y + box/2, box
                );
                
                if (i === 0) {
                    // Cabeça
                    segmentGradient.addColorStop(0, '#00ffff');
                    segmentGradient.addColorStop(1, '#0088ff');
                } else {
                    // Corpo
                    const alpha = 1 - (i / snake.length) * 0.5;
                    segmentGradient.addColorStop(0, `rgba(0, 255, 136, ${alpha})`);
                    segmentGradient.addColorStop(1, `rgba(0, 136, 255, ${alpha})`);
                }
                
                ctx.fillStyle = segmentGradient;
                ctx.fillRect(segment.x + 1, segment.y + 1, box - 2, box - 2);
                
                // Borda brilhante
                ctx.strokeStyle = i === 0 ? '#00ffff' : '#00ff88';
                ctx.lineWidth = 2;
                ctx.strokeRect(segment.x + 1, segment.y + 1, box - 2, box - 2);
            }

            // Desenhar comida com efeito pulsante
            const pulse = Math.sin(Date.now() / 200) * 0.2 + 0.8;
            ctx.shadowBlur = 30 * pulse;
            ctx.shadowColor = '#ff0080';
            
            const foodGradient = ctx.createRadialGradient(
                food.x + box/2, food.y + box/2, 0,
                food.x + box/2, food.y + box/2, box * pulse
            );
            foodGradient.addColorStop(0, '#ff0080');
            foodGradient.addColorStop(0.5, '#ff00ff');
            foodGradient.addColorStop(1, 'rgba(255, 0, 128, 0)');
            
            ctx.fillStyle = foodGradient;
            ctx.beginPath();
            ctx.arc(food.x + box/2, food.y + box/2, (box/2 - 2) * pulse, 0, 2 * Math.PI);
            ctx.fill();

            ctx.shadowBlur = 0;

            // Lógica do movimento
            let snakeX = snake[0].x;
            let snakeY = snake[0].y;

            if (direction === 'LEFT') snakeX -= box;
            if (direction === 'UP') snakeY -= box;
            if (direction === 'RIGHT') snakeX += box;
            if (direction === 'DOWN') snakeY += box;

            // Verificar se comeu a comida
            if (snakeX === food.x && snakeY === food.y) {
                score++;
                if (!isMuted) {
                    blipSound.currentTime = 0;
                    blipSound.play();
                }
                updateScore('currentScore', score);
                if (score > bestScore) {
                    bestScore = score;
                    updateScore('bestScore', bestScore);
                }

                // Aumenta a velocidade a cada 3 pontos
                if (score > 0 && score % 3 === 0) {
                    increaseSpeed();
                }
                food = generateFood();
            } else {
                snake.pop();
            }

            const newHead = {x: snakeX, y: snakeY};

            // Verificar colisões
            if (snakeX < 0 || snakeY < 0 || snakeX >= canvasSize || snakeY >= canvasSize || collision(newHead, snake)) {
                if (!isMuted) {
                    explosionSound.currentTime = 0;
                    explosionSound.play();
                }
                isGameOver = true;
                clearInterval(game);
                saveScore();
                gameOver();
                return;
            }

            snake.unshift(newHead);
        }

        function startGame() {
            snake = [{x: 10 * box, y: 10 * box}];
            direction = 'RIGHT';
            score = 0;
            isPaused = false;
            isGameOver = false;
            updateScore('currentScore', score);
            clearInterval(game);
            currentInterval = initialInterval; // Reseta para a velocidade inicial
            game = null; // Garante que a referência ao intervalo antigo seja limpa
            game = setInterval(draw, currentInterval);
        }

        function increaseSpeed() {
            // Calcula o novo intervalo, garantindo que não seja menor que o mínimo
            const newInterval = Math.max(minInterval, currentInterval - speedStep);
            if (newInterval < currentInterval) {
                currentInterval = newInterval;
                clearInterval(game); // Para o loop atual
                game = setInterval(draw, currentInterval); // Inicia um novo loop mais rápido
            }
        }

        function pauseGame() {
            isPaused = !isPaused;
            document.getElementById('pauseBtn').textContent = isPaused ? 'RETOMAR' : 'PAUSAR';
        }

        function gameOver() {
            document.getElementById('finalScore').textContent = score;
            document.getElementById('gameOverModal').classList.add('show');
        }

        function saveScore() {
            if (score > 0) {
                const formData = new FormData();
                formData.append('pontos', score);

                fetch('salvar_pontuacao.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => console.log('Pontuação salva:', data));
            }
        }

        function restartGame() {
            document.getElementById('gameOverModal').classList.remove('show');
            startGame();
        }

        function updateScore(elementId, value) {
            const element = document.getElementById(elementId);
            element.textContent = value;
            element.classList.add('updated');
            setTimeout(() => element.classList.remove('updated'), 500);
        }

        // Iniciar animação do canvas
        drawInitialState();
    </script>
</body>
</html>