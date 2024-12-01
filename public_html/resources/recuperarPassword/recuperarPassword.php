<?php
require_once '../conexao.php';
require '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $conn = getDatabaseConnection();

    // Verifica se o email existe no banco de dados
    $sql = "SELECT * FROM utilizador WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Gera um token e define a validade (1 hora)
        $token = bin2hex(random_bytes(30));
        $expiration = date("Y-m-d H:i:s", strtotime("+1 hour"));

        // Salva o token na bd
        $update_sql = "UPDATE utilizador SET resetPasswordToken = ?, expirePasswordToken = ? WHERE email = ?";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("sss", $token, $expiration, $email);
        $stmt_update->execute();

        // Configuração do email
        $mail = new PHPMailer(true);

        try {
            // Configurações do servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; // Servidor SMTP para Hotmail/Outlook
            $mail->SMTPAuth = true;
            $mail->Username = 'projetorocular@gmail.com'; // Insira seu email Hotmail
            $mail->Password = 'hnsj hewy rlsy mujh'; //  password original do email JRbricolage! app pass: smnr akqt gsot jjid
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Configuração do remetente e destinatário
            $mail->setFrom('jr.bricolage-suporte@hotmail.com', 'SAW');
            $mail->addAddress($email, $user['nome']);

            // Conteúdo do email
            $reset_link = "https://saw.pt/recuperarPassword/redefinirPassword.php?token=$token";
            $mail->isHTML(true);
            $mail->Subject = 'Recuperação de Password';
            $mail->Body = "
                <p>Olá, <strong>" . htmlspecialchars($user['nome']) . "</strong>!</p>
                <p>Clique no link abaixo para redefinir a sua password. Este link é válido por 1 hora:</p>
                <p><a href='$reset_link'>$reset_link</a></p>
                <p>Se você não solicitou a redefinição, ignore este email.</p>
                <p>Obrigado!</p>
            ";

            // Envia o email
            $mail->send();
            echo "<p style='color:green;text-align:center;'>Um email de recuperação foi enviado para $email.</p>";
        } catch (Exception $e) {
            echo "<p style='color:red;text-align:center;'>Erro ao enviar o email. Verifique as configurações: {$mail->ErrorInfo}</p>";
        }
    } else {
        echo "<p style='color:red;text-align:center;'>Email não encontrado.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Password</title>
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
        input, button {
            padding: 0.5em;
            font-size: 1em;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <h1>Recuperar Password</h1>
    <form method="POST" action="">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <button type="submit">Enviar</button>
    </form>
</body>
</html>