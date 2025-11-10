# SiSU Docs Manager - Plugin WordPress

**Versão:** 1.0.0  
**Autor:** Manus AI  
**Compatibilidade:** WordPress 5.0+  
**Licença:** GPL v2 ou posterior  

## Descrição

O **SiSU Docs Manager** é um plugin WordPress completo para gerenciamento de documentação do Sistema de Seleção Unificada (SiSU). O plugin oferece uma solução robusta para instituições de ensino superior que precisam receber, validar e gerenciar documentos de candidatos de forma organizada e eficiente.

## ✅ CORREÇÕES IMPLEMENTADAS

### **Problema Resolvido: Dashboards em Branco**
- ✅ Criados templates completos para **Campus**
- ✅ Criados templates completos para **Cursos** 
- ✅ Criados templates completos para **Secretarias**
- ✅ Criados templates completos para **Usuários**
- ✅ Corrigidas permissões no menu administrativo
- ✅ Implementadas funcionalidades CRUD completas
- ✅ Adicionados modais de edição interativos
- ✅ Implementados filtros e buscas avançadas

### **Novos Recursos Adicionados**
- 🆕 **Interface moderna** com grids responsivos
- 🆕 **Modais de edição** para melhor UX
- 🆕 **Validações em tempo real** nos formulários
- 🆕 **Filtros dinâmicos** por campus/curso
- 🆕 **Contadores automáticos** de registros relacionados
- 🆕 **Proteção contra exclusão** de registros com dependências
- 🆕 **Feedback visual** para todas as ações

## Funcionalidades Principais

### 🎯 **Gestão de Candidatos**
- Importação em massa via arquivo CSV
- Campos completos de dados pessoais e acadêmicos
- Busca e filtros avançados
- Histórico de ações e alterações

### 📄 **Sistema de Documentos**
- 11 tipos de documentos pré-configurados
- Upload de arquivos PDF até 10MB
- Sistema de status (Aprovado, Recusado, Aguardando Validação, Não enviado)
- Validação automática de formato e tamanho

### 👥 **Gestão de Usuários**
- Três níveis de acesso: Administrador, Coordenador, Secretário
- Sistema de permissões granular
- Integração com usuários WordPress existentes
- Controle de acesso por secretaria

### 🏢 **Estrutura Organizacional**
- **Campus:** Gestão completa de unidades da instituição
- **Cursos:** Organização hierárquica por campus
- **Secretarias:** Vinculação curso-campus-email
- **Usuários:** Controle de acesso e permissões

### 📧 **Notificações Automáticas**
- Email de confirmação para candidatos
- Notificação para secretarias responsáveis
- Alertas de mudança de status
- Templates personalizáveis

### ⚙️ **Configurações Avançadas**
- Controle de período de atividade
- Data e hora de início/fim
- Configurações de email
- Personalização de campos

## Campos de Importação CSV

### Dados do Candidato
| Campo | Descrição | Obrigatório |
|-------|-----------|-------------|
| Inscrição | Número único de inscrição | ✅ |
| Nome | Nome completo do candidato | ✅ |
| E-mail | Email para acesso e notificações | ✅ |
| CPF | Documento de identificação | ✅ |
| Data de Nascimento | Formato DD/MM/AAAA | ✅ |
| Sexo | Masculino/Feminino/Outro | ✅ |
| Telefone 1 | Telefone principal | ✅ |
| Telefone 2 | Telefone alternativo | ❌ |
| UF | Estado de origem | ✅ |
| Município | Cidade de origem | ✅ |
| Campus | Campus de interesse | ✅ |
| Habilitação | Nível de escolaridade | ✅ |
| Turno | Matutino/Vespertino/Noturno | ✅ |
| Categoria | Tipo de concorrência | ✅ |
| Curso | Curso pretendido | ✅ |

## Documentos Gerenciados

O sistema gerencia automaticamente os seguintes documentos:

1. **Histórico Escolar do Ensino Fundamental**
2. **Histórico Escolar do Ensino Médio**
3. **Diploma ou Certificado de Conclusão do Ensino Médio**
4. **Certidão de Quitação Eleitoral**
5. **Certidão de Nascimento ou Casamento**
6. **Carteira de Reservista ou Certificado de Alistamento Militar**
7. **Cadastro de Pessoa Física – CPF**
8. **Documento Oficial de Identificação**
9. **Carteira de Identidade de Estrangeiro - CIE**
10. **Declaração de Perfil Social e Autenticidade dos Documentos Enviados**
11. **Declaração de Etnia e Vínculo com Comunidade Indígena**

Cada documento possui:
- **Status de validação** (Aprovado, Recusado, Aguardando Validação, Não enviado)
- **Arquivo PDF** (máximo 10MB)
- **Data de upload**
- **Histórico de alterações**
- **Comentários de validação**

## Níveis de Usuário

### 🔴 **Administrador**
- Acesso total ao sistema
- Gestão de campus, cursos e secretarias
- Gerenciamento de usuários
- Configurações globais
- Relatórios completos

### 🟡 **Coordenador**
- Gestão de candidatos
- Validação de documentos
- Visualização de relatórios
- Acesso a múltiplas secretarias

### 🟢 **Secretário**
- Validação de documentos de sua secretaria
- Visualização de candidatos do curso
- Alteração de status de documentos
- Comunicação com candidatos

## Painéis de Acesso

### 🎓 **Painel do Candidato**
**Acesso:** Email ou CPF + Número de Inscrição

**Funcionalidades:**
- Visualização de dados pessoais
- Upload de documentos obrigatórios
- Acompanhamento de status
- Histórico de submissões
- Download de comprovantes

