<?php
ini_set('display_errors', 0);
header('Content-Type: application/json');

try {
    include __DIR__ . '/../conexao.php';

    $imagemCaminhoRelativo = $_POST['imagem'] ?? '';
    if (!$imagemCaminhoRelativo) {
        throw new Exception("Imagem não especificada.");
    }

    // Busca pelo nome do arquivo (evita erros de caminho de pasta no LIKE)
    $nomeArquivo = basename($imagemCaminhoRelativo);
    $termoBusca = "%" . $nomeArquivo . "%";

    // Verifica em todas as colunas de imagem do banco
    $sql = "SELECT COUNT(*) AS total FROM isc_product_images 
            WHERE imagefile LIKE ? 
            OR imagefiletiny LIKE ? 
            OR imagefilethumb LIKE ? 
            OR imagefilestd LIKE ? 
            OR imagefilezoom LIKE ?";

    $stmt = $con->prepare($sql);
    $stmt->bind_param("sssss", $termoBusca, $termoBusca, $termoBusca, $termoBusca, $termoBusca);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total = $row['total'] ?? 0;
    $stmt->close();

    $basePath = realpath(__DIR__ . "/../../../../product_images");
    
    // Normalização crucial para Windows: converte barras / para \ no caminho físico
    $imagemLimpa = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $imagemCaminhoRelativo);
    $caminhoCompleto = $basePath . DIRECTORY_SEPARATOR . $imagemLimpa;

    if ($total == 0) {
        if (file_exists($caminhoCompleto)) {
            // Tenta apagar o arquivo
            if (unlink($caminhoCompleto)) {
                echo json_encode(["status" => "apagada", "imagem" => $imagemCaminhoRelativo]);
            } else {
                echo json_encode(["status" => "erro", "mensagem" => "Permissão negada ao excluir."]);
            }
        } else {
            echo json_encode(["status" => "erro", "mensagem" => "Arquivo não existe no disco."]);
        }
    } else {
        echo json_encode(["status" => "mantida", "imagem" => $imagemCaminhoRelativo, "uso" => $total]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "erro", "mensagem" => $e->getMessage()]);
}

if (isset($con)) $con->close();