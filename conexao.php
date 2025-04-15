<?php
// conexao.php

// [1] Ajuste as variáveis abaixo conforme seu ambiente:
$host   = 'localhost';       // Geralmente é 'localhost'
$dbname = 'u979853733_teste_curso';   // Nome do seu banco de dados
$user   = 'u979853733_erik';            // Usuário do banco (ex: 'root')
$pass   = 'Barao2012!';                // Senha do banco (ex: '' se não houver senha)

// [2] Cria a conexão com o MySQL:
$conn = new mysqli($host, $user, $pass, $dbname);

// [3] Verifica erros na conexão:
if ($conn->connect_error) {
    die("Falha na conexão com o banco de dados: " . $conn->connect_error);
}

// [4] Define o charset para UTF-8 (opcional mas recomendado):
$conn->set_charset("utf8");
?>