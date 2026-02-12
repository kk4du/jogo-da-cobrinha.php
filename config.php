<?php
// Configurações do banco de dados
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'snake_game');

// Conexão com o banco de dados
function getConnection() {
    try {
        $conn = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $conn;
    } catch(PDOException $e) {
        die("Erro na conexão: " . $e->getMessage());
    }
}

// Iniciar sessão
session_start();

// Função para verificar se usuário está logado
function isLoggedIn() {
    return isset($_SESSION['usuario_id']);
}

// Função para redirecionar
function redirect($url) {
    header("Location: $url");
    exit();
}
?>