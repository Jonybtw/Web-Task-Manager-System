<?php
// inclui a conexao
include 'conexao.php';

// verifica se o formulario foi submetido
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recolher dados do formulário
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = password_hash($_POST['senha'], PASSWORD_DEFAULT); // hash da senha

    // verifica se o email já está registrado
    $verificar_email = "SELECT id FROM utilizadores WHERE email = '$email'";
    $resultado_verificar = mysqli_query($conexao, $verificar_email);

    if (mysqli_num_rows($resultado_verificar) > 0) {
        // exibir mensagem de popup com a mensagem de erro
        echo '<script>
                alert("Erro no registo: Este e-mail já está registado.");
                window.location.href = "registo.php";
            </script>';
        exit(); // evitar execução adicional
    }

    // insere utilizador na base de dados
    $query = "INSERT INTO utilizadores (nome, email, senha) VALUES ('$nome', '$email', '$senha')";
    $resultado = mysqli_query($conexao, $query);

    if ($resultado) {
        // exibir mensagem de popup com a mensagem de sucesso
        echo '<script>
                alert("Registo bem-sucedido! Faça login.");
                window.location.href = "login.php";
            </script>';
        exit(); // evitar execução adicional
    } else {
        // exibir mensagem de popup com a mensagem de erro
        echo '<script>
                alert("Erro no registo: ' . mysqli_error($conexao) . '");
                window.location.href = "registo.php";
            </script>';
        exit(); // evitar execução adicional
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Registo</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="registo-styles.css">
</head>
<body>
    <form method="post" action="">
        <h2>Registo</h2>
        <label for="nome">Nome:</label>
        <input type="text" name="nome" required><br>

        <label for="email">Email:</label>
        <input type="email" name="email" required><br>

        <label for="senha">Senha:</label>
        <input type="password" name="senha" required><br>

        <input type="submit" value="Registar">
        <p>Já tens conta? <a href="login.php">Login</a></p>
    </form>
</body>
</html>
