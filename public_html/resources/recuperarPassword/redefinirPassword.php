<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Password</title>
    <link rel="stylesheet" href="redefinirPassword.css">
</head>

<body>
    <div class="container">
        <div class="login-box">
            <h1>Redefinir Password</h1>
            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">
                <label for="new_password">Nova Password:</label>
                <input type="password" id="new_password" name="new_password" required>
                <button type="submit">Redefinir</button>
            </form>
        </div>
    </div>

    <?php
    require_once '../conexao.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
        $token = $_POST['token'];
        $new_password = $_POST['new_password'];

        $conn = getDatabaseConnection();

        // Verifica se o token é válido e não expirou
        $sql = "SELECT * FROM utilizador WHERE resetPasswordToken = ? AND expirePasswordToken > NOW()";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        $verificacaoPassword = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";
        if (!preg_match($verificacaoPassword, $new_password)) {
            echo "<p style='color:red;text-align:center;'>Password deve conter pelo menos 8 caracteres, uma letra maiúscula, uma letra minúscula, um número e um caracter especial!</p>";
        } else if ($result->num_rows > 0) {

            $user = $result->fetch_assoc();

            // Gera um novo salt e encripta a nova senha
            $salt = bin2hex(random_bytes(10));
            $hashedPassword = password_hash($salt . $new_password, PASSWORD_BCRYPT);

            // Atualiza a senha e limpa o token
            $update_sql = "UPDATE utilizador SET password = ?, salt = ?, resetPasswordToken = NULL, expirePasswordToken = NULL WHERE resetPasswordToken = ?";
            $stmt_update = $conn->prepare($update_sql);
            $stmt_update->bind_param("sss", $hashedPassword, $salt, $token);
            $stmt_update->execute();

            echo "<p style='color:green;text-align:center;'>Senha redefinida com sucesso. Você pode fazer login agora.</p>";
        } else {
            echo "<p style='color:red;text-align:center;'>Token inválido ou expirado.</p>";
        }
    }
    ?>
</body>

</html>