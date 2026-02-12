-- Criar banco de dados
CREATE DATABASE IF NOT EXISTS snake_game;
USE snake_game;

-- Tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    senha VARCHAR(255) NOT NULL,
    data_cadastro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabela de pontuações
CREATE TABLE IF NOT EXISTS pontuacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    pontos INT NOT NULL,
    data_jogo TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
);

-- Índice para melhorar performance nas consultas de ranking
CREATE INDEX idx_pontos ON pontuacoes(pontos DESC);
CREATE INDEX idx_usuario_pontos ON pontuacoes(usuario_id, pontos DESC);