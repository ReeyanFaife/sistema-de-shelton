<?php
// create_user.php
require_once 'functions.php';
require_login();
require_role('admin');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'funcionario';
    $allowed_roles = ['admin', 'funcionario'];

    if (!in_array($role, $allowed_roles, true)) {
        $message = "<div class='alert alert-warning'>Nivel de acesso invalido.</div>";
    } elseif ($name === '' || $username === '' || $password === '') {
        $message = "<div class='alert alert-warning'>Preencha todos os campos.</div>";
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, username, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $username, $hash, $role]);
            $safe_username = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
            $message = "<div class='alert alert-success'>Utilizador '$safe_username' criado com sucesso.</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Erro ao criar utilizador. Verifique se o nome de utilizador ja existe.</div>";
        }
    }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Criar Utilizador - Shelton Business</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      min-height: 100vh;
      background: #f8fafc;
      font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    .page-shell {
      min-height: 100vh;
      display: grid;
      place-items: center;
      padding: 24px;
    }

    .user-card {
      width: min(100%, 520px);
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      box-shadow: 0 18px 45px rgba(15, 23, 42, 0.08);
    }

    .icon-box {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      background: #eff6ff;
      color: #2563eb;
      font-size: 1.25rem;
      flex: 0 0 auto;
    }

    @media (max-width: 576px) {
      .page-shell {
        display: block;
        padding: 14px;
      }

      .user-card {
        min-height: calc(100vh - 28px);
        border-radius: 12px;
      }
    }
  </style>
</head>
<body>
  <main class="page-shell">
    <div class="card user-card p-4 p-sm-5">
      <div class="d-flex align-items-center gap-3 mb-4">
        <span class="icon-box"><i class="bi bi-person-plus-fill"></i></span>
        <div>
          <h4 class="mb-1 fw-bold">Novo Utilizador</h4>
          <p class="mb-0 text-secondary small">Crie uma conta para acesso ao sistema.</p>
        </div>
      </div>

      <?= $message ?>

      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Nome Completo</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Nome de Utilizador</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Palavra-passe</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Nivel de Acesso</label>
          <select name="role" class="form-select">
            <option value="funcionario" selected>Operador</option>
            <option value="admin">Administrador</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary w-100 py-2">Salvar Utilizador</button>
        <a href="dashboard.php" class="btn btn-outline-secondary w-100 mt-2">Voltar ao Painel</a>
      </form>
    </div>
  </main>
</body>
</html>
