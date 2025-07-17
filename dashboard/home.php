<div class="area area_home">
    <div class="box1">

        <div class="sun_box1">
            <span class="square"></span>
            <div class="container1">
                <p class="titulo_green">Cadastre/Atualize Meta-Tags Produtos e Categorias </p>
                <div class="section1">
                    <div id="cidade">
                        <div class="texto">Adicione Uma Cidade</div>
                        <div class="campos">
                            <div>
                                <input type="text" name="cidade1" placeholder="Digite a cidade 1">
                                <button type="button" class="btn-add-city">+</button>
                            </div>
                        </div>
                    </div>
                    <div id="numero">
                        <div class="texto">Adicione Um Número</div>
                        <div class="campos">
                            <div>
                                <input type="text" name="telefone1" placeholder="Digite o telefone 1">
                                <button type="button" class="btn-add-phone">+</button>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="botoes">
                    <button type="button" class="btn botao">META TAGS PRODUTOS</button>
                    <button type="button" class="btn botaocat">META TAGS CATEGORIAS</button>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
include(__DIR__ . '/../backend/conexao.php');
$qtde_prods = mysqli_num_rows(mysqli_query($con, "SELECT * FROM isc_categories"));
?>
