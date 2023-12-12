<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

// inicializa o PHPMailer
$mail = new PHPMailer(true);

// incluir a conexão com o banco de dados
include 'conexao.php';

// inicializar a mensagem
$mensagem = '';

// verifica se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Recolher dados do formulário
    $email = $_POST['email'];

    // Gerar um token único para recuperação de senha
    $token = bin2hex(random_bytes(32));

    // Calcular a data de expiração (por exemplo, 1 hora a partir do momento atual)
    $data_expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));

    // Atualizar o token e a data de expiração na base de dados
    $query = "UPDATE utilizadores SET reset_token = '$token', token_expira_em = '$data_expiracao' WHERE email = '$email'";
    $resultado = mysqli_query($conexao, $query);

    if ($resultado) {
        try {
            // Configuração do servidor SMTP local (MailHog)
            $mail->isSMTP();
            $mail->CharSet = 'UTF-8';
            $mail->Host       = 'localhost'; // O servidor SMTP do MailHog
            $mail->SMTPAuth   = false; // Não requer autenticação
            $mail->Port       = 1025; // A porta padrão do MailHog

            // Configuração do remetente e destinatário
            $mail->setFrom('gestaodetarefas@gestaodetarefas.com', 'Gestão de Tarefas');
            $mail->addAddress($email);

            // Conteúdo do e-mail
            $mail->isHTML(true);
            $mail->Subject = 'Recuperação de Senha';
            $mail->Body    = "Solicitou a recuperação de senha. Clique no link abaixo para redefinir sua senha:<br><br><a href='http://localhost/taskmanager/nova_senha.php?token=$token'>Redefinir Senha</a>";

            // Envia o e-mail
            $mail->send();
            $mensagem = 'Email enviado com sucesso. Verifique a sua caixa de entrada para instruções.';
        } catch (Exception $e) {
            $mensagem = "Erro ao enviar e-mail: {$mail->ErrorInfo}";
        }
    } else {
        $mensagem = "Erro ao processar a recuperação de senha: " . mysqli_error($conexao);
    }

    // Exibir a mensagem usando um popup
    echo '<script>
            alert("' . $mensagem . '");
            window.location.href = "login.php";
        </script>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Recuperação de Senha</title>
    <link rel="icon" href="favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="reset-styles.css">
</head>
<body>
    <form method="post" action="">
        <h2>Recuperação de Senha</h2>
        <label for="email">Email:</label>
        <input type="email" name="email" required><br>

        <input type="submit" value="Recuperar Senha">
        <p>Voltar ao login? <a href="login.php">Login</a></p>
    </form>
</body>
</html>
