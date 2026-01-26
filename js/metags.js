/**
 * ARQUIVO: metags.js
 * FUNÇÃO: Gerenciamento de Meta-Tags com Filtro por Categoria e SSE
 */

// Contadores para campos dinâmicos
let numC = 1;
let numT = 1;

$(document).ready(function () {
    // 1. CARREGAMENTO INICIAL
    // Carrega as categorias assim que o documento estiver pronto para popular o Modal
    carregarCategoriasTags();

    // 2. CONTROLE DO MODAL DE CATEGORIAS
    // Abrir o modal
    $(document).on('click', '#btnAbrirFiltro', function (e) {
        e.preventDefault();
        $('#modalCategorias').fadeIn(200);
    });

    // Fechar o modal (Botão X ou Botão Confirmar)
    $(document).on('click', '.close-modal, .btn-confirmar-filtro', function () {
        $('#modalCategorias').fadeOut(200);
        atualizarLabelFiltro();
    });

    // Fechar ao clicar fora da área branca do modal
    $(window).on('click', function (event) {
        if ($(event.target).is('#modalCategorias')) {
            $('#modalCategorias').fadeOut(200);
            atualizarLabelFiltro();
        }
    });

    // 3. MANIPULAÇÃO DE CAMPOS DINÂMICOS (CIDADE / TELEFONE)
    // Adicionar campo de Cidade
    $(document).on('click', '.btn-add-city', function () {
        numC++;
        const campos = document.querySelector('#cidade .campos');
        if (campos) {
            $(campos).append(`
                <div style="margin-top:5px;">
                    <input type="text" name="cidade${numC}" placeholder="Digite a cidade ${numC}">
                </div>`);
        }
    });

    // Adicionar campo de Telefone
    $(document).on('click', '.btn-add-phone', function () {
        numT++;
        const campos = document.querySelector('#numero .campos');
        if (campos) {
            $(campos).append(`
                <div style="margin-top:5px;">
                    <input type="text" name="telefone${numT}" placeholder="Digite o telefone ${numT}">
                </div>`);
        }
    });
});

/**
 * Busca o HTML das categorias do servidor
 */
function carregarCategoriasTags() {
    $('#categoriasContainerTags').html('<div class="loading">Carregando categorias...</div>');
    $.get(`backend/metaTags/getCategorias.php`, function (html) {
        $('#categoriasContainerTags').html(html);
    });
}

/**
 * Atualiza o texto abaixo do botão para informar se o filtro está ativo
 */
function atualizarLabelFiltro() {
    const selecionadas = $('#categoriasContainerTags input[name="categoria[]"]:checked').length;
    if (selecionadas > 0) {
        $('#infoFiltro').text(`${selecionadas}`).css('color', '#4CAF50');
    } else {
        $('#infoFiltro').text("0").css('color', '#666');
    }
}

/**
 * FUNÇÃO PRINCIPAL: Inicia o processamento via Server-Sent Events (SSE)
 * @param {string} urlBase - Caminho para o arquivo PHP (recebeTags ou recebeTagsCat)
 * @param {jQuery} $btn - Referência do botão clicado para desabilitar/habilitar
 */
function iniciarProcessamentoTags(urlBase, $btn) {
    $btn.prop('disabled', true);
    
    // Coleta Cidades Preenchidas
    const cidades = [];
    for (let i = 1; i <= numC; i++) {
        const v = $(`[name=cidade${i}]`).val()?.trim();
        if (v) cidades.push(v);
    }

    // Coleta Telefones Preenchidos
    const telefones = [];
    for (let i = 1; i <= numT; i++) {
        const v = $(`[name=telefone${i}]`).val()?.trim();
        if (v) telefones.push(v);
    }

    // Coleta IDs das Categorias selecionadas no Modal
    const selecionadas = $('#categoriasContainerTags input[name="categoria[]"]:checked')
        .map(function () { return this.value; }).get();

    // Monta Query String
    const params = $.param({
        num1: cidades.length,
        num2: telefones.length,
        cidades: JSON.stringify(cidades),
        telefones: JSON.stringify(telefones),
        id_categorias: JSON.stringify(selecionadas) // Array de IDs convertido para string
    });

    // Inicia a barra de progresso
    $('.progress').css('width', '0%');
    $('.Value').text('0%');

    // Abre conexão SSE
    const evt = new EventSource(`${urlBase}?${params}`);

    // Escuta progresso
    evt.addEventListener('progress', e => {
        const pct = parseInt(e.data, 10);
        $('.progress').css('width', pct + '%');
        $('.Value').text(pct + '%');
    });

    // Escuta conclusão
    evt.addEventListener('complete', e => {
        $('.progress').css('width', '100%');
        $('.Value').text('100%');
        evt.close();
        $btn.prop('disabled', false);
        alert('Operação concluída com sucesso!');
    });

    // Trata erros
    evt.addEventListener('error', e => {
        console.error('SSE Error:', e);
        evt.close();
        $btn.prop('disabled', false);
        alert('Houve um erro no processamento. Verifique o console.');
    });
}

