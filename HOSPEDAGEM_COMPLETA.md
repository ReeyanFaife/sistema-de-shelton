# 🚀 GUIA COMPLETO: HOSPEDAR SEU SISTEMA DO ZERO

## 📋 RESUMO RÁPIDO
- **Custo:** Totalmente GRÁTIS
- **Tempo:** ~30-40 minutos
- **Plataforma recomendada:** Render.com
- **Banco de dados:** PostgreSQL (Neon.tech)

---

## 🎯 OPÇÕES DE HOSPEDAGEM GRATUITA

| Plataforma | Horas/mês | Sempre ativo? | Banco grátis? | Recomendado? |
|-----------|-----------|--------------|-------------|------------|
| **Render** | 750 | ✅ SIM | ✅ 500MB | ⭐⭐⭐ MELHOR |
| Railway | Limitado | ❌ Pode dormir | ✅ Sim | ⭐⭐ Bom |
| Heroku | DESCONTINUADO | ❌ Não | ❌ Pago | ❌ NÃO USE |
| PythonAnywhere | Limitado | ❌ Dorme | ❌ Não | ⭐ Último caso |

**→ Vamos usar RENDER (melhor para PHP de longa duração)**

---

## ✅ PASSO 1: CRIAR CONTA NO RENDER.COM (5 min)

1. Acesse: https://render.com
2. Clique **"Sign up"**
3. Escolha **"Sign up with GitHub"** (mais fácil)
4. Autorize e confirme email

