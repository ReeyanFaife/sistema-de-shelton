<?php
// migrate.php
require_once 'config.php';

try {
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Elimina tabelas antigas (se existirem) para aplicar a nova estrutura sem conflitos
    $dropTables = "
        DROP TABLE IF EXISTS audit_logs CASCADE;
        DROP TABLE IF EXISTS payments CASCADE;
        DROP TABLE IF EXISTS installments CASCADE;
        DROP TABLE IF EXISTS loans CASCADE;
        DROP TABLE IF EXISTS loan_types CASCADE;
        DROP TABLE IF EXISTS clients CASCADE;
        DROP TABLE IF EXISTS users CASCADE;
    ";
    $pdo->exec($dropTables);

    // 2. Criação da nova estrutura
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
      client_id INTEGER REFERENCES clients(id) ON DELETE CASCADE,
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
    echo "<h3>✅ Nova base de dados criada com sucesso!</h3>";

    // 3. Inserção do Utilizador Administrador Padrão
    $defaultPass = password_hash('admin123', PASSWORD_DEFAULT);
    $stmtUser = $pdo->prepare("
        INSERT INTO users (name, username, password_hash, role) 
        VALUES (:n, :u, :p, :r)
    ");
    $stmtUser->execute([
        'n' => 'Administrador',
        'u' => 'admin',
        'p' => $defaultPass,
        'r' => 'admin'
    ]);

    // 4. Inserção de Tipos de Empréstimos Iniciais
    $stmtTypes = $pdo->prepare("
        INSERT INTO loan_types (name, annual_interest, default_term_months) 
        VALUES (:name, :interest, :term)
    ");
    $stmtTypes->execute(['name' => 'Empréstimo Pessoal', 'interest' => 0.1800, 'term' => 12]);
    $stmtTypes->execute(['name' => 'Empréstimo Comercial', 'interest' => 0.2400, 'term' => 24]);

    echo "<p>👤 <strong>Conta de Administrador criada:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Utilizador:</strong> admin</li>";
    echo "<li><strong>Palavra-passe:</strong> admin123</li>";
    echo "</ul>";

    echo '<p><a href="login.php" style="padding: 10px 15px; background: #2563eb; color: white; text-decoration: none; border-radius: 5px;">Ir para o Login</a></p>';

} catch (PDOException $e) {
    echo "<h3>❌ Erro ao sincronizar a base de dados:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
