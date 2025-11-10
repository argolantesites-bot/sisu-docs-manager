# Guia de Instalação - SiSU Docs Manager

Este documento fornece instruções detalhadas para instalação e configuração inicial do plugin SiSU Docs Manager.

## Pré-requisitos

### Requisitos do Servidor
- **WordPress:** 5.0 ou superior
- **PHP:** 7.4 ou superior (recomendado 8.0+)
- **MySQL:** 5.6 ou superior (recomendado 8.0+)
- **Memória PHP:** Mínimo 128MB (recomendado 256MB+)

### Configurações PHP Recomendadas
```ini
upload_max_filesize = 50M
post_max_size = 50M
max_execution_time = 300
memory_limit = 256M
max_input_vars = 3000
```

### Extensões PHP Necessárias
- mysqli
- gd ou imagick
- curl
- zip
- mbstring

## Instalação

### Método 1: Upload Manual

1. **Download do Plugin**
   - Baixe o arquivo `sisu-docs-manager.zip`
   - Extraia o conteúdo em seu computador

2. **Upload via FTP**
   ```bash
   # Conecte-se ao seu servidor via FTP
   # Navegue até o diretório do WordPress
   cd /public_html/wp-content/plugins/
   
   # Faça upload da pasta sisu-docs-manager
   # Certifique-se de que as permissões estão corretas
   chmod -R 755 sisu-docs-manager/
   ```

3. **Upload via Painel WordPress**
   - Acesse **Plugins > Adicionar Novo**
   - Clique em **Enviar Plugin**
   - Selecione o arquivo `sisu-docs-manager.zip`
   - Clique em **Instalar Agora**

### Método 2: Instalação Direta no Servidor

```bash
# Navegue até o diretório de plugins
cd /var/www/html/wp-content/plugins/

# Clone ou copie os arquivos do plugin
cp -r /caminho/para/sisu-docs-manager ./

# Defina permissões corretas
chown -R www-data:www-data sisu-docs-manager/
chmod -R 755 sisu-docs-manager/
```

## Ativação

1. **Ativar o Plugin**
   - Acesse **Plugins > Plugins Instalados**
   - Localize **SiSU Docs Manager**
   - Clique em **Ativar**

2. **Verificar Ativação**
   - Após ativação, você verá o menu **SiSU Docs** no painel administrativo
   - Verifique se não há erros na tela

## Configuração Inicial

### 1. Configurações Básicas

Acesse **SiSU Docs > Configurações** e configure:

#### Sistema de Ativação
```
✓ Marcar "Sistema Ativo"
✓ Data de Início: [Data/hora de início do período]
✓ Data de Fim: [Data/hora de fim do período]
```

#### Configurações de Upload
```
✓ Tamanho Máximo: 10MB (ou conforme necessário)
✓ Tipos Permitidos: PDF (padrão)
```

#### Configurações de Email
```
✓ Ativar "Notificações por Email"
✓ Email do Administrador: [seu-email@dominio.com]
```

#### Mensagens Personalizadas
```
✓ Mensagem Sistema Inativo: [Personalizar conforme necessário]
✓ Mensagem de Boas-vindas: [Personalizar conforme necessário]
```

### 2. Estrutura Organizacional

Configure na seguinte ordem:

#### 2.1 Campus
Acesse **SiSU Docs > Campus**
```
Exemplo:
- Campus Central
- Campus Norte
- Campus Sul
```

#### 2.2 Cursos
Acesse **SiSU Docs > Cursos**
```
Exemplo:
- Engenharia de Software (Campus Central)
- Medicina (Campus Norte)
- Direito (Campus Sul)
```

#### 2.3 Secretarias
Acesse **SiSU Docs > Secretarias**
```
Exemplo:
- Nome: Secretaria de Engenharia
- Email: engenharia@instituicao.edu.br
- Curso: Engenharia de Software
- Campus: Campus Central
```

### 3. Usuários Administrativos

#### 3.1 Criar Usuários WordPress
```
1. Acesse Usuários > Adicionar Novo
2. Preencha dados básicos
3. Defina role como "Subscriber" (será alterado pelo plugin)
```

#### 3.2 Configurar Roles SiSU
Acesse **SiSU Docs > Usuários** e configure:

**Secretário:**
- Pode validar documentos de sua secretaria
- Pode visualizar candidatos de seus cursos

**Coordenador:**
- Pode gerenciar múltiplas secretarias
- Pode visualizar relatórios amplos

**Administrador:**
- Acesso total ao sistema
- Pode gerenciar configurações

## Importação de Candidatos

### 1. Preparar Arquivo CSV

Baixe o template em **SiSU Docs > Candidatos > Baixar Template CSV**

#### Campos Obrigatórios:
- Inscrição
- Nome
- E-mail
- CPF
- Campus
- Curso

#### Campos Opcionais:
- Data de Nascimento
- Sexo
- Telefone 1
- Telefone 2
- UF
- Município
- Habilitação
- Turno
- Categoria

### 2. Formato do CSV

