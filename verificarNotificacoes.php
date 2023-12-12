<?php
include 'conexao.php';

function obterNotificacoes($conexao, $utilizador_id) {
    // consulta sql para obter tarefas a expirar
    $query_tarefas_prestes_a_expirar = "SELECT titulo, data_termino FROM tarefas WHERE utilizador_id = '$utilizador_id' AND data_termino >= NOW() AND data_termino <= DATE_ADD(NOW(), INTERVAL 1 DAY)";
    $resultado_tarefas_prestes_a_expirar = mysqli_query($conexao, $query_tarefas_prestes_a_expirar);

    // consulta sql para obter tarefas compartilhadas a expirar
    $query_tarefas_compartilhadas_prestes_a_expirar = "SELECT t.titulo, t.data_termino FROM tarefas t 
        INNER JOIN compartilhamento_tarefas ct ON t.id = ct.tarefa_id 
        WHERE ct.utilizador_compartilhado_id = '$utilizador_id' 
        AND t.data_termino >= NOW() AND t.data_termino <= DATE_ADD(NOW(), INTERVAL 1 DAY)";
    $resultado_tarefas_compartilhadas_prestes_a_expirar = mysqli_query($conexao, $query_tarefas_compartilhadas_prestes_a_expirar);

    // array para armazenar as notificacoes
    $notificacoes = [];

    // verifica tarefas a expirar e adiciona a notificacoes
    while ($tarefa = mysqli_fetch_assoc($resultado_tarefas_prestes_a_expirar)) {
        $notificacoes[] = [
            'mensagem' => "A tarefa '{$tarefa['titulo']}' está prestes a expirar.",
            'data_termino' => $tarefa['data_termino']
        ];
    }

    // verifica tarefas compartilhadas a expirar e adiciona a notificacoes
    while ($tarefa = mysqli_fetch_assoc($resultado_tarefas_compartilhadas_prestes_a_expirar)) {
        $notificacoes[] = [
            'mensagem' => "A tarefa compartilhada '{$tarefa['titulo']}' está prestes a expirar.",
            'data_termino' => $tarefa['data_termino']
        ];
    }

    return $notificacoes;
}

// verifica se o utilizador = autenticado
session_start();
if (!isset($_SESSION['utilizador_id'])) {
    exit();
}

$utilizador_id = $_SESSION['utilizador_id'];

// obtem notificações ao utilizador atual
$notificacoes = obterNotificacoes($conexao, $utilizador_id);

// retorna notificacoes em JSON
echo json_encode($notificacoes);
?>
