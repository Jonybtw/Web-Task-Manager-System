<?php
// inclui a conexao
include 'conexao.php';

// verifica se o utilizador = autenticado
session_start();
if (!isset($_SESSION['utilizador_id'])) {
    header("Location: login.php");
    exit();
}

// busca o id do utilizador autenticado
$utilizador_id = $_SESSION['utilizador_id'];

$mensagem = '';

// funcao para obter tarefas
function obterTarefas($conexao, $utilizador_id) {
    $query = "SELECT t.*, c.nome as categoria_nome, p.nivel as prioridade_nivel
              FROM tarefas t
              LEFT JOIN categorias c ON t.categoria_id = c.id
              LEFT JOIN prioridades p ON t.prioridade_id = p.id
              LEFT JOIN compartilhamento_tarefas ct ON t.id = ct.tarefa_id
              WHERE t.utilizador_id = '$utilizador_id' OR ct.utilizador_compartilhado_id = '$utilizador_id'";
    $resultado = mysqli_query($conexao, $query);
    return $resultado;
}

// funcao para obter categorias
function obterCategorias($conexao) {
    $query = "SELECT * FROM categorias";
    $resultado = mysqli_query($conexao, $query);
    return $resultado;
}

// funcao para obter prioridades
function obterPrioridades($conexao) {
    $query = "SELECT * FROM prioridades";
    $resultado = mysqli_query($conexao, $query);
    return $resultado;
}

function adicionarEditarTarefa($conexao, $utilizador_id, $id, $titulo, $descricao, $data_termino, $status, $categoria_id, $prioridade_id) {
    $titulo = mysqli_real_escape_string($conexao, $titulo);
    $descricao = mysqli_real_escape_string($conexao, $descricao);

    // verifica se categoria_id e prioridade_id != NULL
    $categoria_id = (isset($categoria_id) && $categoria_id !== null) ? intval($categoria_id) : null;
    $prioridade_id = (isset($prioridade_id) && $prioridade_id !== null) ? intval($prioridade_id) : null;

    // verifica se categoria_id e prioridade_id == valida
    $query_verificar_categoria = "SELECT id FROM categorias WHERE id = '$categoria_id'";
    $resultado_verificar_categoria = mysqli_query($conexao, $query_verificar_categoria);

    if (!$resultado_verificar_categoria || mysqli_num_rows($resultado_verificar_categoria) == 0) {
        $mensagem = "Categoria inválida.";
        return false;
    }

    try {
        if ($id) {
            $query = "UPDATE tarefas SET titulo = '$titulo', descricao = '$descricao', data_termino = '$data_termino', status = '$status', categoria_id = '$categoria_id', prioridade_id = '$prioridade_id' WHERE id = '$id' AND utilizador_id = '$utilizador_id'";
        } else {
            $query = "INSERT INTO tarefas (utilizador_id, titulo, descricao, data_termino, status, categoria_id, prioridade_id) VALUES ('$utilizador_id', '$titulo', '$descricao', '$data_termino', '$status', '$categoria_id', '$prioridade_id')";
        }

        $resultado = mysqli_query($conexao, $query);

        if ($resultado) {
            return true;
        } else {
            throw new Exception(mysqli_error($conexao));
        }
    } catch (Exception $e) {
        $mensagem = "Erro: " . $e->getMessage();
        return false;
    }
    echo '<script>
            alert("' . $mensagem . '");
        </script>';
}


// funcao para obter detalhes de uma tarefa
function obterDetalhesTarefa($conexao, $utilizador_id, $id) {
    $query = "SELECT t.*, c.nome as categoria_nome, p.nivel as prioridade_nivel
              FROM tarefas t
              LEFT JOIN categorias c ON t.categoria_id = c.id
              LEFT JOIN prioridades p ON t.prioridade_id = p.id
              WHERE t.id = '$id' AND t.utilizador_id = '$utilizador_id'";
    $resultado = mysqli_query($conexao, $query);
    return $resultado;
}

