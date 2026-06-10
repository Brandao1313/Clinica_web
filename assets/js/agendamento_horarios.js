// ====================================================
// ARQUIVO: assets/js/agendamento_horarios.js
// Descrição: Carregamento dinâmico (fetch) de médicos por
//            especialidade e horários disponíveis por médico/data,
//            usado no formulário "Agendar Consulta" do painel do cliente
// ====================================================

(function () {
    const selectEspecialidade = document.getElementById('especialidade');
    const selectMedico = document.getElementById('medico');
    const inputData = document.getElementById('data_consulta');
    const selectHorario = document.getElementById('horario');
    const valorConsultaInfo = document.getElementById('valor-consulta-info');

    if (!selectEspecialidade || !selectMedico || !inputData || !selectHorario) {
        return;
    }

    const baseUrl = document.body.getAttribute('data-base-url') || '';

    function limparSelect(select, placeholder) {
        select.innerHTML = '';
        const opcao = document.createElement('option');
        opcao.value = '';
        opcao.textContent = placeholder;
        select.appendChild(opcao);
    }

    function carregarMedicos() {
        const idEspecialidade = selectEspecialidade.value;
        limparSelect(selectMedico, '-- Selecione um médico --');
        limparSelect(selectHorario, '-- Selecione um horário --');
        selectMedico.disabled = true;
        selectHorario.disabled = true;
        if (valorConsultaInfo) {
            valorConsultaInfo.textContent = '';
        }

        if (!idEspecialidade) {
            return;
        }

        fetch(baseUrl + 'backend/controllers/medicos_por_especialidade.php?id_especialidade=' + encodeURIComponent(idEspecialidade))
            .then(function (resposta) { return resposta.json(); })
            .then(function (dados) {
                const medicos = dados.medicos || [];
                limparSelect(selectMedico, medicos.length ? '-- Selecione um médico --' : 'Nenhum médico disponível');

                medicos.forEach(function (medico) {
                    const opcao = document.createElement('option');
                    opcao.value = medico.id;
                    opcao.textContent = medico.nome + ' (' + medico.crm + ') - ' + medico.valor_formatado;
                    opcao.dataset.valor = medico.valor_formatado;
                    selectMedico.appendChild(opcao);
                });

                selectMedico.disabled = medicos.length === 0;
            })
            .catch(function () {
                limparSelect(selectMedico, 'Erro ao carregar médicos');
            });
    }

    function carregarHorarios() {
        const idMedico = selectMedico.value;
        const data = inputData.value;
        limparSelect(selectHorario, '-- Selecione um horário --');
        selectHorario.disabled = true;

        const opcaoSelecionada = selectMedico.options[selectMedico.selectedIndex];
        if (valorConsultaInfo) {
            valorConsultaInfo.textContent = (idMedico && opcaoSelecionada && opcaoSelecionada.dataset.valor)
                ? 'Valor da consulta: ' + opcaoSelecionada.dataset.valor
                : '';
        }

        if (!idMedico || !data) {
            return;
        }

        fetch(baseUrl + 'backend/controllers/horarios_disponiveis.php?id_medico=' + encodeURIComponent(idMedico) + '&data=' + encodeURIComponent(data))
            .then(function (resposta) { return resposta.json(); })
            .then(function (dados) {
                const horarios = dados.horarios || [];
                limparSelect(selectHorario, horarios.length ? '-- Selecione um horário --' : 'Nenhum horário disponível nesta data');

                horarios.forEach(function (hora) {
                    const opcao = document.createElement('option');
                    opcao.value = hora;
                    opcao.textContent = hora;
                    selectHorario.appendChild(opcao);
                });

                selectHorario.disabled = horarios.length === 0;
            })
            .catch(function () {
                limparSelect(selectHorario, 'Erro ao carregar horários');
            });
    }

    selectEspecialidade.addEventListener('change', carregarMedicos);
    selectMedico.addEventListener('change', carregarHorarios);
    inputData.addEventListener('change', carregarHorarios);
})();
