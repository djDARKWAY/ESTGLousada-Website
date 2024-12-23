<?php
error_reporting(0);
require_once '../conexao.php';

$conn = getDatabaseConnection();

if (!isset($_SESSION['cargo']) || $_SESSION['cargo'] !== 'Administrador') {
    header('Location: ../login/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $tipo = $_POST['tipo'];
    $descricao = $_POST['descricao'];
    $capacidade = isset($_POST['capacidade']) ? intval($_POST['capacidade']) : null;
    $estado = $_POST['estado'];

    if (empty($nome) || empty($tipo) || empty($estado)) {
        $erro = "Preencha todos os campos obrigatórios.";
    } else {
        $sql = "INSERT INTO sala (nome, tipo, descricao, capacidade, estado) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssis", $nome, $tipo, $descricao, $capacidade, $estado);

        if ($stmt->execute()) {
            $sucesso = "Sala adicionada com sucesso!";
        } else {
            $erro = "Erro ao adicionar a sala. Por favor, tente novamente.";
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adicionar Sala</title>
    <link rel="stylesheet" href="adicionarSala.css">
</head>

<body>
    <div class="container">
        <div class="sala-box">
            <h1>Adicionar Sala</h1>
            <form method="POST" action="">
                <?php if (isset($sucesso)): ?>
                    <p style="color:lightgreen; font-weight:bold;"><?php echo $sucesso; ?></p>
                <?php endif; ?>
                <?php if (isset($erro)): ?>
                    <p style="color:red; font-weight:bold;"><?php echo $erro; ?></p>
                <?php endif; ?>
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required>
                <label for="tipo">Tipo:</label>
                <select id="tipo" name="tipo" required>
                    <?php
                    $tipos = ['Arte', 'Auditório', 'Biblioteca', 'Informática', 'Laboratório', 'Mecânica', 'Multimédia', 'Música', 'Outros', 'Pavilhão', 'Reunião', 'Teórica'];
                    foreach ($tipos as $tipo) {
                        echo "<option value='$tipo'>$tipo</option>";
                    }
                    ?>
                </select>
                <label for="descricao">Descrição:</label>
                <textarea id="descricao" name="descricao" rows="4"></textarea>
                <label for="capacidade">Capacidade:</label>
                <input type="number" id="capacidade" name="capacidade" min="1" >
                <label for="estado">Estado:</label>
                <select id="estado" name="estado" required>
                    <option value="Disponível">Disponível</option>
                    <option value="Indisponível">Indisponível</option>
                    <option value="Brevemente">Brevemente</option>
                </select>
                <button type="submit" name="submit">Adicionar Sala</button>
            </form>
        </div>
    </div>
</body>
</html>