// funcao para excluir uma tarefa/tarefa compartilhada
function excluirTarefa($conexao, $utilizador_id, $id) {
    // inicia transacao
    mysqli_autocommit($conexao, false);

    try {
        // exclui a tarefa
        $query_excluir_tarefa = "DELETE FROM tarefas WHERE id = '$id' AND utilizador_id = '$utilizador_id'";
        $resultado_excluir_tarefa = mysqli_query($conexao, $query_excluir_tarefa);

        if (!$resultado_excluir_tarefa) {
            throw new Exception(mysqli_error($conexao));
        }

        // exclui em compartilhamento_tarefas
        $query_excluir_compartilhamento = "DELETE FROM compartilhamento_tarefas WHERE tarefa_id = '$id'";
        $resultado_excluir_compartilhamento = mysqli_query($conexao, $query_excluir_compartilhamento);

        if (!$resultado_excluir_compartilhamento) {
            throw new Exception(mysqli_error($conexao));
        }

        // commit se tem problemas
        mysqli_commit($conexao);

        return true;
    } catch (Exception $e) {
        // rollback se houve algum problema
        mysqli_rollback($conexao);

        return false;
    } finally {
        // restaura o modo de autocommit
        mysqli_autocommit($conexao, true);
    }
}

function compartilharTarefa($conexao, $tarefa_id, $compartilhar_email) {
    // verifica se o email do utilizador existe
    $verificar_email = mysqli_query($conexao, "SELECT id FROM utilizadores WHERE email = '$compartilhar_email'");
    
    if (!$verificar_email) {
        $mensagem = "Erro na verificação de email. Detalhes: " . mysqli_error($conexao);
    }

    if (mysqli_num_rows($verificar_email) > 0) {
        $row = mysqli_fetch_assoc($verificar_email);
        $utilizador_compartilhado_id = $row['id'];

        // verifica se o utilizador a tem tarefa compartilhada
        $query_verificar_compartilhamento = "SELECT id FROM compartilhamento_tarefas WHERE tarefa_id = '$tarefa_id' AND utilizador_compartilhado_id = '$utilizador_compartilhado_id'";
        $resultado_verificar_compartilhamento = mysqli_query($conexao, $query_verificar_compartilhamento);

        if (!$resultado_verificar_compartilhamento) {
            $mensagem = "Erro na verificação de compartilhamento. Detalhes: " . mysqli_error($conexao);
        }

        if (mysqli_num_rows($resultado_verificar_compartilhamento) == 0) {
            // insere a tarefa na tabela de compartilhamento_tarefas
            $inserir_compartilhamento = mysqli_query($conexao, "INSERT INTO compartilhamento_tarefas (tarefa_id, utilizador_compartilhado_id) VALUES ('$tarefa_id', '$utilizador_compartilhado_id')");

            if ($inserir_compartilhamento) {
                // apenas chama adicionarEditarTarefa se deu sucesso
                adicionarEditarTarefa($conexao, $_SESSION['utilizador_id'], $tarefa_id, '', '', '', '', null, null);
                $mensagem = "Tarefa compartilhada com sucesso!";
            } else {
                $mensagem = "Erro ao compartilhar a tarefa. Detalhes: " . mysqli_error($conexao);
            }
        } else {
            $mensagem = "A tarefa já está compartilhada com este usuário.";
        }
    } else {
        $mensagem = "O utilizador com o email fornecido não existe.";
    }
    echo '<script>
            alert("' . $mensagem . '");
        </script>';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['compartilhar_tarefa'])) {
    $tarefa_selecionada = $_POST['tarefa_selecionada'];
    $compartilhar_email = $_POST['compartilhar_email'];

    // chama a funcao compartilharTarefa e obtem a mensagem
    $mensagem_compartilhamento = compartilharTarefa($conexao, $tarefa_selecionada, $compartilhar_email);

    // mostra a mensagem
    $mensagem = $mensagem_compartilhamento;
    echo '<script>
            alert("' . $mensagem . '");
        </script>';
}

