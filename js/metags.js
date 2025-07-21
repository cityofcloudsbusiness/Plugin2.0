// Inicializa contadores
let numC = 1;
let numT = 1;

// Evento delegativo: Adiciona cidade
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('btn-add-city')) {
        numC++;
        const campos = document.querySelector('#cidade .campos');
        if (campos) {
            campos.insertAdjacentHTML('beforeend', `
                    <div>
                        <input type="text" name="cidade${numC}" placeholder="Digite a cidade ${numC}">
                    </div>`);
        }
    }
});

// Evento delegativo: Adiciona telefone
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('btn-add-phone')) {
        numT++;
        const campos = document.querySelector('#numero .campos');
        if (campos) {
            campos.insertAdjacentHTML('beforeend', `
                    <div>
                        <input type="text" name="telefone${numT}" placeholder="Digite o telefone ${numT}">
                    </div>`);
        }
    }
});

// AO CLICAR EM SEC2 CADASTRAR TITULO DE PRODUTO E CATEGORIA


// Função genérica que dispara o SSE e mostra mensagens de ERRO do servidor
function runSSE(type) {
    const oldRaw = $('#oldTitle').val().trim();
    const newRaw = $('#newTitle').val().trim();
    if (!oldRaw || !newRaw) {
        alert('Preencha ambos os campos antes de prosseguir.');
        return;
    }

    // desabilita botões
    $('.botoasec2prod, .botoasec2cat').prop('disabled', true);

    // zera barra e textos
    $('.progress').css('width', '0%');
    $('.Value').text('0%');

    // **Atenção aqui**: caminho CORRETO para o SSE
    const url = `backend/metaTags/processAlterTitles.php`
        + `?type=${type}`
        + `&old=${encodeURIComponent(oldRaw)}`
        + `&new=${encodeURIComponent(newRaw)}`;

    const evt = new EventSource(url);

    // Lida com o seu evento progress
    evt.addEventListener('progress', e => {
        const p = parseInt(e.data, 10);
        $('.progress').css('width', p + '%');
        $('.Value').text(p + '%');
    });

    // Quando o PHP enviar complete
    evt.addEventListener('complete', () => {
        $('.progress').css('width', '100%');
        $('.Value').text('100%');
        evt.close();
        $('.botoasec2prod, .botoasec2cat').prop('disabled', false);
        alert('Operação concluída!');
    });

    // Captura erros de conexão *e* seu próprio `event: error`
    evt.addEventListener('error', e => {
        // se veio .data, é seu event: error do PHP
        if (e.data) {
            alert('Erro do servidor: ' + e.data);
        } else {
            alert('Erro de conexão SSE.');
        }
        evt.close();
        $('.botoasec2prod, .botoasec2cat').prop('disabled', false);
    });
}

// Delegated event handling (funcionará mesmo se #area_apresent vier por AJAX)
$(document).on('click', '#area_apresent .botaosec2prod', function (e) {
    e.preventDefault();
    runSSE('product');
});
$(document).on('click', '#area_apresent .botaosec2cat', function (e) {
    e.preventDefault();
    runSSE('category');
});
// AO CLICAR CADASTRAR META-DESCRIÇÃO SEGUNDO CONTAINER

$(document).on('click', '#area_apresent .botaodesc', function (e) {
    e.preventDefault();
    const d = $('#desc').val().trim();
    if (!d) {
        alert('Digite uma descrição.');
        return;
    }
    const evt = new EventSource(
        'backend/metaTags/recebeTagsRootCat.php?desc=' + encodeURIComponent(d)
    );
    evt.addEventListener('progress', e => {
        const pct = parseInt(e.data, 10);
        $('main#principal #right .progress').css('width', pct + '%');
        $('main#principal #right .Value').text(pct + '%');
    });
    evt.addEventListener('complete', () => {
        $('main#principal #right .progress').css('width', '100%');
        $('main#principal #right .Value').text('100%');
        evt.close();
        
        alert('Operação concluída!');
    });
    evt.addEventListener('error', () => {
        evt.close();
        alert('Erro ao atualizar.');
    });
});


// AO CLICAR CADASTRAR META-SEARCH PRODUTO
$(document).on('click', '#area_apresent .botaoprod', function (e) {
    e.preventDefault();
    const $btn = $(this).prop('disabled', true);

    // coleta arrays
    const cidades = [];
    for (let i = 1; i <= numC; i++) {
        const v = $(`[name=cidade${i}]`).val().trim();
        if (v) cidades.push(v);
    }
    const telefones = [];
    for (let i = 1; i <= numT; i++) {
        const v = $(`[name=telefone${i}]`).val().trim();
        if (v) telefones.push(v);
    }

    // abre SSE passando tudo via query string
    const params = $.param({
        num1: cidades.length,
        num2: telefones.length,
        cidades: JSON.stringify(cidades),
        telefones: JSON.stringify(telefones)
    });
    const evt = new EventSource('backend/metaTags/recebeTags.php?' + params);

    evt.addEventListener('progress', e => {
        const pct = parseInt(e.data, 10);
        $('main#principal #right .progress').css('width', pct + '%');
        $('main#principal #right .Value').text(pct + '%');
    });
    evt.addEventListener('complete', e => {
        $('main#principal #right .progress').css('width', '100%');
        $('main#principal #right .Value').text('100%');
        evt.close();
        $btn.prop('disabled', false);
        
        alert('Operação concluída!');
    });
    evt.addEventListener('error', e => {
        console.error('SSE error:', e);
        evt.close();
        $btn.prop('disabled', false);
    });
});

// AO CLICAR CADASTRAR META-SEARCH CATEGORIA
$(document).on('click', '#area_apresent .botaocat', function (e) {
    e.preventDefault();
    const $btn = $(this).prop('disabled', true);

    // Coleta cidades e telefones
    const cidades = [];
    for (let i = 1; i <= numC; i++) {
        const v = $(`[name=cidade${i}]`).val().trim();
        if (v) cidades.push(v);
    }
    const telefones = [];
    for (let i = 1; i <= numT; i++) {
        const v = $(`[name=telefone${i}]`).val().trim();
        if (v) telefones.push(v);
    }

    // Abre SSE para progresso
    const params = $.param({
        num1: cidades.length,
        num2: telefones.length,
        cidades: JSON.stringify(cidades),
        telefones: JSON.stringify(telefones)
    });
    const evt = new EventSource('backend/metaTags/recebeTagsCat.php?' + params);

    evt.addEventListener('progress', e => {
        const pct = parseInt(e.data, 10);
        $('main#principal #right .progress').css('width', pct + '%');
        $('main#principal #right .Value').text(pct + '%');
    });
    evt.addEventListener('complete', () => {
        $('main#principal #right .progress').css('width', '100%');
        $('main#principal #right .Value').text('100%');
        evt.close();
        $btn.prop('disabled', false);
        
        alert('Operação concluída!');
    });
    evt.addEventListener('error', () => {
        evt.close();
        $btn.prop('disabled', false);
    });
});