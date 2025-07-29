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

function atualizarProgresso(p) {
    p = parseFloat(p).toFixed(3);
    $('.progress').css('width', p + '%');
    $('.Value').text(p + '%');
}

function carregarCategorias(pagina = 1) {
    $.get('backend/metaTags/getCategorias.php?page=' + pagina, function (html) {
        $('#categoriasContainer').html(html);

        // Delegação de evento para botões de página
        $('#categoriasContainer').on('click', '.btn-pagina', function () {
            const paginaSelecionada = $(this).data('pagina');
            carregarCategorias(paginaSelecionada);
        });
    });
}


function inicializarImportExport() {
    // Só executa se o container existir
    if (!document.querySelector('#categoriasContainer')) return;

    carregarCategorias();

    $('.btn_importprod').on('click', function () {
        const file = $('#arquivoJSON')[0].files[0];
        if (!file) return alert("Selecione um arquivo JSON!");

        const fd = new FormData();
        fd.append('arquivo', file);

        $.ajax({
            url: 'backend/metaTags/importar.php',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
            success: function (res) {
                alert("Importação finalizada.");
                console.log(res);
            }
        });
    });

    $('.btn_exportprod').on('click', function () {
        const selecionadas = [];
        $('input[name="categoria[]"]:checked').each(function () {
            selecionadas.push($(this).val());
        });

        if (selecionadas.length === 0) return alert("Selecione categorias.");

        let total = selecionadas.length;
        let count = 0;

        function exportarUma(catId) {
            $.post('backend/metaTags/exportar.php', { id_categoria: catId }, function (res) {
                count++;
                atualizarProgresso((count / total) * 100);
                if (count < total) {
                    exportarUma(selecionadas[count]);
                } else {
                    alert("Exportação finalizada!");
                }
            }, 'json');
        }

        exportarUma(selecionadas[0]);
    });
}

// Reexecuta sempre que uma nova página é carregada dinamicamente
document.addEventListener("DOMContentLoaded", () => {
    const target = document.getElementById("area_apresent");
    const observer = new MutationObserver(() => {
        inicializarImportExport();
    });
    if (target) observer.observe(target, { childList: true, subtree: true });
});
