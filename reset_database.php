<?php
// reset_database.php - Reseta TUDO e recria as tabelas
require_once 'config.php';

try {
    echo "<h2>🔄 Resetando banco de dados...</h2>";
    
    // 1. Deletar TODAS as tabelas
    $dropAll = "
        DROP TABLE IF EXISTS audit_logs CASCADE;
        DROP TABLE IF EXISTS payments CASCADE;
        DROP TABLE IF EXISTS installments CASCADE;
        DROP TABLE IF EXISTS loans CASCADE;
        DROP TABLE IF EXISTS loan_types CASCADE;
        DROP TABLE IF EXISTS clients CASCADE;
        DROP TABLE IF EXISTS users CASCADE;
    ";
    $pdo->exec($dropAll);
    echo "✅ Tabelas antigas removidas<br>";

    // 2. Recriar estrutura completa
    $sql = "
    -- Usuários
    CREATE TABLE users (
      id SERIAL PRIMARY KEY,
      name VARCHAR(150) NOT NULL,
      username VARCHAR(80) UNIQUE NOT NULL,
      password_hash VARCHAR(255) NOT NULL,
      role VARCHAR(20) NOT NULL CHECK (role IN ('admin','funcionario')),
      phone VARCHAR(40),
      created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
    );

    -- Clientes
    CREATE TABLE clients (
      id SERIAL PRIMARY KEY,
      name VARCHAR(150) NOT NULL,
      bi VARCHAR(50) UNIQUE,
      phone VARCHAR(40),
      address TEXT,
      email VARCHAR(120),
      created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
    );

    -- Tipos de empréstimos
    CREATE TABLE loan_types (
      id SERIAL PRIMARY KEY,
      name VARCHAR(100) NOT NULL,
      annual_interest NUMERIC(6,4) NOT NULL,
      default_term_months INTEGER NOT NULL,
      created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
    );

    -- Empréstimos
    CREATE TABLE loans (
      id SERIAL PRIMARY KEY,
      client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
      loan_type_id INTEGER REFERENCES loan_types(id),
      amount NUMERIC(14,2) NOT NULL,
      interest_rate NUMERIC(8,6) NOT NULL,
      term_months INTEGER NOT NULL,
      start_date DATE NOT NULL,
      total_with_interest NUMERIC(14,2) NOT NULL,
      status VARCHAR(20) NOT NULL DEFAULT 'ativo',
      created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
    );

    -- Parcelas
    CREATE TABLE installments (
      id SERIAL PRIMARY KEY,
      loan_id INTEGER REFERENCES loans(id) ON DELETE CASCADE,
      installment_number INTEGER NOT NULL,
      due_date DATE NOT NULL,
      amount_due NUMERIC(14,2) NOT NULL,
      amount_paid NUMERIC(14,2) DEFAULT 0,
      paid_at TIMESTAMP WITH TIME ZONE,
      status VARCHAR(20) DEFAULT 'pendente',
      created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
    );

    -- Pagamentos
    CREATE TABLE payments (
      id SERIAL PRIMARY KEY,
      installment_id INTEGER REFERENCES installments(id) ON DELETE SET NULL,
      loan_id INTEGER REFERENCES loans(id) ON DELETE SET NULL,
      amount NUMERIC(14,2) NOT NULL,
      paid_by INTEGER REFERENCES users(id),
      paid_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
      receipt TEXT
    );

    -- Histórico de ações (Audit Log)
    CREATE TABLE audit_logs (
      id SERIAL PRIMARY KEY,
      user_id INTEGER REFERENCES users(id),
      action TEXT NOT NULL,
      created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
    );
    ";

    $pdo->exec($sql);
    echo "✅ Todas as tabelas criadas com sucesso!<br>";

    // 3. Criar usuário admin padrão
    $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("
        INSERT INTO users (name, username, password_hash, role, phone)
        VALUES (:name, :username, :hash, :role, :phone)
    ");
    $stmt->execute([
        ':name' => 'Administrador',
        ':username' => 'admin',
        ':hash' => $adminPassword,
        ':role' => 'admin',
        ':phone' => '+244923456789'
    ]);
    echo "✅ Usuário admin criado (user: admin, password: admin123)<br>";

    // 4. Criar alguns tipos de empréstimo padrão
    $stmt = $pdo->prepare("
        INSERT INTO loan_types (name, annual_interest, default_term_months)
        VALUES (:name, :interest, :months)
    ");
    $stmt->execute([':name' => 'Empréstimo Pessoal', ':interest' => 0.15, ':months' => 12]);
    $stmt->execute([':name' => 'Empréstimo Comercial', ':interest' => 0.12, ':months' => 24]);
    $stmt->execute([':name' => 'Empréstimo Habitacional', ':interest' => 0.08, ':months' => 120]);
    echo "✅ Tipos de empréstimo criados<br>";

    echo "<h2 style='color: green;'>✅ BANCO RESETADO COM SUCESSO!</h2>";
    echo "<p>Agora você pode:</p>";
    echo "<ol>";
    echo "<li>Fazer <strong>login</strong> com: <br>User: <code>admin</code><br>Password: <code>admin123</code></li>";
    echo "<li>Ou <strong><a href='register.php'>registrar novo usuário</a></strong></li>";
    echo "</ol>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ ERRO:</h2>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}
?>
