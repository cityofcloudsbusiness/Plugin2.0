<div class="area area_basededados">
    <div class="box1">

        <div class="sun_box1 sunform">
            <span class="square"></span>
            <div class="container1">
                <p class="titulo_green">Sistema de Importação e Exportação de Produtos por Categorias</p>
                <div class="ajust_import">
                    <div class="sq_right">
                        <div class="texto_title">Importação de Produtos</div>
                        <label for="arquivoJSON">
                            <img id="imgBD" src="img/exelupload.png" alt="">
                            <input type="file" id="arquivoJSON">
                        </label>
                    </div>



                    <div>
                        <div class="texto_title">Exportação de Produtos</div>
                        <div id="categoriasWrapper">
                            <div id="categoriasContainer"></div>
                        </div>

                    </div>

                </div>
                <div class="botoes">
                    <button type="button" class="btn btn_importprod">IMPORTAR</button>
                    <button type="button" class="btn btn_exportprod">EXPORTAR</button>
                </div>
            </div>
        </div>

        <div class="sun_box3 sunform">
            <span class="square"></span>
            <div class="container1">
                <p class="titulo_green">Porcentagem do Valor</p>
                <div class="container1">
                    <div class="section1" id="area_preco">

                        <div>
                            <label for="mode" class="texto_title">Aplicar em:</label>
                            <select id="mode">
                                <option value="all">Todos os produtos</option>
                                <option value="category">Por categoria</option>
                            </select>
                        </div>

                        <div id="wrap_category" style="opacity:0">
                            <label for="category" class="texto_title">Categoria:</label>
                            <select id="category"></select>
                        </div>

                        <div id="percent_esp">
                            <label for="percent" class="texto_title">Percentual (%):</label>
                            
                        </div>

                        <div class="btns">
                            <button type="button" class="btn_atualizar_precos btn_exportprod button_lg_p">Aplicar %</button>
                            <input type="number" id="percent" step="0.01" value="10">
                            <button type="button" class="btn_reset_precos button_lg_p">Zerar %</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<?php
include(__DIR__ . '/../backend/conexao.php');
$qtde_prods = mysqli_num_rows(mysqli_query($con, "SELECT * FROM isc_categories"));
?>