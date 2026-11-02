<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit;
}
require_once __DIR__ . '/../config/conexao.php';

$stmt = $conn->prepare(
    "SELECT id, nome, preco, ativo FROM produtos
     WHERE usuario_id = ? ORDER BY nome"
);
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$produtos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt = $conn->prepare(
    "SELECT id, nome, email, telefone, ativo FROM fornecedores
     WHERE usuario_id = ? ORDER BY nome"
);
$stmt->bind_param("i", $_SESSION['usuario_id']);
$stmt->execute();
$fornecedores = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Dados</title>
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
        <div class="container" style="max-width: 900px;">

            <h2 class="text-white mt-4 mb-2">Gerenciar Dados</h2>

            <div id="status" class="alert d-none" role="alert"></div>

            <h3 class="text-white mt-4 mb-3" style="font-size: 1.1rem;">Produtos</h3>
            <div class="table-responsive mb-5">
                <table class="table table-dark table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th style="width: 130px;">Preço</th>
                            <th style="width: 100px;">Ativo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $p): ?>
                            <tr data-entidade="produto" data-id="<?= (int) $p['id'] ?>">
                                <td>
                                    <input type="text" class="form-control form-control-sm campo-ajax"
                                           data-campo="nome"
                                           value="<?= htmlspecialchars($p['nome']) ?>">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0"
                                           class="form-control form-control-sm campo-ajax"
                                           data-campo="preco"
                                           value="<?= number_format($p['preco'], 2, '.', '') ?>">
                                </td>
                                <td>
                                    <select class="form-select form-select-sm campo-ajax" data-campo="ativo">
                                        <option value="1" <?= $p['ativo'] ? 'selected' : '' ?>>Sim</option>
                                        <option value="0" <?= !$p['ativo'] ? 'selected' : '' ?>>Não</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($produtos)): ?>
                            <tr><td colspan="3" class="text-muted">Nenhum produto cadastrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <h3 class="text-white mt-4 mb-3" style="font-size: 1.1rem;">Fornecedores</h3>
            <div class="table-responsive mb-5">
                <table class="table table-dark table-sm align-middle">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th style="width: 150px;">Telefone</th>
                            <th style="width: 100px;">Ativo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($fornecedores as $f): ?>
                            <tr data-entidade="fornecedor" data-id="<?= (int) $f['id'] ?>">
                                <td>
                                    <input type="text" class="form-control form-control-sm campo-ajax"
                                           data-campo="nome"
                                           value="<?= htmlspecialchars($f['nome']) ?>">
                                </td>
                                <td>
                                    <input type="email" class="form-control form-control-sm campo-ajax"
                                           data-campo="email"
                                           value="<?= htmlspecialchars($f['email'] ?? '') ?>">
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm campo-ajax"
                                           data-campo="telefone"
                                           value="<?= htmlspecialchars($f['telefone'] ?? '') ?>">
                                </td>
                                <td>
                                    <select class="form-select form-select-sm campo-ajax" data-campo="ativo">
                                        <option value="1" <?= $f['ativo'] ? 'selected' : '' ?>>Sim</option>
                                        <option value="0" <?= !$f['ativo'] ? 'selected' : '' ?>>Não</option>
                                    </select>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($fornecedores)): ?>
                            <tr><td colspan="4" class="text-muted">Nenhum fornecedor cadastrado.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>

    <script src="../assets/js/menu.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    (function () {
        const statusBox = document.getElementById('status');

        function mostrarStatus(msg, tipo) {
            statusBox.className = 'alert alert-' + tipo;
            statusBox.textContent = msg;
            clearTimeout(window.__statusTimer);
            window.__statusTimer = setTimeout(() => {
                statusBox.className = 'alert d-none';
            }, 2500);
        }

        document.querySelectorAll('.campo-ajax').forEach(el => {        
            el.addEventListener('blur', salvar);
            if (el.tagName === 'SELECT') {
                el.addEventListener('change', salvar);
            }
        });

        async function salvar(event) {
            const el       = event.currentTarget;
            const tr       = el.closest('tr');
            const entidade = tr.dataset.entidade;
            const id       = tr.dataset.id;
            const campo    = el.dataset.campo;
            const valor    = el.value;

            const fd = new FormData();
            fd.append('entidade', entidade);
            fd.append('id', id);
            fd.append('campo', campo);
            fd.append('valor', valor);

            el.classList.add('is-loading');
            try {
                const resp = await fetch('ajax_atualizar.php', {
                    method: 'POST',
                    body: fd
                });
                const dados = await resp.json();

                if (dados.ok) {
                    el.classList.remove('is-invalid');
                    el.classList.add('is-valid');
                    mostrarStatus('Salvo com sucesso.', 'success');
                    setTimeout(() => el.classList.remove('is-valid'), 1000);
                } else {
                    el.classList.add('is-invalid');
                    mostrarStatus(dados.erro || 'Erro ao salvar.', 'danger');
                }
            } catch (e) {
                el.classList.add('is-invalid');
                mostrarStatus('Falha de rede: ' + e.message, 'danger');
            } finally {
                el.classList.remove('is-loading');
            }
        }
    })();
    </script>
</body>

</html>