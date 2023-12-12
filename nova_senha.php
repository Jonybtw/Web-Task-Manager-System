<?php
// inclui a conexao
include 'conexao.php';

// verificar se o formulario foi submetido
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // recolhe dados do formulario
    $email = $_POST['email'];
    $token = $_POST['token'];
    $nova_senha = $_POST['nova_senha'];

    // verificar se o token esta correto e ainda nao expirou
    $query = "SELECT id, reset_token, token_expira_em FROM utilizadores WHERE email = '$email'";
    $resultado = mysqli_query($conexao, $query);

    if ($resultado && mysqli_num_rows($resultado) > 0) {
        $row = mysqli_fetch_assoc($resultado);

        $reset_token_banco = $row['reset_token'];
        $token_expira_em = strtotime($row['token_expira_em']);

        if ($reset_token_banco === $token && $token_expira_em > time()) {
            // hash da nova senha
            $hash_nova_senha = password_hash($nova_senha, PASSWORD_DEFAULT);

            // atualizar a senha na base de dados e limpa as colunas de recuperação de senha
            $query_update = "UPDATE utilizadores SET senha = '$hash_nova_senha', reset_token = NULL, token_expira_em = NULL WHERE id = " . $row['id'];
            $resultado_update = mysqli_query($conexao, $query_update);

            if ($resultado_update) {
                // exibir mensagem de sucesso e redirecionar para a página de login
                echo '<script>
                        alert("Senha redefinida com sucesso! Faça login com a nova senha.");
                        window.location.href = "login.php";
                    </script>';
                exit(); // evitar execução adicional
            } else {
                // exibir mensagem de erro
                echo '<script>
                        alert("Erro ao redefinir senha: ' . mysqli_error($conexao) . '");
                        window.location.href = "login.php";
                    </script>';
                exit(); // evitar execução adicional
            }
        } else {
            // exibir mensagem de erro
            echo '<script>
                    alert("Token inválido ou expirado.");
                    window.location.href = "login.php";
                </script>';
            exit(); // evitar execução adicional
        }
    } else {
        // exibir mensagem de erro
        echo '<script>
                alert("Utilizador não encontrado.");
                window.location.href = "login.php";
            </script>';
        exit(); // evitar execução adicional
    }
} else {
    // verifica se o token esta presente na URL
    if (isset($_GET['token'])) {
        $token_url = $_GET['token'];

        //  se o token esta correto e ainda nao expirou
        $query = "SELECT email, reset_token, token_expira_em FROM utilizadores WHERE reset_token = '$token_url'";
        $resultado = mysqli_query($conexao, $query);

        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $row = mysqli_fetch_assoc($resultado);
            $token_expira_em = strtotime($row['token_expira_em']);

            if ($token_expira_em > time()) {
                // mostra o formulário para a nova senha
                $email = $row['email'];
                ?>
                <!DOCTYPE html>
                <html lang="pt-PT">
                <head>
                    <meta charset="UTF-8">
                    <title>Nova Senha</title>
                    <link rel="icon" href="favicon.ico" type="image/x-icon">
                </head>
                <body>
                    <form method="post" action="">
                        <h2>Definir Nova Senha</h2>
                        <link rel="stylesheet" href="reset-styles.css">
                        <input type="hidden" name="email" value="<?php echo $email; ?>">
                        <input type="hidden" name="token" value="<?php echo $token_url; ?>">
                        <label for="nova_senha">Nova Senha:</label>
                        <input type="password" name="nova_senha" required><br>

                        <input type="submit" value="Definir Nova Senha">
                    </form>
                </body>
                </html>
                <?php
            } else {
                // exibir mensagem de erro
                echo '<script>
                        alert("O token expirou.");
                        window.location.href = "login.php";
                    </script>';
                exit(); // evitar execução adicional
            }
        } else {
            // exibir mensagem de erro
            echo '<script>
                    alert("Token inválido.");
                    window.location.href = "login.php";
                </script>';
            exit(); // evitar execução adicional
        }
    } else {
        // exibir mensagem de erro
        echo '<script>
                alert("Token não fornecido na URL.");
                window.location.href = "login.php";
            </script>';
        exit(); // evitar execução adicional
    }
}
?>
