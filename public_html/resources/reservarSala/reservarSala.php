<?php
error_reporting(0);
session_start();

include('../conexao.php');
$conn = getDatabaseConnection();

if (!isset($_GET['idSala']) || empty($_GET['idSala'])) {
    die("Sala não especificada.");
}

$idSala = $_GET['idSala'];

$sql = "SELECT idSala, nome, tipo, descricao, capacidade, estado FROM sala WHERE idSala = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $idSala);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Sala não encontrada.");
}

$sala = $result->fetch_assoc();

$dataReserva = isset($_GET['dataReserva']) ? $_GET['dataReserva'] : date('Y-m-d');

$sqlReservas = "SELECT TIME_FORMAT(horaInicio, '%H:%i') AS horaInicio, TIME_FORMAT(horaFim, '%H:%i') AS horaFim FROM reserva WHERE idSala = ? AND dataReserva = ?";
$stmtReservas = $conn->prepare($sqlReservas);
$stmtReservas->bind_param("is", $idSala, $dataReserva);
$stmtReservas->execute();
$reservasResult = $stmtReservas->get_result();

$reservas = [];
while ($row = $reservasResult->fetch_assoc()) {
    $horaInicio = $row['horaInicio'];
    $horaFim = $row['horaFim'];

    $horaAtual = strtotime($horaInicio);
    while ($horaAtual < strtotime($horaFim)) {
        $reservas[] = date('H:i', $horaAtual);
        $horaAtual = strtotime("+1 hour", $horaAtual);
    }
}

$stmtReservas->close();

function getSalaImage($tipo)
{
    $imagens = [
        'Informática' => '../media/salaInformatica.jpg',
        'Auditório' => '../media/salaAuditorio.jpg',
        'Arte' => '../media/salaArte.jpg',
        'Biblioteca' => '../media/salaBiblioteca.jpg',
        'Laboratório' => '../media/salaLaboratorio.jpg',
        'Mecânica' => '../media/salaMecanica.jpg',
        'Multimédia' => '../media/salaMultimedia.jpg',
        'Música' => '../media/salaMusica.jpg',
        'Pavilhão' => '../media/salaPavilhao.jpg',
        'Reunião' => '../media/salaReuniao.jpg',
        'Teórica' => '../media/salaTeorica.jpg'
    ];
    return isset($imagens[$tipo]) ? $imagens[$tipo] : '../media/salaDefault.png';
}
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Sala - <?php echo htmlspecialchars($sala['nome']); ?></title>
    <link rel="stylesheet" href="reservarSala.css">
</head>

<body>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="logo">
                <img class="PPorto" src="../media/logoPPorto.png">
                Gestão de salas ESTG
            </div>
            <div class="nav-links">
                <a href="/index.php">Página Principal</a>
                <?php if (isset($_SESSION['cargo'])): ?>
                    <a href="/perfil/perfil.php">Perfil</a>
                    <a href="/logout.php">Logout</a>
                <?php else: ?>
                    <a href="login/login.php">Login</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container">
        <div class="room-details">
            <h1><?php echo htmlspecialchars($sala['nome']); ?></h1>
            <div class="image-container">
                <img src="<?php echo getSalaImage($sala['tipo']); ?>" alt="Imagem da sala">
            </div>
            <div class="info">
                <p><strong>Tipo:</strong> <?php echo htmlspecialchars($sala['tipo']); ?></p>
                <p><strong>Capacidade:</strong> <?php echo htmlspecialchars($sala['capacidade']); ?></p>
                <p><strong>Descrição:</strong> <?php echo htmlspecialchars($sala['descricao']); ?></p>
                <p><strong>Estado:</strong> <?php echo htmlspecialchars($sala['estado']); ?></p>
            </div>
        </div>

        <div class="reservations">
            <div class="date-picker">
                <label for="dataReserva">Data:</label>
                <input type="date" id="dataReserva" value="<?php echo $dataReserva; ?>" onchange="updateDataReserva()">
            </div>

            <div class="table-container">
                <div class="morning-afternoon">
                    <h2>Manhã</h2>
                    <table class="time-table">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Disponibilidade</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $horasManha = range(7, 14);
                            foreach ($horasManha as $hora) {
                                $horaFormatada = str_pad($hora, 2, '0', STR_PAD_LEFT) . ":00";
                                $horaReservada = in_array($horaFormatada, $reservas);

                                echo "<tr>";
                                echo "<td>" . $horaFormatada . "</td>";

                                if ($horaReservada) {
                                    echo "<td>Reservado</td>";
                                    echo "<td><input type='checkbox' disabled></td>";
                                } else {
                                    echo "<td>Disponível</td>";
                                    echo "<td><input type='checkbox' class='checkbox' data-id-sala='$idSala' data-hora='$horaFormatada'></td>";
                                }

                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>

                    <h2>Tarde</h2>
                    <table class="time-table">
                        <thead>
                            <tr>
                                <th>Hora</th>
                                <th>Disponibilidade</th>
                                <th>Ação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $horasTarde = range(15, 22);
                            foreach ($horasTarde as $hora) {
                                $horaFormatada = str_pad($hora, 2, '0', STR_PAD_LEFT) . ":00";
                                $horaReservada = in_array($horaFormatada, $reservas);

                                echo "<tr>";
                                echo "<td>" . $horaFormatada . "</td>";

                                if ($horaReservada) {
                                    echo "<td>Reservado</td>";
                                    echo "<td><input type='checkbox' disabled></td>";
                                } else {
                                    echo "<td>Disponível</td>";
                                    echo "<td><input type='checkbox' class='checkbox' data-id-sala='$idSala' data-hora='$horaFormatada'></td>";
                                }

                                echo "</tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div>
                <button class="btn" onclick="confirmarReserva()">Reservar Sala</button>
            </div>
        </div>
    </main>

    <script>
        function updateDataReserva() {
            var dataReserva = document.getElementById('dataReserva').value;
            window.location.href = "?idSala=<?php echo $idSala; ?>&dataReserva=" + dataReserva;
        }

        function confirmarReserva() {
            var checkboxes = document.querySelectorAll('.checkbox:checked');
            if (checkboxes.length > 0) {
                var horariosSelecionados = [];
                checkboxes.forEach(function (checkbox) {
                    horariosSelecionados.push(checkbox.getAttribute('data-hora'));
                });

                if (confirm("Tem a certeza de que deseja reservar para as seguintes horas: " + horariosSelecionados.join(', ') + "?")) {
                    var idSala = "<?php echo $idSala; ?>";
                    window.location.href = "/reservarSala/reservarSala.php?idSala=" + idSala + "&horarios=" + horariosSelecionados.join(',');
                }
            } else {
                alert("Selecione ao menos uma hora para reservar.");
            }
        }
    </script>
</body>

</html>


<?php
$stmt->close();
$conn->close();
?>