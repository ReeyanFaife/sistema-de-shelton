<?php
// register.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

// Se o usuário já estiver logado, redireciona para o dashboard
if (isset($_SESSION['user'])) {
    header('Location: dashboard.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!empty($name) && !empty($username) && !empty($password)) {
        // Criptografa a senha de forma segura com BCRYPT
        $hash = password_hash($password, PASSWORD_BCRYPT);

        try {
            // Insere o novo usuário na tabela 'users' do PostgreSQL
            $stmt = $pdo->prepare("INSERT INTO users (name, username, password_hash, role) VALUES (?, ?, ?, 'admin')");
            $stmt->execute([$name, $username, $hash]);

            $message = "Conta criada com sucesso! Você já pode acessar a plataforma.";
        } catch (PDOException $e) {
            $error = "Este nome de utilizador já está em uso. Por favor, escolha outro.";
        }
    } else {
        $error = "Por favor, preencha todos os campos.";
    }
}
?>
<!doctype html>
<html lang="pt">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cadastrar - Shelton Business | Microcrédito</title>
  <!-- Bootstrap 5.3.2 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Ícones do Bootstrap -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  
  <style>
    :root {
      --primary-color: #0d6efd;
      --text-dark: #212529;
    }

    body, html {
      height: 100%;
      margin: 0;
      font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
    }

    body {
      background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.7)), 
                        url('https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?q=80&w=1920&auto=format&fit=crop');
      background-no-repeat: no-repeat;
      background-position: center;
      background-size: cover;
      background-attachment: fixed;
    }

    .brand {
      font-weight: 800;
      letter-spacing: -1px;
      font-size: 3rem;
      color: white;
      text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
      margin-bottom: 0;
    }

    .subtitle {
      font-weight: 400;
      font-size: 1.25rem;
      color: rgba(255, 255, 255, 0.85);
      text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
      margin-top: -5px;
      margin-bottom: 30px;
    }

    /* Efeito Glassmorphism no Cartão */
    .card-register {
      border: none;
      border-radius: 20px;
      backdrop-filter: blur(10px);
      background-color: rgba(255, 255, 255, 0.85);
      box-shadow: 0 15px 35px rgba(0,0,0,0.2);
      padding: 40px !important;
    }

    .form-label {
      font-weight: 600;
      color: var(--text-dark);
      font-size: 0.9rem;
      margin-bottom: 0.5rem;
    }

    .form-control {
      border-radius: 10px;
      padding: 12px 15px;
      border: 1px solid #ced4da;
      color: black !important;
      background-color: white;
      transition: all 0.2s ease-in-out;
    }

    .form-control:focus {
      border-color: #86b7fe;
      box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
    }

    .form-control::placeholder {
      color: #6c757d;
      opacity: 0.8;
    }

    .input-group-text {
      background-color: white;
      border-right: none;
      border-radius: 10px 0 0 10px;
      color: #6c757d;
    }

    .input-group .form-control {
      border-left: none;
      border-radius: 0 10px 10px 0;
    }

    .btn-register {
      border-radius: 10px;
      padding: 12px;
      font-weight: 600;
      font-size: 1.1rem;
      letter-spacing: 0.5px;
      transition: all 0.3s ease;
    }

    .btn-register:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(13, 110, 253, 0.3);
    }

    .copyright {
      font-weight: 500;
      font-size: 0.9rem;
      color: rgba(255, 255, 255, 0.7);
      margin-top: 25px;
      text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
    }

    .is-invalid + .invalid-feedback {
      display: block;
    }
  </style>
</head>
<body>

<div class="container vh-100 d-flex align-items-center justify-content-center">
  <div class="col-11 col-sm-9 col-md-7 col-lg-5 col-xl-4">
    
    <!-- Cabeçalho -->
    <div class="text-center">
      <h1 class="brand">Shelton Business</h1>
      <p class="subtitle">Sistema de Microcrédito</p>
    </div>

    <!-- Card de Cadastro -->
    <div class="card card-register p-4">
      <h3 class="text-center mb-4 fw-bold" style="color: var(--text-dark);">Criar Nova Conta</h3>
      
      <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show small py-2" role="alert">
          <i class="bi bi-check-circle-fill me-1"></i> <?= htmlspecialchars($message) ?><br>
          <a href="login.php" class="fw-bold text-success">Clique aqui para Entrar</a>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show small py-2" role="alert">
          <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error) ?>
          <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      <?php endif; ?>

      <form action="register.php" method="post" id="registerForm" novalidate>
        
        <!-- Nome Completo -->
        <div class="mb-3">
          <label for="name" class="form-label">Nome Completo</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-card-heading"></i></span>
            <input type="text" name="name" id="name" class="form-control" required placeholder="Ex: Shelton Faife">
            <div class="invalid-feedback">Por favor, insira seu nome.</div>
          </div>
        </div>

        <!-- Nome de Utilizador -->
        <div class="mb-3">
          <label for="username" class="form-label">Nome de utilizador</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" name="username" id="username" class="form-control" required placeholder="Digite o nome de utilizador">
            <div class="invalid-feedback">Por favor, insira um nome de utilizador.</div>
          </div>
        </div>
        
        <!-- Senha -->
        <div class="mb-4">
          <label for="password" class="form-label">Senha</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" id="password" class="form-control" required placeholder="Crie uma senha forte">
            <div class="invalid-feedback">Por favor, defina uma senha.</div>
          </div>
        </div>
        
        <!-- Botão Cadastrar -->
        <div class="d-grid mb-3">
          <button class="btn btn-primary btn-register" type="submit">
            Cadastrar Conta <i class="bi bi-person-check-fill ms-2"></i>
          </button>
        </div>
        
      </form>

      <!-- Voltar ao Login -->
      <div class="text-center pt-3 border-top">
        <p class="small text-muted mb-2">Já possui uma conta?</p>
        <a href="login.php" class="btn btn-outline-secondary w-100 py-2 fw-semibold" style="border-radius: 10px;">
          <i class="bi bi-arrow-left me-1"></i> Voltar para o Login
        </a>
      </div>

    </div>

    <!-- Rodapé -->
    <p class="text-center copyright">© <?php echo date('Y'); ?> Shelton Business. Todos os direitos reservados.</p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.getElementById('registerForm').addEventListener('submit', function(e){
  this.classList.remove('was-validated');
  const inputs = this.querySelectorAll('.form-control');
  inputs.forEach(input => input.classList.remove('is-invalid'));

  const n = this.name;
  const u = this.username;
  const p = this.password;
  let isValid = true;

  if (!n.value.trim()) {
    n.classList.add('is-invalid');
    isValid = false;
  }

  if (!u.value.trim()) {
    u.classList.add('is-invalid');
    isValid = false;
  }

  if (!p.value.trim()) {
    p.classList.add('is-invalid');
    isValid = false;
  }

  if (!isValid) {
    e.preventDefault();
    this.querySelector('.is-invalid').focus();
  } else {
    this.classList.add('was-validated');
  }
});
</script>

</body>
</html>