// GATILHOS DOS BOTÕES DE AÇÃO
$(document).on('click', '.botaoprod', function (e) {
    e.preventDefault();
    iniciarProcessamentoTags('backend/metaTags/recebeTags.php', $(this));
});

$(document).on('click', '.botaocat', function (e) {
    e.preventDefault();
    iniciarProcessamentoTags('backend/metaTags/recebeTagsCat.php', $(this));
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


// // AO CLICAR CADASTRAR META-SEARCH PRODUTO
// $(document).on('click', '#area_apresent .botaoprod', function (e) {
//     e.preventDefault();
//     const $btn = $(this).prop('disabled', true);

//     // coleta arrays
//     const cidades = [];
//     for (let i = 1; i <= numC; i++) {
//         const v = $(`[name=cidade${i}]`).val().trim();
//         if (v) cidades.push(v);
//     }
//     const telefones = [];
//     for (let i = 1; i <= numT; i++) {
//         const v = $(`[name=telefone${i}]`).val().trim();
//         if (v) telefones.push(v);
//     }

//     // abre SSE passando tudo via query string
//     const params = $.param({
//         num1: cidades.length,
//         num2: telefones.length,
//         cidades: JSON.stringify(cidades),
//         telefones: JSON.stringify(telefones)
//     });
//     const evt = new EventSource('backend/metaTags/recebeTags.php?' + params);

//     evt.addEventListener('progress', e => {
//         const pct = parseInt(e.data, 10);
//         $('main#principal #right .progress').css('width', pct + '%');
//         $('main#principal #right .Value').text(pct + '%');
//     });
//     evt.addEventListener('complete', e => {
//         $('main#principal #right .progress').css('width', '100%');
//         $('main#principal #right .Value').text('100%');
//         evt.close();
//         $btn.prop('disabled', false);

//         alert('Operação concluída!');
//     });
//     evt.addEventListener('error', e => {
//         console.error('SSE error:', e);
//         evt.close();
//         $btn.prop('disabled', false);
//     });
// });

// // AO CLICAR CADASTRAR META-SEARCH CATEGORIA
// $(document).on('click', '#area_apresent .botaocat', function (e) {
//     e.preventDefault();
//     const $btn = $(this).prop('disabled', true);

//     // Coleta cidades e telefones
//     const cidades = [];
//     for (let i = 1; i <= numC; i++) {
//         const v = $(`[name=cidade${i}]`).val().trim();
//         if (v) cidades.push(v);
//     }
//     const telefones = [];
//     for (let i = 1; i <= numT; i++) {
//         const v = $(`[name=telefone${i}]`).val().trim();
//         if (v) telefones.push(v);
//     }

//     // Abre SSE para progresso
//     const params = $.param({
//         num1: cidades.length,
//         num2: telefones.length,
//         cidades: JSON.stringify(cidades),
//         telefones: JSON.stringify(telefones)
//     });
//     const evt = new EventSource('backend/metaTags/recebeTagsCat.php?' + params);

//     evt.addEventListener('progress', e => {
//         const pct = parseInt(e.data, 10);
//         $('main#principal #right .progress').css('width', pct + '%');
//         $('main#principal #right .Value').text(pct + '%');
//     });
//     evt.addEventListener('complete', () => {
//         $('main#principal #right .progress').css('width', '100%');
//         $('main#principal #right .Value').text('100%');
//         evt.close();
//         $btn.prop('disabled', false);

//         alert('Operação concluída!');
//     });
//     evt.addEventListener('error', () => {
//         evt.close();
//         $btn.prop('disabled', false);
//     });
// });


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

$(document).on("click", ".btn_rename_img", function () {
    const cidade = $("#renamecidade").val().trim();
    const telefone = $("#renametelefone").val().trim();
    const barra = $(".progress");
    const valor = $(".Value");
    const status = $("#status");

    if (!cidade || !telefone) {
        alert("Preencha os campos de cidade e telefone.");
        return;
    }

    $.getJSON("backend/metaTags/get_images.php", function (data) {
        if (data.status === "ok") {
            const imagens = data.imagens;
            if (imagens.length === 0) {
                status.text("Nenhuma imagem encontrada.");
                return;
            }
            processarImagens(imagens, cidade, telefone, barra, valor, status);
        } else {
            status.text("Erro ao buscar imagens.");
            console.error(data.mensagem);
        }
    }).fail(function (err) {
        console.error("Erro ao buscar imagens:", err);
        status.text("Erro na requisição.");
    });
});

function processarImagens(imagens, cidade, telefone, barra, valor, status) {
    let total = imagens.length;
    let processadas = 0;

    function processarProxima() {
        if (processadas >= total) {
            status.text("Processo concluído!");
            barra.css("width", "100%");
            valor.text("100%");
            return;
        }

        const imagemAtual = imagens[processadas];

        $.post("backend/metaTags/renomear.php", {
            "imagem[caminho]": imagemAtual.caminho,
            "imagem[campo]": imagemAtual.campo,
            "cidade": cidade,
            "telefone": telefone
        }, function (response) {
            if (response.status === "ok" || response.status === "pulado") {
                processadas++;
                const percentFloat = (processadas / total) * 100;
                const percent = Math.ceil(percentFloat);
                barra.css("width", percent + "%");
                valor.text(percentFloat.toFixed(2) + "%");

                const msg = response.status === "ok"
                    ? `✅ Renomeada ${processadas} de ${total}`
                    : `⚠️ Pulada ${processadas} de ${total} (${response.mensagem})`;

                status.text(msg);
                processarProxima();
            } else {
                status.text("❌ Erro ao renomear imagem.");
                console.error(response.mensagem);
            }

        }, "json").fail(function (err) {
            console.error("Erro de comunicação:", err);
            status.text("Erro ao renomear imagens.");
        });
    }

    processarProxima();
}


// AO CLICAR EM LIMPAR IMAGENS ORFANS
$(document).on("click", "#area_apresent .btn_limpar", function (e) {
    e.preventDefault();
    const $btn = $(this).prop('disabled', true);
    
    // Feedback visual imediato antes da requisição pesada
    $('.progress').css('width', '5%');
    $('.Value').text('Lendo imgs...');

    $.post("backend/metaTags/get_images_folder.php", function (response) {
        if (response.status === "ok" && response.imagens.length > 0) {
            $('.Value').text('0% - Iniciando limpeza...');
            verificarImagens(response.imagens, $btn);
        } else {
            alert("Nenhuma imagem encontrada ou erro na pasta.");
            $('.Value').text('0%');
            $btn.prop('disabled', false);
        }
    }, "json").fail(function () {
        alert("Erro ao buscar imagens da pasta.");
        $btn.prop('disabled', false);
    });
});

function verificarImagens(imagens2, $btn) {
    let total2 = imagens2.length;
    let processadas2 = 0;

    function verificarProxima() {
        if (processadas2 >= total2) {
            $('.progress').css('width', '100%');
            $('.Value').text('100%');
            $btn.prop('disabled', false);
            alert("Limpeza concluída!");
            return;
        }

        let imagemAtual = imagens2[processadas2];

        $.post("backend/metaTags/verificar_apagar.php", { imagem: imagemAtual }, function (response) {
            processadas2++;
            let p = ((processadas2 / total2) * 100).toFixed(2);
            $('.progress').css('width', p + '%');
            $('.Value').text(p + '%');
            
            // Chama a próxima da fila (Recursividade controlada)
            verificarProxima();
        }, "json").fail(function () {
            processadas2++;
            verificarProxima();
        });
    }

    verificarProxima();
}