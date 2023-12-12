// Função para solicitar permissão de notificação
function solicitarPermissaoNotificacao() {
    if (!("Notification" in window)) {
        console.error("Este navegador não suporta notificações.");
        return;
    }

    Notification.requestPermission().then(function (permissao) {
        if (permissao === "granted") {
        console.log("Permissão de notificação concedida.");
        } else {
        console.warn("Permissão de notificação negada.");
        }
    });
    }

    // Função para exibir notificação
    function exibirNotificacao(mensagem) {
    if (Notification.permission === "granted") {
        var options = {
        body: mensagem,
        icon: "favicone.ico"
        };

        var notificacao = new Notification("Tarefa Prestes a Vencer", options);

        notificacao.onclick = function () {
        // Ação a ser tomada ao clicar na notificação (pode redirecionar para uma página, etc.)
        console.log("Notificação clicada.");
        };
    } else if (Notification.permission !== "denied") {
        // Se a permissão não foi negada, solicita permissão
        solicitarPermissaoNotificacao();
    }
    }

    // Função para verificar notificações do servidor
    function verificarNotificacoes() {
    fetch("verificarNotificacoes.php")
        .then(function (response) {
        return response.json();
        })
        .then(function (notificacoes) {
        notificacoes.forEach(function (notificacao) {
            var dataTermino = new Date(notificacao.data_termino);
            var agora = new Date();

            // Verificar se a tarefa está prestes a vencer (por exemplo, dentro de 24 horas)
            if (dataTermino > agora && dataTermino - agora < 24 * 60 * 60 * 1000) {
            exibirNotificacao(notificacao.mensagem);
            }
        });
        })
        .catch(function (erro) {
        console.error("Erro ao carregar notificações:", erro);
        });
    }

    // Solicitar permissão de notificação quando a página é carregada
    document.addEventListener("DOMContentLoaded", solicitarPermissaoNotificacao);

    // Verificar notificações a cada minuto (pode ajustar o intervalo conforme necessário)
    setInterval(verificarNotificacoes, 60 * 1000);