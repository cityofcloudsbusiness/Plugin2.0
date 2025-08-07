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


    // IMPORTAÇÃO com barra de progresso

    // import_export.js

    $(document).ready(function () {
        // Carrega lista de categorias no lado do EXPORT
        if ($('.btn_exportprod').length) {
            carregarCategorias();
        }

        // EXPORTAR
        $(document).on('click', '.btn_exportprod', function () {
            const selecionadas = $('input[name="categoria[]"]:checked')
                .map(function () { return this.value; }).get();
            if (!selecionadas.length) {
                return alert("Selecione ao menos uma categoria.");
            }

            // zera barra
            $('.progress').css('width', '0%');
            $('.Value').text('0.0%');

            $.ajax({
                url: window.BASE_URL + '/backend/metaTags/exportar.php',
                type: 'POST',
                dataType: 'json',
                data: { id_categorias: selecionadas },
                xhr: function () {
                    const xhr = new XMLHttpRequest();
                    xhr.addEventListener('progress', function (e) {
                        if (e.lengthComputable) {
                            const pct = (e.loaded / e.total) * 100;
                            $('.progress').css('width', pct + '%');
                            $('.Value').text(pct.toFixed(1) + '%');
                        }
                    });
                    return xhr;
                },
                success: function () {
                    $('.progress').css('width', '100%');
                    $('.Value').text('100%');
                    window.location = window.BASE_URL + '/backend/metaTags/download.php';
                    alert("Exportação concluída!");
                },
                error: function (_, _, err) {
                    console.error("Erro na exportação:", err);
                    alert("Falha ao exportar.");
                }
            });
        });


        // IMPORTAR
        $(document).on('click', '.btn_importprod', function () {
            const file = $('#arquivoJSON')[0].files[0];
            if (!file) {
                return alert("Selecione um arquivo JSON!");
            }

            console.log("Enviando prepararImportacao.php...");
            const fd = new FormData();
            fd.append('arquivo', file);

            $.ajax({
                url: window.BASE_URL + '/backend/metaTags/prepararImportacao.php',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function (res) {
                    if (res.erro) {
                        return alert("Falha ao preparar importação:\n" + res.erro);
                    }
                    console.log("Resposta prepararImportacao:", res);

                    const etapas = res.etapas;
                    const total = etapas.length;
                    let atual = 0;

                    function importarEtapa() {
                        if (atual >= total) {
                            $('.Name').text("Importação finalizada!");
                            return;
                        }
                        const arquivo = etapas[atual];
                        console.log(`Importando etapa ${atual + 1}/${total}: ${arquivo}`);

                        $.post(window.BASE_URL + '/backend/metaTags/importar.php', { etapa: arquivo }, function (r) {
                            if (r.erro) {
                                console.error("importar.php retornou erro:", r.erro);
                                return alert("Erro na importação:\n" + r.erro);
                            }
                            console.log("Resposta importar.php:", r);

                            // atualiza barra
                            const pct = ((atual + 1) / total) * 100;
                            $('.progress').css('width', pct + '%');
                            $('.Value').text(pct.toFixed(2) + '%');
                            $('.Name').text(`Importando... ${(atual + 1)} / ${total}`);

                            atual++;
                            importarEtapa();
                        }, 'json')
                            .fail(function (xhr) {
                                console.error("Falha no AJAX importar.php:", xhr.responseText);
                                alert("Erro no servidor ao importar.");
                            });
                    }

                    importarEtapa();
                },
                error: function (xhr) {
                    console.error("Falha em prepararImportacao.php:", xhr.responseText);
                    alert("Falha ao preparar importação.");
                }
            });
        });
    });

    // IMPORTAÇÃO com barra de progresso

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
