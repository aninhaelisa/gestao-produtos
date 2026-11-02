<?php
session_start();
require_once __DIR__ . '/conexao.php';
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../paginas/login.html");
    exit;
}

$email          = trim($_POST['email'] ?? '');
$senha_digitada = trim($_POST['senha'] ?? '');
if ($email === '' || $senha_digitada === '') {
    header("Location: ../paginas/login.html?erro=campos_vazios");
    exit;
}
$sql  = "SELECT id, nome, senha_hash, ativo FROM usuarios WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$resultado = $stmt->get_result();
if ($resultado->num_rows !== 1) {
    $stmt->close();
    $conn->close();
    header("Location: ../paginas/login.html?erro=email");
    exit;
}

$usuario = $resultado->fetch_assoc();
$stmt->close();
if ((int) $usuario['ativo'] === 0) {
    $conn->close();
    header("Location: ../paginas/login.html?erro=inativo");
    exit;
}
if (!password_verify($senha_digitada, $usuario['senha_hash'])) {
    $conn->close();
    header("Location: ../paginas/login.html?erro=senha");
    exit;
}
$_SESSION['usuario_id']   = $usuario['id'];
$_SESSION['usuario_nome'] = $usuario['nome'];
$_SESSION['cesta']        = [];

$conn->close();
header("Location: ../paginas/produtos.php");
exit;