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

    $verificacaoPassword = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";

    // Validações
    $nome = trim(filter_var($nome, FILTER_SANITIZE_STRING));
    $email = trim(filter_var($email, FILTER_SANITIZE_EMAIL));
    $contacto = trim(filter_var($contacto, FILTER_SANITIZE_STRING));

    if ($checkResult->num_rows > 0) {
        $erro = "Este email já está em uso.";
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "<p>Formato de email inválido!</p>";
    } else if ($novaPassword && !preg_match($verificacaoPassword, $novaPassword)) {
        echo "<p>Password deve conter pelo menos 8 caracteres, uma letra maiúscula, uma letra minúscula, um número e um caractere especial.</p>";
    } else if (!preg_match('/^(255|91|92|93|96)[0-9]{7}$/', $contacto)) {
        echo "<p>O número tem de começar por 255, 91, 92, 93 ou 96 e ter 9 dígitos.</p>";
    } else {
        $imagemPerfil = null;
        if (isset($_FILES['imagemPerfil']) && $_FILES['imagemPerfil']['error'] == 0) {
            $imagemTipo = $_FILES['imagemPerfil']['type'];
            if ($imagemTipo == 'image/png' || $imagemTipo == 'image/jpeg') {
                $imagemPerfil = file_get_contents($_FILES['imagemPerfil']['tmp_name']);
            } else {
                echo "<p>Por favor, carregue uma imagem PNG ou JPEG.</p>";
            }
        }

        if ($novaPassword) {
            $salt = bin2hex(random_bytes(10));
            $hashedPassword = password_hash($salt . $novaPassword, PASSWORD_BCRYPT);
            $updateSql = "UPDATE utilizador SET nome = ?, email = ?, contacto = ?, imagemPerfil = ?, password = ?, salt = ? WHERE idUtilizador = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("ssssssi", $nome, $email, $contacto, $imagemPerfil, $hashedPassword, $salt, $idUtilizador);
        } else {
            $updateSql = "UPDATE utilizador SET nome = ?, email = ?, contacto = ?, imagemPerfil = ? WHERE idUtilizador = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("ssssi", $nome, $email, $contacto, $imagemPerfil, $idUtilizador);
        }

        if ($stmt->execute()) {
            $mensagem = "Informações atualizadas com sucesso!";
        } else {
            $erro = "Erro ao atualizar informações!";
        }

        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil</title>
    <link rel="stylesheet" href="perfil.css">
</head>

<body>
    <div class="container">
        <div class="perfil-box">
            <?php if (isset($mensagem)): ?>
                <p style="color:lightgreen; font-weight:bold;"><?php echo $mensagem; ?></p>
            <?php endif; ?>
            <?php if (isset($erro)): ?>
                <p style="color:red; font-weight:bold;"><?php echo $erro; ?></p>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data">
                <h1><?php echo htmlspecialchars($utilizador['username']); ?></h1>
                <div class="imagemPerfil">
                    <?php
                    if ($utilizador['imagemPerfil']) {
                        echo '<img src="data:image/jpeg;base64,' . base64_encode($utilizador['imagemPerfil']) . '" alt="Foto de Perfil" style="width: 270px; height: 270px; border-radius: 50%; object-fit: cover;">';
                    } else {
                        echo 'Sem imagem de perfil.';
                    }
                    ?>
                </div>
                <input type="file" id="imagemPerfil" name="imagemPerfil" accept="image/png, image/jpeg">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($utilizador['nome']); ?>"
                    required>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email"
                    value="<?php echo htmlspecialchars($utilizador['email']); ?>" required>

                <label for="contacto">Contacto:</label>
                <input type="text" id="contacto" name="contacto"
                    value="<?php echo htmlspecialchars($utilizador['contacto']); ?>" required>

                <label for="novaPassword">Nova palavra-passe (opcional):</label>
                <input type="password" id="novaPassword" name="novaPassword">

                <button type="submit">Guardar alterações</button>

                <a class="voltar" href="../index.php">◄ Voltar</a>
            </form>
        </div>
    </div>
</body>

</html>