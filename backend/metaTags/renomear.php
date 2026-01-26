<?php
// Impede que warnings quebrem o JSON de retorno
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    include "../conexao.php";

    if ($con->connect_error) {
        throw new Exception("Erro na conexão: " . $con->connect_error);
    }

    // Coleta de dados do POST
    $imagemOriginal = $_POST['imagem']['caminho'] ?? '';
    $campo = $_POST['imagem']['campo'] ?? '';
    $cidade = $_POST['cidade'] ?? '';
    $telefone = $_POST['telefone'] ?? '';

    if (!$imagemOriginal || !$campo || !$cidade || !$telefone) {
        throw new Exception("Parâmetros inválidos ou incompletos.");
    }

    // 1. Localizar o ID do produto baseado na imagem atual
    $sql = "SELECT imageprodid FROM isc_product_images WHERE $campo = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("s", $imagemOriginal);
    $stmt->execute();
    $result = $stmt->get_result();
    $produtoId = $result->fetch_assoc()['imageprodid'] ?? null;
    $stmt->close();

    if (!$produtoId) {
        throw new Exception("Vínculo da imagem não encontrado no banco.");
    }

    // 2. Buscar o nome do produto para gerar o novo nome amigável
    $sql = "SELECT prodname FROM isc_products WHERE productid = ?";
    $stmt = $con->prepare($sql);
    $stmt->bind_param("i", $produtoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $prodName = $result->fetch_assoc()['prodname'] ?? 'produto';
    $stmt->close();

    // Limpeza do nome (Slug)
    $prodSlug = preg_replace('/[^a-zA-Z0-9]/', '-', strtolower($prodName));
    
    // 3. Configuração de caminhos
    $basePath = realpath(__DIR__ . "/../../../../product_images");
    $diretorioInterno = dirname($imagemOriginal); // Ex: a/309
    $extensao = pathinfo($imagemOriginal, PATHINFO_EXTENSION);
    
    $novoNomeArquivo = "{$prodSlug}-{$cidade}-{$telefone}-" . uniqid() . ".{$extensao}";
    $caminhoRelativoNovo = $diretorioInterno . '/' . $novoNomeArquivo;

    // Caminhos Absolutos para o Sistema Operacional
    $caminhoAntigoFisico = $basePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imagemOriginal);
    $caminhoNovoFisico = $basePath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $caminhoRelativoNovo);

    // 4. Verificação física e Renomeação
    if (!file_exists($caminhoAntigoFisico)) {
        echo json_encode(["status" => "pulado", "mensagem" => "Arquivo não existe no disco.", "path" => $caminhoAntigoFisico]);
        exit;
    }

    if (rename($caminhoAntigoFisico, $caminhoNovoFisico)) {
        
        // Tenta redimensionar se for um formato suportado
        redimensionarImagem($caminhoNovoFisico, 800);

        // 5. Atualiza o banco de dados com o novo caminho relativo
        $sqlUpdate = "UPDATE isc_product_images SET $campo = ? WHERE $campo = ?";
        $stmtUp = $con->prepare($sqlUpdate);
        $stmtUp->bind_param("ss", $caminhoRelativoNovo, $imagemOriginal);
        $stmtUp->execute();
        $stmtUp->close();

        echo json_encode([
            "status" => "ok", 
            "novo_nome" => $caminhoRelativoNovo
        ]);
    } else {
        throw new Exception("Falha ao renomear o arquivo físico.");
    }

} catch (Exception $e) {
    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}

if (isset($con)) $con->close();

/**
 * Função aprimorada com suporte a WebP
 */
function redimensionarImagem($caminho, $larguraMax) {
    if (!extension_loaded('gd')) return false;

    list($larguraOrig, $alturaOrig, $tipo) = getimagesize($caminho);
    if (!$larguraOrig) return false;

    // Se a imagem já for menor que o máximo, não redimensiona
    if ($larguraOrig <= $larguraMax) return true;

    $proporcao = $larguraMax / $larguraOrig;
    $novaAltura = (int)($alturaOrig * $proporcao);

    $imgNova = imagecreatetruecolor($larguraMax, $novaAltura);

    // Mantém transparência para PNG e WebP
    if ($tipo == IMAGETYPE_PNG || $tipo == IMAGETYPE_WEBP) {
        imagealphablending($imgNova, false);
        imagesavealpha($imgNova, true);
    }

    switch ($tipo) {
        case IMAGETYPE_JPEG: $imgOrigem = imagecreatefromjpeg($caminho); break;
        case IMAGETYPE_PNG:  $imgOrigem = imagecreatefrompng($caminho); break;
        case IMAGETYPE_WEBP: $imgOrigem = imagecreatefromwebp($caminho); break;
        default: return false;
    }

    imagecopyresampled($imgNova, $imgOrigem, 0, 0, 0, 0, $larguraMax, $novaAltura, $larguraOrig, $alturaOrig);

    // Salva sobre o arquivo original
    switch ($tipo) {
        case IMAGETYPE_JPEG: imagejpeg($imgNova, $caminho, 85); break;
        case IMAGETYPE_PNG:  imagepng($imgNova, $caminho, 8); break;
        case IMAGETYPE_WEBP: imagewebp($imgNova, $caminho, 80); break;
    }

    imagedestroy($imgOrigem);
    imagedestroy($imgNova);
    return true;
}