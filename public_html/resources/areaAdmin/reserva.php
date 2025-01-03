<?php
error_reporting(0);
include('../header/header.php');
require_once '../conexao.php';

$conn = getDatabaseConnection();

if (!isset($_SESSION['cargo']) || $_SESSION['cargo'] !== 'Administrador') {
    header('Location: ../login/login.php');
    exit();
}

// Eliminar reserva
if (isset($_GET['eliminar'])) {
    $idReserva = (int) $_GET['eliminar'];
    $stmt = $conn->prepare("UPDATE `reserva` SET `estado` = 'Cancelada' WHERE idReserva = ?");
    $stmt->bind_param("i", $idReserva);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

$whereClauses = [];
$params = [];
$types = "";

// Filtrar por ID da reserva
if (!empty($_GET['idReserva'])) {
    $whereClauses[] = "reserva.idReserva = ?";
    $params[] = (int) $_GET['idReserva'];
    $types .= "i";
}

if (!empty($_GET['username'])) {
    $whereClauses[] = "utilizador.username LIKE ?";
    $params[] = "%" . $_GET['username'] . "%";
    $types .= "s";
}

if (!empty($_GET['nomeSala'])) {
    $whereClauses[] = "sala.nome LIKE ?";
    $params[] = "%" . $_GET['nomeSala'] . "%";
    $types .= "s";
}


if (!empty($_GET['dataReserva'])) {
    $whereClauses[] = "reserva.dataReserva = ?";
    $params[] = $_GET['dataReserva'];
    $types .= "s";
}

if (!empty($_GET['estado'])) {
    $whereClauses[] = "reserva.estado = ?";
    $params[] = $_GET['estado'];
    $types .= "s";
}

$whereSQL = "";
if ($whereClauses) {
    $whereSQL = "WHERE " . implode(" AND ", $whereClauses);
}

// Query com JOIN para exibir nomes
$sql = "
    SELECT reserva.*, utilizador.username, sala.nome AS nomeSala
    FROM reserva
    INNER JOIN utilizador ON reserva.idUtilizador = utilizador.idUtilizador
    INNER JOIN sala ON reserva.idSala = sala.idSala
    $whereSQL ORDER BY dataReserva DESC, horaFim DESC
";

$stmt = $conn->prepare($sql);

if ($types) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas</title>
    <link rel="stylesheet" href="reserva.css">
</head>

<body>

    <div class="container">
        <h1>Gestão de Reservas</h1>

        <div class="filters-container">
            <form method="GET" id="filtersForm">
                <div class="filters">
                    <p>Filtros de pesquisa</p>
                    <div class="filter-row">
                        <div class="filter-item">
                            <label for="idReserva">ID</label>
                            <input type="text" name="idReserva"
                                value="<?php echo htmlspecialchars($_GET['idReserva'] ?? ''); ?>">
                        </div>

                        <div class="filter-item">
                            <label for="username">Utilizador</label>
                            <input type="text" name="username"
                                value="<?php echo htmlspecialchars($_GET['username'] ?? ''); ?>">
                        </div>

                        <div class="filter-item">
                            <label for="nomeSala">Sala</label>
                            <input type="text" name="nomeSala"
                                value="<?php echo htmlspecialchars($_GET['nomeSala'] ?? ''); ?>">
                        </div>

                        <div class="filter-item">
                            <label for="estado">Estado</label>
                            <select name="estado">
                                <option value="">Todos</option>
                                <option value="Confirmada" <?php echo ($_GET['estado'] ?? '') === 'Confirmada' ? 'selected' : ''; ?>>
                                    Confirmada</option>
                                <option value="Cancelada" <?php echo ($_GET['estado'] ?? '') === 'Cancelada' ? 'selected' : ''; ?>>
                                    Cancelada</option>
                            </select>
                        </div>
                        

                        <div class="date-filter">
                            <label for="dataReserva">Data</label>
                            <input type="date" name="dataReserva"
                                value="<?php echo htmlspecialchars($_GET['dataReserva'] ?? ''); ?>">
                        </div>

                        <div class="filter-item">
                            <button type="submit">Aplicar</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="reserva-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Utilizador</th>
                        <th>Sala</th>
                        <th>Data</th>
                        <th>Hora Início</th>
                        <th>Hora Fim</th>
                        <th>Estado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($reserva = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $reserva['idReserva']; ?></td>
                            <td><?php echo $reserva['username']; ?></td>
                            <td><?php echo $reserva['nomeSala']; ?></td>
                            <td><?php echo $reserva['dataReserva']; ?></td>
                            <td><?php echo $reserva['horaInicio']; ?></td>
                            <td><?php echo $reserva['horaFim']; ?></td>
                            <td><?php echo $reserva['estado']; ?></td>
                            <td>
                                <?php if (($reserva['dataReserva'] > date('Y-m-d')) && ($reserva['estado'] === 'Confirmada')): ?>
                                <a class="btn btn-secondary"
                                    href="editarReserva.php?idReserva=<?php echo $reserva['idReserva']; ?>">Editar</a>
                                <a class="btn" href="?eliminar=<?php echo $reserva['idReserva']; ?>"
                                    onclick="return confirm('Tem a certeza que deseja eliminar?')">Eliminar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>