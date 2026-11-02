<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../paginas/login.html");
    exit;
}

require_once __DIR__ . '/../config/conexao.php';

$mensagem = "";
$mensagem_tipo = "success";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_produto_id'])) {
    $idProduto = (int) $_POST['add_produto_id'];

    if (!isset($_SESSION['cesta'])) {
        $_SESSION['cesta'] = [];
    }
    $stmt = $conn->prepare(
        "SELECT id FROM produtos WHERE id = ? AND usuario_id = ? AND ativo = 1"
    );
    $stmt->bind_param("ii", $idProduto, $_SESSION['usuario_id']);
    $stmt->execute();

    if ($stmt->get_result()->num_rows === 1) {
        if (!in_array($idProduto, $_SESSION['cesta'], true)) {
            $_SESSION['cesta'][] = $idProduto;
            $mensagem      = "Produto adicionado à cesta!";
            $mensagem_tipo = "success";
        } else {
            $mensagem      = "Este produto já está na cesta.";
            $mensagem_tipo = "warning";
        }
    }
    $stmt->close();
}

$busca = trim($_GET['q'] ?? '');

if ($busca !== '') {
    $like = '%' . $busca . '%';
    $stmt = $conn->prepare(
        "SELECT id, nome, descricao, preco
         FROM produtos
         WHERE usuario_id = ? AND ativo = 1
           AND (nome LIKE ? OR descricao LIKE ?)
         ORDER BY nome"
    );
    $stmt->bind_param("iss", $_SESSION['usuario_id'], $like, $like);
} else {
    $stmt = $conn->prepare(
        "SELECT id, nome, descricao, preco
         FROM produtos
         WHERE usuario_id = ? AND ativo = 1
         ORDER BY nome"
    );
    $stmt->bind_param("i", $_SESSION['usuario_id']);
}

$stmt->execute();
$produtos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PRODUTOS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="pagina-login">

    <!------------------------------------------------------------------------------->

    <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu">
        <span></span>
        <span></span>
        <span></span>
    </button>
    <div class="menu-overlay" id="menuOverlay"></div>
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header"></div>
        <ul class="menu-list">
            <li><a href="adicionar_produto.php"><i class="bi bi-box-seam"></i><span>Add produto</span></a></li>
            <li><a href="cesta.php"><i class="bi bi-cart3"></i><span>Cesta</span></a></li>
            <li><a href="adicionar_fornecedor.php"><i class="bi bi-person-square"></i><span>Add fornecedor</span></a></li>
            <li><a href="produtos.php"><i class="bi bi-archive-fill"></i><span>Produtos</span></a></li>
        </ul>
    </nav>

    <div class="box-produtos px-3 px-md-4" style="position: static; height: auto; overflow: visible;">
        <form role="search" method="get" style="max-width: 400px;" class="formulario-pesquisa">
            <div class="input-group search-custom rounded-pill border border-white rounded">
                <input type="search"
                    name="q"
                    value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                    class="form-control bg-transparent text-white border-0 shadow-none placeholder-white"
                    placeholder="Pesquisar..."
                    aria-label="Pesquisar">
                <button class="btn bg-transparent text-white border-0 shadow-none" type="submit" aria-label="Buscar">
                    <i class="bi bi-search"></i>
                </button>
            </div>
        </form>
        <!------------------------------------------------------------------------------->

        <?php if ($mensagem): ?>
            <div class="alert alert-<?= $mensagem_tipo ?> mt-3"><?= htmlspecialchars($mensagem) ?></div>
        <?php endif; ?>
        <div class="row mt-4">
            <?php if (empty($produtos)): ?>
                <div class="col-12">
                    <p class="text-white">Nenhum produto cadastrado ainda.</p>
                </div>
            <?php else: ?>
                <?php foreach ($produtos as $p): ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100 shadow-sm">

                            <div class="card-img-top d-flex align-items-center justify-content-center bg-secondary bg-opacity-25"
                                style="height: 80px;">
                                <i class="bi bi-box-seam" style="font-size: 4rem; color: #6c757d;"></i>
                            </div>
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title"><?= htmlspecialchars($p['nome']) ?></h5>
                                <p class="card-text text-muted small">
                                    <?= htmlspecialchars($p['descricao'] ?: 'Sem descrição') ?>
                                </p>
                                <p class="fw-bold fs-5 mt-auto">
                                    R$ <?= number_format($p['preco'], 2, ',', '.') ?>
                                </p>
                                <?php $na_cesta = in_array($p['id'], $_SESSION['cesta'], true); ?>

                                <?php if ($na_cesta): ?>
                                    <button class="btn w-100 custom-btn-cesta-na" disabled>
                                        <i class="bi bi-check-circle"></i> Já está na cesta
                                    </button>
                                <?php else: ?>
                                    <form method="post" class="mt-2">
                                        <input type="hidden" name="add_produto_id" value="<?= (int) $p['id'] ?>">
                                        <button type="submit" class="btn btn-primary w-100 custom-btn-cesta">
                                            <i class="bi bi-cart-plus"></i> Adicionar à cesta
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>