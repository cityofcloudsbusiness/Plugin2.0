<?php
require 'auth.php';
$menu = require 'navbar/menu.php';

$baseUrl = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
?>
<!doctype html>
<html>

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css" />

    <script>
        window.BASE_URL = '<?= $baseUrl ?>';
    </script>

</head>

<body>
    <script src="https://code.jquery.com/jquery-3.7.1.js" integrity="sha256-eKhayi8LEQwp4NKxN+CfCh+3qOVUtJn3QNZ0TciWLP4=" crossorigin="anonymous"></script>
    <main id="principal">
        <section id="left">
            <div class="menu_left flex justify-center items-center flex-col">

                <div class="superior">
                    <div class="foto_login flex justify-center">
                        <i class="bi bi-person-circle"></i>
                    </div>
                    <div class="nome text-orange-300 text-center text-lg">
                        <?php echo $_SESSION['email']; ?>
                    </div>
                    <div class="mesage text-xs text-white">Gerente Administrativo</div>
                </div>

                <div class="inferior">
                    <ul>
                        <?php $i = 0;
                        foreach ($menu as $items): ?>
                            <li class="flex flex-row font-sans text-white justify-start items-center <?= $i === 0 ? 'active' : ''; ?> menu-item"><i style="margin-right: 15px;"><?= $items['icon']; ?></i><a href="<?= $items['route'] ?>" onclick="loadPage('<?= $items['route'] ?>', this,this.dataset.titulo); return false;" data-titulo="<?= $items['titulo'] ?>"><?= $items['label']; ?></a></li>
                        <?php $i++;
                        endforeach; ?>
                    </ul>
                </div>

            </div>
        </section>

        <section id="right" class="flex flex-col">
            <nav>
                <h1 id="titulo_nav"></h1>

                <div class="barra_progresso">
                    <div class="skills">
                        <span class="Name">UPLOADING</span>
                        <div class="percent">
                            <div class="progress" style="width: 0%;"></div>
                        </div>
                        <span class="Value">0%</span>
                    </div>
                </div>
            </nav>

            <div id="area_apresent"></div>

        </section>
    </main>

    <script>
        function loadPage(url, linkElement, titulo) {
            const li = linkElement.closest('li');

            // Remove .active de todos os <li>
            document.querySelectorAll('.menu-item').forEach(el => el.classList.remove('active'));

            // Adiciona .active no <li> clicado
            if (li) li.classList.add('active');

            // Carrega a página de forma assíncrona na div
            fetch(url)
                .then(res => res.text())
                .then(html => {
                    document.getElementById('area_apresent').innerHTML = html;
                    document.getElementById('titulo_nav').innerHTML = titulo;

                    // Só dispara se existir o botão de exportação (ou importação)
                    if (document.querySelector('.btn_exportprod')) {
                        carregarCategorias(0);
                    }
                })

                .catch(err => {
                    document.getElementById('area_apresent').innerHTML = "<p>Erro ao carregar conteúdo.</p>";
                    console.error(err);
                });
        }

        window.addEventListener('DOMContentLoaded', () => {
            const firstLink = document.querySelector('.menu-item a');
            if (firstLink) {
                const titulo = firstLink.dataset.titulo;
                loadPage(firstLink.getAttribute('href'), firstLink, titulo);
            }
        });
    </script>


    <script src="js/import_export.js"></script>
    <script src="js/metags.js"></script>

</body>

</html>