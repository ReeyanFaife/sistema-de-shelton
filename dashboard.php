<?php
// dashboard.php
require_once 'functions.php';
require_login();
$user = $_SESSION['user'];

// --- PAGAMENTOS REGISTRADOS ---
$payments = $pdo->query("
    SELECT DISTINCT client_id
    FROM payments
")->fetchAll(PDO::FETCH_COLUMN);

// --- Estatísticas simples ---
$total_clients = $pdo->query("SELECT COUNT(*) FROM clients")->fetchColumn();

// --- Tratar lista de pagamentos vazia ---
$payments_list = !empty($payments)
    ? implode(',', array_map('intval', $payments))
    : '0';

// --- Empréstimos ativos, excluindo clientes pagos ---
$active_loans = $pdo->query("
    SELECT COUNT(*) 
    FROM loans l
    WHERE l.status = 'ativo'
    AND l.client_id NOT IN ($payments_list)
")->fetchColumn();

// --- Clientes em dívida (parcelas vencidas ou prazo expirado), excluindo clientes pagos ---
$clients_in_debt = $pdo->query("
    SELECT COUNT(DISTINCT c.id)
    FROM clients c
    JOIN loans l ON l.client_id = c.id
    LEFT JOIN installments i ON i.loan_id = l.id
    WHERE (
        (i.due_date < CURRENT_DATE AND (i.amount_due - COALESCE(i.amount_paid,0)) > 0)
        OR (
            l.status = 'ativo'
            AND l.start_date IS NOT NULL
            AND CURRENT_DATE > (l.start_date + (l.term_months || ' months')::interval)
        )
    )
    AND c.id NOT IN ($payments_list)
")->fetchColumn();

// --- Clientes pagos ---
$clients_paid = $pdo->query("
    SELECT COUNT(DISTINCT client_id)
    FROM payments
")->fetchColumn();
?>
<!doctype html>
<html lang="pt" data-bs-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Painel - Shelton Business</title>
  
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
    }

    [data-bs-theme="dark"] {
      --bg-main: #0f172a;
      --bg-card: #1e293b;
      --border-color: #334155;
      --sidebar-bg: #1e293b;
      --text-main: #f8fafc;
      --text-muted: #94a3b8;
      --action-icon-bg: #334155;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: var(--bg-main);
      color: var(--text-main);
      overflow-x: hidden;
      transition: background-color 0.3s ease, color 0.3s ease;
    }

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

    .nav-link-custom i {
      font-size: 1.2rem;
    }

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

    .main-content {
      margin-left: var(--sidebar-width);
      padding: 2rem;
      min-height: 100vh;
      transition: margin-left 0.3s ease;
    }

    .card-stat {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 1.5rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.02);
      transition: all 0.2s ease;
      position: relative;
      overflow: hidden;
    }

    .card-stat:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 20px -5px rgba(0, 0, 0, 0.1);
    }

    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.4rem;
    }

    .stat-blue { background: rgba(37, 99, 235, 0.15); color: #3b82f6; }
    .stat-green { background: rgba(22, 163, 74, 0.15); color: #22c55e; }
    .stat-amber { background: rgba(217, 119, 6, 0.15); color: #f59e0b; }
    .stat-red { background: rgba(220, 38, 38, 0.15); color: #ef4444; }

    .action-card {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 1.25rem;
      text-decoration: none;
      color: var(--text-main);
      display: flex;
      align-items: center;
      gap: 1rem;
      font-weight: 600;
      transition: all 0.2s ease;
    }

    .action-card:hover {
      border-color: var(--accent-color);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(0,0,0,0.05);
      color: var(--accent-color);
    }

    .action-icon {
      width: 40px;
      height: 40px;
      border-radius: 10px;
      background: var(--action-icon-bg);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
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

    @media (max-width: 991px) {
      .sidebar {
        width: 100%;
        height: auto;
        position: sticky;
        top: 0;
        border-right: none;
        border-bottom: 1px solid var(--border-color);
        padding: 0.75rem;
      }
      .sidebar > div:first-child {
        min-width: 0;
      }
      .brand-logo {
        padding: 0.25rem 0.5rem 0.75rem;
      }
      .nav-label {
        display: none;
      }
      .sidebar nav {
        flex-direction: row !important;
        gap: 0.5rem;
        overflow-x: auto;
        padding-bottom: 0.25rem;
        scrollbar-width: none;
      }
      .sidebar nav::-webkit-scrollbar {
        display: none;
      }
      .nav-link-custom {
        flex: 0 0 auto;
        min-width: 76px;
        justify-content: center;
        padding: 0.6rem 0.75rem;
        margin-bottom: 0;
        border-radius: 12px;
        font-size: 0.76rem;
        text-align: center;
        gap: 0.35rem;
        flex-direction: column;
      }
      .nav-link-custom i {
        font-size: 1.1rem;
      }
      .user-profile {
        margin-top: 0.75rem;
        padding-top: 0.75rem;
      }
      .main-content {
        margin-left: 0;
        padding: 1rem;
      }
      .main-content > header {
        align-items: flex-start !important;
        flex-direction: column;
        gap: 0.75rem;
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
      <a class="nav-link-custom active" href="dashboard.php">
        <i class="bi bi-grid-1x2-fill"></i>
        <span>Painel Principal</span>
      </a>
      <a class="nav-link-custom" href="clients.php">
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
      <a class="nav-link-custom" href="create_user.php">
        <i class="bi bi-person-plus-fill"></i>
        <span>Novo Utilizador</span>
      </a>
    </nav>
  </div>

  <div class="user-profile">
    <div class="d-flex align-items-center gap-2">
      <div class="stat-icon" style="background: var(--action-icon-bg); color: var(--text-main); width: 36px; height: 36px; font-size: 1rem;">
        <i class="bi bi-person"></i>
      </div>
      <div>
        <div class="fw-bold fs-7 text-truncate" style="max-width: 120px;"><?= htmlspecialchars($user['name'] ?? 'Operador') ?></div>
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
  <header class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="fw-bold mb-1">Visão Geral</h3>
      <p class="small mb-0" style="color: var(--text-muted);">Acompanhe o estado do seu microcrédito em tempo real.</p>
    </div>

    <button class="theme-toggle-btn" id="themeToggleBtn" aria-label="Alternar Tema">
      <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
      <span id="themeText">Modo Escuro</span>
    </button>
  </header>

  <!-- CARTÕES DE ESTATÍSTICAS -->
  <section class="row g-3 mb-4">
    <div class="col-xl-3 col-md-6">
      <div class="card-stat">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="fw-semibold small" style="color: var(--text-muted);">Total de Clientes</span>
          <div class="stat-icon stat-blue">
            <i class="bi bi-people"></i>
          </div>
        </div>
        <h3 class="fw-bold mb-0"><?= number_format($total_clients, 0, ',', '.') ?></h3>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card-stat">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="fw-semibold small" style="color: var(--text-muted);">Empréstimos Ativos</span>
          <div class="stat-icon stat-green">
            <i class="bi bi-cash-stack"></i>
          </div>
        </div>
        <h3 class="fw-bold mb-0"><?= number_format($active_loans, 0, ',', '.') ?></h3>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card-stat">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="fw-semibold small" style="color: var(--text-muted);">Clientes Pagos</span>
          <div class="stat-icon stat-amber">
            <i class="bi bi-check-circle"></i>
          </div>
        </div>
        <h3 class="fw-bold mb-0"><?= number_format($clients_paid, 0, ',', '.') ?></h3>
      </div>
    </div>

    <div class="col-xl-3 col-md-6">
      <div class="card-stat">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <span class="fw-semibold small" style="color: var(--text-muted);">Em Dívida</span>
          <div class="stat-icon stat-red">
            <i class="bi bi-exclamation-circle"></i>
          </div>
        </div>
        <h3 class="fw-bold mb-0"><?= number_format($clients_in_debt, 0, ',', '.') ?></h3>
      </div>
    </div>
  </section>

  <!-- AÇÕES RÁPIDAS -->
  <section class="mb-4">
    <h5 class="fw-bold mb-3">Ações Rápidas</h5>
    <div class="row g-3">
      <div class="col-md-6 col-lg-3">
        <a href="clients.php" class="action-card">
          <div class="action-icon text-primary">
            <i class="bi bi-person-gear"></i>
          </div>
          <span>Gerir Clientes</span>
        </a>
      </div>
      <div class="col-md-6 col-lg-3">
        <a href="registrarEmpréstimo.php" class="action-card">
          <div class="action-icon text-success">
            <i class="bi bi-node-plus"></i>
          </div>
          <span>Novo Empréstimo</span>
        </a>
      </div>
      <div class="col-md-6 col-lg-3">
        <a href="Pagamentos.php" class="action-card">
          <div class="action-icon text-warning">
            <i class="bi bi-credit-card-2-front"></i>
          </div>
          <span>Registrar Pagamento</span>
        </a>
      </div>
      <div class="col-md-6 col-lg-3">
        <a href="create_user.php" class="action-card">
          <div class="action-icon text-purple" style="color: #8b5cf6;">
            <i class="bi bi-person-plus-fill"></i>
          </div>
          <span>Novo Utilizador</span>
        </a>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="mt-5 pt-3 border-top text-center small" style="border-color: var(--border-color) !important; color: var(--text-muted);">
    <p class="mb-0">© <?= date('Y') ?> Shelton Business — Sistema de Microcrédito</p>
  </footer>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', () => {
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
  });
</script>

</body>
</html>
