/**
 * ARQUIVO: metags.js
 * Gerenciamento de Modal, Campos Dinâmicos e Processamento SSE
 */

let numC = 1;
let numT = 1;

$(document).ready(function () {
    // 1. CARREGAMENTO INICIAL
    carregarCategoriasTags();

    // 2. CONTROLE DO MODAL
    $(document).on('click', '#btnAbrirFiltro', function (e) {
        e.preventDefault();
        $('#modalCategorias').fadeIn(200);
    });

    $(document).on('click', '.close-modal, .btn-confirmar-filtro', function () {
        $('#modalCategorias').fadeOut(200);
        atualizarLabelFiltro();
    });

    $(window).on('click', function (event) {
        if ($(event.target).is('#modalCategorias')) {
            $('#modalCategorias').fadeOut(200);
            atualizarLabelFiltro();
        }
    });

    // 3. SELEÇÃO INTELIGENTE (Checkboxes)
    
    // Marcar/Desmarcar Todas
    $(document).on('change', '#selectAllCats', function() {
        const marcado = $(this).is(':checked');
        $('#categoriasContainerTags input[name="categoria[]"]').prop('checked', marcado);
        atualizarLabelFiltro();
    });

    // Atualiza contador ao clicar em qualquer checkbox individual
    $(document).on('change', '#categoriasContainerTags input[name="categoria[]"]', function() {
        atualizarLabelFiltro();
    });

    // 4. CAMPOS DINÂMICOS (Cidades e Telefones)
    $(document).on('click', '.btn-add-city', function () {
        numC++;
        $('#cidade .campos').append(`
            <div style="margin-top:5px;">
                <input type="text" name="cidade${numC}" placeholder="Digite a cidade ${numC}">
            </div>`);
    });

    $(document).on('click', '.btn-add-phone', function () {
        numT++;
        $('#numero .campos').append(`
            <div style="margin-top:5px;">
                <input type="text" name="telefone${numT}" placeholder="Digite o telefone ${numT}">
            </div>`);
    });
});

/**
 * Busca as categorias respeitando a hierarquia enviada pelo PHP
 */
function carregarCategoriasTags() {
    $('#categoriasContainerTags').html('<div class="loading">Sincronizando categorias...</div>');
    // Adicionado timestamp para evitar cache do navegador
    $.get(`backend/metaTags/getCategorias.php?t=${Date.now()}`, function (html) {
        $('#categoriasContainerTags').html(html);
        atualizarLabelFiltro();
    });
}

/**
 * Atualiza o número de categorias selecionadas na Home
 */
function atualizarLabelFiltro() {
    const selecionadas = $('#categoriasContainerTags input[name="categoria[]"]:checked').length;
    $('#infoFiltro').text(selecionadas);
    
    // Ajuste de cor conforme seleção
    if (selecionadas > 0) {
        $('#infoFiltro').css({'color': '#4CAF50', 'font-weight': 'bold'});
    } else {
        $('#infoFiltro').css({'color': '#666', 'font-weight': 'normal'});
    }
}

/**
 * Dispara o processamento via Server-Sent Events (SSE)
 */
function iniciarProcessamentoTags(urlBase, $btn) {
    $btn.prop('disabled', true);
    
    // Coleta Cidades (pega todos os inputs de texto dentro da div #cidade)
    const cidades = [];
    $('#cidade input[type="text"]').each(function() {
        const v = $(this).val().trim();
        if (v) cidades.push(v);
    });

    // Coleta Telefones
    const telefones = [];
    $('#numero input[type="text"]').each(function() {
        const v = $(this).val().trim();
        if (v) telefones.push(v);
    });

    // Coleta IDs das Categorias
    const selecionadas = $('#categoriasContainerTags input[name="categoria[]"]:checked')
        .map(function () { return this.value; }).get();

    const params = $.param({
        cidades: JSON.stringify(cidades),
        telefones: JSON.stringify(telefones),
        id_categorias: JSON.stringify(selecionadas)
    });

    $('.progress').css('width', '0%');
    $('.Value').text('0%');

    const evt = new EventSource(`${urlBase}?${params}`);

    evt.addEventListener('progress', e => {
        const pct = parseInt(e.data, 10);
        $('.progress').css('width', pct + '%');
        $('.Value').text(pct + '%');
    });

    evt.addEventListener('complete', () => {
        $('.progress').css('width', '100%');
        $('.Value').text('100%');
        evt.close();
        $btn.prop('disabled', false);
        alert('Processamento concluído!');
    });

    evt.addEventListener('error', e => {
        console.error('Erro SSE:', e);
        evt.close();
        $btn.prop('disabled', false);
        alert('Houve um erro na conexão. Tente novamente.');
    });
}

