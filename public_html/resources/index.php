<?php
error_reporting(0);
session_start();

// Incluir o ficheiro de conexão à base de dados
include('conexao.php');
$conn = getDatabaseConnection();

// Verificar se o utilizador está autenticado via sessão ou cookie
$autenticado = false;
if (isset($_SESSION['cargo'])) {
    $autenticado = true;
} elseif (isset($_COOKIE['remember_me'])) {
    $rememberMeToken = $_COOKIE['remember_me'];

    $sql = "SELECT idUtilizador, cargo, nome FROM utilizador WHERE rememberToken = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $rememberMeToken);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Configurar a sessão com os dados do utilizador
        $_SESSION['idUtilizador'] = $user['idUtilizador'];
        $_SESSION['cargo'] = $user['cargo'];
        $_SESSION['nome'] = $user['nome'];
        $autenticado = true;
    }
}

// Eliminar sala
if (isset($_GET['eliminarSala'])) {
    $idSala = (int) $_GET['eliminarSala'];
    $stmt = $conn->prepare("DELETE FROM sala WHERE idSala = ?");
    $stmt->bind_param("i", $idSala);
    $stmt->execute();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Receber os parâmetros dos filtros
$tipoFiltro = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$capacidadeFiltro = isset($_GET['capacidade']) ? $_GET['capacidade'] : '';

// Preparar a query com placeholders
$sql = "SELECT idSala, nome, tipo, descricao, capacidade, estado FROM sala WHERE 1=1";

// Adicionar filtro de tipo, se presente
if ($tipoFiltro && $tipoFiltro !== '') {
    $sql .= " AND tipo = ?";
}

// Adicionar filtro de capacidade, se presente
if ($capacidadeFiltro) {
    $sql .= " AND capacidade >= ?";
}

// Preparar a query
$stmt = $conn->prepare($sql);

// Verificar se os parâmetros existem e associá-los corretamente
if ($tipoFiltro && $tipoFiltro !== '' && $capacidadeFiltro) {
    $stmt->bind_param("si", $tipoFiltro, $capacidadeFiltro);
} elseif ($tipoFiltro && $tipoFiltro !== '') {
    $stmt->bind_param("s", $tipoFiltro);
} elseif ($capacidadeFiltro) {
    $stmt->bind_param("i", $capacidadeFiltro);
}

// Executar a query
$stmt->execute();
$result = $stmt->get_result();
$salas = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $salas[] = $row;
    }
}

// Obter os tipos distintos de sala
$sqlTipos = "SELECT DISTINCT tipo FROM sala";
$resultTipos = $conn->query($sqlTipos);
$tipos = [];

if ($resultTipos->num_rows > 0) {
    while ($row = $resultTipos->fetch_assoc()) {
        $tipos[] = $row['tipo'];
    }
}

function verificarDisponibilidade($idSala, $conn)
{
    // Define o total de horas disponíveis no dia
    $totalHorasDia = 16;
    $dataFiltro = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d', strtotime('+1 day'));

    // Seleciona todas as reservas confirmadas para a data fornecida
    $sql = "SELECT horaInicio, horaFim FROM reserva 
            WHERE idSala = ? 
              AND estado = 'Confirmada'
              AND dataReserva = ?
              AND (
                  (horaInicio < horaFim) 
              )";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $idSala, $dataFiltro);
    $stmt->execute();
    $result = $stmt->get_result();

    $totalHorasReservadas = 0;

    // Calcula o total de horas reservadas
    while ($row = $result->fetch_assoc()) {
        // Calcula a duração da reserva em horas
        $inicio = strtotime($row['horaInicio']);
        $fim = strtotime($row['horaFim']);
        $duracaoHoras = ($fim - $inicio) / 3600; // Converte segundos para horas
        $totalHorasReservadas += $duracaoHoras;
    }

    // Verifica a disponibilidade
    $horasDisponiveis = $totalHorasDia - $totalHorasReservadas;
    return $horasDisponiveis > 0 ? 'DISPONÍVEL' : 'INDISPONÍVEL';
}

function getSalaImage($tipo)
{
    $imagens = [
        'Informática' => 'media/salaInformatica.jpg',
        'Auditório' => 'media/salaAuditorio.jpg',
        'Arte' => 'media/salaArte.jpg',
        'Biblioteca' => 'media/salaBiblioteca.jpg',
        'Laboratório' => 'media/salaLaboratorio.jpg',
        'Mecânica' => 'media/salaMecanica.jpg',
        'Multimédia' => 'media/salaMultimedia.jpg',
        'Música' => 'media/salaMusica.jpg',
        'Pavilhão' => 'media/salaPavilhao.jpg',
        'Reunião' => 'media/salaReuniao.jpg',
        'Teórica' => 'media/salaTeorica.jpg'
    ];
    return isset($imagens[$tipo]) ? $imagens[$tipo] : 'media/salaDefault.png';
}
?>

<!DOCTYPE html>
<html lang="pt-PT">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de salas - ESTG</title>
    <link rel="stylesheet" href="index.css">
</head>

