<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 2em auto;
            max-width: 500px;
            padding: 1em;
            background-color: #f9f9f9;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h1 {
            text-align: center;
            color: #333;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1em;
        }

        label {
            font-weight: bold;
        }

        input,
        select,
        button {
            padding: 0.5em;
            font-size: 1em;
        }

        button {
            background-color: #ff4081;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #e0336f;
        }
    </style>

</head>

<body>
    <h1>Register</h1>
    <form method="POST" action="" onsubmit="return validatePassword();">

        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <label for="nome">Nome:</label>
        <input type="text" id="nome" name="nome" required value="<?php echo isset($_POST['nome']) ? htmlspecialchars($_POST['nome']) : ''; ?>">

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">

        <label for="contacto">Contacto:</label>
        <input type="text" id="contacto" name="contacto" required value="<?php echo isset($_POST['contacto']) ? htmlspecialchars($_POST['contacto']) : ''; ?>">

        <label for="imagemPerfil">Imagem de Perfil:</label>
        <input type="file" id="imagemPerfil" name="imagemPerfil">

        <button type="submit" name="submit">Register</button>
    </form>

    <?php
    require_once '../conexao.php';

    $conn = getDatabaseConnection();
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
        // Dados do formulário

        $username = $_POST['username'];
        $password = $_POST['password'];
        $nome = $_POST['nome'];
        $email = $_POST['email'];
        $contacto = $_POST['contacto'];
        $cargo = "Professor";
        $imagemPerfil = NULL;

        // Geração de "sal" e encriptação da senha
        $salt = bin2hex(random_bytes(10)); // Gera um sal único de 8 bytes
        $hashedPassword = password_hash($salt . $password, PASSWORD_BCRYPT);

        // Verificar se o número ou login já existe
        $check_sql = "SELECT * FROM utilizador WHERE username = ?";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("s", $username);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        $check_sql = "SELECT * FROM utilizador WHERE email = ?";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("s", $email);
        $stmt_check->execute();
        $result1 = $stmt_check->get_result();

        $verificacaoPassword = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/";

        if ($result->num_rows > 0) {
            echo "<p style='color:red;text-align:center;'>Username já está em uso!</p>";
        } else if ($result1->num_rows > 0) {
            echo "<p style='color:red;text-align:center;'>Email já está em uso!</p>";
        } else if (!preg_match($verificacaoPassword, $password)) {
            echo "<p style='color:red;text-align:center;'>Password deve conter pelo menos 8 caracteres, uma letra maiúscula, uma letra minúscula, um número e um caracter especial!</p>";
        } else {
            // Inserção dos dados
            $sql = "INSERT INTO utilizador (username, password, nome, email, contacto, cargo, imagemPerfil, salt) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssss", $username, $hashedPassword, $nome, $email, $contacto, $cargo, $imagemPerfil, $salt);

            if ($stmt->execute()) {
                echo "<script>
                        window.location.href = '../index.php';
                        alert('Registo efetuado com sucesso!');
                      </script>";
            } else {
                echo "<p style='color:red;text-align:center;'>Erro: " . $stmt->error . "</p>";
            }

            $stmt->close();
        }

        $stmt_check->close();
        $conn->close();
    }
    ?>
</body>

</html</script>>