```csv
Inscrição,Nome,E-mail,CPF,Data de Nascimento,Sexo,Telefone 1,Telefone 2,UF,Município,Campus,Habilitação,Turno,Categoria,Curso
2024001,João Silva,joao@email.com,123.456.789-00,01/01/1990,Masculino,(11) 99999-9999,(11) 88888-8888,SP,São Paulo,Campus Central,Ensino Médio Completo,Matutino,Ampla Concorrência,Engenharia de Software
```

### 3. Importar Dados

1. Acesse **SiSU Docs > Candidatos**
2. Clique em **Importar CSV**
3. Selecione o arquivo preparado
4. Clique em **Importar Candidatos**
5. Verifique o relatório de importação

## Configuração de Páginas Públicas

### 1. Criar Páginas

Crie as seguintes páginas no WordPress:

#### Página de Login
```
Título: Login do Candidato
Slug: login-candidato
Conteúdo: [sisu_login]
```

#### Página do Dashboard
```
Título: Dashboard do Candidato
Slug: dashboard-candidato
Conteúdo: [sisu_dashboard]
```

### 2. Configurar Menu

Adicione as páginas ao menu principal:
```
1. Acesse Aparência > Menus
2. Adicione "Login do Candidato" ao menu
3. Configure como necessário
```

## Configuração de Email

### Método 1: SMTP (Recomendado)

Instale um plugin SMTP como **WP Mail SMTP**:

```
1. Instale WP Mail SMTP
2. Configure com suas credenciais SMTP
3. Teste o envio de emails
```

### Método 2: Configuração Manual

Adicione ao `wp-config.php`:

```php
// Configuração SMTP
define('SMTP_HOST', 'smtp.seudominio.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@seudominio.com');
define('SMTP_PASS', 'sua-senha');
define('SMTP_SECURE', 'tls');
```

## Configuração de Segurança

### 1. Proteção de Diretórios

O plugin cria automaticamente proteção `.htaccess`, mas verifique:

```apache
# /wp-content/uploads/sisu-docs/.htaccess
Options -Indexes
deny from all
<Files ~ "\.(pdf)$">
    Order allow,deny
    Allow from all
</Files>
```

### 2. Permissões de Arquivos

```bash
# Definir permissões corretas
find /wp-content/plugins/sisu-docs-manager/ -type f -exec chmod 644 {} \;
find /wp-content/plugins/sisu-docs-manager/ -type d -exec chmod 755 {} \;

# Diretório de uploads
chmod 755 /wp-content/uploads/sisu-docs/
```

### 3. Backup Inicial

Configure backup automático incluindo:
- Banco de dados (tabelas `wp_sisu_*`)
- Diretório `/wp-content/uploads/sisu-docs/`
- Configurações do plugin

## Testes de Funcionamento

### 1. Teste de Upload

1. Acesse a página de login do candidato
2. Faça login com dados de um candidato importado
3. Teste upload de um documento PDF
4. Verifique se o arquivo foi salvo corretamente

### 2. Teste de Validação

1. Acesse o painel administrativo
2. Vá para **SiSU Docs > Documentos**
3. Encontre o documento enviado
4. Altere o status para "Aprovado"
5. Verifique se o email foi enviado

### 3. Teste de Notificações

1. Configure um email de teste
2. Envie um documento como candidato
3. Verifique se os emails foram recebidos:
   - Confirmação para o candidato
   - Notificação para a secretaria

## Solução de Problemas

### Erro de Ativação

**Problema:** Plugin não ativa  
**Solução:**
```php
// Verificar logs de erro
tail -f /var/log/apache2/error.log

// Ou ativar debug no WordPress
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Erro de Upload

**Problema:** Arquivos não fazem upload  
**Solução:**
```php
// Verificar configurações PHP
phpinfo();

// Aumentar limites se necessário
ini_set('upload_max_filesize', '50M');
ini_set('post_max_size', '50M');
```

### Erro de Email

**Problema:** Emails não são enviados  
**Solução:**
```php
// Testar função wp_mail
wp_mail('teste@email.com', 'Teste', 'Mensagem de teste');

// Verificar logs do servidor
tail -f /var/log/mail.log
```

### Erro de Permissões

**Problema:** Usuários não conseguem acessar funcionalidades  
**Solução:**
```php
// Reativar o plugin para recriar roles
// Ou executar manualmente:
$activator = new Sisu_Activator();
$activator->activate();
```

## Manutenção

### Backup Regular

Configure backup automático semanal:
```bash
#!/bin/bash
# Script de backup
DATE=$(date +%Y%m%d_%H%M%S)
mysqldump -u user -p database > backup_sisu_$DATE.sql
tar -czf backup_files_$DATE.tar.gz /wp-content/uploads/sisu-docs/
```

### Limpeza de Arquivos

Execute mensalmente:
```php
// Limpar arquivos órfãos
$database = new Sisu_Database();
$database->cleanup_orphaned_files();
```

### Monitoramento

Monitore regularmente:
- Espaço em disco (uploads)
- Performance do banco de dados
- Logs de erro
- Funcionamento dos emails

## Suporte

Para suporte técnico:
1. Verifique os logs de erro
2. Consulte a documentação completa
3. Entre em contato com a equipe de desenvolvimento

---

**Instalação concluída com sucesso!** O sistema está pronto para receber documentos dos candidatos.
