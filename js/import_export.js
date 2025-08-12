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



// PORCENTAGEM DE PREÇO DE PRODUTO
// js/import_export.js
// Módulo de Percentual de Preço (SSE) — robusto para páginas carregadas via AJAX (loadPage)
(function () {
  'use strict';

  // API relativa ao index.php (raiz)
  const API = 'backend/metaTags/precos.php';

  // guarda se já ligamos os listeners globais
  let globalBound = false;

  // função pública para o index chamar após injetar a página
  window.initPrecoModule = function () {
    const scope = document.getElementById('area_preco');
    if (!scope) return; // ainda não está na tela
    wireScope(scope);
    bindGlobalOnce();
  };

  // liga uma única vez os listeners globais (delegação + captura)
  function bindGlobalOnce() {
    if (globalBound) return;
    globalBound = true;

    // Clique: Aplicar % e Zerar %
    // Usamos CAPTURA para impedir handlers herdados (ex.: exportação em .btn_exportprod)
    document.addEventListener(
      'click',
      function (e) {
        const applyBtn = e.target.closest('.btn_atualizar_precos');
        const resetBtn = e.target.closest('.btn_reset_precos');
        if (!applyBtn && !resetBtn) return;

        // BLOQUEIA handlers de outros módulos (ex.: exportação)
        e.preventDefault();
        e.stopImmediatePropagation();
        e.stopPropagation();

        const scope = document.getElementById('area_preco');
        if (!scope) return;

        const modeEl    = scope.querySelector('#mode');
        const catSel    = scope.querySelector('#category');
        const percentEl = scope.querySelector('#percent');

        const mode = modeEl ? modeEl.value : 'all';
        const id   = (catSel && catSel.value) ? catSel.value : 0;

        if (applyBtn) {
          const percent = (percentEl && percentEl.value) ? percentEl.value : 0;
          setTitle('Processando…'); setProgress(0);
          runSSE('update_prices', { mode, id, percent });
        }
        if (resetBtn) {
          setTitle('Revertendo…'); setProgress(0);
          runSSE('reset_prices', { mode, id });
        }
      },
      true // CAPTURA
    );
  }

  // observa trocas no container onde você injeta páginas
  const mountPoint = document.getElementById('area_apresent');
  if (mountPoint) {
    const mo = new MutationObserver(() => {
      const scope = document.getElementById('area_preco');
      if (scope && !scope.dataset.precoWired) {
        wireScope(scope);
        bindGlobalOnce();
      }
    });
    mo.observe(mountPoint, { childList: true, subtree: true });
  }

  // se a página já veio com #area_preco (ex.: primeiro load)
  if (document.getElementById('area_preco')) {
    window.initPrecoModule();
  }

  // ---- helpers de UI ----
  function setProgress(p) {
    const barEl = document.querySelector('nav .barra_progresso .progress');
    const valEl = document.querySelector('nav .barra_progresso .Value');
    if (!barEl || !valEl) return;
    const n = (parseFloat(p) || 0).toFixed(2);
    barEl.style.width = n + '%';
    valEl.textContent = n + '%';
  }
  function setTitle(t) {
    const titulo = document.getElementById('titulo_nav');
    if (titulo) titulo.textContent = t || '';
  }
  function log() { try { console.log('[precos]', ...arguments); } catch (e) {} }

  // liga por-escopo: carrega categorias e toggle do select
  function wireScope(scope) {
    if (!scope || scope.dataset.precoWired) return;
    scope.dataset.precoWired = '1';

    const modeEl   = scope.querySelector('#mode');
    const catWrap  = scope.querySelector('#wrap_category');
    const catSel   = scope.querySelector('#category');

    // Carregar categorias
    fetch(API + '?action=list_categories', { cache: 'no-store' })
      .then(r => r.ok ? r.json() : Promise.reject(new Error('HTTP ' + r.status)))
      .then(cats => {
        if (!catSel) return;
        catSel.innerHTML = '';
        cats.forEach(c => {
          const o = document.createElement('option');
          o.value = c.categoryid;
       	  o.textContent = c.catname;
          catSel.appendChild(o);
        });
        log('Categorias carregadas:', cats.length);
      })
      .catch(err => console.error('Erro ao carregar categorias', err));

    // Toggle do bloco de categoria — só invisível (sem display:none)
    function refreshCatVisibility() {
      if (!modeEl || !catWrap) return;
      const show = modeEl.value === 'category';
      catWrap.style.opacity = show ? '1' : '0';
      catWrap.style.pointerEvents = show ? 'auto' : 'none';
    }
    if (modeEl) {
      modeEl.addEventListener('change', refreshCatVisibility);
      // estado inicial
      refreshCatVisibility();
    }
  }

  // abre SSE
  function runSSE(action, params) {
    // encerra SSE anterior
    if (window.__es instanceof EventSource) {
      try { window.__es.close(); } catch (_) {}
    }
    const url = API + '?' + new URLSearchParams({ action, ...params }).toString();
    log('SSE URL:', url);
    const es = new EventSource(url);
    window.__es = es;

    es.onmessage = ev => {
      const data = String(ev.data || '');
      if (data.startsWith('progress:')) {
        setProgress(data.split(':')[1] || '0');
      } else if (data === 'done') {
        setProgress(100);
        setTitle('Concluído!');
        log('Finalizado.');
        es.close();
      } else if (data.startsWith('error:')) {
        setTitle('Erro: ' + data.slice(6));
        console.error('SSE error:', data);
        es.close();
      } else if (data.startsWith('init:')) {
        log('Total de itens:', data.split(':')[1]);
      }
    };
    es.onerror = err => {
      console.error('Falha na conexão SSE', err);
      setTitle('Erro na conexão.');
      try { es.close(); } catch (_) {}
    };
  }
})();

// PORCENTAGEM DE PREÇO DE PRODUTO


});
