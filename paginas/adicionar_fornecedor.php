<?php
session_start();

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}

require_once __DIR__ . '/../config/conexao.php';

$erros = [];
$dados = [
    'nome'        => '',
    'documento'   => '',
    'email'       => '',
    'telefone'    => '',
    'cep'         => '',
    'logradouro'  => '',
    'numero'      => '',
    'complemento' => '',
    'bairro'      => '',
    'cidade'      => '',
    'estado'      => '',
    'observacoes' => '',
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($dados as $campo => $_) {
        $dados[$campo] = trim($_POST[$campo] ?? '');
    }
    $dados['estado'] = strtoupper($dados['estado']);
    if ($dados['nome'] === '') {
        $erros[] = "Informe o nome do fornecedor.";
    }
    if ($dados['email'] !== '' && !filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
        $erros[] = "E-mail inválido.";
    }
    if ($dados['estado'] !== '' && strlen($dados['estado']) !== 2) {
        $erros[] = "O estado deve ter 2 letras (ex.: SP).";
    }
    if (empty($erros)) {
        $documento = ($dados['documento'] !== '') ? $dados['documento'] : null;
        $stmt = $conn->prepare(
            "INSERT INTO fornecedores
                (usuario_id, nome, documento, email, telefone,
                 cep, logradouro, numero, complemento, bairro, cidade, estado, observacoes, ativo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
        );

        $stmt->bind_param(
            "issssssssssss",
            $_SESSION['usuario_id'],
            $dados['nome'],
            $documento,
            $dados['email'],
            $dados['telefone'],
            $dados['cep'],
            $dados['logradouro'],
            $dados['numero'],
            $dados['complemento'],
            $dados['bairro'],
            $dados['cidade'],
            $dados['estado'],
            $dados['observacoes']
        );

        if ($stmt->execute()) {
            $stmt->close();
            $conn->close();
            header("Location: adicionar_fornecedor.php?ok=1");
            exit;
        } else {
            if ($stmt->errno === 1062) {
                $erros[] = "Já existe um fornecedor com esse documento.";
            } else {
                $erros[] = "Erro ao salvar: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
$stmt = $conn->prepare(
    "SELECT id, nome, documento, email, telefone, cidade, estado
     FROM fornecedores
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
    <title>Adicionar Fornecedor</title>
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
            <li><a href="adicionar_fornecedor.php"><i class="bi bi-person-square"></i><span>Add fornecedor</span></a></li>
            <li><a href="produtos.php"><i class="bi bi-archive-fill"></i><span>Produtos</span></a></li>
            <li><a href="gerenciar_dados.php"><i class="bi bi-pencil-square"></i><span>Gerenciar dados</span></a></li>
        </ul>
    </nav>

    <div class="box-produtos px-3 px-md-4">
        <div class="container" style="max-width: 820px;">

            <h2 class="text-white titulo-form-produto">Adicionar Fornecedor</h2>

            <?php if ($sucesso): ?>
                <div class="alert alert-success">
                    Fornecedor cadastrado com sucesso!
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

            <form method="post" class="p-4 rounded mb-4 form-produto" style="background: var(--menu-bg);">

                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label text-white">Nome / Razão social *</label>
                        <input type="text" name="nome" required
                               value="<?= htmlspecialchars($dados['nome']) ?>"
                               class="form-control">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label text-white">CPF / CNPJ (opcional)</label>
                        <input type="text" name="documento"
                               value="<?= htmlspecialchars($dados['documento']) ?>"
                               class="form-control" placeholder="000.000.000-00">
                    </div>
                    <div class="col-md-7">
                        <label class="form-label text-white">E-mail</label>
                        <input type="email" name="email"
                               value="<?= htmlspecialchars($dados['email']) ?>"
                               class="form-control">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label text-white">Telefone</label>
                        <input type="text" name="telefone"
                               value="<?= htmlspecialchars($dados['telefone']) ?>"
                               class="form-control" placeholder="(11) 99999-0000">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-white">CEP</label>
                        <input type="text" name="cep"
                               value="<?= htmlspecialchars($dados['cep']) ?>"
                               class="form-control" placeholder="00000-000">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-white">Logradouro</label>
                        <input type="text" name="logradouro"
                               value="<?= htmlspecialchars($dados['logradouro']) ?>"
                               class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-white">Número</label>
                        <input type="text" name="numero"
                               value="<?= htmlspecialchars($dados['numero']) ?>"
                               class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-white">Complemento</label>
                        <input type="text" name="complemento"
                               value="<?= htmlspecialchars($dados['complemento']) ?>"
                               class="form-control">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label text-white">Bairro</label>
                        <input type="text" name="bairro"
                               value="<?= htmlspecialchars($dados['bairro']) ?>"
                               class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-white">Cidade</label>
                        <input type="text" name="cidade"
                               value="<?= htmlspecialchars($dados['cidade']) ?>"
                               class="form-control">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label text-white">UF</label>
                        <input type="text" name="estado" maxlength="2"
                               value="<?= htmlspecialchars($dados['estado']) ?>"
                               class="form-control text-uppercase" placeholder="SP">
                    </div>
                    <div class="col-12">
                        <label class="form-label text-white">Observações</label>
                        <textarea name="observacoes" rows="2" class="form-control"><?= htmlspecialchars($dados['observacoes']) ?></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="custom-btn w-100">
                            <i class="bi bi-check-circle"></i> Salvar fornecedor
                        </button>
                    </div>

                </div>
            </form>

            <h3 class="text-white mt-4 mb-3" style="font-size: 1.1rem;">
                Fornecedores cadastrados (<?= count($fornecedores) ?>)
            </h3>

            <?php if (empty($fornecedores)): ?>
                <div class="p-3 rounded mb-5" style="background: var(--menu-bg);">
                    <p class="text-white mb-0">Nenhum fornecedor cadastrado ainda.</p>
                </div>
            <?php else: ?>
                <ul class="list-group mb-5">
                    <?php foreach ($fornecedores as $f): ?>
                        <li class="list-group-item"
                            style="background: var(--menu-bg); color: white; border-color: rgba(255,255,255,0.1);">
                            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                                <div>
                                    <strong><?= htmlspecialchars($f['nome']) ?></strong>
                                    <?php if ($f['documento']): ?>
                                        <span class="text-muted small">— <?= htmlspecialchars($f['documento']) ?></span>
                                    <?php endif; ?>
                                    <div class="text-muted small">
                                        <?php
                                        $partes = array_filter([
                                            $f['email'],
                                            $f['telefone'],
                                            trim(($f['cidade'] ?? '') . '/' . ($f['estado'] ?? ''), '/')
                                        ]);
                                        echo htmlspecialchars(implode(' · ', $partes));
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

        </div>
    </div>

    <script src="../assets/js/menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>