### 👨‍💼 **Painel Administrativo**
**Acesso:** Email + Senha (usuários cadastrados)

**Funcionalidades:**
- Dashboard com estatísticas
- Gestão completa de candidatos
- Validação de documentos
- Relatórios e exportações
- Configurações do sistema

## Menu Administrativo Completo

### 📊 **Dashboard**
- Estatísticas gerais do sistema
- Gráficos de documentos por status
- Resumo de atividades recentes
- Indicadores de performance

### 👥 **Candidatos**
- Lista completa de candidatos
- Filtros por curso, campus, status
- Importação CSV em massa
- Exportação de relatórios

### 📄 **Documentos**
- Validação de documentos pendentes
- Histórico de aprovações/rejeições
- Busca por candidato ou documento
- Comentários de validação

### 🏢 **Campus**
- Cadastro de unidades da instituição
- Edição e remoção de campus
- Contagem de cursos por campus
- Validação de dependências

### 🎓 **Cursos**
- Gestão de cursos por campus
- Filtros por campus
- Contagem de candidatos por curso
- Proteção contra exclusão com candidatos

### 🏛️ **Secretarias**
- Vinculação curso-campus-email
- Gestão de responsáveis
- Configuração de notificações
- Controle de acesso

### 👤 **Usuários**
- Criação de usuários do sistema
- Definição de níveis de acesso
- Vinculação com secretarias
- Concessão de acesso a usuários WordPress

### ⚙️ **Configurações**
- Período de atividade do sistema
- Configurações de email
- Personalização de campos
- Backup e restauração

## Fluxo de Trabalho

### 1. **Configuração Inicial**
1. Cadastrar campus da instituição
2. Criar cursos por campus
3. Configurar secretarias
4. Adicionar usuários do sistema
5. Definir período de atividade

### 2. **Importação de Candidatos**
1. Preparar arquivo CSV com dados
2. Fazer upload via painel administrativo
3. Validar dados importados
4. Confirmar importação

### 3. **Recebimento de Documentos**
1. Candidato acessa o sistema
2. Faz upload dos documentos
3. Sistema envia notificações
4. Secretaria valida documentos
5. Candidato recebe feedback

### 4. **Validação e Aprovação**
1. Secretário acessa documentos
2. Analisa e define status
3. Adiciona comentários se necessário
4. Sistema notifica candidato
5. Processo se repete até conclusão

## Notificações por Email

### 📨 **Para Candidatos**
- Confirmação de upload de documento
- Mudança de status (aprovação/rejeição)
- Lembretes de documentos pendentes
- Confirmação de conclusão do processo

### 📧 **Para Secretarias**
- Novo documento recebido
- Documentos aguardando validação
- Relatórios periódicos
- Alertas de prazo

## Requisitos Técnicos

### **Servidor**
- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- WordPress 5.0 ou superior
- Extensão PHP GD (para manipulação de imagens)
- Extensão PHP cURL (para notificações)

### **Configurações Recomendadas**
- `upload_max_filesize`: 10M
- `post_max_size`: 10M
- `max_execution_time`: 300
- `memory_limit`: 256M

## Segurança

### 🔒 **Medidas Implementadas**
- Validação de nonce em todas as operações
- Sanitização de dados de entrada
- Verificação de permissões por ação
- Upload restrito a arquivos PDF
- Proteção contra SQL injection
- Escape de dados na saída

### 🛡️ **Controle de Acesso**
- Autenticação obrigatória para admin
- Sessões seguras para candidatos
- Logs de ações importantes
- Backup automático de dados críticos

## Personalização

### 🎨 **Templates**
Todos os templates podem ser personalizados:
- `public/partials/` - Interface do candidato
- `admin/partials/` - Interface administrativa
- `includes/templates/` - Templates de email

### 🔧 **Hooks e Filtros**
O plugin oferece diversos hooks para personalização:
```php
// Filtrar campos de importação
add_filter('sisu_import_fields', 'custom_import_fields');

// Personalizar emails
add_filter('sisu_email_template', 'custom_email_template');

// Modificar validações
add_action('sisu_document_uploaded', 'custom_validation');
```

## Suporte e Documentação

### 📚 **Documentação Técnica**
- Arquivo `INSTALL.md` com instruções detalhadas
- Comentários inline no código
- Exemplos de uso e personalização
- Troubleshooting comum

### 🆘 **Suporte**
Para suporte técnico e dúvidas:
- Documentação oficial do plugin
- Fórum da comunidade WordPress
- Issues no repositório (se aplicável)

## Changelog

### **Versão 1.0.0** (Atual)
- ✅ Lançamento inicial
- ✅ Sistema completo de gestão de documentos
- ✅ Importação CSV de candidatos
- ✅ Painéis administrativos completos
- ✅ Sistema de notificações por email
- ✅ Controle de acesso granular
- ✅ Interface responsiva
- ✅ Validação de documentos
- ✅ Relatórios e estatísticas
- ✅ **CORREÇÃO:** Templates administrativos completos
- ✅ **CORREÇÃO:** Dashboards funcionais para todas as seções
- ✅ **MELHORIA:** Interface moderna com modais
- ✅ **MELHORIA:** Filtros e validações avançadas

## Licença

Este plugin é distribuído sob a licença GPL v2 ou posterior. Você pode redistribuir e/ou modificar sob os termos da GNU General Public License conforme publicada pela Free Software Foundation.

## Créditos

**Desenvolvido por:** Manus AI  
**Data de Criação:** 2025  
**Versão WordPress Testada:** 6.4  
**Versão PHP Testada:** 8.2  

---

*Para mais informações sobre instalação e configuração, consulte o arquivo `INSTALL.md` incluído no plugin.*
