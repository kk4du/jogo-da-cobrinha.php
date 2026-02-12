<?php
require_once 'config.php';

$erro = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $senha = $_POST['senha'];
    $confirma_senha = $_POST['confirma_senha'];
    
    if (empty($username) || empty($email) || empty($senha)) {
        $erro = 'Todos os campos são obrigatórios!';
    } elseif (strlen($username) < 3) {
        $erro = 'O username deve ter pelo menos 3 caracteres!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Email inválido!';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres!';
    } elseif ($senha !== $confirma_senha) {
        $erro = 'As senhas não coincidem!';
    } else {
        try {
            $conn = getConnection();
            
            // Verificar se username já existe
            $stmt = $conn->prepare("SELECT id FROM usuarios WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $erro = 'Username já existe!';
            } else {
                // Verificar se email já existe
                $stmt = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $erro = 'Email já cadastrado!';
                } else {
                    // Cadastrar usuário
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO usuarios (username, email, senha) VALUES (?, ?, ?)");
                    $stmt->execute([$username, $email, $senha_hash]);
                    
                    $sucesso = 'Cadastro realizado com sucesso! Redirecionando...';
                    header("refresh:2;url=login.php");
                }
            }
        } catch(PDOException $e) {
            $erro = 'Erro ao cadastrar: ' . $e->getMessage();
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
                <h1 class="game-title">🐍 CADASTRO</h1>
            </header>
            
            <?php if ($erro): ?>
                <div class="alert alert-danger bg-transparent border-danger text-danger"><?php echo htmlspecialchars($erro); ?></div>
            <?php endif; ?>
            
            <?php if ($sucesso): ?>
                <div class="alert alert-success bg-transparent border-success text-success"><?php echo htmlspecialchars($sucesso); ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="mb-4">
                    <label for="username" class="form-label">Nome:</label>
                    <input type="text" id="username" name="username" class="form-control" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="mb-4">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="mb-4">
                    <label for="senha" class="form-label">Senha:</label>
                    <input type="password" id="senha" name="senha" class="form-control" required>
                </div>
                
                <div class="mb-4">
                    <label for="confirma_senha" class="form-label">Confirmar Senha:</label>
                    <input type="password" id="confirma_senha" name="confirma_senha" class="form-control" required>
                </div>
                
                <button type="submit" class="btn-submit w-100">Cadastrar</button>
            </form>
            
            <div class="text-center mt-3">
                <span style="color: rgba(255,255,255,0.7)">Já tem conta?</span> <a href="login.php">Faça login</a>
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