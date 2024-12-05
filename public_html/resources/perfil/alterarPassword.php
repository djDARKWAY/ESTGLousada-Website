<?php
session_start();
if (!isset($_SESSION['utilizador'])) {
    header('Location: ../login/login.php');
    exit();
}

require_once '../conexao.php';
$conn = getDatabaseConnection();
$idUtilizador = $_SESSION['utilizador'];

// Obter dados do utilizador
$sql = "SELECT salt, password FROM utilizador WHERE idUtilizador = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idUtilizador);
$stmt->execute();
$result = $stmt->get_result();
$utilizador = $result->fetch_assoc();

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $passwordAntiga = $_POST['passwordAntiga'];
    $novaPassword = $_POST['novaPassword'];
    $confirmarNovaPassword = $_POST['confirmarNovaPassword'];

    // Validar password antiga
    $salt = $utilizador['salt'];
    $hashedPassword = $utilizador['password'];

    if (empty($passwordAntiga) || empty($novaPassword) || empty($confirmarNovaPassword)) {
        $erro = "Todos os campos são obrigatórios!";
    } elseif (!password_verify($salt . $passwordAntiga, $hashedPassword)) {
        $erro = "A palavra-passe antiga está incorreta!";
    } elseif ($novaPassword !== $confirmarNovaPassword) {
        $erro = "As novas palavras-passe não coincidem!";
    } elseif (strlen($novaPassword) < 8) {
        $erro = "A nova palavra-passe deve ter pelo menos 8 caracteres.";
    } else {

        // Atualizar a palavra-passe
        $salt = bin2hex(random_bytes(10));
        $novaPasswordHash = password_hash($salt . $novaPassword, PASSWORD_DEFAULT);

        $updateSql = "UPDATE utilizador SET password = ?, salt = ? WHERE idUtilizador = ?";
        $stmtUpdate = $conn->prepare($updateSql);
        $stmtUpdate->bind_param("ssi", $novaPasswordHash, $salt, $idUtilizador);

        if ($stmtUpdate->execute()) {
            $mensagem = "Palavra-passe alterada com sucesso!";
        } else {
            $erro = "Erro ao alterar a palavra-passe!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Palavra-passe</title>
    <link rel="stylesheet" href="alterarPassword.css">
</head>

<body>
    <div class="container">
        <?php if (isset($mensagem)): ?>
            <p style="color:lightgreen; font-weight:bold;"><?php echo $mensagem; ?></p>
        <?php endif; ?>
        <?php if (isset($erro)): ?>
            <p style="color:red; font-weight:bold;"><?php echo $erro; ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="passwordAntiga">Antiga palavra-passe:</label>
            <input type="password" id="passwordAntiga" name="passwordAntiga" required>

            <label for="novaPassword">Nova palavra-passe:</label>
            <input type="password" id="novaPassword" name="novaPassword" required>

            
            <label for="confirmarNovaPassword">Confirmar nova palavra-passe:</label>
            <input type="password" id="confirmarNovaPassword" name="confirmarNovaPassword" required>

            <button type="submit">Alterar palavra-passe</button>

            <a class="voltar" href="../perfil/perfil.php">◄ Voltar</a>
        </form>
    </div>
</body>

</html>