<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../config/conexao.php';

$erros = [];
$dados = [
    'codigo'        => '',
    'nome'          => '',
    'descricao'     => '',
    'preco'         => '',
    'fornecedor_id' => '',
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $dados['codigo']        = trim($_POST['codigo'] ?? '');
    $dados['nome']          = trim($_POST['nome'] ?? '');
    $dados['descricao']     = trim($_POST['descricao'] ?? '');
    $dados['preco']         = trim($_POST['preco'] ?? '');
    $dados['fornecedor_id'] = (int) ($_POST['fornecedor_id'] ?? 0);
    if ($dados['nome'] === '') {
        $erros[] = "Informe o nome do produto.";
    }
    if ($dados['preco'] === '' || !is_numeric($dados['preco']) || (float) $dados['preco'] < 0) {
        $erros[] = "Informe um preço válido (>= 0).";
    }
    if ($dados['fornecedor_id'] <= 0) {
        $erros[] = "Selecione um fornecedor.";
    }
    if (empty($erros)) {
        $stmt = $conn->prepare(
            "SELECT id FROM fornecedores
             WHERE id = ? AND usuario_id = ? AND ativo = 1"
        );
        $stmt->bind_param("ii", $dados['fornecedor_id'], $_SESSION['usuario_id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows !== 1) {
            $erros[] = "Fornecedor inválido.";
        }
        $stmt->close();
    }
    if (empty($erros)) {
        $codigo = ($dados['codigo'] !== '') ? $dados['codigo'] : null;
        $preco  = (float) $dados['preco'];

        $stmt = $conn->prepare(
            "INSERT INTO produtos
                (usuario_id, fornecedor_id, codigo, nome, descricao, preco, ativo)
             VALUES (?, ?, ?, ?, ?, ?, 1)"
        );
        $stmt->bind_param(
            "iisssd",
            $_SESSION['usuario_id'],
            $dados['fornecedor_id'],
            $codigo,
            $dados['nome'],
            $dados['descricao'],
            $preco
        );

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: adicionar_produto.php?ok=1");
            exit;
        } else {
            if ($stmt->errno === 1062) {
                $erros[] = "Já existe um produto com esse código. Escolha outro.";
            } else {
                $erros[] = "Erro ao salvar: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

$stmt = $conn->prepare(
    "SELECT id, nome FROM fornecedores
     WHERE usuario_id = ? AND ativo = 1
     ORDER BY nome"
);
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$fornecedores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();

$sucesso = isset($_GET['ok']) && $_GET['ok'] == 1;
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Produto</title>
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
            <li><a href="#"><i class="bi bi-cart3"></i><span>Cesta</span></a></li>
            <li><a href="#"><i class="bi bi-person-square"></i><span>Add fornecedor</span></a></li>
            <li><a href="produtos.php"><i class="bi bi-archive-fill"></i><span>Produtos</span></a></li>
        </ul>
    </nav>

    <div class="box-produtos px-3 px-md-4">
        <div class="container" style="max-width: 700px;">
            <h2 class="text-white titulo-form-produto">Adicionar Produto</h2>

            <?php if ($sucesso): ?>
                <div class="alert alert-success">
                    Produto cadastrado com sucesso!
                    <a href="produtos.php" class="alert-link">Ver produtos</a>
                </div>
            <?php endif; ?>

            <?php if (!empty($erros)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        <?php foreach ($erros as $e): ?>
                            <li><?= htmlspecialchars($e) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" class="p-4 rounded mb-5 form-produto" style="background: var(--menu-bg);">
                <div class="mb-3">
                    <label class="form-label text-white">Código (opcional)</label>
                    <input type="text" name="codigo"
                           value="<?= htmlspecialchars($dados['codigo']) ?>"
                           class="form-control" placeholder="Ex.: PROD-009">
                </div>

                <div class="mb-3">
                    <label class="form-label text-white">Nome *</label>
                    <input type="text" name="nome" required
                           value="<?= htmlspecialchars($dados['nome']) ?>"
                           class="form-control">
                </div>

                <div class="mb-3">
                    <label class="form-label text-white">Descrição</label>
                    <textarea name="descricao" rows="2" class="form-control"><?= htmlspecialchars($dados['descricao']) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label text-white">Preço (R$) *</label>
                    <input type="number" name="preco" step="0.01" min="0" required
                           value="<?= htmlspecialchars($dados['preco']) ?>"
                           class="form-control">
                </div>

                <div class="mb-4">
                    <label class="form-label text-white">Fornecedor *</label>
                    <select name="fornecedor_id" class="form-select" required>
                        <option value="">Selecione...</option>
                        <?php foreach ($fornecedores as $f): ?>
                            <option value="<?= (int) $f['id'] ?>"
                                <?= $dados['fornecedor_id'] == $f['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($f['nome']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($fornecedores)): ?>
                        <small class="text-warning d-block mt-2">
                            Você ainda não tem fornecedores cadastrados. Cadastre um antes de adicionar produtos.
                        </small>
                    <?php endif; ?>
                </div>

                <button type="submit" class="custom-btn w-100">Salvar produto</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>