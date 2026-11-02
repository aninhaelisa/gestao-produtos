<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['usuario_id'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'erro' => 'Não autenticado']);
    exit;
}

require_once __DIR__ . '/../config/conexao.php';

$entidade = $_POST['entidade'] ?? '';  
$id       = (int) ($_POST['id'] ?? 0);
$campo    = $_POST['campo'] ?? '';
$valor    = trim($_POST['valor'] ?? '');

$permitidos = [
    'produto'    => ['nome', 'descricao', 'preco', 'ativo'],
    'fornecedor' => ['nome', 'email', 'telefone', 'ativo'],
];

if (!isset($permitidos[$entidade]) || !in_array($campo, $permitidos[$entidade], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Campo ou entidade inválidos']);
    exit;
}

if ($campo === 'preco') {
    if (!is_numeric($valor) || (float) $valor < 0) {
        echo json_encode(['ok' => false, 'erro' => 'Preço inválido']);
        exit;
    }
    $valor = (float) $valor;
    $tipos = 'd';
} elseif ($campo === 'ativo') {
    $valor = ($valor === '1') ? 1 : 0;
    $tipos = 'i';
} else {
    if ($valor === '' && in_array($campo, ['nome', 'email'], true)) {
        echo json_encode(['ok' => false, 'erro' => 'Este campo não pode ser vazio']);
        exit;
    }
    if ($campo === 'email' && $valor !== '' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok' => false, 'erro' => 'E-mail inválido']);
        exit;
    }
    $tipos = 's';
}

$tabela = ($entidade === 'produto') ? 'produtos' : 'fornecedores';

$sql  = "UPDATE $tabela SET $campo = ? WHERE id = ? AND usuario_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($tipos . "ii", $valor, $id, $_SESSION['usuario_id']);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    echo json_encode(['ok' => true, 'valor' => $valor]);
} else {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Erro ao atualizar: ' . $stmt->error]);
}