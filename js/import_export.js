// 1) Função global para buscar e renderizar as categorias paginadas
function carregarCategorias(page = 0) {
  // mostra loading
  $('#categoriasContainer').html('<div class="loading">Carregando categorias...</div>');

  // 2) use caminho absoluto
  const url = `${window.BASE_URL}/backend/metaTags/getCategorias.php?page=${page}`;
  $.get(url, function(html) {
    $('#categoriasContainer').html(html);
  });
}

$(document).ready(function () {
  // 3) Delegação de evento para paginação
  $(document).on('click', '.page-btn', function () {
    const page = $(this).data('page');  // pega data-page
    carregarCategorias(page);           // recarrega lista
  });

  // Botão EXPORTAR
  $('.btn_exportprod').on('click', function () {
    const selecionadas = $('input[name="categoria[]"]:checked')
                         .map(function() { return this.value; })
                         .get();
    if (!selecionadas.length) {
      return alert("Selecione ao menos uma categoria.");
    }

    let total = selecionadas.length,
        count = 0;

    function exportarUma() {
      const catId = selecionadas[count++];
      $.post(
        `${window.BASE_URL}/backend/metaTags/exportar.php`,
        { id_categoria: catId },
        function() {
          // atualiza barra
          const pct = (count / total) * 100;
          $('.progress').css('width', pct + '%');
          $('.Value').text(pct.toFixed(1) + '%');

          if (count < total) {
            exportarUma();
          } else {
            alert("Exportação finalizada!");
          }
        },
        'json'
      );
    }

    exportarUma();
  });

  // Botão IMPORTAR
  $('.btn_importprod').on('click', function () {
    const file = $('#arquivoJSON')[0].files[0];
    if (!file) return alert("Selecione um arquivo JSON!");

    const fd = new FormData();
    fd.append('arquivo', file);

    $.ajax({
      url: `${window.BASE_URL}/backend/metaTags/importar.php`,
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
});
