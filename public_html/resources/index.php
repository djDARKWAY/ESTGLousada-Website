<?php
// Incluir o ficheiro de conexão à base de dados
include('conexao.php');
$conn = getDatabaseConnection();

// Receber os parâmetros dos filtros
$tipoFiltro = isset($_GET['tipo']) ? $_GET['tipo'] : '';
$capacidadeFiltro = isset($_GET['capacidade']) ? $_GET['capacidade'] : '';

// Modificar a query para filtrar conforme os parâmetros
$sql = "SELECT idSala, nome, tipo, descricao, capacidade, estado, imagemSala FROM sala WHERE 1=1";

// Se o filtro de tipo não for vazio e for diferente de "Todos os tipos", adicionar o filtro de tipo
if ($tipoFiltro && $tipoFiltro !== '') {
    $sql .= " AND tipo = '$tipoFiltro'";
}

// Se o filtro de capacidade for definido, adicionar o filtro de capacidade
if ($capacidadeFiltro) {
    $sql .= " AND capacidade >= $capacidadeFiltro";
}

$result = $conn->query($sql);

// Array para armazenar as salas
$salas = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $salas[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestão de Salas - ESTG</title>
    <link rel="stylesheet" href="index.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-content">
            <div class="logo">
                <img class="PPorto" src="media/logoPPorto.png">
                Gestão de Salas ESTG
            </div>
            <div class="nav-links">
                <a href="./login/login.php">Login</a>
                <a href="./registar/registar.php">Registar</a>
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
                        <option value="Arte" <?php echo $tipoFiltro == 'Arte' ? 'selected' : ''; ?>>Arte</option>
                        <option value="Auditório" <?php echo $tipoFiltro == 'Auditório' ? 'selected' : ''; ?>>Auditório</option>
                        <option value="Biblioteca" <?php echo $tipoFiltro == 'Biblioteca' ? 'selected' : ''; ?>>Biblioteca</option>
                        <option value="Informática" <?php echo $tipoFiltro == 'Informática' ? 'selected' : ''; ?>>Informática</option>
                        <option value="Laboratório" <?php echo $tipoFiltro == 'Laboratório' ? 'selected' : ''; ?>>Laboratório</option>
                        <option value="Mecânica" <?php echo $tipoFiltro == 'Mecânica' ? 'selected' : ''; ?>>Mecânica</option>
                        <option value="Multimédia" <?php echo $tipoFiltro == 'Multimédia' ? 'selected' : ''; ?>>Multimédia</option>
                        <option value="Música" <?php echo $tipoFiltro == 'Música' ? 'selected' : ''; ?>>Música</option>
                        <option value="Pavilhão" <?php echo $tipoFiltro == 'Pavilhão' ? 'selected' : ''; ?>>Pavilhão</option>
                        <option value="Reunião" <?php echo $tipoFiltro == 'Reunião' ? 'selected' : ''; ?>>Reunião</option>
                        <option value="Teórica" <?php echo $tipoFiltro == 'Teórica' ? 'selected' : ''; ?>>Teórica</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="capacidade">Capacidade Mínima</label>
                    <input type="number" id="capacidade" min="1" onchange="applyFilters()" value="<?php echo $capacidadeFiltro; ?>">
                </div>
                <div class="filter-group">
                    <label for="data">Data</label>
                    <input type="date" id="data" onchange="applyFilters()">
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button class="btn" onclick="">Limpar filtros</button>
                </div>
            </div>
        </div>

        <div class="grid" id="salas-grid">
            <?php foreach ($salas as $sala): ?>
            <div class="card">
                <img src="/api/placeholder/400/200" alt="<?php echo $sala['nome']; ?>" class="card-image">
                <div class="card-content">
                    <span class="room-type"><?php echo $sala['tipo']; ?></span>
                    <h3><?php echo $sala['nome']; ?></h3>
                    <div class="room-details">
                        <div class="room-info">
                            <span>Capacidade:</span>
                            <span><?php echo $sala['capacidade']; ?> pessoas</span>
                        </div>
                        <div class="room-info">
                            <span>Descrição:</span>
                            <span><?php echo $sala['descricao']; ?></span>
                        </div>
                    </div>
                    <?php
                    // Determinar o estado da sala
                    $statusClass = '';
                    $statusText = '';
                    switch ($sala['estado']) {
                        case 'disponível':
                            $statusClass = 'status-disponivel';
                            $statusText = 'Disponível';
                            break;
                        case 'indisponível':
                            $statusClass = 'status-indisponivel';
                            $statusText = 'Indisponível';
                            break;
                        case 'brevemente':
                            $statusClass = 'status-brevemente';
                            $statusText = 'Em Breve';
                            break;
                    }
                    ?>
                    <span class="status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                    <button class="btn reservar-btn">Reservar</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        // Função para aplicar os filtros
        function applyFilters() {
            const tipo = document.getElementById('tipo').value;
            const capacidade = document.getElementById('capacidade').value;

            let url = window.location.pathname + "?";

            if (tipo) url += `tipo=${tipo}&`;
            if (capacidade) url += `capacidade=${capacidade}&`;

            // Remover o último '&' caso haja
            url = url.endsWith('&') ? url.slice(0, -1) : url;

            // Atualiza a página com os filtros aplicados
            window.location.href = url;
        }
    </script>
</body>
</html>

<?php
// Fechar a conexão com a base de dados
$conn->close();
?>
