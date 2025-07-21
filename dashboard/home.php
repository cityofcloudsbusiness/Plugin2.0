<div class="area area_home">
    <div class="box1">

        <div class="sun_box1 sunform">
            <span class="square"></span>
            <div class="container1">
                <p class="titulo_green">Cadastre/Atualize Meta-Tags Produtos e Categorias </p>
                <div class="section1">
                    <div id="cidade">
                        <div class="texto_title">Adicione Uma Cidade</div>
                        <div class="campos">
                            <div>
                                <input type="text" name="cidade1" placeholder="Digite a cidade 1">
                                <button type="button" class="btn-add-city">+</button>
                            </div>
                        </div>
                    </div>
                    <div id="numero">
                        <div class="texto_title">Adicione Um Número</div>
                        <div class="campos">
                            <div>
                                <input type="text" name="telefone1" placeholder="Digite o telefone 1">
                                <button type="button" class="btn-add-phone">+</button>
                            </div>

                        </div>
                    </div>
                </div>
                <div class="botoes">
                    <button type="button" class="btn botaoprod">META-TAGS PRODUTOS</button>
                    <button type="button" class="btn botaocat">META-TAGS CATEGORIAS</button>
                </div>
            </div>
        </div>

        <div class="sun_box2 sunform">
            <div class="area_sun">
                <span class="square"></span>
                <div class="container1">
                    <p class="titulo_green">Cadastre/Atualize Mesta-Desc Categorias</p>
                    <div class="botoes">
                        <p class="titulo_green texto_title">Descrição a aplicar nas categorias sem parent:</p>
                        <textarea id="desc" rows="4" placeholder="Digite a descrição..." class="inptstyle" required></textarea>
                        <button type="button" class="btn botaodesc">CADASTRAR-META DESCRIÇÕES</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="sun_box3 sunform">
            <span class="square"></span>
            <div class="container1">
                <p class="titulo_green">teste</p>
            </div>
        </div>

    </div>

    <div class="box1 box2">
       <div class="sun_box1 sunform">
            <span class="square"></span>
            <div class="container1">
                <p class="titulo_green">Atualizando titulo de Categorias e Produtos</p>
                <div class="section1">
                    <div id="cidade">
                        <div class="texto_title">Caracter a ser substituido</div>
                        <div class="campos">
                            <div>
                                <input id="oldTitle" type="text" name="oldTitle" placeholder="Digite titulo a substituir">
                            </div>
                        </div>
                    </div>
                    <div id="numero">
                        <div class="texto_title">Caracter a substituir</div>
                        <div class="campos">
                            <div>
                                <input id="newTitle" type="text" name="newTitle" placeholder="Digite titulo substituto">
                            </div>

                        </div>
                    </div>
                </div>
                <div class="botoes">
                    <button type="button" class="btn botaosec2prod">ATUALIZAR PRODUTOS</button>
                    <button type="button" class="btn botaosec2cat">ATUALIZAR CATEGORIAS</button>
                </div>
            </div>
        </div>

        <div class="sun_box2 sunform">
            <div class="area_sun">
                <span class="square"></span>
                <div class="container1">
                 
                </div>
            </div>
        </div>

        <div class="sun_box3 sunform">
            <span class="square"></span>
            <div class="container1">
                <p class="titulo_green">teste</p>
            </div>
        </div>
        
</div>

<?php
include(__DIR__ . '/../backend/conexao.php');
$qtde_prods = mysqli_num_rows(mysqli_query($con, "SELECT * FROM isc_categories"));
?>