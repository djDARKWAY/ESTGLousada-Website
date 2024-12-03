<?php
error_reporting(0);
session_start();

// Incluir o ficheiro de conexão à base de dados
include('conexao.php');
$conn = getDatabaseConnection();


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
                Gestão de salas ESTG
            </div>

            <div class="nav-links">
                <?php
                if ($_SESSION['cargo'] == 'Administrador') {
                    echo '<a href="admin/admin.php">Área de administração</a>';
                    echo '<a href="perfil/perfil.php">Perfil</a>';
                    echo '<a href="logout.php">Logout</a>';
                    // Adicionar rotas
                } elseif ($_SESSION['cargo'] == 'Professor') {
                    echo '<a href="perfil/perfil.php">Perfil</a>';
                    echo '<a href="logout.php">Logout</a>';
                    // Adicionar rotas
                } else {
                    echo '<a href="login/login.php">Login</a>';
                    echo '<a href="registar/registar.php">Registar</a>';
                }
                ?>
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
                <div class="filter-group">
                    <label for="data">Data</label>
                    <input type="date" id="data" onchange="applyFilters()">
                </div>
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
                            <div class="status">
                                (disponibilidade)
                            </div>
                        </div>
                        <button class="btn reservar-btn">Reservar</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        function applyFilters() {
            const tipo = document.getElementById('tipo').value;
            const capacidade = document.getElementById('capacidade').value;

            let url = window.location.pathname + "?";

            if (tipo) url += `tipo=${tipo}&`;
            if (capacidade) url += `capacidade=${capacidade}&`;

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