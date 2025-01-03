<?php
require_once '../conexao.php';
require '../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = ""; // Inicializa a variável da mensagem

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    $conn = getDatabaseConnection();

    $sql = "SELECT * FROM utilizador WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        $token = bin2hex(random_bytes(30));
        $tokenHASH = password_hash($token, PASSWORD_DEFAULT);
        $expiration = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $update_sql = "UPDATE utilizador SET resetPasswordToken = ?, expirePasswordToken = ? WHERE email = ?";
        $stmt_update = $conn->prepare($update_sql);
        $stmt_update->bind_param("sss", $tokenHASH, $expiration, $email);
        $stmt_update->execute();

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'projetorocular@gmail.com';
            $mail->Password = 'hnsj hewy rlsy mujh';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('jr.bricolage-suporte@hotmail.com', 'SAW');
            $mail->addAddress($email, $user['nome']);

            $reset_link = "https://saw.pt/recuperarPassword/redefinirPassword.php?token=$token";
            $mail->CharSet = 'UTF-8';
            $mail->isHTML(true);
            $mail->Subject = "Recuperação de password";
            $mail->Body = "
                <p>Olá, <strong>" . htmlspecialchars($user['nome']) . "</strong>!</p>
                <p>Clique no link abaixo para redefinir a sua password. Este link é válido por 1 hora:</p>
                <p><a href='$reset_link'>$reset_link</a></p>
                <p>Se você não solicitou a redefinição, ignore este email.</p>
                <p>Obrigado!</p>
            ";

            $mail->send();
            $message = "<p class='message success'>Se o email estiver associado a uma conta, receberá um email com instruções para redefinir a password.</p>";
        } catch (Exception $e) {
            $message = "<p class='message error'>Erro ao enviar o email. Verifique as configurações: {$mail->ErrorInfo}</p>";
        }
    } else {
        $message = "<p class='message success'>Se o email estiver associado a uma conta, receberá um email com instruções para redefinir a password.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Password</title>
    <link rel="stylesheet" href="recuperarPassword.css">
    <style>
        .message {
            max-width: 80%;
            margin: 10px auto;
            text-align: center;
            word-wrap: break-word;
        }

        .message.success {
            color: green;
        }

        .message.error {
            color: red;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="login-box">
            <h1>Recuperar password</h1>
            <form method="POST" action="">
                <label for="email">Insira seu email de recuperação de conta:</label>
                <input type="email" id="email" name="email" required>
                <button type="submit">Enviar</button>
                <a class="voltar" href="../login/login.php ">◄ Voltar</a>
            </form>
            <?php echo $message; ?>
            
        </div>
    </div>
</body>

</html>
