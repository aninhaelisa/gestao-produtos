<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../config/conexao.php';

if (!isset($_SESSION['cesta']) || !is_array($_SESSION['cesta'])) {
    $_SESSION['cesta'] = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['remove_id'])) {
        $rid = (int) $_POST['remove_id'];
        $_SESSION['cesta'] = array_values(array_filter(
            $_SESSION['cesta'],
            fn($id) => (int) $id !== $rid
        ));
        header("Location: cesta.php");
        exit;
    }
    if (isset($_POST['limpar'])) {
        $_SESSION['cesta'] = [];
        header("Location: cesta.php");
        exit;
    }
}

$itens    = [];
$subtotal = 0.0;

if (!empty($_SESSION['cesta'])) {
    $ids = array_values(array_unique(array_map('intval', $_SESSION['cesta'])));

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $tipos        = str_repeat('i', count($ids)) . 'i';         
    $params       = array_merge($ids, [$_SESSION['usuario_id']]);

    $sql = "SELECT id, nome, descricao, preco
            FROM produtos
            WHERE id IN ($placeholders)
              AND usuario_id = ?
              AND ativo = 1
            ORDER BY nome";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($tipos, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $encontrados = [];
    while ($row = $result->fetch_assoc()) {
        $itens[]              = $row;
        $subtotal            += (float) $row['preco'];
        $encontrados[$row['id']] = true;
    }
    $stmt->close();
    $_SESSION['cesta'] = array_values(array_filter(
        $_SESSION['cesta'],
        fn($id) => isset($encontrados[$id])
    ));
}

$conn->close();
const FRETE_VALOR         = 15.00;  
const FRETE_GRATIS_ACIMA  = 100.00; 

if ($subtotal <= 0) {
    $frete = 0.00;
} elseif ($subtotal >= FRETE_GRATIS_ACIMA) {
    $frete = 0.00;
} else {
    $frete = FRETE_VALOR;
}

$total = $subtotal + $frete;
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cesta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>

    <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu">
        <span></span><span></span><span></span>
    </button>
    <div class="menu-overlay" id="menuOverlay"></div>
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header"></div>
        <ul class="menu-list">
            <li><a href="adicionar_produto.php"><i class="bi bi-box-seam"></i><span>Add produto</span></a></li>
            <li><a href="cesta.php"><i class="bi bi-cart3"></i><span>Cesta</span></a></li>
            <li><a href="#"><i class="bi bi-person-square"></i><span>Add fornecedor</span></a></li>
            <li><a href="produtos.php"><i class="bi bi-archive-fill"></i><span>Produtos</span></a></li>
        </ul>
    </nav>

    <div class="box-produtos px-3 px-md-4">
        <div class="container" style="max-width: 820px;">
            <h2 class="text-white mt-4 mb-4">Minha Cesta</h2>

            <?php if (empty($itens)): ?>

                <div class="p-4 rounded mb-5" style="background: var(--menu-bg);">
                    <p class="text-white mb-3">Sua cesta está vazia.</p>
                    <a href="produtos.php" class="custom-btn d-inline-block text-decoration-none">
                        Ver produtos
                    </a>
                </div>

            <?php else: ?>

                <ul class="list-group mb-4">
                    <?php foreach ($itens as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start"
                            style="background: var(--menu-bg); color: white; border-color: rgba(255,255,255,0.1);">
                            <div class="me-3">
                                <h6 class="mb-1"><?= htmlspecialchars($item['nome']) ?></h6>
                                <small class="text-muted">
                                    <?= htmlspecialchars($item['descricao'] ?: 'Sem descrição') ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold mb-2">
                                    R$ <?= number_format($item['preco'], 2, ',', '.') ?>
                                </div>
                                <form method="post" class="m-0">
                                    <input type="hidden" name="remove_id" value="<?= (int) $item['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i> Remover
                                    </button>
                                </form>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="p-4 rounded mb-4" style="background: var(--menu-bg);">
                    <div class="d-flex justify-content-between text-white mb-2">
                        <span>Subtotal</span>
                        <span>R$ <?= number_format($subtotal, 2, ',', '.') ?></span>
                    </div>
                    <div class="d-flex justify-content-between text-white mb-3">
                        <span>Frete</span>
                        <span>
                            <?php if ($frete == 0): ?>
                                <span class="badge bg-success">Grátis</span>
                            <?php else: ?>
                                R$ <?= number_format($frete, 2, ',', '.') ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php if ($subtotal > 0 && $subtotal < FRETE_GRATIS_ACIMA): ?>
                        <p class="text-muted small mb-3">
                            Falta R$ <?= number_format(FRETE_GRATIS_ACIMA - $subtotal, 2, ',', '.') ?>
                            para ganhar frete grátis.
                        </p>
                    <?php endif; ?>
                    <hr class="text-secondary">
                    <div class="d-flex justify-content-between text-white fw-bold fs-5">
                        <span>Total</span>
                        <span>R$ <?= number_format($total, 2, ',', '.') ?></span>
                    </div>
                </div>

                <div class="d-flex gap-2 mb-5 flex-wrap">
                    <a href="produtos.php" class="custom-btn text-decoration-none flex-grow-1 text-center">
                        <i class="bi bi-arrow-left"></i> Continuar comprando
                    </a>
                    <form method="post" class="m-0 flex-grow-1">
                        <input type="hidden" name="limpar" value="1">
                        <button type="submit"
                                class="custom-btn w-100"
                                style="background: transparent; border: 2px solid var(--pink-bright); color: var(--pink-bright);"
                                onclick="return confirm('Esvaziar a cesta?');">
                            <i class="bi bi-x-circle"></i> Esvaziar cesta
                        </button>
                    </form>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>