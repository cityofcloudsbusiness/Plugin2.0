document.addEventListener('change', function (e) {
    // “e.target” é o elemento que disparou o change
    if (e.target && e.target.id === 'arquivoExcel') {
        const imgIcon = document.getElementById('imgExcel');
        if (e.target.files && e.target.files.length > 0) {
            imgIcon.src = 'img/pla.png';
        } else {
            imgIcon.src = 'img/exelupload.png';
        }
    }
});
// 1) Função global para puxar TODAS as categorias de uma vez
function carregarCategorias() {
    $('#categoriasContainer').html('<div class="loading">Carregando categorias...</div>');
    // caminho absoluto relativo ao root:
    $.get(`${window.BASE_URL}/backend/metaTags/getCategorias.php`, function (html) {
        $('#categoriasContainer').html(html);
    });
}

$(document).ready(function () {
    // 2) dispara assim que a tela for injetada pelo loadPage():
    if (document.querySelector('.btn_exportprod')) {
        carregarCategorias();
    }

    // 3) IMPORTAR (sem mudança)
    $('.btn_importprod').on('click', function () {
        const file = $('#arquivoJSON')[0].files[0];
        if (!file) return alert("Selecione um arquivo JSON!");
        const fd = new FormData(); fd.append('arquivo', file);
        $.ajax({
            url: `${window.BASE_URL}/backend/metaTags/importar.php`,
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: res => {
                alert("Importação finalizada.");
                console.log(res);
            }
        });
    });

    // 4) EXPORTAR (corrigido para usar $.ajax corretamente)
    $(document).on('click', '#area_apresent .btn_exportprod', function () {
        const sel = $('input[name="categoria[]"]:checked')
            .map(function () { return this.value; }).get();
        if (!sel.length) return alert("Selecione categorias.");

        let total = sel.length, i = 0;

        function next() {
            $.ajax({
                url: `${window.BASE_URL}/backend/metaTags/exportar.php`,
                type: 'POST',
                dataType: 'json',
                data: {
                    id_categoria: sel[i++],
                    verif: 2
                },
                success: function () {
                    const pct = (i / total) * 100;
                    $('.progress').css('width', pct + '%');
                    $('.Value').text(pct.toFixed(1) + '%');

                    if (i < total) {
                        next();
                    } else {
                        // Quando tudo terminar, dispara o download
                        window.location = `${window.BASE_URL}/backend/metaTags/download.php`;
                        alert("Exportação finalizada!");
                    }
                },
                error: function (xhr, status, err) {
                    console.error("Erro na exportação:", err);
                    alert("Erro ao exportar categoria.");
                }
            });
        }

        next();
    });
});