// verificar se o formulario foi submetido para criar ou editar tarefa
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // garante que o utilizador_id seja passado corretamente
    $utilizador_id = $_SESSION['utilizador_id'];
    if (empty($utilizador_id)) {
        $mensagem = "Utilizador não encontrado.";
        exit();
    }

    // verifica tentativa de compartilhar tarefa
    if (isset($_POST['compartilhar_tarefa'])) {
        $tarefa_selecionada = $_POST['tarefa_selecionada'];
        $compartilhar_email = $_POST['compartilhar_email'];

        $mensagem_compartilhamento = compartilharTarefa($conexao, $tarefa_selecionada, $compartilhar_email);

        // mostra a mensagem
        $mensagem = $mensagem_compartilhamento;
    } else {
        // Se != compartilhar e uma tentativa de criar ou editar tarefa
        $id = isset($_POST['id']) ? $_POST['id'] : null;
        $titulo = isset($_POST['titulo']) ? $_POST['titulo'] : '';
        $descricao = isset($_POST['descricao']) ? $_POST['descricao'] : '';
        $data_termino = isset($_POST['data_termino']) ? $_POST['data_termino'] : '';
        $status = isset($_POST['status']) ? $_POST['status'] : '';
        $categoria_id = isset($_POST['categoria']) ? $_POST['categoria'] : null;
        $prioridade_id = isset($_POST['prioridade']) ? $_POST['prioridade'] : null;

        // chama adicionarEditarTarefa para criar ou editar a tarefa
        if (adicionarEditarTarefa($conexao, $utilizador_id, $id, $titulo, $descricao, $data_termino, $status, $categoria_id, $prioridade_id)) {
            $mensagem = "Operação bem-sucedida!";
        } else {
            $mensagem = "Erro na operação.";
        }
    }
}


// verifica se ja foi o apagar de uma tarefa
if (isset($_GET['excluir'])) {
    $id_excluir = $_GET['excluir'];

    if (excluirTarefa($conexao, $utilizador_id, $id_excluir)) {
        $mensagem = "Tarefa excluída com sucesso!";
    } else {
        $mensagem = "Erro ao excluir a tarefa.";
    }
    echo '<script>
            alert("' . $mensagem . '");
        </script>';
}

// verifica se foi pedido detalhes de uma tarefa
if (isset($_GET['detalhes'])) {
    $id_detalhes = $_GET['detalhes'];
    $detalhes_tarefa = obterDetalhesTarefa($conexao, $utilizador_id, $id_detalhes);

    if ($detalhes_tarefa && mysqli_num_rows($detalhes_tarefa) > 0) {
        $detalhes = mysqli_fetch_assoc($detalhes_tarefa);
        // mostra os detalhes
    } else {
        $mensagem = "Tarefa não encontrada.";
    }
}

// obtem todas as tarefas do utilizador
$tarefas = obterTarefas($conexao, $utilizador_id);
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Gestão de Tarefas</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <script src="jquery-3.7.1.min.js"></script>
    <link rel="stylesheet" href="tarefas-styles.css">
