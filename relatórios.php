<?php
// relatorios.php
require_once 'functions.php';
require_login();

global $pdo;

// --- Função para calcular parcela mensal ---
function calcMonthlyPayment($amount, $monthly_interest, $term_months) {
    if ($term_months <= 0) return $amount;
    $r = $monthly_interest / 100;
    if ($r == 0) return $amount / $term_months;
    return $amount * $r / (1 - pow(1 + $r, -$term_months));
}

// --- 1. HISTÓRICO DE PAGAMENTOS CONFIRMADOS ---
$payments = $pdo->query("
    SELECT 
        p.id,
        c.id AS client_id,
        c.name AS cliente,
        p.amount AS valor_pago,
        p.paid_by AS pago_por,
        p.paid_at AS data_pagamento
    FROM payments p
    JOIN clients c ON c.id = p.client_id
    ORDER BY p.paid_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// --- 2. CLIENTES EM DÍVIDA ---
$debtors = $pdo->query("
    SELECT 
        c.id AS client_id, 
        c.name, 
        c.phone,
        l.id AS loan_id,
        l.amount,
        l.monthly_interest,
        l.term_months,
        l.start_date,
        l.status,
        COALESCE(SUM(i.amount_due - COALESCE(i.amount_paid, 0)), 0) AS devida_parcelas,
        COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.client_id = c.id), 0) AS total_pago_cliente,
        CASE 
            WHEN EXISTS (
                SELECT 1 FROM installments i2 
                WHERE i2.loan_id = l.id 
                  AND i2.due_date < CURRENT_DATE 
                  AND (i2.amount_due - COALESCE(i2.amount_paid, 0)) > 0
            ) THEN 'Parcelas vencidas'
            WHEN l.start_date IS NOT NULL 
                 AND CURRENT_DATE > (l.start_date + (l.term_months || ' months')::interval)
                 AND l.status = 'ativo' THEN 'Prazo vencido'
            ELSE NULL
        END AS motivo
    FROM clients c
    JOIN loans l ON l.client_id = c.id
    LEFT JOIN installments i ON i.loan_id = l.id
    WHERE l.status = 'ativo'
      AND (
          (i.due_date < CURRENT_DATE AND (i.amount_due - COALESCE(i.amount_paid, 0)) > 0)
          OR (
              l.start_date IS NOT NULL 
              AND CURRENT_DATE > (l.start_date + (l.term_months || ' months')::interval)
          )
      )
    GROUP BY c.id, c.name, c.phone, l.id, l.amount, l.monthly_interest, l.term_months, l.start_date, l.status
    ORDER BY devida_parcelas DESC
")->fetchAll(PDO::FETCH_ASSOC);

// --- 3. EMPRÉSTIMOS ATIVOS E CADASTRADOS ---
$active = $pdo->query("
    SELECT 
        l.*, 
        c.id AS client_id,
        c.name AS client_name,
        COALESCE(l.status, 'ativo') AS status_real,
        COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.client_id = c.id), 0) AS total_pago_cliente
    FROM loans l
    JOIN clients c ON l.client_id = c.id 
    ORDER BY l.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// --- EXPORTAÇÃO CSV (VIA SERVIDOR) ---
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $type = $_GET['type'] ?? 'completo';
    $filename = "relatorio_" . $type . "_" . date('Y-m-d_H-i') . ".csv";

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $output = fopen('php://output', 'w');
    // Adiciona BOM para UTF-8 (corrige acentuação no Excel)
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));

    if ($type === 'devedores') {
        fputcsv($output, ['Cliente', 'Contacto', 'Montante Emprestado', 'Saldo Devedor (MZN)', 'Motivo/Status'], ';');
        foreach ($debtors as $d) {
            $pmt = calcMonthlyPayment($d['amount'], $d['monthly_interest'], $d['term_months']);
            $total_com_juros = $pmt * $d['term_months'];
            $saldo_devedor = max(0, $total_com_juros - $d['total_pago_cliente']);
            fputcsv($output, [
                $d['name'],
                $d['phone'] ?? '-',
                number_format($d['amount'], 2, ',', '.'),
                number_format($saldo_devedor, 2, ',', '.'),
                $d['motivo'] ?? 'Pendente'
            ], ';');
        }
    } elseif ($type === 'pagamentos') {
        fputcsv($output, ['Cliente', 'Valor Pago (MZN)', 'Processado Por', 'Data/Hora'], ';');
        foreach ($payments as $p) {
            fputcsv($output, [
                $p['cliente'],
                number_format($p['valor_pago'], 2, ',', '.'),
                $p['pago_por'] ?? 'Sistema',
                date('d/m/Y H:i', strtotime($p['data_pagamento']))
            ], ';');
        }
    } else {
        // Relatório de Empréstimos
        fputcsv($output, ['Cliente', 'Montante Base (MZN)', 'Total c/ Juros (MZN)', 'Prazo (Meses)', 'Parcela Mensal (MZN)', 'Total Pago (MZN)', 'Status'], ';');
        foreach ($active as $a) {
            $pmt = calcMonthlyPayment($a['amount'], $a['monthly_interest'], $a['term_months']);
            $total_with_interest = $pmt * $a['term_months'];
            fputcsv($output, [
                $a['client_name'],
                number_format($a['amount'], 2, ',', '.'),
                number_format($total_with_interest, 2, ',', '.'),
                $a['term_months'],
                number_format($pmt, 2, ',', '.'),
                number_format($a['total_pago_cliente'], 2, ',', '.'),
                ucfirst($a['status_real'])
            ], ';');
        }
    }

    fclose($output);
    exit;
}

