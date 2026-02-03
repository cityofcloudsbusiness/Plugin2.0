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



//AO CLICAR EM TRANSFORMAR CATEGORIAS EM PRODUTOS DESTAQUES

// Delegated click para o botão de destaques
// usa event delegation pra capturar o clique mesmo se #area_apresent for trocado por AJAX
$(document).on('click', '#area_apresent .btn_convert_cdprod', function (e) {
    e.preventDefault();
    runCategoriaDestaqueSSE();
});

function runCategoriaDestaqueSSE() {
    const btn = $('.btn_convert_cdprod').prop('disabled', true);

    // zera UI
    $('.progress').css('width', '0%');
    $('.Value').text('0%');

    // monta a URL absoluta com base dinâmica
    const url = window.location.origin
        + window.BASE_URL
        + '/backend/metaTags/processConfigCatDestaque.php';
    console.log('SSE endpoint:', url);

    const evt = new EventSource(url);

    evt.onopen = () => console.log('SSE conectado');
    evt.onerror = err => {
        console.error('SSE erro de conexão', err);
        alert('Falha na conexão SSE. Veja console → Network.');
        evt.close();
        btn.prop('disabled', false);
    };

    evt.addEventListener('progress', e => {
        const p = parseInt(e.data, 10);
        $('.progress').css('width', p + '%');
        $('.Value').text(p + '%');
    });

    evt.addEventListener('complete', () => {
        $('.progress').css('width', '100%');
        $('.Value').text('100%');
        evt.close();
        btn.prop('disabled', false);
        alert('Produtos destaque criados com sucesso!');
    });
}


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


// AO CLICAR EM UPLOAD PLANILHA EXCEL

$(function () {
    document.addEventListener('change', function (e) {
        // “e.target” é o elemento que disparou o change
        if (e.target && e.target.id === 'arquivoJSON') {
            const imgIcon = document.getElementById('imgBD');
            if (e.target.files && e.target.files.length > 0) {
                imgIcon.src = 'img/bd.png';
            } else {
                imgIcon.src = 'img/exelupload.png';
            }
        }
    });


    $(document).on('click', '.btn_excel_up', function () {
        const file = $('#arquivoExcel')[0].files[0];
        if (!file) { alert('Selecione um arquivo.'); return; }
        $('.load1').show();

        const fd = new FormData();
        fd.append('arquivo', file);

        $.ajax({
            url: window.BASE_URL + '/backend/metaTags/processUpload.php',
            method: 'POST',
            data: fd,
            contentType: false,
            processData: false,
            success() { runSSE(); },
            error() {
                alert('Falha no upload.');
                $('.load1').hide();
            }
        });
    });

    function runSSE() {
        const url = window.BASE_URL + '/backend/metaTags/processImportExcel.php';
        console.log('SSE →', url);
        const evt = new EventSource(
            window.BASE_URL + '/backend/metaTags/processImportExcel.php'
        );
        evt.onopen = () => console.log('SSE conectado');
        evt.onerror = e => {
            console.error('SSE erro', e);
            alert('Erro na conexão SSE.');
            evt.close();
            $('.load1').hide();
        };
        evt.addEventListener('progress', e => {
            const p = parseInt(e.data, 10);
            $('.progress').css('width', p + '%');
            $('.Value').text(p + '%');
        });
        evt.addEventListener('complete', () => {
            $('.progress').css('width', '100%');
            $('.Value').text('100%');
            evt.close();
            $('.load1 p').text('Concluído');
        });
    }
});


