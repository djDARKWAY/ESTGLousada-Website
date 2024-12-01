<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <h1>Login</h1>
    <form method="POST" action="">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <div>
            <input type="checkbox" id="remember" name="remember">
            <label for="remember">Lembrar-me</label>
        </div>

        <button type="submit" name="submit">Login</button>
    </form>
    <p style="text-align:center;">
        <a href="../recuperarPassword/recuperarPassword.php">Esqueceu-se da password?</a>
    </p>

    <?php
    require_once '../conexao.php';

    $conn = getDatabaseConnection();

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
        $username = $_POST['username'];
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

            if (password_verify($salt . $password, $hashedPassword)) {
                session_start();
                $_SESSION['utilizador'] = $utilizador['idUtilizador'];

                // "Remember Me" funcionalidade
                if (isset($_POST['remember'])) {
                    $token = bin2hex(random_bytes(16));
                    setcookie('remember_me', $token, time() + (1 * 24 * 60 * 60), "/"); // Cookie válido por 1 dias

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