// --- CÁLCULOS DE RESUMO ---
$total_recebido = array_sum(array_column($payments, 'valor_pago'));
$total_mensal = 0;
$total_emprestado = 0;

foreach ($active as $a) {
    if (strtolower($a['status_real']) === 'ativo') {
        $total_emprestado += $a['amount'];
        $total_mensal += calcMonthlyPayment($a['amount'], $a['monthly_interest'], $a['term_months']);
    }
}
?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios - Shelton Business</title>
    <!-- Google Fonts & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <!-- SheetJS (Para Exportar Excel via Browser) -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.min.js"></script>
    <style>
        :root {
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --text-main: #0f172a;
            --text-muted: #64748b;
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --radius: 16px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: 'Plus Jakarta Sans', sans-serif; 
            background: var(--bg-body); 
            color: var(--text-main); 
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header { 
            background: var(--surface); 
            border-bottom: 1px solid var(--border);
            padding: 20px 0; 
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--text-main);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .container { 
            max-width: 1400px; 
            margin: 32px auto; 
            padding: 0 24px; 
            flex: 1;
            width: 100%;
        }

        .btn { 
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 18px; 
            border: 1px solid transparent; 
            border-radius: 10px; 
            cursor: pointer; 
            font-weight: 600; 
            font-size: 14px;
            transition: all 0.2s ease; 
            text-decoration: none;
        }
        .btn-secondary { background: #f1f5f9; color: var(--text-main); border-color: var(--border); }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-success { background: #ecfdf5; color: #047857; border-color: #a7f3d0; }
        .btn-success:hover { background: #d1fae5; }
        .btn-primary { background: #eff6ff; color: #1d4ed8; border-color: #bfdbfe; }
        .btn-primary:hover { background: #dbeafe; }

        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
            border-radius: 6px;
        }

        /* CARDS KPI LADO A LADO */
        .stats-grid { 
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px; 
            margin-bottom: 32px; 
        }
        .stat-card { 
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 20px; 
            border-radius: var(--radius); 
            box-shadow: var(--shadow-sm); 
            display: flex; 
            align-items: center; 
            gap: 16px; 
        }
        .stat-icon { 
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px; 
        }
        .icon-blue { background: #eff6ff; color: #2563eb; }
        .icon-green { background: #ecfdf5; color: var(--success); }
        .icon-amber { background: #fffbeb; color: var(--warning); }
        .icon-red { background: #fef2f2; color: var(--danger); }

        .stat-content label { 
            display: block;
            font-size: 12px; 
            font-weight: 600; 
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }
        .stat-content span { 
            font-size: 20px; 
            font-weight: 700; 
            color: var(--text-main);
        }

        /* PAINÉIS / TABELAS LADO A LADO */
        .panels-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 24px;
            align-items: start;
        }

        .panel-full {
            grid-column: 1 / -1;
        }

        .card-panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            height: 100%;
        }
        .panel-header {
            padding: 18px 24px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #fafafa;
        }
        .panel-title {
            font-size: 16px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .panel-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }
        th {
            background: #f8fafc;
            color: var(--text-muted);
            font-weight: 600;
            padding: 12px 18px;
            border-bottom: 1px solid var(--border);
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        td {
            padding: 12px 18px;
            border-bottom: 1px solid var(--border);
            color: var(--text-main);
            vertical-align: middle;
        }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-danger { background: #fef2f2; color: #b91c1c; }
        .badge-warning { background: #fffbeb; color: #b45309; }
        .badge-success { background: #ecfdf5; color: #047857; }
        .badge-secondary { background: #f1f5f9; color: #475569; }

        footer { 
            text-align: center; 
            color: var(--text-muted); 
            font-size: 13px; 
            padding: 24px; 
            border-top: 1px solid var(--border); 
            background: var(--surface); 
            margin-top: auto;
        }

        /* Estilos de Impressão (PDF) */
        @media print {
            header, footer, .btn, .panel-actions { display: none !important; }
            body { background: white; }
            .container { margin: 0; max-width: 100%; padding: 0; }
            .card-panel { border: none; box-shadow: none; margin-bottom: 20px; page-break-inside: avoid; }
            .panels-grid { display: block; }
        }

        @media (max-width: 1024px) {
            .panels-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>

<header>
    <div class="header-content">
        <div class="header-title">
            <i class="fas fa-chart-line" style="color: var(--primary);"></i> Relatórios Financeiros
        </div>
        <div class="header-actions">
            <!-- Botão Imprimir / Salvar PDF geral -->
            <button onclick="window.print()" class="btn btn-primary">
                <i class="fas fa-file-pdf"></i> Imprimir / PDF
            </button>
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </div>
</header>

<div class="container">

    <!-- CARDS DE RESUMO (LADO A LADO) -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-green"><i class="fas fa-hand-holding-dollar"></i></div>
            <div class="stat-content">
                <label>Total Recebido</label>
                <span><?= number_format($total_recebido, 2, ',', '.') ?> <small style="font-size: 12px;">MZN</small></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-blue"><i class="fas fa-calendar-check"></i></div>
            <div class="stat-content">
                <label>Parcelas Mensais</label>
                <span><?= number_format($total_mensal, 2, ',', '.') ?> <small style="font-size: 12px;">MZN</small></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-amber"><i class="fas fa-building-columns"></i></div>
            <div class="stat-content">
                <label>Ativo Emprestado</label>
                <span><?= number_format($total_emprestado, 2, ',', '.') ?> <small style="font-size: 12px;">MZN</small></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-red"><i class="fas fa-user-slash"></i></div>
            <div class="stat-content">
                <label>Em Inadimplência</label>
                <span><?= count($debtors) ?> <small style="font-size: 12px;">Clientes</small></span>
            </div>
        </div>
    </div>

    <!-- PAINÉIS PRINCIPAIS (LADO A LADO) -->
    <div class="panels-grid">

        <!-- PAINEL 1: CLIENTES EM DÍVIDA -->
        <div class="card-panel">
            <div class="panel-header">
                <div class="panel-title" style="color: var(--danger);">
                    <i class="fas fa-triangle-exclamation"></i> Clientes em Dívida
                </div>
                <div class="panel-actions">
                    <a href="?export=csv&type=devedores" class="btn btn-success btn-sm" title="Baixar em CSV">
                        <i class="fas fa-file-csv"></i> CSV
                    </a>
                    <button onclick="exportToExcel('tbl-devedores', 'Clientes_Em_Divida')" class="btn btn-secondary btn-sm" title="Baixar Excel">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="tbl-devedores">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Contacto</th>
                            <th>Emprestado</th>
                            <th>Saldo Devedor</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($debtors)): ?>
                            <?php foreach($debtors as $d): 
                                $pmt = calcMonthlyPayment($d['amount'], $d['monthly_interest'], $d['term_months']);
                                $total_com_juros = $pmt * $d['term_months'];
                                $saldo_devedor = max(0, $total_com_juros - $d['total_pago_cliente']);
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
                                    <td><?= htmlspecialchars($d['phone'] ?? '-') ?></td>
                                    <td><?= number_format($d['amount'], 2, ',', '.') ?></td>
                                    <td style="color: var(--danger); font-weight:700;"><?= number_format($saldo_devedor, 2, ',', '.') ?> MZN</td>
                                    <td>
                                        <?php if ($d['motivo'] === 'Prazo vencido'): ?>
                                            <span class="badge badge-warning"><i class="fas fa-clock"></i> Prazo vencido</span>
                                        <?php elseif ($d['motivo'] === 'Parcelas vencidas'): ?>
                                            <span class="badge badge-danger"><i class="fas fa-circle-exclamation"></i> Atrasado</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Pendente</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 24px;">
                                    <i class="fas fa-check-circle" style="color: var(--success);"></i> Nenhum cliente em dívida.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PAINEL 2: PAGAMENTOS CONFIRMADOS -->
        <div class="card-panel">
            <div class="panel-header">
                <div class="panel-title" style="color: var(--success);">
                    <i class="fas fa-circle-check"></i> Pagamentos Recebidos
                </div>
                <div class="panel-actions">
                    <a href="?export=csv&type=pagamentos" class="btn btn-success btn-sm" title="Baixar em CSV">
                        <i class="fas fa-file-csv"></i> CSV
                    </a>
                    <button onclick="exportToExcel('tbl-pagamentos', 'Historico_Pagamentos')" class="btn btn-secondary btn-sm" title="Baixar Excel">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="tbl-pagamentos">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Valor Pago</th>
                            <th>Processado por</th>
                            <th>Data/Hora</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($payments)): ?>
                            <?php foreach($payments as $p): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($p['cliente']) ?></strong></td>
                                    <td style="color: var(--success); font-weight:700;"><?= number_format($p['valor_pago'], 2, ',', '.') ?> MZN</td>
                                    <td><?= htmlspecialchars($p['pago_por'] ?? 'Sistema') ?></td>
                                    <td><?= date('d/m/Y H:i', strtotime($p['data_pagamento'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 24px;">Nenhum pagamento registrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PAINEL 3: EMPRÉSTIMOS CADASTRADOS -->
        <div class="card-panel panel-full">
            <div class="panel-header">
                <div class="panel-title" style="color: var(--primary);">
                    <i class="fas fa-briefcase"></i> Empréstimos Cadastrados
                </div>
                <div class="panel-actions">
                    <a href="?export=csv&type=emprestimos" class="btn btn-success btn-sm" title="Baixar em CSV">
                        <i class="fas fa-file-csv"></i> CSV
                    </a>
                    <button onclick="exportToExcel('tbl-emprestimos', 'Emprestimos_Cadastrados')" class="btn btn-secondary btn-sm" title="Baixar Excel">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                </div>
            </div>
            <div class="table-responsive">
                <table id="tbl-emprestimos">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Montante Base</th>
                            <th>Total c/ Juros</th>
                            <th>Prazo</th>
                            <th>Parcela Mensal</th>
                            <th>Total Pago</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($active)): ?>
                            <?php foreach($active as $a): 
                                $pmt = calcMonthlyPayment($a['amount'], $a['monthly_interest'], $a['term_months']);
                                $total_with_interest = $pmt * $a['term_months'];
                                $status_clean = strtolower($a['status_real']);
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($a['client_name']) ?></strong></td>
                                    <td><?= number_format($a['amount'], 2, ',', '.') ?> MZN</td>
                                    <td><?= number_format($total_with_interest, 2, ',', '.') ?> MZN</td>
                                    <td><?= (int)$a['term_months'] ?> meses</td>
                                    <td><strong><?= number_format($pmt, 2, ',', '.') ?> MZN</strong></td>
                                    <td style="color: var(--success); font-weight:600;"><?= number_format($a['total_pago_cliente'], 2, ',', '.') ?> MZN</td>
                                    <td>
                                        <?php if ($status_clean === 'ativo'): ?>
                                            <span class="badge badge-success">Ativo</span>
                                        <?php elseif ($status_clean === 'pago'): ?>
                                            <span class="badge badge-secondary">Quitado</span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><?= htmlspecialchars($a['status_real']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 24px;">Nenhum empréstimo cadastrado.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<footer>
    © <b>Shelton Business</b> • Sistema Integrado de Microcrédito
</footer>

<script>
// Função para Exportar qualquer tabela para Excel (.xlsx) via JavaScript
function exportToExcel(tableId, filename) {
    const table = document.getElementById(tableId);
    const wb = XLSX.utils.table_to_book(table, { sheet: "Relatório" });
    XLSX.writeFile(wb, filename + "_" + new Date().toISOString().slice(0, 10) + ".xlsx");
}
</script>

</body>
</html>