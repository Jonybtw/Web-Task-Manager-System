<?php
// incluir conexao
include 'conexao.php';

// verificar se o formulario foi submetido
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // recolher dados do formulário
    $email = mysqli_real_escape_string($conexao, $_POST['email']);
    $senha = mysqli_real_escape_string($conexao, $_POST['senha']);

    // consultar a base de dados para obter informacoes do utilizador
    $query = "SELECT id, senha FROM utilizadores WHERE email = '$email'";
    $resultado = mysqli_query($conexao, $query);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $row = mysqli_fetch_assoc($resultado);

        // verificar a senha usando password_verify
        if (password_verify($senha, $row['senha'])) {
            // iniciar a sessao
            session_start();
            $_SESSION['utilizador_id'] = $row['id'];

            // redirecionar para a página principal
            header("Location: tarefas.php");
            exit(); // evita execucao dupla
        } else {
            // exibir mensagem de popup com a mensagem de erro
            echo '<script>
                    alert("Credenciais inválidas.");
                    window.location.href = "login.php";
                </script>';
            exit(); // evitar execução adicional
        }
    } else {
        // exibir mensagem de popup com a mensagem de erro
        echo '<script>
                alert("Credenciais inválidas.");
                window.location.href = "login.php";
            </script>';
        exit(); // evitar execução adicional
    }

    // redireciona de volta para a página de login com mensagem de erro
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="login-styles.css">
</head>
<body>
    <div>
        <form method="post" action="">
        <h2>Login</h2>
            <label for="email">Email:</label>
            <input type="email" name="email" required><br>

            <label for="senha">Senha:</label>
            <input type="password" name="senha" required><br>

            <input type="submit" value="Login">
            <p>Esqueceste-te da senha? <a href="resetar_senha.php">Resetar</a></p>
            <p>Não tens conta? <a href="registo.php">Registar</a></p>
        </form>
    </div>
</body>
</html>
