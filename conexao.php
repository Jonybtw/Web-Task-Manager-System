<?php
// parametros base de dados
$host = 'localhost';
$usuario_bd = 'root';
$senha_bd = '';
$nome_bd = 'gestao_tarefas';

// conexao
$conexao = mysqli_connect($host, $usuario_bd, $senha_bd, $nome_bd);

// verificar a conexao
if (!$conexao) {
    die("Erro na conexão ao banco de dados: " . mysqli_connect_error());
}
?>
