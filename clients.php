<?php
// clients.php
require_once 'functions.php';
require_login();
$user = $_SESSION['user'];

// --- INSERIR CLIENTE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    $name = trim($_POST['name']);
    $bi = trim($_POST['bi']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if ($name === '' || $bi === '' || $phone === '' || $address === '') {
        $error = "⚠️ Todos os campos devem ser preenchidos!";
    } else {
        $stmt = $pdo->prepare("INSERT INTO clients (name, bi, phone, address) VALUES (:n, :bi, :ph, :ad)");
        $stmt->execute(['n' => $name, 'bi' => $bi, 'ph' => $phone, 'ad' => $address]);
        audit($pdo, $_SESSION['user']['id'], "Criou cliente: $name");
        header('Location: clients.php');
        exit;
    }
}

// --- EDITAR CLIENTE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $bi = trim($_POST['bi']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);

    if ($name === '' || $bi === '' || $phone === '' || $address === '') {
        $error = "⚠️ Todos os campos devem ser preenchidos!";
    } else {
        $stmt = $pdo->prepare("UPDATE clients SET name=?, bi=?, phone=?, address=? WHERE id=?");
        $stmt->execute([$name, $bi, $phone, $address, $id]);
        audit($pdo, $_SESSION['user']['id'], "Editou cliente: $name");
        header('Location: clients.php');
        exit;
    }
}

// --- APAGAR CLIENTE ---
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM clients WHERE id=?");
    $stmt->execute([$id]);
    audit($pdo, $_SESSION['user']['id'], "Apagou cliente ID: $id");
    header('Location: clients.php');
    exit;
}

// --- EDITAR CLIENTE (CARREGAR DADOS) ---
$edit_client = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM clients WHERE id=?");
    $stmt->execute([$id]);
    $edit_client = $stmt->fetch(PDO::FETCH_ASSOC);
}

