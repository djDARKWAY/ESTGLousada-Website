<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registar</title>
    <link rel="stylesheet" href="registar.css">
</head>

<body>
    <div class="container">
        <div class="register-box">
            <h1>Registo</h1>
            <form method="POST" action="" onsubmit="return validatePassword();" enctype="multipart/form-data">
                <label for="username">Utilizador:</label>
                <input type="text" id="username" name="username" required
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">

                <label for="password">Palavra-passe:</label>
                <input type="password" id="password" name="password" required>

                <label for="nome">Nome completo:</label>
                <input type="text" id="nome" name="nome" required
                    value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

                <label for="contacto">Contacto:</label>
                <input type="text" id="contacto" name="contacto" required
                    value="<?php echo isset($_POST['contacto']) ? htmlspecialchars($_POST['contacto']) : ''; ?>">

                <label for="imagemPerfil">Imagem de perfil (opcional):</label>
                <input type="file" id="imagemPerfil" name="imagemPerfil">

                <button type="submit" name="submit">Registar</button>
            </form>

            <?php
            require_once '../conexao.php';
            $conn = getDatabaseConnection();

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
                $username = strtolower($_POST['username']);
                $password = $_POST['password'];
                $nome = $_POST['nome'];
                $email = strtolower($_POST['email']);
                $contacto = $_POST['contacto'];
                $cargo = "Professor";

                $salt = bin2hex(random_bytes(10));
                $hashedPassword = password_hash($salt . $password, PASSWORD_BCRYPT);

                // Verifica se o username já está em uso
                $checkSql = "SELECT * FROM utilizador WHERE username = ?";
                $stmtCheck = $conn->prepare($checkSql);
                $stmtCheck->bind_param("s", $username);
                $stmtCheck->execute();
                $result = $stmtCheck->get_result();

                // Verifica se o email já está em uso
                $checkSql = "SELECT * FROM utilizador WHERE email = ?";
                $stmtCheck = $conn->prepare($checkSql);
                $stmtCheck->bind_param("s", $email);
                $stmtCheck->execute();
                $result1 = $stmtCheck->get_result();

                $verificacaoPassword = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";

                // Validações
                if ($result->num_rows > 0) {
                    echo "<p>Username já está em uso!</p>";
                } else if ($result1->num_rows > 0) {
                    echo "<p>Email já está em uso!</p>";
                } else if (!preg_match($verificacaoPassword, $password)) {
                    echo "<p>Password deve conter pelo menos 8 caracteres...</p>";
                } else {
                    // Processamento da imagem
                    $imagemPerfil = null;
                    if (isset($_FILES['imagemPerfil']) && $_FILES['imagemPerfil']['error'] == 0) {
                        $imagemTipo = $_FILES['imagemPerfil']['type'];
                        if ($imagemTipo == 'image/png' || $imagemTipo == 'image/jpeg') {
                            $imagemPerfil = file_get_contents($_FILES['imagemPerfil']['tmp_name']);
                        } else {
                            echo "<p>Por favor, carregue uma imagem PNG ou JPEG.</p>";
                        }
                    }

                    // Insere os dados na base de dados
                    $sql = "INSERT INTO utilizador (username, password, nome, email, contacto, cargo, imagemPerfil, salt) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("ssssssss", $username, $hashedPassword, $nome, $email, $contacto, $cargo, $imagemPerfil, $salt);

                    if ($stmt->execute()) {
                        echo "<script>
                            alert('Registo efetuado com sucesso!');
                            window.location.href = '../index.php';
                          </script>";
                    } else {
                        echo "<p>Erro: " . $stmt->error . "</p>";
                    }
                    $stmt->close();
                }
                $stmtCheck->close();
                $conn->close();
            }
            ?>
        </div>
    </div>
</body>

</html>