/* ====================================================
   ARQUIVO: assets/js/app.js
   Descrição: Comportamentos visuais leves (toasts,
              modal de confirmação) - não interfere na
              lógica de backend.
   ==================================================== */

document.addEventListener('DOMContentLoaded', function () {
    inicializarFlashToasts();
    inicializarModalCancelamento();
    inicializarLoadingFormularios();
});

/**
 * Faz as mensagens flash (.flash-toast) desaparecerem
 * automaticamente após 5 segundos, com barra de progresso.
 */
function inicializarFlashToasts() {
    var toasts = document.querySelectorAll('.flash-toast');

    toasts.forEach(function (toast) {
        var remover = function () {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(20px)';
            setTimeout(function () {
                toast.remove();
            }, 200);
        };

        var timer = setTimeout(remover, 5000);

        var fechar = toast.querySelector('.flash-toast-fechar');
        if (fechar) {
            fechar.addEventListener('click', function () {
                clearTimeout(timer);
                remover();
            });
        }
    });
}

/**
 * Substitui o confirm() nativo por um modal estilizado
 * para links/botões com [data-confirm].
 * Uso: <a href="..." data-confirm="Mensagem...">Cancelar</a>
 */
function inicializarModalCancelamento() {
    var overlay = document.getElementById('modal-confirmacao');
    if (!overlay) {
        return;
    }

    var titulo = overlay.querySelector('[data-modal-titulo]');
    var texto = overlay.querySelector('[data-modal-texto]');
    var btnConfirmar = overlay.querySelector('[data-modal-confirmar]');
    var btnCancelar = overlay.querySelector('[data-modal-cancelar]');
    var linkAtual = null;

    document.querySelectorAll('[data-confirm]').forEach(function (elemento) {
        elemento.addEventListener('click', function (evento) {
            evento.preventDefault();
            linkAtual = elemento;

            if (titulo && elemento.dataset.confirmTitulo) {
                titulo.textContent = elemento.dataset.confirmTitulo;
            }
            if (texto) {
                texto.textContent = elemento.dataset.confirm;
            }

            overlay.classList.add('aberto');
        });
    });

    function fecharModal() {
        overlay.classList.remove('aberto');
        linkAtual = null;
    }

    if (btnCancelar) {
        btnCancelar.addEventListener('click', fecharModal);
    }

    overlay.addEventListener('click', function (evento) {
        if (evento.target === overlay) {
            fecharModal();
        }
    });

    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', function () {
            if (linkAtual) {
                window.location.href = linkAtual.href;
            }
        });
    }
}

/**
 * Adiciona efeito visual de carregamento ao enviar
 * formulários (sem impedir o envio real).
 */
function inicializarLoadingFormularios() {
    document.querySelectorAll('form').forEach(function (form) {
        form.addEventListener('submit', function () {
            var botao = form.querySelector('button[type="submit"]');
            if (botao) {
                botao.classList.add('carregando');
            }
        });
    });
}