<body>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="logo">
                <img class="PPorto" src="media/logoPPorto.png">
                <a href="index.php">Gestão de salas ESTG</a>
            </div>

            <div class="nav-links">
            <?php if ($_SESSION['cargo'] == 'Administrador'): ?>
                <div class="dropdown">
                    <button class="dropdown-btn">Área de administração</button>
                    <div class="dropdown-content">
                        <a href="areaAdmin/areaAdmin.php">Utilizadores</a>
                        <a href="areaAdmin/reserva.php">Reservas</a>
                    </div>
                </div>
                <a href="perfil/perfil.php">Perfil</a>
                <a href="logout.php">Logout</a>
            <?php elseif ($_SESSION['cargo'] == 'Professor'): ?>
                <a href="perfil/perfil.php">Perfil</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login/login.php">Login</a>
                <a href="registar/registar.php">Registar</a>
            <?php endif; ?>
        </div>
        </div>
    </nav>

    <main class="container">
        <div class="filters">
            <div class="filters-grid">
                <div class="filter-group">
                    <label for="tipo">Tipo de Sala</label>
                    <select id="tipo" onchange="applyFilters()">
                        <option value="">Todos os tipos</option>
                        <?php foreach ($tipos as $tipo): ?>
                            <option value="<?php echo $tipo; ?>" <?php echo $tipoFiltro == $tipo ? 'selected' : ''; ?>>
                                <?php echo $tipo; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="capacidade">Capacidade Mínima</label>
                    <input type="number" id="capacidade" min="1" onchange="applyFilters()"
                        value="<?php echo $capacidadeFiltro ? $capacidadeFiltro : 0; ?>">

                </div>
                <?php if ($autenticado): ?>
                    <div class="filter-group">
                        <label for="data">Data</label>
                        <input type="date" id="data" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                            value="<?php echo isset($_GET['data']) && $_GET['data'] >= date('Y-m-d', strtotime('+1 day')) ? $_GET['data'] : date('Y-m-d', strtotime('+1 day')); ?>"
                            onchange="applyFilters()">
                    </div>
                <?php endif; ?>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button class="btn" onclick="resetFilters()">Limpar filtros</button>
                </div>
            </div>
        </div>

        <div class="grid">
            <?php foreach ($salas as $sala): ?>
                <div class="card">
                    <img src="<?php echo getSalaImage($sala['tipo']); ?>" alt="Imagem da sala" class="card-image">
                    <div class="card-content">
                        <h3><?php echo $sala['nome']; ?></h3>
                        <div class="room-details">
                            <div class="room-info">
                                <span>Capacidade: <?php echo $sala['capacidade']; ?></span>
                                <span>Tipo: <?php echo $sala['tipo']; ?></span>
                            </div>
                            <?php if ($autenticado): ?>
                                <div class="status">
                                    <?php
                                    $estado = verificarDisponibilidade($sala['idSala'], $conn);
                                    if ($estado === 'DISPONÍVEL') {
                                        echo '<span style="color: green;">' . $estado . '</span>';
                                    } elseif ($estado === 'INDISPONÍVEL') {
                                        echo '<span style="color: red;">' . $estado . '</span>';
                                    } elseif ($estado === 'BREVEMENTE') {
                                        echo '<span style="color: orange;">' . $estado . '</span>';
                                    }
                                    ?>
                                </div>
                                <?php if ($_SESSION['cargo'] == 'Administrador'): ?>
                                    <a href="../areaAdmin/editarSala.php?idSala=<?php echo $sala['idSala']; ?>"
                                        class="btn editar-btn">
                                        Editar
                                    </a>
                                    <a class="btn eliminar-btn" href="?eliminarSala=<?php echo $sala['idSala']; ?>"
                                        onclick="return confirm('Tem a certeza que deseja eliminar esta sala?')">
                                        Eliminar
                                    </a>
                                <?php else: ?>
                                    <a href="../reservarSala/reservarSala.php?idSala=<?php echo $sala['idSala']; ?>&dataReserva=<?php echo isset($_GET['data']) ? $_GET['data'] : date('Y-m-d', strtotime('+1 day')); ?>"
                                        class="btn reservar-btn">
                                        Reservar
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        function applyFilters() {
            const tipo = document.getElementById('tipo').value;
            const capacidade = document.getElementById('capacidade').value;
            const dataInput = document.getElementById('data');
            const data = dataInput.value;

            const today = new Date();
            today.setHours(0, 0, 0, 0);

            const selectedDate = new Date(data);

            if (selectedDate <= today) {
                alert("Você não pode selecionar uma data anterior a amanhã.");

                const tomorrow = new Date();
                tomorrow.setDate(today.getDate() + 1);
                dataInput.value = tomorrow.toISOString().split('T')[0];
                return;
            }

            let url = window.location.pathname + "?";

            if (tipo) url += `tipo=${tipo}&`;
            if (capacidade) url += `capacidade=${capacidade}&`;
            if (data) url += `data=${data}&`;

            url = url.endsWith('&') ? url.slice(0, -1) : url;

            window.location.href = url;
        }

        function resetFilters() {
            window.location.href = window.location.pathname;
        }
    </script>
</body>

</html>

<?php
// Fechar a conexão com a base de dados
$stmt->close();
$conn->close();
?>