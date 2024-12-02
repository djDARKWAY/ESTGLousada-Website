<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="login.css">
</head>

<body>
    <div class="container">
        <div class="login-box">
            <h1>Login</h1>
            <form method="POST" action="">
                <label for="username">Utilizador:</label>
                <input type="text" id="username" name="username" required>

                <label for="password">Palavra-passe:</label>
                <input type="password" id="password" name="password" required>

                <div class="checkbox-container">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Manter sessão iniciada</label>
                </div>

                <button type="submit" name="submit">Login</button>
            </form>
            <p>
                <a href="../recuperarPassword/recuperarPassword.php">Esqueceu-se da password?</a>
            </p>
        </div>
    </div>

    <?php
    require_once '../conexao.php';
    $conn = getDatabaseConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
        // Sanitização básica das entradas
        $username = filter_var($_POST['username'], FILTER_SANITIZE_STRING);
        $password = $_POST['password'];
    
        // Verificar o login
        $sql = "SELECT * FROM utilizador WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $utilizador = $result->fetch_assoc();
            $salt = $utilizador['salt'];
            $hashedPassword = $utilizador['password'];

            // Verifica se a senha fornecida corresponde à senha armazenada
            if (password_verify($salt . $password, $hashedPassword)) {
                session_start();
                $_SESSION['utilizador'] = $utilizador['idUtilizador'];

                // Verifica se o utilizador pediu para manter a sessão
                if (isset($_POST['remember'])) {
                    $token = bin2hex(random_bytes(16));
                    setcookie('remember_me', $token, time() + (1 * 24 * 60 * 60), "/");
    
                    $update_token = "UPDATE utilizador SET rememberToken = ? WHERE idUtilizador = ?";
                    $stmt_update = $conn->prepare($update_token);
                    $stmt_update->bind_param("si", $token, $utilizador['idUtilizador']);
                    $stmt_update->execute();
                }
                echo "<script>window.location.href = '../index.php';</script>";
            } else {
                echo "<p style='color:red;text-align:center;'>Erro: Password incorreta.</p>";
            }
        } else {
            echo "<p style='color:red;text-align:center;'>Erro: Login não encontrado.</p>";
        }
    }
    ?>

</body>

</html>