![Render Login](https://render.com/images/og-image.png)

---

## ✅ PASSO 2: CRIAR BANCO DE DADOS POSTGRESQL (5 min)

1. No dashboard Render, clique **"+ New +"** (canto superior direito)
2. Selecione **"PostgreSQL"**
3. Preencha:
   - **Name:** `sistema-emprestimo-db`
   - **Database:** `neondb` (padrão)
   - **User:** `neondb_owner` (padrão)
   - **Region:** `Ohio` ou `Frankfurt` (escolha próxima ao Brasil)
   - **PostgreSQL Version:** `Latest`
4. Clique **"Create Database"**

**⏳ Aguarde 2-3 minutos até ficarem VERDES os status**

Quando estiver pronto, você verá:
```
Host: blablabla.onrender.com
Database: neondb
User: neondb_owner
Password: [senha_gerada_automaticamente]
Port: 5432
```

**📌 IMPORTANTE: Copie estas informações em lugar seguro!**

---

## ✅ PASSO 3: PREPARAR SEU CÓDIGO NO GIT (10 min)

### 3.1 - Instalar Git (se não tiver)
- Windows: Baixe em https://git-scm.com/download/win
- Instale e reinicie o VS Code

### 3.2 - Configurar Git
```bash
git config --global user.name "Seu Nome"
git config --global user.email "seu.email@gmail.com"
```

### 3.3 - Inicializar repositório do seu projeto
```bash
# Abra terminal na pasta do projeto
cd "d:\UTDED\Molide2\Sistema de Emprestimo\sistema-de-shelton"

# Inicializar git
git init
git add .
git commit -m "Initial commit"
```

---

## ✅ PASSO 4: CRIAR REPOSITÓRIO NO GITHUB (5 min)

1. Acesse: https://github.com/new
2. Preencha:
   - **Repository name:** `sistema-emprestimo`
   - **Description:** `Sistema de Empréstimo`
   - **Private:** ✅ MARQUE (segurança)
3. Clique **"Create repository"**

### Conectar seu projeto local ao GitHub:
```bash
# No seu terminal (já na pasta do projeto):
git remote add origin https://github.com/SEU_USUARIO/sistema-emprestimo.git
git branch -M main
git push -u origin main
```

**Pronto! Seu código está no GitHub**

---

## ✅ PASSO 5: CRIAR WEB SERVICE NO RENDER (5 min)

1. No dashboard Render, clique **"+ New +"**
2. Selecione **"Web Service"**
3. Clique **"Connect GitHub"** e autorize
4. Procure seu repositório `sistema-emprestimo`
5. Clique **"Connect"**

### Configure:
- **Name:** `sistema-emprestimo`
- **Environment:** `PHP`
- **Region:** Mesmo da database (ex: Ohio)
- **Branch:** `main`
- **Build Command:** `echo "Build skipped"` (deixe assim)
- **Start Command:** `echo "Using default PHP"` (deixe assim)
- **Plan:** 🆓 Free

6. Clique **"Create Web Service"**

---

## ✅ PASSO 6: CONFIGURAR VARIÁVEIS DE AMBIENTE (3 min)

Após criar o Web Service, no painel dele:

1. Vá em **"Environment"** (menu esquerdo)
2. Clique **"+ Add Environment Variable"**
3. Adicione as variáveis do seu banco:

```
PGHOST = dpg-da0difbl550s73d7173g-a.oregon-postgres.render.com
PGDATABASE = neondb_vrib
PGUSER = neondb_owner
PGPASSWORD = wNrpKeZxhHMtTU3WUQYd3u5I47yYy3d1
PGPORT = 5432
```

**Clique "Save"** e aguarde redeploy (2-3 min)

⚠️ **IMPORTANTE - SEGURANÇA:**
- ❌ **NÃO commite essas credenciais no GitHub!**
- ✅ Use APENAS em variáveis de ambiente do Render
- ✅ Seu código lê via `getenv('PGHOST')` etc
- 🔒 Se compartilhou a senha publicamente → regenere no Render

---

## ✅ PASSO 7: VERIFICAR DEPLOY (2 min)

1. No painel da Web Service, clique em **"Logs"**
2. Aguarde até ver: `Server running...` ou similar (verde ✅)
3. Copie a URL gerada: `https://sistema-emprestimo.onrender.com`
4. Acesse no navegador

**Se aparecer a página → Sucesso! 🎉**

---

## ✅ PASSO 8: CRIAR TABELAS NO BANCO (3 min)

Se tem um arquivo `migrate.php` (versionamento de DB):

1. Acesse: `https://sistema-emprestimo.onrender.com/migrate.php`
2. Execute as migrations
3. Se der erro, verifique os logs

Se não tem migrations, execute seu SQL direto:
```bash
# Conectar ao banco Render (no seu PC):
psql -h [seu_host].onrender.com -U neondb_owner -d neondb
# Digite a senha

# Depois cole seus CREATEs...
```

---

## ✅ PASSO 9: TESTAR A APLICAÇÃO

1. Acesse: `https://sistema-emprestimo.onrender.com`
2. Tente login/register
3. Tente criar empréstimo
4. Verifique em Logs se tem erros

---

## ⚡ MANTER ATIVO (IMPORTANTE!)

Render FREE fica em SLEEP após 15 minutos sem uso!

### Solução: Criar um "Background Worker"

1. No dashboard Render, **"+ New +"** → **"Background Worker"**
2. Selecione seu repositório
3. Preencha:
   - **Name:** `keep-alive`
   - **Environment:** `Other`
   - **Build Command:** `echo "No build"`
   - **Start Command:** `while true; do curl https://sistema-emprestimo.onrender.com; sleep 840; done`
4. Clique "Create Background Worker"

✅ **Agora sua app NUNCA dorme!**

---

## 📝 ATUALIZAR A APP (Para o futuro)

Sempre que fizer mudanças:

```bash
# No seu PC, na pasta do projeto:
git add .
git commit -m "Descrição da mudança"
git push origin main

# Render auto-deploya em ~2-3 minutos
```

---

## 🔧 SOLUÇÃO DE PROBLEMAS

### ❌ "Application failed to start"
**Solução:**
1. Verifique **Logs** → procure por erros de sintaxe PHP
2. Verifique se variáveis de ambiente estão corretas
3. Verifique se o banco está UP (verifique dashboard PostgreSQL)

### ❌ "Connection refused" ao banco
**Solução:**
1. Copie variáveis CORRETAS do Render DB
2. Teste conexão local primeiro com credenciais
3. Aguarde 5 minutos e redeploy

### ❌ Aplicação está lenta/timeout
**Solução:**
1. Render FREE tem limite de CPU/RAM
2. Otimize queries do banco
3. Aumente plano (pago) se necessário

---

## 📊 RESUMO FINAL

```
✅ Conta Render: GRÁTIS
✅ PostgreSQL: GRÁTIS (500MB)
✅ PHP Web Service: GRÁTIS (750h/mês)
✅ Banda: GRÁTIS (ilimitada)
✅ Domínio: GRÁTIS (onrender.com)

Custo Total: R$ 0,00 indefinidamente! 🎉
```

---

## ❓ PRÓXIMAS AÇÕES

- [ ] Registrar no Render.com
- [ ] Criar banco PostgreSQL
- [ ] Criar repositório no GitHub
- [ ] Fazer push do código
- [ ] Criar Web Service
- [ ] Configurar variáveis
- [ ] Testar acesso
- [ ] Criar Background Worker (keep-alive)

**Tudo pronto! Siga os passos na ordem e em 30-40 minutos está online!**

Tem dúvida em algum passo específico? Me chama!
