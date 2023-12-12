<?php
// inicia a sessao
session_start();

// destroi todas as variaveis de sessão
$_SESSION = array();

// expira o cookie de sessao
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// destruir a sessao
session_destroy();
echo '<script>
            alert("Sucesso.");
            window.location.href = "login.php";
        </script>';
        exit();
// redireciona para a pagina de login
header("Location: login.php");
exit();
?>