// GATILHOS DOS BOTÕES
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
    
    // 1. Coleta a descrição
    const d = $('#desc').val().trim();
    if (!d) {
        alert('Digite uma descrição.');
        return;
    }

    // 2. Coleta as categorias selecionadas no modal
    const selecionadas = $('#categoriasContainerTags input[name="categoria[]"]:checked')
        .map(function () { return this.value; }).get();

    if (selecionadas.length === 0) {
        alert('Selecione ao menos uma categoria no filtro antes de cadastrar a descrição.');
        return;
    }

    // 3. Prepara os parâmetros (descrição + array de IDs)
    const params = $.param({
        desc: d,
        id_categorias: JSON.stringify(selecionadas)
    });

    console.log("🚀 Iniciando atualização de Meta Description para categorias selecionadas...");

    const evt = new EventSource('backend/metaTags/recebeTagsRootCat.php?' + params);

    evt.addEventListener('progress', e => {
        const pct = parseInt(e.data, 10);
        $('main#principal #right .progress').css('width', pct + '%');
        $('main#principal #right .Value').text(pct + '%');
    });

    evt.addEventListener('complete', () => {
        $('main#principal #right .progress').css('width', '100%');
        $('main#principal #right .Value').text('100%');
        evt.close();
        alert('Meta-descrições atualizadas com sucesso!');
    });

    evt.addEventListener('error', e => {
        console.error('Erro SSE:', e);
        evt.close();
        alert('Erro ao atualizar. Verifique a conexão.');
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
    const $btn = $(this).prop('disabled', true);

    if (!cidade || !telefone) {
        alert("Preencha cidade e telefone.");
        $btn.prop('disabled', false); return;
    }

    console.log("%c🚀 Iniciando Renomeação por Frações de ID...", "color: yellow; font-weight: bold;");

    $.getJSON("backend/metaTags/get_images.php", function (data) {
        if (data.status === "ok") {
            processarLotes(data.min_id, data.max_id, cidade, telefone, $btn);
        }
    });
});

function processarLotes(atualId, maxId, cidade, telefone, $btn) {
    if (atualId > maxId) {
        console.log("%c🏁 Concluído!", "color: green; font-weight: bold;");
        $(".Value").text("100%");
        $btn.prop('disabled', false);
        return;
    }

    $.post("backend/metaTags/renomear.php", {
        p1: atualId,
        p2: cidade,
        p3: telefone
    }, function (res) {
        if (res.logs) res.logs.forEach(l => console.log(l));
        
        // Atualiza a barra
        let pct = (((atualId - 0) / (maxId - 0)) * 100).toFixed(2);
        $(".progress").css("width", pct + "%");
        $(".Value").text(pct + "%");

        processarLotes(res.proximo_id, maxId, cidade, telefone, $btn);
    }, "json").fail(function(xhr) {
        console.error("❌ Erro no ID " + atualId + ". Tentando pular...");
        setTimeout(() => processarLotes(atualId + 50, maxId, cidade, telefone, $btn), 1000);
    });
}
//apagar imagens orfans
$(document).on("click", "#area_apresent .btn_limpar", function (e) {
    e.preventDefault();
    const $btn = $(this).prop('disabled', true);
    console.log("🚀 Iniciando Mapeamento de Pastas...");
    
    $('.progress').css('width', '5%');
    $('.Value').text('Mapeando pastas...');

    $.getJSON("backend/metaTags/get_images_folder.php", function (res) {
        if (res.status === "ok") {
            console.log("📂 Pastas encontradas:", res.pastas);
            processarFilaDePastas(res.pastas, 0, $btn, 0);
        } else {
            console.error("❌ Erro ao listar pastas:", res.mensagem);
            $btn.prop('disabled', false);
        }
    }).fail(function(d) {
        console.error("❌ Falha crítica ao acessar get_images_folder.php", d.responseText);
    });
});

function processarFilaDePastas(fila, index, $btn, totalApagados) {
    if (index >= fila.length) {
        console.log("✅ PROCESSO FINALIZADO!");
        console.log("📊 Total Geral de Imagens Apagadas:", totalApagados);
        $('.progress').css('width', '100%');
        $('.Value').text('100% - Concluído!');
        alert("Limpeza concluída! Total apagado: " + totalApagados);
        $btn.prop('disabled', false);
        return;
    }

    let pastaAtual = fila[index];
    console.log(`⏳ Processando pasta [${index + 1}/${fila.length}]: product_images/${pastaAtual}`);
    $('.Value').text(`Pasta: ${pastaAtual} (${index + 1}/${fila.length})`);

    $.post("backend/metaTags/verificar_apagar.php", { pasta: pastaAtual }, function (res) {
        if (res.status === "ok") {
            console.log(`   - ✅ Sucesso em: ${res.pasta}`);
            console.log(`   - 🗑️ Apagados: ${res.apagados} | 📦 Mantidos: ${res.mantidos} | ⚠️ Erros: ${res.erros}`);
            totalApagados += res.apagados;
        } else {
            console.warn(`   - ⚠️ Pasta ${pastaAtual} retornou status: ${res.status}`);
        }

        index++;
        let p = ((index / fila.length) * 100).toFixed(2);
        $('.progress').css('width', p + '%');
        
        // Chamada da próxima pasta
        processarFilaDePastas(fila, index, $btn, totalApagados);

    }, "json").fail(function (xhr) {
        console.error(`   - ❌ Erro ao processar pasta ${pastaAtual}. Status: ${xhr.status}`);
        index++;
        processarFilaDePastas(fila, index, $btn, totalApagados);
    });
}