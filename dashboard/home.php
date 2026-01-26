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

                <div class="mods">
                    <button type="button" id="btnAbrirFiltro" class="btn">
                        🔍 
                    </button>
                    <div id="infoFiltro">0</div>
                </div>

                <div class="botoes">
                    <button type="button" class="btn botaoprod">META-TAGS PRODUTOS</button>
                    <button type="button" class="btn botaocat">META-TAGS CATEGORIAS</button>
                </div>
            </div>
        </div>

        <div id="modalCategorias" class="modal-tags">
            <div class="modal-content-tags">
                <div class="modal-header-tags">
                    <span class="texto_title">Selecione as Categorias</span>
                    <span class="close-modal">&times;</span>
                </div>
                <div id="categoriasContainerTags" class="modal-body-tags">
                    Carregando categorias...
                </div>
                <div class="modal-footer-tags">
                    <button type="button" class="btn btn-confirmar-filtro">CONFIRMAR E FECHAR</button>
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
                <p class="titulo_green">Conversão Categoria</p>
                <div class="container1">
                    <div class="section1">
                        <div class="texto_title text-center">Conversão em Produto Destaque</div>
                        <span>Esse processo visa tranformar categorias em produtos destaques</span>
                        <button class="btn_convert_cdprod">CONVERTER</button>
                    </div>
                </div>
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

                    <form id="formUpload" enctype="multipart/form-data" onsubmit="return false;">
                        <p class="titulo_green">Selecione a planilha</p>
                        <div class="container1">
                            <div class="texto_title">Cadastro de Banco de Dado Excel</div>
                            <div class="sq_right">
                                <label for="arquivoExcel">
                                    <img id="imgExcel" src="img/exelupload.png" alt="">
                                    <input type="file" id="arquivoExcel">
                                </label>
                            </div>
                            <div class="res"></div>
                            <div class="ctn2">
                                <button type="button" class="btn btn_excel_up">CADASTRAR PLANILHA</button>
                            </div>
                        </div>

                    </form>


                </div>
            </div>
        </div>

        <div class="sun_box3 sunform">
            <span class="square"></span>
            <div class="container1">
                <p class="titulo_green">Codificando Produto</p>
                <div class="container1">
                    <div class="section1">
                        <div class="texto_title text-center">Adicionando codigo de <br> Identificação ao Produto</div>
                        <span>Esse processo adiciona um código unico ao Produto para identifica-lo</span>
                        <button class="btn_codifica btncanto">CODIFICAR</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="box1 box3">

        <div class="sun_box1 sunform">
            <span class="square"></span>
            <div class="container1">
                <div class="titulo_green">Renomeando Imagem</div>
                <div class="ajust1">
                    <div>
                        <input type="text" class="forminput" placeholder="Cidade" id="renamecidade">
                    </div>
                    <div>
                        <input type="text" class="forminput" placeholder="Telefone" id="renametelefone">
                    </div>

                </div>
                <button type="button" class="btn btn_rename_img btnfin">RENOMEAR IMAGENS</button>
            </div>
        </div>

        <div class="sun_box2 sunform">
            <span class="square"></span>

            <div class="container1">
                <div class="titulo_green">Limpeza de Imagens Orfans</div>
                <div class="section1">
                    <span>Esse sistema apaga as imagens Orfans</span>

                </div>
                <button type="button" class="btn btn_limpar btnfin">LIMPAR IMG'S ORFANS</button>
            </div>
        </div>

        <div class="sun_box3 sunform">
            <span class="square"></span>

            <div class="container1">
                <p class="titulo_green">Tags de Produto</p>
                <div class="container1">
                    <div class="section1">
                        <button class="btn_apaga_metatags">LIMPAR</button>
                    </div>
                </div>
            </div>

        </div>

    </div>



    <?php
    include(__DIR__ . '/../backend/conexao.php');
    $qtde_prods = mysqli_num_rows(mysqli_query($con, "SELECT * FROM isc_categories"));
    ?>