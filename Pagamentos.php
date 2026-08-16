<?php
// pagamentos.php
require_once 'functions.php';
require_login();
$user = $_SESSION['user'];
global $pdo;

// --- Criar tabela payments se não existir ---
$pdo->exec("
CREATE TABLE IF NOT EXISTS payments (
    id SERIAL PRIMARY KEY,
    client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
    amount NUMERIC(10,2) NOT NULL,
    paid_by VARCHAR(100),
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
");

// --- Variáveis para mensagem ---
$message = '';
$message_type = ''; // 'success' ou 'error'

// --- Registrar pagamento ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $client_id = isset($_POST['client_id']) ? (int) $_POST['client_id'] : 0;
    $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
    $paid_by = $_SESSION['user']['username'] ?? 'Sistema';

    if ($client_id <= 0) {
        $message = "Selecione um cliente válido.";
        $message_type = 'error';
    } elseif ($amount <= 0) {
        $message = "O valor deve ser maior que zero.";
        $message_type = 'error';
    } else {
        // Inserção direta sem bloqueio de duplicidade
        // Use a single-line SQL string (no backslashes) to avoid sending
        // accidental backslash characters to the DB engine.
        $sql = "INSERT INTO payments (client_id, amount, paid_by) VALUES (:client_id, :amount, :paid_by)";
        $stmt = $pdo->prepare($sql);
        // Bind with explicit types to avoid type-mismatch (Postgres strict types)
        $stmt->bindValue(':client_id', $client_id, PDO::PARAM_INT);
        $stmt->bindValue(':amount', sprintf('%.2f', $amount));
        $stmt->bindValue(':paid_by', $paid_by, PDO::PARAM_STR);
        $stmt->execute();

        $message = "Pagamento registrado com sucesso!";
        $message_type = 'success';
    }
}

// --- Buscar TODOS os clientes cadastrados ---
$clients = $pdo->query("
    SELECT id, name 
    FROM clients 
    ORDER BY name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// --- Buscar histórico de todos os pagamentos ---
$payments = $pdo->query("
    SELECT p.id, c.name AS cliente, p.amount AS pago, p.paid_by, p.paid_at
    FROM payments p
    JOIN clients c ON c.id = p.client_id
    ORDER BY p.paid_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// --- Estatísticas ---
$total_clients = count($clients);
$total_payments = count($payments);
$total_amount_paid = 0;
foreach ($payments as $p) {
    $total_amount_paid += $p['pago'];
}
?>
<!doctype html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Pagamentos - Shelton Business</title>
    <!-- Google Fonts & Font Awesome -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer" />
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
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
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
            max-width: 1200px;
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

        .container { 
            max-width: 1200px; 
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
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            font-weight: 600; 
            font-size: 14px;
            transition: all 0.2s ease; 
            text-decoration: none;
        }
        .btn-secondary { background: #f1f5f9; color: var(--text-main); }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-primary { background: var(--primary); color: white; box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25); }
        .btn-primary:hover { background: var(--primary-hover); transform: translateY(-1px); }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text-main); }
        .btn-outline:hover { background: #f8fafc; border-color: var(--text-muted); }

        .alert {
            padding: 14px 18px;
            border-radius: 12px;
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .alert-success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .alert-error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }

        .stats-grid { 
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px; 
            margin-bottom: 32px; 
        }
        .stat-card { 
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 24px; 
            border-radius: var(--radius); 
            box-shadow: var(--shadow-sm); 
            display: flex; 
            align-items: center; 
            gap: 20px; 
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }
        .stat-icon { 
            width: 54px;
            height: 54px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px; 
        }
        .icon-blue { background: #eff6ff; color: #2563eb; }
        .icon-green { background: #ecfdf5; color: var(--success); }
        .icon-amber { background: #fffbeb; color: var(--warning); }

        .stat-content label { 
            display: block;
            font-size: 13px; 
            font-weight: 600; 
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .stat-content span { 
            font-size: 24px; 
            font-weight: 700; 
            color: var(--text-main);
        }

        .form-card { 
            background: var(--surface); 
            border: 1px solid var(--border);
            border-radius: var(--radius); 
            padding: 28px; 
            box-shadow: var(--shadow-lg); 
            margin-bottom: 32px; 
            display: none; 
            animation: fadeIn 0.3s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid var(--border);
        }
        .form-header h2 { font-size: 18px; font-weight: 700; }
        
        .form-group { margin-bottom: 20px; }
        .form-group label { 
            font-weight: 600; 
            display: block; 
            margin-bottom: 8px; 
            font-size: 14px; 
        }
        .form-control { 
            width: 100%; 
            padding: 12px 16px; 
            border: 1px solid var(--border); 
            border-radius: 10px; 
            font-size: 14px; 
            font-family: inherit;
            background: #f8fafc;
            color: var(--text-main);
            transition: all 0.2s ease; 
        }
        .form-control:focus { 
            background: #fff;
            border-color: var(--primary); 
            outline: none; 
            box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1); 
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .section-title { font-size: 18px; font-weight: 700; }

        .cards-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); 
            gap: 20px; 
        }
        .payment-card { 
            background: var(--surface); 
            border: 1px solid var(--border);
            border-radius: var(--radius); 
            padding: 20px; 
            box-shadow: var(--shadow-sm); 
            transition: all 0.2s ease; 
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        .payment-card:hover { 
            transform: translateY(-2px); 
            box-shadow: var(--shadow-md); 
        }
        .card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e0e7ff;
            color: var(--primary);
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .user-info h3 { font-size: 15px; font-weight: 600; }
        .badge {
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            background: #ecfdf5;
            color: #047857;
        }
        .card-details {
            display: flex;
            flex-direction: column;
            gap: 8px;
            font-size: 13px;
            color: var(--text-muted);
            border-top: 1px solid #f1f5f9;
            padding-top: 14px;
            margin-bottom: 16px;
        }
        .card-details div {
            display: flex;
            justify-content: space-between;
        }
        .amount-highlight {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main);
        }

        /* --- Modal do Recibo --- */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 20px;
        }
        .receipt-box {
            background: #fff;
            width: 100%;
            max-width: 450px;
            border-radius: 16px;
            padding: 32px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
            position: relative;
        }
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #e2e8f0;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        .receipt-header h2 { font-size: 20px; font-weight: 700; color: #0f172a; }
        .receipt-header p { font-size: 12px; color: #64748b; margin-top: 4px; }
        .receipt-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .receipt-row span { color: #64748b; }
        .receipt-row strong { color: #0f172a; text-align: right; }
        .receipt-total {
            border-top: 2px dashed #e2e8f0;
            margin-top: 16px;
            padding-top: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .receipt-total span { font-size: 16px; font-weight: 700; }
        .receipt-total strong { font-size: 22px; color: #4f46e5; }
        .receipt-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: #94a3b8;
        }
        .receipt-actions {
            display: flex;
            gap: 10px;
            margin-top: 24px;
        }
        .receipt-actions button { flex: 1; }

        footer { 
            text-align: center; 
            color: var(--text-muted); 
            font-size: 13px; 
            padding: 24px; 
            border-top: 1px solid var(--border); 
            background: var(--surface); 
            margin-top: auto;
        }

        /* --- Estilos de Impressão --- */
        @media print {
            body * {
                visibility: hidden;
            }
            .modal-overlay {
                position: absolute;
                left: 0; top: 0;
                width: 100%; height: auto;
                background: none;
                display: block !important;
            }
            .receipt-box, .receipt-box * {
                visibility: visible;
            }
            .receipt-box {
                box-shadow: none;
                border: 1px solid #ccc;
                position: absolute;
                left: 50%;
                top: 20px;
                transform: translateX(-50%);
                width: 100%;
                max-width: 500px;
            }
            .receipt-actions {
                display: none !important;
            }
        }

        @media (max-width: 640px) {
            .container { padding: 0 16px; margin: 20px auto; }
            .stats-grid { grid-template-columns: 1fr; }
            .cards-grid { grid-template-columns: 1fr; }
        }
    </style>
    <script>
        function toggleForm() {
            var form = document.getElementById('form');
            form.style.display = (form.style.display === 'none' || form.style.display === '') ? 'block' : 'none';
        }

        function openReceipt(id, cliente, valor, responsavel, data) {
            document.getElementById('rec-id').innerText = '#' + String(id).padStart(5, '0');
            document.getElementById('rec-cliente').innerText = cliente;
            document.getElementById('rec-valor').innerText = valor + ' MZN';
            document.getElementById('rec-resp').innerText = responsavel;
            document.getElementById('rec-data').innerText = data;
            
            document.getElementById('receiptModal').style.display = 'flex';
        }

        function closeReceipt() {
            document.getElementById('receiptModal').style.display = 'none';
        }

        function printReceipt() {
            window.print();
        }
    </script>
</head>
<body>

<header>
    <div class="header-content">
        <div class="header-title">
            <i class="fas fa-wallet" style="color: var(--primary);"></i> Shelton Business
        </div>
        <button class="btn btn-secondary" onclick="history.back()">
            <i class="fas fa-arrow-left"></i> Voltar
        </button>
    </div>
</header>

<div class="container">

    <!-- Estatísticas -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon icon-blue"><i class="fas fa-users"></i></div>
            <div class="stat-content">
                <label>Total de Clientes</label>
                <span><?= $total_clients ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-green"><i class="fas fa-receipt"></i></div>
            <div class="stat-content">
                <label>Pagamentos Registrados</label>
                <span><?= $total_payments ?></span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon icon-amber"><i class="fas fa-coins"></i></div>
            <div class="stat-content">
                <label>Total Recebido</label>
                <span><?= number_format($total_amount_paid, 2, ',', '.') ?> <small style="font-size:14px;">MZN</small></span>
            </div>
        </div>
    </div>

    <!-- Mensagens do Servidor -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $message_type ?>">
            <i class="fas <?= $message_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
            <?= $message ?>
        </div>
    <?php endif; ?>

    <!-- Seção Principal -->
    <div class="section-header">
        <h1 class="section-title">Histórico de Pagamentos</h1>
        <?php if (!empty($clients)): ?>
            <button class="btn btn-primary" onclick="toggleForm()">
                <i class="fas fa-plus"></i> Registrar Pagamento
            </button>
        <?php endif; ?>
    </div>

    <!-- Formulário para Registrar Pagamentos -->
    <?php if (!empty($clients)): ?>
    <form method="POST" id="form" class="form-card" <?= $message ? 'style="display:block;"' : '' ?>>
        <div class="form-header">
            <h2>Registrar Pagamento</h2>
            <button type="button" class="btn btn-secondary" onclick="toggleForm()" style="padding: 6px 12px;"><i class="fas fa-times"></i></button>
        </div>

        <div class="form-group">
            <label for="client_id">Selecione o Cliente</label>
            <select name="client_id" id="client_id" class="form-control" required>
                <option value="">-- Selecionar Cliente --</option>
                <?php foreach ($clients as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="amount">Valor Pago (MZN)</label>
            <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0.01" placeholder="0.00" required>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center; padding: 12px;">
            <i class="fas fa-check"></i> Confirmar Pagamento
        </button>
    </form>
    <?php else: ?>
        <div class="alert alert-error">
            <i class="fas fa-user-slash"></i> Nenhum cliente cadastrado no sistema.
        </div>
    <?php endif; ?>

    <!-- Histórico de Pagamentos -->
    <div class="cards-grid">
        <?php if (empty($payments)): ?>
            <p style="color: var(--text-muted); grid-column: 1 / -1;">Nenhum pagamento registrado até o momento.</p>
        <?php else: ?>
            <?php foreach ($payments as $p): 
                $initial = strtoupper(mb_substr($p['cliente'], 0, 1));
                $valorFormatado = number_format($p['pago'], 2, ',', '.');
                $dataFormatada = date('d/m/Y - H:i', strtotime($p['paid_at']));
            ?>
                <div class="payment-card">
                    <div>
                        <div class="card-top">
                            <div class="user-info">
                                <div class="avatar"><?= $initial ?></div>
                                <div>
                                    <h3><?= htmlspecialchars($p['cliente']) ?></h3>
                                </div>
                            </div>
                            <span class="badge"><i class="fas fa-check"></i> Recebido</span>
                        </div>
                        
                        <div class="card-details">
                            <div>
                                <span>Valor Pago</span>
                                <strong class="amount-highlight"><?= $valorFormatado ?> MZN</strong>
                            </div>
                            <div>
                                <span>Processado por</span>
                                <strong><?= htmlspecialchars($p['paid_by']) ?></strong>
                            </div>
                            <div>
                                <span>Data</span>
                                <strong><?= $dataFormatada ?></strong>
                            </div>
                        </div>
                    </div>

                    <button class="btn btn-outline" style="width: 100%; justify-content: center;" onclick="openReceipt(
                        '<?= $p['id'] ?>', 
                        '<?= htmlspecialchars(addslashes($p['cliente'])) ?>', 
                        '<?= $valorFormatado ?>', 
                        '<?= htmlspecialchars(addslashes($p['paid_by'])) ?>', 
                        '<?= $dataFormatada ?>'
                    )">
                        <i class="fas fa-print"></i> Imprimir Recibo
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

</div>

<!-- Modal do Recibo -->
<div id="receiptModal" class="modal-overlay">
    <div class="receipt-box">
        <div class="receipt-header">
            <h2>Shelton Business</h2>
            <p>Sistema Integrado de Microcrédito</p>
            <p style="margin-top: 8px; font-weight: 600; color: #0f172a;">COMPROVANTE DE PAGAMENTO</p>
        </div>

        <div class="receipt-row">
            <span>Nº do Recibo:</span>
            <strong id="rec-id">#00000</strong>
        </div>
        <div class="receipt-row">
            <span>Cliente:</span>
            <strong id="rec-cliente">---</strong>
        </div>
        <div class="receipt-row">
            <span>Data / Hora:</span>
            <strong id="rec-data">---</strong>
        </div>
        <div class="receipt-row">
            <span>Atendido por:</span>
            <strong id="rec-resp">---</strong>
        </div>

        <div class="receipt-total">
            <span>TOTAL PAGO:</span>
            <strong id="rec-valor">0,00 MZN</strong>
        </div>

        <div class="receipt-footer">
            <p>Obrigado pela preferência!</p>
            <p style="margin-top: 4px;">Este recibo serve como comprovante oficial de pagamento.</p>
        </div>

        <div class="receipt-actions">
            <button class="btn btn-secondary" onclick="closeReceipt()">Fechar</button>
            <button class="btn btn-primary" onclick="printReceipt()"><i class="fas fa-print"></i> Imprimir</button>
        </div>
    </div>
</div>

<footer>
    © <b>Shelton Business</b> • Sistema Integrado de Microcrédito
</footer>

</body>
</html>
