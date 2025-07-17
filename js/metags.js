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


// Ao clicar em CADASTRAR
$(document).on('click','#area_apresent .botaocat', function (e) {
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
    });
    evt.addEventListener('error', () => {
        evt.close();
        $btn.prop('disabled', false);
    });
});