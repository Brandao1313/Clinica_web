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
    inicializarMascarasInput();
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
 * Máscaras de formatação para CPF e telefone.
 * Uso: <input data-mascara="cpf"> ou <input data-mascara="telefone">
 * O backend deve fazer preg_replace('/\D/', '', $valor) antes de processar.
 */
function aplicarMascaraCPF(valor) {
    valor = valor.replace(/\D/g, '').slice(0, 11);
    if (valor.length > 9) return valor.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
    if (valor.length > 6) return valor.replace(/(\d{3})(\d{3})(\d{0,3})/, '$1.$2.$3');
    if (valor.length > 3) return valor.replace(/(\d{3})(\d{0,3})/, '$1.$2');
    return valor;
}

function aplicarMascaraTelefone(valor) {
    valor = valor.replace(/\D/g, '').slice(0, 11);
    if (valor.length === 11) return valor.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
    if (valor.length === 10) return valor.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
    if (valor.length > 6)   return valor.replace(/(\d{2})(\d{1,5})(\d{0,4})/, '($1) $2-$3');
    if (valor.length > 2)   return valor.replace(/(\d{2})(\d{0,5})/, '($1) $2');
    if (valor.length > 0)   return '(' + valor;
    return valor;
}

function inicializarMascarasInput() {
    document.querySelectorAll('[data-mascara="cpf"]').forEach(function (input) {
        // Formata o valor já existente (caso de re-exibição após erro)
        if (input.value) input.value = aplicarMascaraCPF(input.value);
        input.addEventListener('input', function () {
            var pos = input.selectionStart;
            var anterior = input.value.replace(/\D/g, '').length;
            input.value = aplicarMascaraCPF(input.value);
            var posterior = input.value.replace(/\D/g, '').length;
            // Ajusta cursor: avança pelo mesmo número de dígitos digitados
            var diff = input.value.length - (input.value.replace(/\D/g, '').length - posterior + anterior);
            input.setSelectionRange(pos + (posterior - anterior), pos + (posterior - anterior));
        });
    });

    document.querySelectorAll('[data-mascara="telefone"]').forEach(function (input) {
        if (input.value) input.value = aplicarMascaraTelefone(input.value);
        input.addEventListener('input', function () {
            input.value = aplicarMascaraTelefone(input.value);
        });
    });
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
