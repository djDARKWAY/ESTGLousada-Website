<?php
error_reporting(0);
include('../header/header.php');
require_once '../conexao.php';

$conn = getDatabaseConnection();

if (!isset($_SESSION['idUtilizador'])) {
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

$whereClauses[] = "reserva.idUtilizador = ?";
$params[] = $_SESSION['idUtilizador'];
$types = "i";

// Filtro de estado das reservas (Ativas, Históricas, Todos)
if (!empty($_GET['estadoReserva']) && $_GET['estadoReserva'] != 'TODOS') {
    if ($_GET['estadoReserva'] == 'Ativas') {
        $whereClauses[] = "reserva.dataReserva >= CURDATE()";
    } elseif ($_GET['estadoReserva'] == 'Histórico') {
        $whereClauses[] = "reserva.dataReserva < CURDATE()";
    }
}

// Filtro de nome da sala
if (!empty($_GET['nomeSala'])) {
    $whereClauses[] = "sala.nome LIKE ?";
    $params[] = "%" . $_GET['nomeSala'] . "%";
    $types .= "s";
}

// Filtro de data da reserva
if (!empty($_GET['dataReserva'])) {
    $whereClauses[] = "reserva.dataReserva = ?";
    $params[] = $_GET['dataReserva'];
    $types .= "s";
}

$whereSQL = "";
if ($whereClauses) {
    $whereSQL = "WHERE " . implode(" AND ", $whereClauses);
}

$sql = "SELECT 
sala.nome AS nomeSala, reserva.dataReserva, reserva.horaInicio, reserva.horaFim , reserva.idReserva, reserva.idUtilizador, reserva.idSala, reserva.estado
FROM reserva 
JOIN sala ON reserva.idSala = sala.idSala $whereSQL ORDER BY dataReserva DESC, horaFim DESC";

$stmt = $conn->prepare($sql);

if ($types) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$reservas = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservas</title>
    <link rel="stylesheet" href="minhaReserva.css">
</head>

<body>

    <div class="container">
        <h1>Minhas Reservas</h1>

        <div class="filters-container">
            <form method="GET" id="filtersForm">
                <div class="filters">
                    <p>Filtros de pesquisa</p>
                    <div class="filter-row">
                        <div class="filter-item">
                            <label for="nomeSala">Sala</label>
                            <input type="text" name="nomeSala"
                                value="<?php echo htmlspecialchars($_GET['nomeSala'] ?? ''); ?>">
                        </div>

                        <div class="filter-item">
                            <label for="dataReserva">Data</label>
                            <input type="date" name="dataReserva"
                                value="<?php echo htmlspecialchars($_GET['dataReserva'] ?? ''); ?>">
                        </div>

                        <div class="filter-item">
                            <label for="estadoReserva">Estado</label>
                            <select name="estadoReserva" id="estadoReserva" onchange="this.form.submit()">
                                <option value="TODOS" <?php echo ($_GET['estadoReserva'] ?? 'TODOS') == 'TODOS' ? 'selected' : ''; ?>>Todos</option>
                                <option value="Ativas" <?php echo ($_GET['estadoReserva'] ?? '') == 'Ativas' ? 'selected' : ''; ?>>Ativas</option>
                                <option value="Histórico" <?php echo ($_GET['estadoReserva'] ?? '') == 'Histórico' ? 'selected' : ''; ?>>Histórico</option>
                            </select>
                        </div>

                        <div class="filter-item">
                            <button type="submit">Aplicar</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="reserva-table">
            <h1>Reservas</h1>
            <table>
                <thead>
                    <tr>
                        <th>Sala</th>
                        <th>Data</th>
                        <th>Hora Início</th>
                        <th>Hora Fim</th>
                        <th>Estado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservas as $reserva): ?>
                        <tr>
                            <td><?php echo $reserva['nomeSala']; ?></td>
                            <td><?php echo $reserva['dataReserva']; ?></td>
                            <td><?php echo $reserva['horaInicio']; ?></td>
                            <td><?php echo $reserva['horaFim']; ?></td>
                            <td><?php echo $reserva['estado']; ?></td>
                            <td>
                                <?php if ($reserva['dataReserva'] > date('Y-m-d')): ?>
                                    <a class="btn btn-secondary"
                                        href="editarReserva.php?idReserva=<?php echo $reserva['idReserva']; ?>&idSala=<?php echo $reserva['idSala']; ?>">Editar</a>
                                    <a class="btn" href="?eliminar=<?php echo $reserva['idReserva']; ?>"
                                        onclick="return confirm('Tem a certeza que deseja eliminar?')">Eliminar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</body>

</html>