// AO CLICAR NO BOTÃO CODIFICAR
$(document).on('click', '#area_apresent .btn_codifica', function (e) {
    e.preventDefault();
    const btn = $(this).prop('disabled', true);

    // reset UI
    $('.progress').css('width', '0%');
    $('.Value').text('0%');

    // monta URL absoluta
    const url = window.location.origin
        + window.BASE_URL
        + '/backend/metaTags/processGenerateCodes.php';

    const es = new EventSource(url);
    es.onopen = () => console.log('SSE conectado');
    es.onerror = ev => {
        console.error('SSE erro', ev);
        alert('Falha na conexão SSE.');
        es.close();
        btn.prop('disabled', false);
    };

    es.addEventListener('progress', e => {
        const p = parseInt(e.data, 10);
        $('.progress').css('width', p + '%');
        $('.Value').text(p + '%');
    });

    es.addEventListener('complete', () => {
        $('.progress').css('width', '100%');
        $('.Value').text('100%');
        es.close();
        btn.prop('disabled', false);
        alert('Todos os códigos foram gerados!');
    });
});

// AO CLICAR EM RENOMEAR IMAGENS

$(document).on("click", ".btn_rename_img", function (e) {
    e.preventDefault();
    const c = $("#renamecidade").val().trim();
    const t = $("#renametelefone").val().trim();
    
    console.log("%c--- INICIANDO SISTEMA DE FRAÇÕES ---", "color: yellow; font-weight: bold;");

    // Começamos do ID 1 sem perguntar nada ao servidor antes
    processarFraçãoPorID(1, c, t);
});

function processarFraçãoPorID(idAtual, cidade, telefone) {
    // LOG DE TEMPO REAL: Mostra no console antes de enviar
    console.log(`%c[Fração] Solicitando lote a partir do ID: ${idAtual}`, "color: cyan;");

    $.post("backend/metaTags/renomear.php", {
        p1: idAtual, 
        p2: cidade,
        p3: telefone
    }, function (res) {
        if (res.status === "ok") {
            // IMPRESSÃO DOS LOGS DA EXECUÇÃO NO CONSOLE
            if (res.logs && res.logs.length > 0) {
                res.logs.forEach(msg => console.log(msg));
            }

            if (res.encontrou_algo) {
                // Raciocínio: Se achou algo, vai para o próximo ID sugerido pelo PHP
                processarFraçãoPorID(res.proximo_id, cidade, telefone);
            } else {
                console.log("%c--- FIM DA VARREDURA: Não há mais registros ---", "color: green; font-weight: bold;");
            }
        }
    }, "json").fail(function (xhr) {
        // Se a fração falhar (timeout), pula um bloco e continua
        console.error(`%c[ERRO] Falha na fração do ID ${idAtual}. Resposta: ${xhr.responseText}`, "color: red;");
        setTimeout(() => processarFraçãoPorID(idAtual + 10, cidade, telefone), 2000);
    });
}
// AO CLICAR EM LIMPAR IMAGENS ORFANS


$(document).on("click", "#area_apresent .btn_limpar", function (e) {
    e.preventDefault();
    const $btn = $(this).prop('disabled', true);
    $('.Value').text('Mapeando estrutura de pastas...');

    $.getJSON("backend/metaTags/get_images_folder.php", function (res) {
        if (res.status === "ok") {
            processarPastaPorPasta(res.pastas, 0, $btn);
        }
    });
});

function processarPastaPorPasta(listaPastas, index, $btn) {
    if (index >= listaPastas.length) {
        $('.progress').css('width', '100%');
        $('.Value').text('100% - Limpeza Geral Concluída!');
        $btn.prop('disabled', false);
        alert("Processo finalizado com sucesso!");
        return;
    }

    let pastaAtual = listaPastas[index];
    $('.Value').text(`Processando pasta: product_images/${pastaAtual}`);

    $.post("backend/metaTags/verificar_apagar.php", { pasta: pastaAtual }, function (res) {
        index++;
        let p = ((index / listaPastas.length) * 100).toFixed(2);
        $('.progress').css('width', p + '%');
        
        // Pequena pausa para o servidor "respirar" entre pastas
        setTimeout(() => {
            processarPastaPorPasta(listaPastas, index, $btn);
        }, 100); 

    }, "json").fail(function() {
        index++;
        processarPastaPorPasta(listaPastas, index, $btn);
    });
}