<?php
require_once 'config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Não autenticado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['pontos'])) {
    $pontos = (int)$_POST['pontos'];
    $usuario_id = $_SESSION['usuario_id'];
    
    try {
        $conn = getConnection();
        $conn->beginTransaction();
        
        // Salvar pontuação
        $stmt = $conn->prepare("INSERT INTO pontuacoes (usuario_id, pontos) VALUES (?, ?)");
        $stmt->execute([$usuario_id, $pontos]);
        
        // Verificar se é novo recorde
        $stmt = $conn->prepare("SELECT pontos FROM pontuacoes WHERE usuario_id = ? ORDER BY pontos DESC LIMIT 1");
        $stmt->execute([$usuario_id]);
        $resultado = $stmt->fetch();
        $melhor_pontuacao = $resultado ? (int)$resultado['pontos'] : 0;
        $novo_recorde = ($pontos >= $melhor_pontuacao);
        
        $conn->commit();

        echo json_encode([
            'sucesso' => true,
            'novo_recorde' => $novo_recorde,
            'melhor_pontuacao' => $melhor_pontuacao
        ]);
        
    } catch(PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao salvar: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos']);
}
?>