</head>
<body>
    <div>
        <ul>
        <h2>Lista de Tarefas</h2>
            <?php
            while ($tarefa = mysqli_fetch_assoc($tarefas)) {
                echo "<li>";
                echo "<div><strong>Título:</strong> " . $tarefa['titulo'] . "</div>";
                echo "<div><strong>Data de Término:</strong> " . $tarefa['data_termino'] . "</div>";
                echo "<div><strong>Status:</strong> " . ucwords(str_replace('_', ' ', $tarefa['status'])) . "</div>";
                echo "<div><strong>Categoria:</strong> " . $tarefa['categoria_nome'] . "</div>";
                echo "<div><strong>Prioridade:</strong> " . $tarefa['prioridade_nivel'] . "</div>";
                echo "<div><a href='tarefas.php?detalhes=" . $tarefa['id'] . "'>Detalhes</a></div>";
                echo "<div><a href='tarefas.php?excluir=" . $tarefa['id'] . "'>Excluir</a></div>";
                echo "</li>";
            }
            ?>
        </ul>
    </div>

    <div>
    <!-- formulario para criar/editar tarefa -->
    <form method="post" action="">
        <h2>Criar/Editar Tarefas</h2>
        <input type="hidden" name="id" value="<?php echo isset($detalhes) ? $detalhes['id'] : ''; ?>">
        
        <label for="titulo">Título:</label>
        <input type="text" name="titulo" value="<?php echo isset($detalhes) ? $detalhes['titulo'] : ''; ?>" required><br>

        <label for="descricao">Descrição:</label>
        <textarea name="descricao"><?php echo isset($detalhes) ? $detalhes['descricao'] : ''; ?></textarea><br>

        <label for="data_termino">Data de Término:</label>
        <input type="date" name="data_termino" value="<?php echo isset($detalhes) ? $detalhes['data_termino'] : ''; ?>" required><br>

        <label for="status">Status:</label>
        <select name="status">
            <option value="pendente" <?php echo (isset($detalhes) && $detalhes['status'] == 'pendente') ? 'selected' : ''; ?>>Pendente</option>
            <option value="em_andamento" <?php echo (isset($detalhes) && $detalhes['status'] == 'em_andamento') ? 'selected' : ''; ?>>Em Andamento</option>
            <option value="concluida" <?php echo (isset($detalhes) && $detalhes['status'] == 'concluida') ? 'selected' : ''; ?>>Concluída</option>
        </select><br>

        <label for="categoria">Categoria:</label>
        <select name="categoria">
            <?php
            $categorias = obterCategorias($conexao);
            while ($categoria = mysqli_fetch_assoc($categorias)) {
                echo "<option value='" . $categoria['id'] . "'";
                if (isset($detalhes) && $detalhes['categoria_id'] == $categoria['id']) {
                    echo ' selected';
                }
                echo ">" . $categoria['nome'] . "</option>";
            }
            ?>
        </select><br>

        <label for="prioridade">Prioridade:</label>
        <select name="prioridade">
            <?php
            $prioridades = obterPrioridades($conexao);
            while ($prioridade = mysqli_fetch_assoc($prioridades)) {
                echo "<option value='" . $prioridade['id'] . "'";
                if (isset($detalhes) && $detalhes['prioridade_id'] == $prioridade['id']) {
                    echo ' selected';
                }
                echo ">" . $prioridade['nivel'] . "</option>";
            }
            ?>
        </select><br>

        <input type="submit" value="<?php echo isset($detalhes) ? 'Editar' : 'Criar'; ?> Tarefa">
    </form>

    <form method="post" action="">
        <h2>Compartilhar Tarefa</h2>
        <label for="tarefa_selecionada">Selecione a Tarefa:</label>
        <select name="tarefa_selecionada">
            <?php
            // reinicia o ponteiro das tarefas para o início
            mysqli_data_seek($tarefas, 0);
            while ($tarefa = mysqli_fetch_assoc($tarefas)) {
                echo "<option value='{$tarefa['id']}'>{$tarefa['titulo']}</option>";
            }
            ?>
        </select>

        <label for="compartilhar_email">Compartilhar com Utilizador:</label>
        <input type="text" name="compartilhar_email" placeholder="Email do Utilizador">

        <input type="submit" name="compartilhar_tarefa" value="Compartilhar Tarefa">
        
        <div id="notificacoes"></div>
        <script src="verificarNotificacoes.js"></script>
    </form>
    </div>

    <form id="logout" method="post" action="logout.php">
        <input type="submit" value="Logout">
    </form>
</body>
</html>
