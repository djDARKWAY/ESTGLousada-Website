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
                <img src="/api/placeholder/32/32" alt="ESTG Logo">
                Gestão de Salas ESTG
            </div>
            <div class="nav-links">
                <a href="./Login/login.php">Login</a>
                <a href="./registar/registar.php">Registar</a>
            </div>
        </div>
    </nav>

    <main class="container">
        <div class="filters">
            <div class="filters-grid">
                <div class="filter-group">
                    <label for="tipo">Tipo de Sala</label>
                    <select id="tipo">
                        <option value="">Todos os tipos</option>
                        <option value="Arte">Arte</option>
                        <option value="Auditório">Auditório</option>
                        <option value="Biblioteca">Biblioteca</option>
                        <option value="Informática">Informática</option>
                        <option value="Laboratório">Laboratório</option>
                        <option value="Mecânica">Mecânica</option>
                        <option value="Multimédia">Multimédia</option>
                        <option value="Música">Música</option>
                        <option value="Pavilhão">Pavilhão</option>
                        <option value="Reunião">Reunião</option>
                        <option value="Teórica">Teórica</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="capacidade">Capacidade Mínima</label>
                    <input type="number" id="capacidade" min="1">
                </div>
                <div class="filter-group">
                    <label for="data">Data</label>
                    <input type="date" id="data">
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <button class="btn">Filtrar Salas</button>
                </div>
            </div>
        </div>

        <div class="grid">
            <div class="card">
                <img src="/api/placeholder/400/200" alt="Sala 101" class="card-image">
                <div class="card-content">
                    <span class="room-type">Informática</span>
                    <h3>Sala 101</h3>
                    <div class="room-details">
                        <div class="room-info">
                            <span>Capacidade:</span>
                            <span>30 pessoas</span>
                        </div>
                        <div class="room-info">
                            <span>Descrição:</span>
                            <span>Laboratório equipado para experiências químicas e biológicas</span>
                        </div>
                    </div>
                    <span class="status status-disponivel">Disponível</span>
                    <button class="btn reservar-btn">Reservar</button>
                </div>
            </div>

            <div class="card">
                <img src="/api/placeholder/400/200" alt="Sala 102" class="card-image">
                <div class="card-content">
                    <span class="room-type">Laboratório</span>
                    <h3>Sala 102</h3>
                    <div class="room-details">
                        <div class="room-info">
                            <span>Capacidade:</span>
                            <span>25 pessoas</span>
                        </div>
                        <div class="room-info">
                            <span>Descrição:</span>
                            <span>Laboratório equipado para experiências químicas e biológicas</span>
                        </div>
                    </div>
                    <span class="status status-indisponivel">Indisponível</span>
                    <button class="btn reservar-btn">Reservar</button>
                </div>
            </div>

            <div class="card">
                <img src="/api/placeholder/400/200" alt="Sala 103" class="card-image">
                <div class="card-content">
                    <span class="room-type">Auditório</span>
                    <h3>Sala 103</h3>
                    <div class="room-details">
                        <div class="room-info">
                            <span>Capacidade:</span>
                            <span>40 pessoas</span>
                        </div>
                        <div class="room-info">
                            <span>Descrição:</span>
                            <span>Laboratório equipado para experiências químicas e biológicas</span>
                        </div>
                    </div>
                    <span class="status status-brevemente">Em Breve</span>
                    <button class="btn reservar-btn">Reservar</button>
                </div>
            </div>
        </div>
    </main>
</body>
</html>