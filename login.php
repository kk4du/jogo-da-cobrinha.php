<?php
require_once 'config.php';

if (isLoggedIn()) {
    redirect('game.php');
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $senha = $_POST['senha'];
    
    if (empty($username) || empty($senha)) {
        $erro = 'Preencha todos os campos!';
    } else {
        try {
            $conn = getConnection();
            $stmt = $conn->prepare("SELECT id, username, senha FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            $usuario = $stmt->fetch();
            
            if ($usuario && password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['username'] = $usuario['username'];
                redirect('game.php');
            } else {
                $erro = 'Username ou senha incorretos!';
            }
        } catch(PDOException $e) {
            $erro = 'Erro ao fazer login: ' . $e->getMessage();
        }
    }
}
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
<body class="d-flex align-items-center justify-content-center">
    <!-- Partículas de fundo -->
    <div class="particles" id="particles"></div>

    <div class="container auth-container">
        <div class="glass-card">
            <header class="game-header">
                <h1 class="game-title">🐍 LOGIN</h1>
            </header>
            
            <?php if ($erro): ?>
                <div class="alert alert-danger bg-transparent border-danger text-danger"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-4">
                    <label for="username" class="form-label">Nome:</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                
                <div class="mb-4">
                    <label for="senha" class="form-label">Senha:</label>
                    <input type="password" id="senha" name="senha" class="form-control" required>
                </div>
                
                <button type="submit" class="btn-submit w-100">Entrar</button>
            </form>
            
            <div class="text-center mt-3">
                <span style="color: rgba(255,255,255,0.7)">Não tem conta?</span> <a href="cadastro.php">Cadastre-se</a>
            </div>
        </div>
    </div>

    <script>
        const particlesContainer = document.getElementById('particles');
        for (let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDelay = Math.random() * 10 + 's';
            particle.style.animationDuration = (Math.random() * 10 + 5) + 's';
            particlesContainer.appendChild(particle);
        }
    </script>
</body>
</html>