<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../conexao.php';
require_once '../logs.php';

$conn = getDatabaseConnection();


$idReserva = (int)$_GET['idReserva'];

$sqlAdmin = "SELECT username FROM utilizador WHERE idUtilizador = ?";
$stmtAdmin = $conn->prepare($sqlAdmin);
$stmtAdmin->bind_param("i", $_SESSION['idUtilizador']);
$stmtAdmin->execute();
$username = $stmtAdmin->get_result()->fetch_assoc()['username'];

if (!isset($_SESSION['idUtilizador'])) {
    header("Location: ../login/login.php");
    exit();
} else if ($_SESSION['cargo'] !== "Administrador") {
    writeAdminLog("Utilizador '$username' tentou aceder à edição de uma reserva (editarReserva.php) com o ID '$idReserva'.");
    header ("Location: ../error.php?code=403&message=Você não tem permissão para acessar esta área.");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idReserva = (int)$_POST['idReserva'];
    $reservas = json_decode($_POST['reservas'], true);

    $sqlIdSala = "SELECT idSala, dataReserva FROM reserva WHERE idReserva = ?";
    $stmtIdSala = $conn->prepare($sqlIdSala);
    $stmtIdSala->bind_param("i", $idReserva);
    $stmtIdSala->execute();
    $resultIdSala = $stmtIdSala->get_result();

    $reservaInfo = $resultIdSala->fetch_assoc();
    $idSala = $reservaInfo['idSala'];
    $dataReserva = $reservaInfo['dataReserva'];
    $stmtIdSala->close();

    if (count($reservas) === 1) {
        $sqlUpdate = "UPDATE reserva SET horaInicio = ?, horaFim = ? WHERE idReserva = ?";
        $stmtUpdate = $conn->prepare($sqlUpdate);
        $stmtUpdate->bind_param("ssi", $reservas[0]['horaInicio'], $reservas[0]['horaFim'], $idReserva);
        if ($stmtUpdate->execute()) {
            writeAdminLog("Administrador '$username' editou a reserva com o ID '$idReserva' para o horário " . $reservas[0]['horaInicio'] . " - " . $reservas[0]['horaFim'] . ".");
        } else {
            writeAdminLog("Administrador '$username' tentou editar a reserva com o ID '$idReserva', mas ocorreu um erro: " . $stmtUpdate->error);
            echo json_encode(['success' => false, 'message' => 'Erro ao editar a reserva.']);
            exit();
        }
        
        $stmtUpdate->close();
    } elseif (count($reservas) > 1) {
        writeAdminLog("O administrador '$username' tentou editar a reserva com o ID '$idReserva', mas ela contém múltiplos horários separados por horas.");
        echo json_encode(['success' => false, 'message' => 'Não é possível reservar mais de um horário separado por horas.']);
        exit();
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit();
}

$sqlReserva = "SELECT dataReserva, idSala, idUtilizador, horaInicio, horaFim, estado FROM reserva WHERE idReserva = ?";
$stmtReserva = $conn->prepare($sqlReserva);
$stmtReserva->bind_param("i", $idReserva);
$stmtReserva->execute();
$resultReserva = $stmtReserva->get_result();

if (!isset($_GET['idReserva']) || ( $_GET['idReserva'] === "")) {
    writeAdminLog("Administrador '$username' tentou aceder à página de editar sala sem especificar o ID da reserva '$idReserva'.");
    header("Location: ../error.php?code=400&message=Erro ao processar a reserva. Por favor, tente novamente.");
    exit();
} else if ($resultReserva->num_rows === 0) {
    writeAdminLog("Administrador '$username' tentou aceder à página de editar sala, onde a reserva com ID '$idReserva' não foi encontrada.");
    header("Location: ../error.php?code=404&message=Reserva não foi encontrada.");
    exit();
} 

$reserva = $resultReserva->fetch_assoc();
$idUtilizador = $reserva['idUtilizador'];
$idSala = $reserva['idSala'];
$dataReserva = $reserva['dataReserva'];
$estado = $reserva['estado'];
$stmtReserva->close();

$sqlSala = "SELECT idSala, nome, tipo, descricao, capacidade, estado FROM sala WHERE idSala = ?";
$stmtSala = $conn->prepare($sqlSala);
$stmtSala->bind_param("i", $idSala);
$stmtSala->execute();
$resultSala = $stmtSala->get_result();


if (($dataReserva <= date('Y-m-d')) || ($estado !== 'Confirmada')) {
    writeAdminLog("Administrador '$username' tentou editar a reserva com o ID '$idReserva', mas a reserva é passada ou cancelada.");
    header('Location: ../error.php?code=403&message=Não é possível editar reservas passadas ou canceladas.');
    exit();
}

$sala = $resultSala->fetch_assoc();
$stmtSala->close();

$sqlReservas = "SELECT TIME_FORMAT(horaInicio, '%H:%i') AS horaInicio, TIME_FORMAT(horaFim, '%H:%i') AS horaFim, idUtilizador, idReserva 
                FROM reserva 
                WHERE idSala = ? AND dataReserva = ? AND estado = 'Confirmada'";
$stmtReservas = $conn->prepare($sqlReservas);
$stmtReservas->bind_param("is", $idSala, $dataReserva);
$stmtReservas->execute();
$reservasResult = $stmtReservas->get_result();

$reservas = [];
while ($row = $reservasResult->fetch_assoc()) {
    $horaAtual = strtotime($row['horaInicio']);
    $horaFim = strtotime($row['horaFim']);
    $userId = $row['idUtilizador'];
    $reservaId = $row['idReserva'];

    while ($horaAtual < $horaFim) {
        $reservas[date('H:i', $horaAtual)] = [
            'userId' => $userId,
            'reservaId' => $reservaId
        ];
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
    return $imagens[$tipo] ?? '../media/salaDefault.png';
}
?>


<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes da Sala - <?php echo htmlspecialchars($sala['nome']); ?></title>
    <link rel="stylesheet" href="editarReserva.css">
</head>

<body>
    <?php include('../header/header.php'); ?>
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
            </div>
        </div>

        <div class="reservations">
            <div class="date-picker">
                <label for="dataReserva">Data:</label>
                <input type="date" id="dataReserva" value="<?php echo $dataReserva; ?>" disabled ?>
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
                                $horaReservada = array_key_exists($horaFormatada, $reservas);

                                echo "<tr>";
                                echo "<td>" . $horaFormatada . "</td>";

                                if ($horaReservada) {
                                    $proprietario = $reservas[$horaFormatada]['userId'];
                                    $reservaId = $reservas[$horaFormatada]['reservaId'];

                                    if ($proprietario == $idUtilizador && $reservaId == $idReserva) {
                                        echo "<td style='color: blue;font-weight: bold;'>Reservado (Esta Reserva)</td>";
                                        echo "<td><input type='checkbox' class='checkbox' data-id-sala='$idSala' data-hora='$horaFormatada' checked></td>";
                                    } else if ($proprietario == $idUtilizador) {
                                        echo "<td style='color: darkred; font-weight: bold; '>Reservado (Outra Reserva deste utilizador)</td>";
                                        echo "<td><input type='checkbox' disabled checked></td>";
                                    } else {
                                        echo "<td style='color: red;'>Reservado</td>";
                                        echo "<td><input type='checkbox' disabled></td>";
                                    }
                                } else {
                                    echo "<td style='color: green;'>Disponível</td>";
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
                                $horaReservada = array_key_exists($horaFormatada, $reservas);

                                echo "<tr>";
                                echo "<td>" . $horaFormatada . "</td>";

                                if ($horaReservada) {
                                    $proprietario = $reservas[$horaFormatada]['userId'];
                                    $reservaId = $reservas[$horaFormatada]['reservaId'];

                                    if ($proprietario == $idUtilizador && $reservaId == $idReserva) {
                                        echo "<td style='color: blue;font-weight: bold;'>Reservado (Esta Reserva)</td>";
                                        echo "<td><input type='checkbox' class='checkbox' data-id-sala='$idSala' data-hora='$horaFormatada' checked></td>";
                                    } elseif ($proprietario == $idUtilizador) {
                                        echo "<td style='color: darkred; font-weight: bold; '>Reservado (Outra Reserva deste utilizador)</td>";
                                        echo "<td><input type='checkbox' disabled checked></td>";
                                    } else {
                                        echo "<td style='color: red;'>Reservado</td>";
                                        echo "<td><input type='checkbox' disabled></td>";
                                    }
                                } else {
                                    echo "<td style='color: green;'>Disponível</td>";
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
                <button class="btn" onclick="atualizarReserva()">Atualizar Reserva</button>
            </div>
        </div>
    </main>

    <script>
        function atualizarReserva() {
            var checkboxes = document.querySelectorAll('.checkbox:checked');

            if (checkboxes.length > 0) {
                var horariosSelecionados = [];
                var horaInicio = "";
                var horaFim = "";

                checkboxes.forEach(function(checkbox, index) {
                    var hora = checkbox.getAttribute('data-hora');
                    if (horaInicio === "") horaInicio = hora; // Define o início

                    // Verifica se é o último checkbox ou se não são consecutivos
                    if (
                        index === checkboxes.length - 1 ||
                        !isNextHour(checkbox, checkboxes[index + 1])
                    ) {
                        horaFim = incrementHour(hora);
                        horariosSelecionados.push({
                            horaInicio: horaInicio,
                            horaFim: horaFim,
                        });
                        horaInicio = "";
                    }
                });

                if (
                    confirm(
                        "Tem a certeza de que deseja reservar para as seguintes horas: " +
                        horariosSelecionados
                        .map((r) => r.horaInicio + " - " + r.horaFim)
                        .join(", ") +
                        "?"
                    )
                ) {
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", window.location.href, true);
                    xhr.setRequestHeader(
                        "Content-Type",
                        "application/x-www-form-urlencoded"
                    );

                    var data = new URLSearchParams();
                    data.append("idReserva", "<?php echo $idReserva; ?>");
                    data.append("reservas", JSON.stringify(horariosSelecionados));
                    data.append("idSala", "<?php echo $idSala; ?>");
                    data.append("dataReserva", document.getElementById("dataReserva").value);

                    xhr.onload = function() {
                        if (xhr.status === 200) {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                alert("Reserva atualizada com sucesso!");
                                window.location.reload();
                            } else {
                                alert(response.message || "Erro ao realizar a reserva.");
                            }
                        } else {
                            alert("Erro ao realizar a reserva. Tente novamente.");
                        }
                    };

                    xhr.send(data.toString());
                }
            } else {
                alert("Selecione ao menos uma hora para editar a reserva.");
            }
        }

        function isNextHour(currentCheckbox, nextCheckbox) {
            var currentHour = currentCheckbox.getAttribute('data-hora');
            var nextHour = nextCheckbox.getAttribute('data-hora');

            return (
                nextHour &&
                parseInt(nextHour.split(':')[0]) === parseInt(currentHour.split(':')[0]) + 1
            );
        }

        function incrementHour(hora) {
            var parts = hora.split(':');
            var hours = parseInt(parts[0]);
            var minutes = parts[1];
            hours = (hours + 1) % 24;
            return ("0" + hours).slice(-2) + ":" + minutes;
        }
    </script>

</body>

</html>