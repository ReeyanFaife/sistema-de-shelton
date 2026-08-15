<?php
// create_user.php
require_once 'functions.php';
require_login();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'user';

    if (!empty($name) && !empty($username) && !empty($password)) {
        // Criptografa a palavra-passe de forma segura com BCRYPT
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            $stmt = $pdo->prepare("INSERT INTO users (name, username, password_hash, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $username, $hash, $role]);
            $message = "<div class='alert alert-success'>Utilizador '$username' criado com sucesso!</div>";
        } catch (PDOException $e) {
            $message = "<div class='alert alert-danger'>Erro ao criar utilizador: Nome de utilizador já existe.</div>";
        }
    } else {
        $message = "<div class='alert alert-warning'>Preencha todos os campos.</div>";
    }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <title>Criar Utilizador - Shelton Business</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
  <div class="container" style="max-width: 500px;">
    <div class="card shadow-sm p-4">
      <h4 class="mb-3">Novo Utilizador</h4>
      <?= $message ?>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label">Nome Completo</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Nome de Utilizador (Username)</label>
          <input type="text" name="username" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Palavra-passe</label>
          <input type="password" name="password" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Nível de Acesso</label>
          <select name="role" class="form-select">
            <option value="admin">Administrador</option>
            <option value="user">Operador</option>
          </select>
        </div>
        <button type="submit" class="btn btn-primary w-100">Salvar Utilizador</button>
        <a href="dashboard.php" class="btn btn-link w-100 mt-2 text-center">Voltar ao Painel</a>
      </form>
    </div>
  </div>
</body>
</html>
