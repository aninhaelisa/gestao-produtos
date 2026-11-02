<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../paginas/login.html");
    exit;
}

require_once __DIR__ . '/../config/conexao.php';

$mensagem = "";
$mensagem_tipo = "success";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['produto_ids'])) {
    $ids = array_filter(array_map('intval', (array) $_POST['produto_ids']));

    if (empty($ids)) {
        $mensagem      = "Selecione pelo menos 1 produto para adicionar à cesta.";
        $mensagem_tipo = "warning";
    } else {
        if (!isset($_SESSION['cesta'])) {
            $_SESSION['cesta'] = [];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $tipos        = str_repeat('i', count($ids)) . 'i';
        $params       = array_merge($ids, [$_SESSION['usuario_id']]);

        $stmt = $conn->prepare(
            "SELECT id FROM produtos
             WHERE id IN ($placeholders) AND usuario_id = ? AND ativo = 1"
        );
        $stmt->bind_param($tipos, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        $validos = [];
        while ($r = $res->fetch_assoc()) {
            $validos[] = (int) $r['id'];
        }
        $stmt->close();

        $novos    = 0;
        $ja_tinha = 0;
        foreach ($validos as $id) {
            if (!in_array($id, $_SESSION['cesta'], true)) {
                $_SESSION['cesta'][] = $id;
                $novos++;
            } else {
                $ja_tinha++;
            }
        }

        if ($novos > 0) {
            $mensagem = "$novos produto(s) adicionado(s) à cesta." .
                ($ja_tinha ? " ($ja_tinha já estavam)" : "");
            $mensagem_tipo = "success";
        } elseif ($ja_tinha > 0) {
            $mensagem      = "Todos os produtos selecionados já estavam na cesta.";
            $mensagem_tipo = "warning";
        } else {
            $mensagem      = "Nenhum produto válido selecionado.";
            $mensagem_tipo = "warning";
        }
    }
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
            <li><a href="gerenciar_dados.php"><i class="bi bi-pencil-square"></i><span>Gerenciar dados</span></a></li>
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
        <form method="post">
            <div class="row g-4 px-3 px-md-4 py-3">

                <?php if (empty($produtos)): ?>
                    <div class="col-12">
                        <p class="text-white">
                            <?php if (!empty($busca)): ?>
                                Nenhum produto encontrado para "<strong><?= htmlspecialchars($busca) ?></strong>".
                                <a href="produtos.php" class="link-light">Limpar busca</a>
                            <?php else: ?>
                                Nenhum produto cadastrado ainda.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>

                    <?php if (!empty($busca)): ?>
                        <div class="col-12 mb-2">
                            <p class="text-white mb-0">
                                <?= count($produtos) ?> resultado(s) para
                                "<strong><?= htmlspecialchars($busca) ?></strong>".
                                <a href="produtos.php" class="link-light">Limpar busca</a>
                            </p>
                        </div>
                    <?php endif; ?>

                    <?php foreach ($produtos as $p): ?>
                        <?php $na_cesta = in_array($p['id'], $_SESSION['cesta'], true); ?>
                        <div class="col-md-4 mb-4">
                            <div class="card card-produto shadow-sm <?= $na_cesta ? 'border-success' : '' ?>">

                                <div class="card-img-top d-flex align-items-center justify-content-center bg-secondary bg-opacity-25"
                                    style="height: 120px;">
                                    <i class="bi bi-box-seam" style="font-size: 3rem; color: #6c757d;"></i>
                                </div>

                                <div class="card-body d-flex flex-column">

                                    <div class="form-check mb-2">
                                        <input class="form-check-input checkbox-produto"
                                            type="checkbox"
                                            name="produto_ids[]"
                                            value="<?= (int) $p['id'] ?>"
                                            id="prod-<?= (int) $p['id'] ?>"
                                            <?= $na_cesta ? 'disabled' : '' ?>>
                                        <label class="form-check-label small" for="prod-<?= (int) $p['id'] ?>">
                                            <?= $na_cesta ? 'Já na cesta' : 'Selecionar' ?>
                                        </label>
                                    </div>

                                    <h5 class="card-title"><?= htmlspecialchars($p['nome']) ?></h5>
                                    <p class="card-text text-muted small card-descricao">
                                        <?= htmlspecialchars($p['descricao'] ?: 'Sem descrição') ?>
                                    </p>
                                    <p class="fw-bold fs-5 mt-auto">
                                        R$ <?= number_format($p['preco'], 2, ',', '.') ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <div class="col-12 mt-2">
                        <button type="submit" class="custom-btn w-100" id="btn-add-cesta">
                            <i class="bi bi-cart-plus"></i> Adicionar selecionados à cesta
                        </button>
                    </div>

                <?php endif; ?>
            </div>
        </form>
    </div>

    <script src="../assets/js/produtos.js"></script>
    <script src="../assets/js/menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>