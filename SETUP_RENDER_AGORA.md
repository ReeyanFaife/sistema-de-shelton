# 📋 PRÓXIMOS PASSOS IMEDIATOS

## ✅ PASSO 1: Preparar seu código localmente

### 1.1 - Criar arquivo `.env` (SÓ LOCAL, nunca commit!)
Na pasta do seu projeto, crie um arquivo chamado `.env`:

```
PGHOST=dpg-da0difbl550s73d7173g-a.oregon-postgres.render.com
PGDATABASE=neondb_vrib
PGUSER=neondb_owner
PGPASSWORD=wNrpKeZxhHMtTU3WUQYd3u5I47yYy3d1
PGPORT=5432
APP_ENV=development
```

### 1.2 - Criar `.gitignore` (protege `.env`)
```
.env
.env.local
config.php.bak
```

### 1.3 - Atualizar `config.php` para ler `.env`

Seu `config.php` atual tem credenciais hardcoded. Atualize para:

```php
<?php
// config.php - VERSÃO SEGURA

// Carregar .env se existir (desenvolvimento)
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        if (!getenv($key)) {
            putenv("$key=$value");
        }
    }
}

// Obter de variáveis de ambiente (Render usa isso em produção)
$host     = getenv('PGHOST')     ?: 'localhost';
$db       = getenv('PGDATABASE') ?: 'neondb_vrib';
$user     = getenv('PGUSER')     ?: 'neondb_owner';
$pass     = getenv('PGPASSWORD') ?: '';
$port     = getenv('PGPORT')     ?: '5432';

if (empty($pass)) {
    die("❌ Erro: PGPASSWORD não configurada!");
}

$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
} catch (PDOException $e) {
    die("Erro de conexão ao banco: " . $e->getMessage());
}
?>
```

---

## ✅ PASSO 2: Testar localmente

```bash
# Na pasta do projeto
php -S localhost:8000

# Abra http://localhost:8000/index.php
# Se conectar ao banco = sucesso! ✅
```

---

## ✅ PASSO 3: Git - Salvar código SEM credenciais

```bash
# Terminal na pasta do projeto
git init
git add .
git commit -m "Initial commit with secure config"

# Verificar se .env foi ignorado
git status  # não deve aparecer .env
```

---

## ✅ PASSO 4: GitHub

1. Acesse https://github.com/new
2. Crie repositório: `sistema-emprestimo` (PRIVADO!)
3. No terminal:

```bash
git remote add origin https://github.com/SEU_USERNAME/sistema-emprestimo.git
git branch -M main
git push -u origin main
```

---

## ✅ PASSO 5: Conectar ao Render

1. Render Dashboard → **"+ New"** → **"Web Service"**
2. Conecte seu GitHub
3. Escolha o repositório
4. Configure:
   - **Environment:** PHP
   - **Region:** Oregon (mesmo da DB)
   - **Plan:** Free

---

## ✅ PASSO 6: Variáveis no Render (IMPORTANTE!)

No painel da Web Service:
1. Menu esquerdo → **"Environment"**
2. Adicione estas 5 variáveis:

| Chave | Valor |
|-------|-------|
| `PGHOST` | `dpg-da0difbl550s73d7173g-a.oregon-postgres.render.com` |
| `PGDATABASE` | `neondb_vrib` |
| `PGUSER` | `neondb_owner` |
| `PGPASSWORD` | `wNrpKeZxhHMtTU3WUQYd3u5I47yYy3d1` |
| `PGPORT` | `5432` |

3. Clique "Save" → aguarde redeploy (2-3 min)

---

## ✅ PASSO 7: Testar

Acesse sua URL: `https://seu-app.onrender.com`

Se vê a página → **SUCESSO!** 🎉

---

## 🔒 SEGURANÇA - CHECKLIST

- [ ] Arquivo `.env` foi criado ✅
- [ ] `.gitignore` contém `.env` ✅
- [ ] `git status` não mostra `.env` ✅
- [ ] `config.php` lê de variáveis de ambiente ✅
- [ ] Credenciais APENAS no Render Environment, nunca no GitHub ✅
- [ ] Repositório GitHub é PRIVADO ✅

---

## ⚠️ SE COMPARTILHOU A SENHA PUBLICAMENTE

Se essa senha foi vista por alguém:
1. Vá para Render PostgreSQL
2. Clique "Reset Credentials" (gera nova senha)
3. Atualize a variável `PGPASSWORD` no Web Service
4. Atualize o `.env` local também

---

**Pronto! Agora siga os 7 passos acima em ordem! Qualquer dúvida, me chama! 🚀**
