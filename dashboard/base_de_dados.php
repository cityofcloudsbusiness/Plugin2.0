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
    </div>

</div>

<?php
include(__DIR__ . '/../backend/conexao.php');
$qtde_prods = mysqli_num_rows(mysqli_query($con, "SELECT * FROM isc_categories"));
?>