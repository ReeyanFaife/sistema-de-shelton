<?php
// loan.php
require_once 'config.php';
require_once 'functions.php';
require_login();
$user = $_SESSION['user'];

// --- BUSCAR CLIENTES ---
$clients = $pdo->query("SELECT * FROM clients ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- BUSCAR TIPOS DE EMPRÉSTIMOS ---
$loan_types = $pdo->query("SELECT * FROM loan_types ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

// --- PROCESSAR FORMULÁRIO (REGISTAR EMPRÉSTIMO) ---
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = $_POST['client_id'] ?? '';
    $loan_type_id = $_POST['loan_type_id'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $monthly_interest = $_POST['monthly_interest'] ?? '';
    $term_months = $_POST['term_months'] ?? '';
    $start_date = $_POST['start_date'] ?? '';

    // Se for "custom", define como NULL
    if ($loan_type_id === 'custom' || $loan_type_id === '') {
        $loan_type_id = null;
    }

    if ($client_id && $amount !== '' && $monthly_interest !== '' && $term_months && $start_date) {
        // Conversão de tipos
        $amount = floatval($amount);
        $monthly_interest = floatval($monthly_interest);
        $term_months = intval($term_months);

        // Calcular total com juros (juros composto mensal)
        if ($monthly_interest > 0) {
            $total_with_interest = $amount * pow(1 + ($monthly_interest / 100), $term_months);
        } else {
            $total_with_interest = $amount;
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO loans (
                    client_id, loan_type_id, amount, monthly_interest, term_months, start_date, total_with_interest
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $client_id, $loan_type_id, $amount, $monthly_interest, $term_months, $start_date, $total_with_interest
            ]);

            $new_loan_id = $pdo->lastInsertId();
            audit($pdo, $_SESSION['user']['id'], "Registrou empréstimo ID: $new_loan_id (Montante: MZN $amount)");
            
            $message = "Empréstimo registrado com sucesso!";
            $message_type = "success";
        } catch (Exception $e) {
            $message = "Erro ao registrar empréstimo: " . $e->getMessage();
            $message_type = "danger";
        }
    } else {
        $message = "Preencha todos os campos obrigatórios antes de salvar.";
        $message_type = "warning";
    }
}
?>
<!doctype html>
<html lang="pt" data-bs-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Registrar Empréstimo - Shelton Business</title>

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
    --preview-bg: #f8fafc;
  }

  [data-bs-theme="dark"] {
    --bg-main: #0f172a;
    --bg-card: #1e293b;
    --border-color: #334155;
    --sidebar-bg: #1e293b;
    --text-main: #f8fafc;
    --text-muted: #94a3b8;
    --action-icon-bg: #334155;
    --preview-bg: #111827;
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

  .content-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02);
  }

  .form-control, .form-select {
    background-color: var(--bg-card);
    border-color: var(--border-color);
    color: var(--text-main);
    padding: 0.75rem 1rem;
    border-radius: 10px;
  }

  .form-control:focus, .form-select:focus {
    background-color: var(--bg-card);
    color: var(--text-main);
    border-color: var(--accent-color);
    box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.15);
  }

  .form-label {
    font-weight: 600;
    font-size: 0.875rem;
    margin-bottom: 0.5rem;
  }

  .preview-box {
    background-color: var(--preview-bg);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 1.5rem;
  }

  .avatar-circle {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(37, 99, 235, 0.1);
    color: var(--accent-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
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
      position: relative;
      border-right: none;
      border-bottom: 1px solid var(--border-color);
    }
    .main-content {
      margin-left: 0;
      padding: 1.5rem;
    }
  }

  /* --- ESTILOS EXCLUSIVOS PARA IMPRESSÃO EM PDF --- */
  @media print {
    @page {
      size: A4 portrait;
      margin: 12mm 10mm;
    }

    body {
      background: #ffffff !important;
      color: #000000 !important;
      font-size: 11pt;
    }

    .sidebar, header, .alert, .theme-toggle-btn, footer, .action-buttons {
      display: none !important;
    }

    .main-content {
      margin-left: 0 !important;
      padding: 0 !important;
    }

    .content-card {
      border: none !important;
      box-shadow: none !important;
      padding: 0 !important;
    }

    .print-header {
      display: flex !important;
      justify-content: space-between;
      align-items: center;
      border-bottom: 2px solid #1e293b;
      padding-bottom: 10px;
      margin-bottom: 20px;
    }

    .print-header h2 {
      margin: 0;
      font-weight: 700;
      color: #0f172a;
      font-size: 18pt;
    }

    .print-header p {
      margin: 0;
      font-size: 9pt;
      color: #64748b;
    }

    /* Formata os campos de input para aparecerem como texto limpo */
    .form-control, .form-select {
      border: none !important;
      padding: 0 !important;
      background: transparent !important;
      font-weight: 600;
      color: #000 !important;
      appearance: none;
      -webkit-appearance: none;
    }

    .form-select {
      background-image: none !important;
    }

    .input-group-text {
      border: none !important;
      background: transparent !important;
      padding-left: 0 !important;
      font-weight: 600;
    }

    .form-label {
      color: #475569 !important;
      font-size: 9pt !important;
      text-transform: uppercase;
      letter-spacing: 0.03em;
      margin-bottom: 2px !important;
    }

    .row.g-4 > [class*="col-"] {
      border-bottom: 1px dashed #e2e8f0;
      padding-bottom: 8px;
    }

    .preview-box {
      border: 1px solid #cbd5e1 !important;
      background: #ffffff !important;
      color: #000000 !important;
      padding: 15px !important;
      margin-top: 15px !important;
    }

    .table-responsive {
      max-height: none !important;
      overflow: visible !important;
    }

    .table {
      border-color: #cbd5e1 !important;
    }

    .table th, .table td {
      padding: 6px 8px !important;
      font-size: 10pt !important;
    }

    .print-signatures {
      display: flex !important;
    }
  }

  .print-header, .print-signatures {
    display: none;
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
      <a class="nav-link-custom" href="clients.php">
        <i class="bi bi-people-fill"></i>
        <span>Gerir Clientes</span>
      </a>
      <a class="nav-link-custom active" href="registrarEmpréstimo.php">
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
      <div class="avatar-circle">
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

  <!-- Cabeçalho visível apenas na Impressão -->
  <div class="print-header">
    <div>
      <h2>Shelton Business</h2>
      <p>Ficha Completa de Simulação e Proposta de Empréstimo</p>
    </div>
    <div class="text-end">
      <p><strong>Emissão:</strong> <?= date('d/m/Y H:i') ?></p>
      <p><strong>Operador:</strong> <?= htmlspecialchars($user['name']) ?></p>
    </div>
  </div>

  <!-- Top Bar -->
  <header class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
      <h3 class="fw-bold mb-1">Registar Novo Empréstimo</h3>
      <p class="small mb-0" style="color: var(--text-muted);">Configure montantes, taxas e prazos para liberação de crédito.</p>
    </div>

    <div class="d-flex align-items-center gap-2">
      <button class="theme-toggle-btn" id="themeToggleBtn" aria-label="Alternar Tema">
        <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
        <span id="themeText" class="d-none d-sm-inline">Modo Escuro</span>
      </button>

      <a href="dashboard.php" class="btn btn-outline-secondary fw-semibold rounded-3 px-3 py-2">
        <i class="bi bi-arrow-left me-1"></i> Voltar ao Painel
      </a>
    </div>
  </header>

  <?php if (!empty($message)): ?>
    <div class="alert alert-<?= $message_type ?> alert-dismissible fade show rounded-3 shadow-sm mb-4" role="alert">
      <i class="bi bi-<?= $message_type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill' ?> me-2"></i>
      <?= htmlspecialchars($message) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <div class="content-card">
    <form method="post" id="loanForm" novalidate>
      
      <!-- Seção do Formulário: Dados do Empréstimo e Cliente -->
      <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-file-earmark-person me-2"></i> Informações Gerais do Empréstimo</h5>
      
      <div class="row g-4" id="formInputs">

        <!-- Cliente -->
        <div class="col-md-6">
          <label class="form-label"><i class="bi bi-person-fill text-primary me-1"></i> Cliente Selecionado</label>
          <select name="client_id" id="client_id" class="form-select" required>
            <option value="">-- Selecione o cliente --</option>
            <?php foreach($clients as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?> (BI: <?= htmlspecialchars($c['bi']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Tipo de Empréstimo -->
        <div class="col-md-6">
          <label class="form-label"><i class="bi bi-tag-fill text-primary me-1"></i> Modalidade / Tipo</label>
          <select name="loan_type_id" id="loan_type_id" class="form-select" required>
            <option value="">-- Selecione ou defina personalizado --</option>
            <?php foreach($loan_types as $lt): 
              $monthly_eq = ($lt['annual_interest'] / 12) * 100;
            ?>
              <option value="<?= $lt['id'] ?>" data-annual="<?= $lt['annual_interest'] ?>" data-monthly="<?= round($monthly_eq, 2) ?>">
                <?= htmlspecialchars($lt['name']) ?> (<?= ($lt['annual_interest']*100) ?>% a.a.)
              </option>
            <?php endforeach; ?>
            <option value="custom">💵 Personalizado (Definir taxa livre)</option>
          </select>
        </div>

        <!-- Montante -->
        <div class="col-md-3">
          <label class="form-label"><i class="bi bi-cash-stack text-primary me-1"></i> Montante Solicitado</label>
          <div class="input-group">
            <span class="input-group-text">MZN</span>
            <input name="amount" id="amount" type="number" step="0.01" class="form-control" placeholder="0.00" required>
          </div>
        </div>

        <!-- Juros mensal -->
        <div class="col-md-3">
          <label class="form-label"><i class="bi bi-percent text-primary me-1"></i> Taxa de Juros Mensal (%)</label>
          <input name="monthly_interest" id="monthly_interest" type="number" step="0.01" class="form-control" placeholder="Ex: 1.5" required>
        </div>

        <!-- Prazo -->
        <div class="col-md-3">
          <label class="form-label"><i class="bi bi-calendar-range text-primary me-1"></i> Prazo de Pagamento</label>
          <select name="term_months" id="term_months" class="form-select" required>
            <option value="">-- Selecione --</option>
            <?php for($i=1;$i<=36;$i++): ?>
              <option value="<?= $i ?>"><?= $i ?> <?= ($i==1?'Mês':'Meses') ?></option>
            <?php endfor; ?>
          </select>
        </div>

        <!-- Data de início -->
        <div class="col-md-3">
          <label class="form-label"><i class="bi bi-calendar-event text-primary me-1"></i> Data de Início</label>
          <input type="date" name="start_date" id="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
        </div>

      </div>

      <!-- SIMULADOR / CRONOGRAMA EM TEMPO REAL -->
      <div class="mt-5 preview-box" id="previewArea">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
          <h5 class="fw-bold mb-0"><i class="bi bi-calculator text-primary me-2"></i> Resumo Financeiro & Cronograma Completo</h5>
          
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-2 rounded-pill fw-semibold" id="summaryBadge">Preencha os campos</span>
            
            <!-- BOTÃO DE IMPRIMIR TUDO -->
            <button type="button" class="btn btn-sm btn-primary rounded-3 px-3 fw-semibold action-buttons" onclick="window.print();">
              <i class="bi bi-printer-fill me-1"></i> Imprimir Tudo / Gerar PDF
            </button>
          </div>
        </div>

        <!-- Cards com Resumo de Valores -->
        <div class="row g-3 text-center mb-4">
          <div class="col-md-4">
            <div class="p-3 border rounded-3 bg-body">
              <small class="text-muted d-block mb-1">Valor da Parcela Mensal</small>
              <h4 class="fw-bold text-primary mb-0" id="simMonthlyPayment">MZN 0.00</h4>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 border rounded-3 bg-body">
              <small class="text-muted d-block mb-1">Total de Juros do Período</small>
              <h4 class="fw-bold text-warning mb-0" id="simTotalInterest">MZN 0.00</h4>
            </div>
          </div>
          <div class="col-md-4">
            <div class="p-3 border rounded-3 bg-body">
              <small class="text-muted d-block mb-1">Montante Total a Devolver</small>
              <h4 class="fw-bold text-success mb-0" id="simTotalWithInterest">MZN 0.00</h4>
            </div>
          </div>
        </div>

        <!-- Tabela de Previsão de Parcelas -->
        <h6 class="fw-bold text-secondary mb-2"><i class="bi bi-list-ol me-1"></i> Tabela Detalhada de Vencimentos</h6>
        <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
          <table class="table table-sm table-striped align-middle mb-0" id="scheduleTable">
            <thead>
              <tr style="color: var(--text-muted); border-bottom: 2px solid var(--border-color);">
                <th style="width: 15%;">Parcela</th>
                <th style="width: 35%;">Data Estimada de Vencimento</th>
                <th style="width: 50%;">Valor da Parcela</th>
              </tr>
            </thead>
            <tbody id="scheduleBody">
              <tr>
                <td colspan="3" class="text-center text-muted py-3">Aguardando dados para gerar o cronograma...</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Termo de Impressão (Assinaturas visíveis só na Impressão) -->
      <div class="print-signatures mt-5 pt-4">
        <div class="row w-100 text-center" style="margin-top: 30px;">
          <div class="col-6">
            <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto; padding-top: 5px;">
              <strong>Assinatura do Cliente</strong>
            </div>
          </div>
          <div class="col-6">
            <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto; padding-top: 5px;">
              <strong>Assinatura / Carimbo Shelton Business</strong>
            </div>
          </div>
        </div>
      </div>

      <!-- Botões de Ação na Tela -->
      <div class="mt-4 d-flex justify-content-end gap-2 action-buttons">
        <a href="dashboard.php" class="btn btn-light px-4 py-2 rounded-3">Cancelar</a>
        <button class="btn btn-primary px-4 py-2 fw-semibold rounded-3 shadow-sm" type="submit">
          <i class="bi bi-check-circle-fill me-1"></i> Criar Empréstimo
        </button>
      </div>

    </form>
  </div>

  <!-- FOOTER -->
  <footer class="mt-5 pt-3 border-top text-center small" style="border-color: var(--border-color) !important; color: var(--text-muted);">
    <p class="mb-0">© <?= date('Y') ?> Shelton Business — Sistema de Microcrédito</p>
  </footer>
</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  document.addEventListener('DOMContentLoaded', () => {
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

    // --- AUTOMAÇÃO DE JUROS COM BASE NO TIPO DE EMPRÉSTIMO ---
    const loanTypeSelect = document.getElementById('loan_type_id');
    const monthlyInterestInput = document.getElementById('monthly_interest');

    loanTypeSelect.addEventListener('change', function() {
      const selectedOption = this.options[this.selectedIndex];
      const monthlyRate = selectedOption.getAttribute('data-monthly');
      
      if (monthlyRate) {
        monthlyInterestInput.value = monthlyRate;
      } else if (this.value === 'custom') {
        monthlyInterestInput.value = '';
        monthlyInterestInput.focus();
      } else {
        monthlyInterestInput.value = '';
      }
      calculateLoan();
    });

    // --- CÁLCULO DINÂMICO E CRONOGRAMA ---
    const amountInput = document.getElementById('amount');
    const termMonthsSelect = document.getElementById('term_months');
    const startDateInput = document.getElementById('start_date');

    const simMonthlyPayment = document.getElementById('simMonthlyPayment');
    const simTotalInterest = document.getElementById('simTotalInterest');
    const simTotalWithInterest = document.getElementById('simTotalWithInterest');
    const scheduleBody = document.getElementById('scheduleBody');
    const summaryBadge = document.getElementById('summaryBadge');

    function calculateLoan() {
      const amount = parseFloat(amountInput.value) || 0;
      const monthlyRate = parseFloat(monthlyInterestInput.value) || 0;
      const termMonths = parseInt(termMonthsSelect.value) || 0;
      const startDateStr = startDateInput.value;

      if (amount <= 0 || termMonths <= 0) {
        simMonthlyPayment.textContent = 'MZN 0.00';
        simTotalInterest.textContent = 'MZN 0.00';
        simTotalWithInterest.textContent = 'MZN 0.00';
        summaryBadge.textContent = 'Preencha os campos';
        scheduleBody.innerHTML = `<tr><td colspan="3" class="text-center text-muted py-3">Informe montante e prazo válidos.</td></tr>`;
        return;
      }

      // Cálculo de Juros Composto Mensal
      let totalWithInterest = amount;
      if (monthlyRate > 0) {
        totalWithInterest = amount * Math.pow(1 + (monthlyRate / 100), termMonths);
      }
      
      const totalInterest = totalWithInterest - amount;
      const monthlyPayment = totalWithInterest / termMonths;

      simMonthlyPayment.textContent = 'MZN ' + monthlyPayment.toLocaleString('pt-MZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      simTotalInterest.textContent = 'MZN ' + totalInterest.toLocaleString('pt-MZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      simTotalWithInterest.textContent = 'MZN ' + totalWithInterest.toLocaleString('pt-MZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      summaryBadge.textContent = `${termMonths} Parcelas Calculadas`;

      // Gerar Cronograma de Vencimentos Mensais
      let htmlRows = '';
      let baseDate = startDateStr ? new Date(startDateStr) : new Date();

      for (let i = 1; i <= termMonths; i++) {
        // Avançar 1 mês para cada parcela
        baseDate.setMonth(baseDate.getMonth() + 1);
        const formattedDate = baseDate.toLocaleDateString('pt-BR');

        htmlRows += `
          <tr>
            <td class="fw-semibold">#${i}</td>
            <td>${formattedDate}</td>
            <td class="fw-bold text-primary">MZN ${monthlyPayment.toLocaleString('pt-MZ', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
          </tr>
        `;
      }
      scheduleBody.innerHTML = htmlRows;
    }

    // Ouvintes de eventos para recalcular em tempo real
    amountInput.addEventListener('input', calculateLoan);
    monthlyInterestInput.addEventListener('input', calculateLoan);
    termMonthsSelect.addEventListener('change', calculateLoan);
    startDateInput.addEventListener('change', calculateLoan);
  });
</script>

</body>
</html>