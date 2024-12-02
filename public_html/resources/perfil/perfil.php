<?php
session_start();
if (!isset($_SESSION['utilizador'])) {
    header('Location: login/login.php');
    exit();
}

require_once '../conexao.php';
$conn = getDatabaseConnection();
$idUtilizador = $_SESSION['utilizador'];

// Obter os dados do utilizador
$sql = "SELECT * FROM utilizador WHERE idUtilizador = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idUtilizador);
$stmt->execute();
$result = $stmt->get_result();
$utilizador = $result->fetch_assoc();

// Processar submissão do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = strtolower($_POST['email']);
    $contacto = $_POST['contacto'];
    $novaPassword = $_POST['novaPassword'] ?? null;

    // Verificar se o email já existe para outro utilizador
    $checkSql = "SELECT idUtilizador FROM utilizador WHERE email = ? AND idUtilizador != ?";
    $stmtCheck = $conn->prepare($checkSql);
    $stmtCheck->bind_param("si", $email, $idUtilizador);
    $stmtCheck->execute();
    $checkResult = $stmtCheck->get_result();

    if ($checkResult->num_rows > 0) {
        $erro = "Este email já está em uso.";
    } else {
        // Atualizar os dados do utilizador
        $updateSql = "UPDATE utilizador SET nome = ?, email = ?, contacto = ? WHERE idUtilizador = ?";
        if ($novaPassword) {
            $salt = bin2hex(random_bytes(10));
            $hashedPassword = password_hash($salt . $novaPassword, PASSWORD_BCRYPT);
            $updateSql = "UPDATE utilizador SET nome = ?, email = ?, contacto = ?, password = ?, salt = ? WHERE idUtilizador = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("sssssi", $nome, $email, $contacto, $hashedPassword, $salt, $idUtilizador);
        } else {
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("sssi", $nome, $email, $contacto, $idUtilizador);
        }
        if ($stmt->execute()) {
            $mensagem = "Informações atualizadas com sucesso.";
        } else {
            $erro = "Erro ao atualizar informações.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Perfil</title>
</head>

<body>
<h1>Editar Perfil</h1>
<?php if (isset($mensagem)): ?>
    <p style="color:green;"><?php echo $mensagem; ?></p>
<?php endif; ?>
<?php if (isset($erro)): ?>
    <p style="color:red;"><?php echo $erro; ?></p>
<?php endif; ?>
<form method="POST" action="">
    <label for="nome">Nome:</label>
    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($utilizador['nome']); ?>" required>

    <label for="email">Email:</label>
    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($utilizador['email']); ?>" required>

    <label for="contacto">Contacto:</label>
    <input type="text" id="contacto" name="contacto" value="<?php echo htmlspecialchars($utilizador['contacto']); ?>" required>

    <label for="novaPassword">Nova Palavra-passe (opcional):</label>
    <input type="password" id="novaPassword" name="novaPassword">

    <button type="submit">Guardar Alterações</button>
</form>
<a href="index.php">Voltar</a>
</body>

</html>