// --- LISTAR CLIENTES ---
$clients = $pdo->query("SELECT * FROM clients ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pt" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Gestão de Clientes - Shelton Business</title>
  
  <!-- Anti-Flicker para Modo Escuro -->
  <script>
    (function() {
      const savedTheme = localStorage.getItem('theme');
      if (savedTheme) {
        document.documentElement.setAttribute('data-bs-theme', savedTheme);
      } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.setAttribute('data-bs-theme', 'dark');
      }
    })();
  </script>

  <!-- Google Fonts & Bootstrap -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  
  <style>
    :root {
      --sidebar-width: 260px;
      --accent-color: #2563eb;
    }

    [data-bs-theme="light"] {
      --bg-main: #f8fafc;
      --bg-card: #ffffff;
      --border-color: #e2e8f0;
      --sidebar-bg: #ffffff;
      --text-main: #334155;
      --text-muted: #64748b;
      --action-icon-bg: #f1f5f9;
      --table-hover: #f8fafc;
    }

    [data-bs-theme="dark"] {
      --bg-main: #0f172a;
      --bg-card: #1e293b;
      --border-color: #334155;
      --sidebar-bg: #1e293b;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --action-icon-bg: #334155;
      --table-hover: #1e293b;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: var(--bg-main);
      color: var(--text-main);
      overflow-x: hidden;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

    /* --- SIDEBAR --- */
    .sidebar {
      width: var(--sidebar-width);
      height: 100vh;
      position: fixed;
      top: 0;
      left: 0;
      background-color: var(--sidebar-bg);
      border-right: 1px solid var(--border-color);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
      padding: 1.5rem 1rem;
      z-index: 1000;
      transition: all 0.3s ease;
    }

    .brand-logo {
      font-size: 1.25rem;
      font-weight: 700;
      color: var(--text-main);
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.5rem;
      text-decoration: none;
    }

    .nav-label {
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: var(--text-muted);
      margin: 1.5rem 0 0.5rem 0.5rem;
    }

    .nav-link-custom {
      display: flex;
      align-items: center;
      gap: 0.85rem;
      padding: 0.75rem 1rem;
      color: var(--text-muted);
      font-weight: 600;
      border-radius: 10px;
      text-decoration: none;
      transition: all 0.2s ease;
      margin-bottom: 0.25rem;
    }

    .nav-link-custom i { font-size: 1.2rem; }

    .nav-link-custom:hover {
      background-color: var(--action-icon-bg);
      color: var(--accent-color);
    }

    .nav-link-custom.active {
      background-color: rgba(37, 99, 235, 0.1);
      color: var(--accent-color);
    }

    .user-profile {
      padding-top: 1rem;
      border-top: 1px solid var(--border-color);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    /* --- CONTEÚDO PRINCIPAL --- */
    .main-content {
      margin-left: var(--sidebar-width);
      padding: 2rem;
      min-height: 100vh;
      transition: margin-left 0.3s ease;
    }

    /* --- TABELA E CARDS --- */
    .content-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }

    .avatar-circle {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: rgba(37, 99, 235, 0.1);
      color: var(--accent-color);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 0.95rem;
    }

    .theme-toggle-btn {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      color: var(--text-main);
      border-radius: 12px;
      padding: 0.5rem 0.85rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      font-weight: 600;
      font-size: 0.875rem;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .theme-toggle-btn:hover {
      border-color: var(--accent-color);
      color: var(--accent-color);
    }

    .search-input-group {
      max-width: 350px;
    }

    @media (max-width: 991px) {
      .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        border-right: none;
        border-bottom: 1px solid var(--border-color);
      }
      .main-content {
        margin-left: 0;
        padding: 1.5rem;
      }
    }
  </style>
</head>
<body>

<!-- SIDEBAR -->
<aside class="sidebar">
  <div>
    <a href="dashboard.php" class="brand-logo">
      <i class="bi bi-briefcase-fill text-primary"></i>
      <span>Shelton Business</span>
    </a>

    <div class="nav-label">Menu Principal</div>
    <nav class="nav flex-column">
      <a class="nav-link-custom" href="dashboard.php">
        <i class="bi bi-grid-1x2-fill"></i>
        <span>Painel Principal</span>
      </a>
      <a class="nav-link-custom active" href="clients.php">
        <i class="bi bi-people-fill"></i>
        <span>Gerir Clientes</span>
      </a>
      <a class="nav-link-custom" href="registrarEmpréstimo.php">
        <i class="bi bi-plus-circle-fill"></i>
        <span>Novo Empréstimo</span>
      </a>
      <a class="nav-link-custom" href="Pagamentos.php">
        <i class="bi bi-wallet-fill"></i>
        <span>Pagamentos</span>
      </a>
      <a class="nav-link-custom" href="relatórios.php">
        <i class="bi bi-bar-chart-fill"></i>
        <span>Relatórios</span>
      </a>
    </nav>
  </div>

  <div class="user-profile">
    <div class="d-flex align-items-center gap-2">
      <div class="avatar-circle" style="width: 36px; height: 36px; font-size: 0.85rem;">
        <i class="bi bi-person"></i>
      </div>
      <div>
        <div class="fw-bold fs-7 text-truncate" style="max-width: 120px;"><?= htmlspecialchars($user['name']) ?></div>
        <small style="color: var(--text-muted); font-size: 0.75rem;">Operador</small>
      </div>
    </div>
    <a href="logout.php" class="btn btn-outline-danger btn-sm border-0" title="Sair do sistema">
      <i class="bi bi-box-arrow-right fs-5"></i>
    </a>
  </div>
</aside>

<!-- MAIN CONTENT -->
<main class="main-content">
  <!-- Top Bar -->
  <header class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
      <h3 class="fw-bold mb-1">Gestão de Clientes</h3>
      <p class="small mb-0" style="color: var(--text-muted);">Cadastre, edite e organize os clientes de microcrédito.</p>
    </div>

    <div class="d-flex align-items-center gap-2">
      <button class="theme-toggle-btn" id="themeToggleBtn" aria-label="Alternar Tema">
        <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
        <span id="themeText" class="d-none d-sm-inline">Modo Escuro</span>
      </button>

      <button type="button" class="btn btn-primary fw-semibold rounded-3 px-3 py-2 d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#clientModal">
        <i class="bi bi-person-plus-fill fs-5"></i>
        <span>Novo Cliente</span>
      </button>
    </div>
  </header>

  <?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- LISTA DE CLIENTES -->
  <section class="content-card">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
      <h5 class="fw-bold mb-0"><i class="bi bi-people me-2 text-primary"></i> Clientes Cadastrados</h5>
      
      <!-- Pesquisa em Tempo Real -->
      <div class="input-group search-input-group">
        <span class="input-group-text bg-transparent border-end-0" style="border-color: var(--border-color);">
          <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text" id="searchInput" class="form-control border-start-0" style="border-color: var(--border-color);" placeholder="Buscar cliente, BI ou telefone...">
      </div>
    </div>

    <div class="table-responsive">
      <table class="table align-middle" id="clientsTable">
        <thead>
          <tr style="border-bottom: 2px solid var(--border-color); color: var(--text-muted);">
            <th>Cliente</th>
            <th>BI</th>
            <th>Telefone</th>
            <th>Endereço</th>
            <th>Data de Registro</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($clients as $c): 
            $initials = strtoupper(substr($c['name'], 0, 2));
          ?>
            <tr style="border-bottom: 1px solid var(--border-color);">
              <td>
                <div class="d-flex align-items-center gap-3">
                  <div class="avatar-circle"><?= $initials ?></div>
                  <div>
                    <div class="fw-bold"><?= htmlspecialchars($c['name']) ?></div>
                    <small style="color: var(--text-muted);">ID: #<?= $c['id'] ?></small>
                  </div>
                </div>
              </td>
              <td class="fw-medium"><?= htmlspecialchars($c['bi']) ?></td>
              <td><?= htmlspecialchars($c['phone']) ?></td>
              <td style="color: var(--text-muted); max-width: 200px;" class="text-truncate"><?= htmlspecialchars($c['address']) ?></td>
              <td><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
              <td class="text-end">
                <a href="clients.php?edit=<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary me-1 rounded-2" title="Editar">
                  <i class="bi bi-pencil-square"></i>
                </a>
                <a href="clients.php?delete=<?= $c['id'] ?>" 
                   onclick="return confirm('Tem certeza que deseja apagar o cliente: <?= htmlspecialchars($c['name']) ?>?')"
                   class="btn btn-sm btn-outline-danger rounded-2" title="Apagar">
                  <i class="bi bi-trash-fill"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (empty($clients)): ?>
            <tr>
              <td colspan="6" class="text-center py-5" style="color: var(--text-muted);">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                Nenhum cliente registrado até o momento.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="mt-5 pt-3 border-top text-center small" style="border-color: var(--border-color) !important; color: var(--text-muted);">
    <p class="mb-0">© <?= date('Y') ?> Shelton Business — Sistema de Microcrédito</p>
  </footer>
</main>

<!-- MODAL DE CADASTRO / EDIÇÃO DE CLIENTE -->
<div class="modal fade" id="clientModal" tabindex="-1" aria-labelledby="clientModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="background-color: var(--bg-card); color: var(--text-main);">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title fw-bold" id="clientModalLabel">
          <?= $edit_client ? '<i class="bi bi-pencil-square text-warning me-2"></i>Editar Cliente' : '<i class="bi bi-person-plus-fill text-primary me-2"></i>Novo Cliente' ?>
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form method="post" id="clientForm" novalidate>
        <div class="modal-body py-3">
          <?php if ($edit_client): ?>
            <input type="hidden" name="id" value="<?= $edit_client['id'] ?>">
          <?php endif; ?>

          <div class="mb-3">
            <label class="form-label fw-semibold small">Nome Completo</label>
            <input name="name" class="form-control rounded-3" placeholder="Ex: João Manuel Silva" 
                   value="<?= $edit_client['name'] ?? '' ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold small">Número de BI / Identificação</label>
            <input name="bi" class="form-control rounded-3" placeholder="Ex: 110203040506A" 
                   value="<?= $edit_client['bi'] ?? '' ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold small">Telefone</label>
            <input name="phone" class="form-control rounded-3" placeholder="Ex: +258 84 123 4567" 
                   value="<?= $edit_client['phone'] ?? '' ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold small">Morada / Endereço</label>
            <input name="address" class="form-control rounded-3" placeholder="Ex: Av. Eduardo Mondlane, Nº 123" 
                   value="<?= $edit_client['address'] ?? '' ?>" required>
          </div>
        </div>

        <div class="modal-footer border-0 pt-0">
          <?php if ($edit_client): ?>
            <a href="clients.php" class="btn btn-light rounded-3">Cancelar</a>
            <button type="submit" class="btn btn-warning fw-semibold rounded-3 px-4" name="edit">Salvar Alterações</button>
          <?php else: ?>
            <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary fw-semibold rounded-3 px-4" name="add">Gravar Cliente</button>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', () => {
    // ABRIR AUTOMATICAMENTE O MODAL EM MODO DE EDIÇÃO
    <?php if ($edit_client): ?>
      const modal = new bootstrap.Modal(document.getElementById('clientModal'));
      modal.show();
    <?php endif; ?>

    // GERENCIAMENTO DE TEMA (DARK / LIGHT)
    const htmlEl = document.documentElement;
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeIcon = document.getElementById('themeIcon');
    const themeText = document.getElementById('themeText');

    function updateButtonUI(theme) {
      if (theme === 'dark') {
        themeIcon.className = 'bi bi-sun-fill text-warning';
        themeText.textContent = 'Modo Claro';
      } else {
        themeIcon.className = 'bi bi-moon-stars-fill';
        themeText.textContent = 'Modo Escuro';
      }
    }

    const currentTheme = htmlEl.getAttribute('data-bs-theme') || 'light';
    updateButtonUI(currentTheme);

    themeToggleBtn.addEventListener('click', () => {
      const activeTheme = htmlEl.getAttribute('data-bs-theme');
      const newTheme = activeTheme === 'dark' ? 'light' : 'dark';

      htmlEl.setAttribute('data-bs-theme', newTheme);
      localStorage.setItem('theme', newTheme);
      updateButtonUI(newTheme);
    });

    // PESQUISA EM TEMPO REAL NA TABELA DE CLIENTES
    const searchInput = document.getElementById('searchInput');
    const tableRows = document.querySelectorAll('#clientsTable tbody tr');

    searchInput.addEventListener('keyup', (e) => {
      const value = e.target.value.toLowerCase().trim();

      tableRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(value) ? '' : 'none';
      });
    });
  });
</script>

</body>
</html>