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

function carregarCategorias() {
    $('#categoriasContainer').html('<div class="loading">Carregando categorias...</div>');
    $.get(`${window.BASE_URL}/backend/metaTags/getCategorias.php`, function (html) {
        $('#categoriasContainer').html(html);
    });
}

$(document).ready(function () {
    if (document.querySelector('.btn_exportprod')) {
        carregarCategorias();
    }

    // IMPORTAR
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

    // EXPORTAR com barra de progresso e download automático
    $(document).on('click', '#area_apresent .btn_exportprod', function () {
        const sel = $('input[name="categoria[]"]:checked')
            .map(function () { return this.value; }).get();

        if (!sel.length) return alert("Selecione categorias.");

        $('.progress').css('width', '0%');
        $('.Value').text('0.0%');

        $.ajax({
            url: `${window.BASE_URL}/backend/metaTags/exportar.php`,
            type: 'POST',
            dataType: 'json',
            data: { id_categorias: sel },
            xhr: function () {
                let xhr = new XMLHttpRequest();
                xhr.addEventListener('progress', function (e) {
                    if (e.lengthComputable) {
                        let percent = (e.loaded / e.total) * 100;
                        $('.progress').css('width', percent + '%');
                        $('.Value').text(percent.toFixed(1) + '%');
                    }
                });
                return xhr;
            },
            success: function () {
                $('.progress').css('width', '100%');
                $('.Value').text('100%');

                // Baixa automaticamente o JSON exportado
                window.location = `${window.BASE_URL}/backend/metaTags/download.php`;
                alert("Exportação finalizada!");
            },
            error: function (xhr, status, err) {
                console.error("Erro na exportação:", err);
                alert("Erro ao exportar categorias.");
            }
        });
    });
});
