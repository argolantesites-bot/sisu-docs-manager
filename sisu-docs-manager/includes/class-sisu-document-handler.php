<?php

/**
 * Classe responsável por gerenciar upload e validação de documentos.
 *
 * Esta classe gerencia todo o processo de upload de documentos,
 * incluindo validação, armazenamento seguro e atualização de status.
 */
class Sisu_Document_Handler {

    /**
     * Instância da classe de banco de dados.
     */
    private $database;

    /**
     * Diretório de upload dos documentos.
     */
    private $upload_dir;

    /**
     * Configurações do plugin.
     */
    private $options;

    /**
     * Construtor da classe.
     */
    public function __construct() {
        $this->database = new Sisu_Database();
        $this->options = get_option('sisu_docs_manager_options', array());
        
        // Definir diretório de upload
        $wp_upload_dir = wp_upload_dir();
        $this->upload_dir = $wp_upload_dir['basedir'] . '/sisu-docs/';
        
        // Criar diretório se não existir
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
            $this->create_htaccess_protection();
        }
        
        // Registrar hooks AJAX
        add_action('wp_ajax_sisu_upload_document', array($this, 'handle_document_upload'));
        add_action('wp_ajax_nopriv_sisu_upload_document', array($this, 'handle_document_upload'));
        add_action('wp_ajax_sisu_remove_document', array($this, 'handle_document_removal'));
        add_action('wp_ajax_nopriv_sisu_remove_document', array($this, 'handle_document_removal'));
        add_action('wp_ajax_sisu_candidate_login', array($this, 'handle_candidate_login'));
        add_action('wp_ajax_nopriv_sisu_candidate_login', array($this, 'handle_candidate_login'));
        add_action('wp_ajax_sisu_candidate_logout', array($this, 'handle_candidate_logout'));
        add_action('wp_ajax_nopriv_sisu_candidate_logout', array($this, 'handle_candidate_logout'));
        add_action('wp_ajax_sisu_upload_documents_batch', array($this, 'handle_batch_upload'));
        add_action('wp_ajax_nopriv_sisu_upload_documents_batch', array($this, 'handle_batch_upload'));
        add_action('wp_ajax_sisu_send_consolidated_email', array($this, 'handle_send_consolidated_email'));
        add_action('wp_ajax_nopriv_sisu_send_consolidated_email', array($this, 'handle_send_consolidated_email'));
    }

    /**
     * Cria proteção .htaccess no diretório de uploads.
     */
    private function create_htaccess_protection() {
        $htaccess_content = "Options -Indexes\n";
        $htaccess_content .= "deny from all\n";
        $htaccess_content .= "<Files ~ \"\\.(pdf)$\">\n";
        $htaccess_content .= "    Order allow,deny\n";
        $htaccess_content .= "    Allow from all\n";
        $htaccess_content .= "</Files>\n";
        
        file_put_contents($this->upload_dir . '.htaccess', $htaccess_content);
    }

    /**
     * Processa o upload de um documento.
     */
    public function handle_document_upload() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sisu_public_nonce')) {
            wp_send_json_error(array('message' => __('Erro de segurança.', 'sisu-docs-manager')));
        }

        // Verificar se o candidato está logado via cookie
        if (!isset($_COOKIE['sisu_candidate_id']) || empty($_COOKIE['sisu_candidate_id'])) {
            wp_send_json_error(array('message' => __('Você precisa estar logado.', 'sisu-docs-manager')));
        }

        $candidate_id = intval($_COOKIE['sisu_candidate_id']);
        $tipo_documento = sanitize_text_field($_POST['tipo_documento']);

        // Verificar se o sistema está ativo
        if (!$this->is_system_active()) {
            wp_send_json_error(array('message' => __('O sistema não está recebendo documentos no momento.', 'sisu-docs-manager')));
        }

        // Validar tipo de documento
        $tipos_documentos = $this->database->get_tipos_documentos();
        if (!isset($tipos_documentos[$tipo_documento])) {
            wp_send_json_error(array('message' => __('Tipo de documento inválido.', 'sisu-docs-manager')));
        }

        // Verificar se há arquivo
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('Erro no upload do arquivo.', 'sisu-docs-manager')));
        }

        $file = $_FILES['file'];

        // Validar arquivo
        $validation_result = $this->validate_uploaded_file($file);
        if (!$validation_result['valid']) {
            wp_send_json_error(array('message' => $validation_result['message']));
        }

        // Processar upload
        $upload_result = $this->process_file_upload($file, $candidate_id, $tipo_documento);
        if (!$upload_result['success']) {
            wp_send_json_error(array('message' => $upload_result['message']));
        }

        // ENVIAR EMAIL USANDO A MESMA FUNCAO QUE FUNCIONA QUANDO SECRETARIO ATUALIZA STATUS
        require_once SISU_DOCS_MANAGER_PATH . 'includes/class-sisu-email-notifier.php';
        $email_notifier = new Sisu_Email_Notifier();
        $email_notifier->notify_all_documents_status($candidate_id);

        wp_send_json_success(array(
            'message' => __('Documento enviado com sucesso!', 'sisu-docs-manager'),
            'file_info' => $upload_result['file_info'],
            'tipo_documento' => $tipo_documento
        ));
    }

    /**
     * Processa a remoção de um documento.
     */
    public function handle_document_removal() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sisu_public_nonce')) {
            wp_send_json_error(array('message' => __('Erro de segurança.', 'sisu-docs-manager')));
        }

        // Verificar se o candidato está logado via cookie
        if (!isset($_COOKIE['sisu_candidate_id']) || empty($_COOKIE['sisu_candidate_id'])) {
            wp_send_json_error(array('message' => __('Você precisa estar logado.', 'sisu-docs-manager')));
        }

        $candidate_id = intval($_COOKIE['sisu_candidate_id']);
        $tipo_documento = sanitize_text_field($_POST['tipo_documento']);

        // Verificar se o sistema está ativo
        if (!$this->is_system_active()) {
            wp_send_json_error(array('message' => __('O sistema não está recebendo documentos no momento.', 'sisu-docs-manager')));
        }

        // Buscar documento
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_documentos';
        
        $documento = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE candidato_id = %d AND tipo_documento = %s",
            $candidate_id, $tipo_documento
        ));

        if (!$documento) {
            wp_send_json_error(array('message' => __('Documento não encontrado.', 'sisu-docs-manager')));
        }

        // Verificar se pode remover (apenas se não foi enviado ou foi recusado)
        if ($documento->status !== 'Não enviado' && $documento->status !== 'Recusado') {
            wp_send_json_error(array('message' => __('Este documento não pode ser removido.', 'sisu-docs-manager')));
        }

        // Remover arquivo físico se existir
        if ($documento->caminho_arquivo && file_exists($this->upload_dir . $documento->caminho_arquivo)) {
            unlink($this->upload_dir . $documento->caminho_arquivo);
        }

        // Atualizar registro no banco
        $wpdb->update(
            $table_name,
            array(
                'nome_arquivo' => null,
                'caminho_arquivo' => null,
                'tamanho_arquivo' => null,
                'status' => 'Não enviado',
                'data_envio' => null,
                'observacoes' => null
            ),
            array('id' => $documento->id),
            array('%s', '%s', '%d', '%s', '%s', '%s'),
            array('%d')
        );

        wp_send_json_success(array('message' => __('Documento removido com sucesso!', 'sisu-docs-manager')));
    }

    /**
     * Processa o login do candidato.
     */
    public function handle_candidate_login() {
        // Verificar nonce
        if (!wp_verify_nonce($_POST['nonce'], 'sisu_public_nonce')) {
            wp_send_json_error(array('message' => __('Erro de segurança.', 'sisu-docs-manager')));
        }

        $email_or_cpf = sanitize_text_field($_POST['email_or_cpf']);
        $inscricao = sanitize_text_field($_POST['inscricao']);

        // Buscar candidato no banco de dados
        global $wpdb;
        $candidatos_table = $wpdb->prefix . 'sisu_candidatos';
        $cursos_table = $wpdb->prefix . 'sisu_cursos';
        $campus_table = $wpdb->prefix . 'sisu_campus';
        
        $candidate = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, cur.nome as curso_nome, cam.nome as campus_nome
             FROM $candidatos_table c
             LEFT JOIN $cursos_table cur ON c.curso_id = cur.id
             LEFT JOIN $campus_table cam ON c.campus_id = cam.id
             WHERE (c.email = %s OR c.cpf = %s) AND c.inscricao = %s",
            $email_or_cpf, $email_or_cpf, $inscricao
        ));

        if ($candidate) {
            // Armazenar ID do candidato em cookie
            setcookie('sisu_candidate_id', $candidate->id, time() + (86400 * 7), '/', '', is_ssl(), true);
            
            // Armazenar dados do candidato em transient (expira em 7 dias)
            set_transient('sisu_candidate_data_' . $candidate->id, $candidate, 7 * DAY_IN_SECONDS);

            wp_send_json_success(array(
                'message' => __('Login realizado com sucesso!', 'sisu-docs-manager'),
                'redirect' => home_url('/dashboard-candidato/')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Dados de acesso inválidos. Verifique seu email/CPF e número de inscrição.', 'sisu-docs-manager')
            ));
        }
    }

    /**
     * Processa o logout do candidato.
     */
    public function handle_candidate_logout() {
        // Obter candidate_id do cookie antes de removê-lo
        if (isset($_COOKIE['sisu_candidate_id'])) {
            $candidate_id = intval($_COOKIE['sisu_candidate_id']);
            delete_transient('sisu_candidate_data_' . $candidate_id);
        }
        
        // Remover cookie
        setcookie('sisu_candidate_id', '', time() - 3600, '/', '', is_ssl(), true);

        wp_send_json_success(array(
            'message' => __('Logout realizado com sucesso!', 'sisu-docs-manager'),
            'redirect' => home_url('/login-candidato/')
        ));
    }

    /**
     * Valida um arquivo enviado.
     *
     * @param array $file Dados do arquivo $_FILES.
     * @return array Resultado da validação.
     */
    private function validate_uploaded_file($file) {
        // Verificar tipo MIME
        $allowed_types = array('application/pdf');
        if (!in_array($file['type'], $allowed_types)) {
            return array(
                'valid' => false,
                'message' => __('Apenas arquivos PDF são permitidos.', 'sisu-docs-manager')
            );
        }

        // Verificar extensão
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($file_extension !== 'pdf') {
            return array(
                'valid' => false,
                'message' => __('Apenas arquivos com extensão .pdf são permitidos.', 'sisu-docs-manager')
            );
        }

        // Verificar tamanho
        $max_size = isset($this->options['max_file_size']) ? $this->options['max_file_size'] : 10485760; // 10MB padrão
        if ($file['size'] > $max_size) {
            return array(
                'valid' => false,
                'message' => sprintf(__('O arquivo é muito grande. Tamanho máximo: %s', 'sisu-docs-manager'), size_format($max_size))
            );
        }

        // Verificar se o arquivo não está vazio
        if ($file['size'] === 0) {
            return array(
                'valid' => false,
                'message' => __('O arquivo está vazio.', 'sisu-docs-manager')
            );
        }

        return array('valid' => true);
    }

    /**
     * Processa o upload do arquivo.
     *
     * @param array $file Dados do arquivo $_FILES.
     * @param int $candidate_id ID do candidato.
     * @param string $tipo_documento Tipo do documento.
     * @return array Resultado do processamento.
     */
    private function process_file_upload($file, $candidate_id, $tipo_documento) {
        global $wpdb;
        
        // Buscar dados do candidato DIRETO DO BANCO
        $candidato = $wpdb->get_row($wpdb->prepare(
            "SELECT nome, inscricao FROM {$wpdb->prefix}sisu_candidatos WHERE id = %d",
            $candidate_id
        ));
        
        if (!$candidato) {
            return array(
                'success' => false,
                'message' => __('Candidato não encontrado.', 'sisu-docs-manager')
            );
        }
        
        // Obter nome do tipo de documento
        $tipos_documentos = $this->database->get_tipos_documentos();
        $nome_documento = isset($tipos_documentos[$tipo_documento]) ? $tipos_documentos[$tipo_documento] : $tipo_documento;
        
        // Limpar e normalizar strings para nome do arquivo
        $nome_candidato_limpo = $this->sanitize_filename_part($candidato->nome);
        $nome_documento_limpo = $this->sanitize_filename_part($nome_documento);
        $inscricao = $candidato->inscricao;
        
        // Fallback: se nome estiver vazio, usar ID
        if (empty($nome_candidato_limpo)) {
            $nome_candidato_limpo = 'CANDIDATO_' . $candidate_id;
        }
        
        // Gerar nome do arquivo: NOMECANDIDATO_NOMEDOCUMENTO_INSCRICAO.pdf
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $unique_filename = $nome_candidato_limpo . '_' . $nome_documento_limpo . '_' . $inscricao . '.' . $file_extension;
        $file_path = $this->upload_dir . $unique_filename;

        // Mover arquivo para diretório de destino
        if (!move_uploaded_file($file['tmp_name'], $file_path)) {
            return array(
                'success' => false,
                'message' => __('Erro ao salvar o arquivo no servidor.', 'sisu-docs-manager')
            );
        }

        // Atualizar ou inserir registro no banco de dados
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_documentos';
        
        // Verificar se já existe registro para este documento
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name WHERE candidato_id = %d AND tipo_documento = %s",
            $candidate_id, $tipo_documento
        ));

        $data = array(
            'nome_arquivo' => sanitize_file_name($file['name']),
            'caminho_arquivo' => $unique_filename,
            'tamanho_arquivo' => $file['size'],
            'status' => 'Aguardando Validação',
            'data_envio' => current_time('mysql'),
            'observacoes' => null
        );

        if ($existing) {
            // Atualizar registro existente
            $result = $wpdb->update(
                $table_name,
                $data,
                array('id' => $existing->id),
                array('%s', '%s', '%d', '%s', '%s', '%s'),
                array('%d')
            );
        } else {
            // Inserir novo registro
            $data['candidato_id'] = $candidate_id;
            $data['tipo_documento'] = $tipo_documento;
            
            $result = $wpdb->insert(
                $table_name,
                $data,
                array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
            );
        }

        if ($result === false) {
            // Remover arquivo se falhou ao salvar no banco
            unlink($file_path);
            return array(
                'success' => false,
                'message' => __('Erro ao salvar informações do arquivo no banco de dados.', 'sisu-docs-manager')
            );
        }

        // DISPARAR HOOK PARA ENVIAR EMAIL (MESMO QUE QUANDO SECRETARIO ATUALIZA STATUS)
        $documento_id = $existing ? $existing->id : $wpdb->insert_id;
        do_action('sisu_document_status_changed', $documento_id, 'Aguardando Validação', '');

        return array(
            'success' => true,
            'file_info' => array(
                'name' => $data['nome_arquivo'],
                'size' => $data['tamanho_arquivo'],
                'path' => $unique_filename
            )
        );
    }

    /**
     * Verifica se o sistema está ativo para recebimento de documentos.
     *
     * @return bool True se ativo, false caso contrário.
     */
    private function is_system_active() {
        $sistema_ativo = isset($this->options['sistema_ativo']) && $this->options['sistema_ativo'];
        
        if (!$sistema_ativo) {
            return false;
        }

        // Verificar período de ativação
        if (!empty($this->options['data_inicio']) && !empty($this->options['data_fim'])) {
            $agora = current_time('timestamp');
            $inicio = strtotime($this->options['data_inicio']);
            $fim = strtotime($this->options['data_fim']);
            
            return ($agora >= $inicio && $agora <= $fim);
        }

        return true;
    }

    /**
     * Envia notificação por email sobre upload de documento.
     *
     * @param int $candidate_id ID do candidato.
     * @param string $tipo_documento Tipo do documento.
     */
    private function send_upload_notification($candidate_id, $tipo_documento, $nome_arquivo = '') {
        // Verificar se notificações estão ativas
        if (!isset($this->options['email_notifications']) || !$this->options['email_notifications']) {
            return;
        }
        
        // Buscar dados do candidato
        global $wpdb;
        $candidatos_table = $wpdb->prefix . 'sisu_candidatos';
        $cursos_table = $wpdb->prefix . 'sisu_cursos';
        $campus_table = $wpdb->prefix . 'sisu_campus';
        $secretarias_table = $wpdb->prefix . 'sisu_secretarias';
        
        $candidate = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, cur.nome as curso_nome, cam.nome as campus_nome
             FROM $candidatos_table c
             LEFT JOIN $cursos_table cur ON c.curso_id = cur.id
             LEFT JOIN $campus_table cam ON c.campus_id = cam.id
             WHERE c.id = %d", $candidate_id
        ));

        if (!$candidate) {
            return;
        }

        // Buscar email da secretaria
        $secretaria = $wpdb->get_row($wpdb->prepare(
            "SELECT email FROM $secretarias_table WHERE curso_id = %d AND campus_id = %d",
            $candidate->curso_id, $candidate->campus_id
        ));

        $tipos_documentos = $this->database->get_tipos_documentos();
        $nome_documento = isset($tipos_documentos[$tipo_documento]) ? $tipos_documentos[$tipo_documento] : $tipo_documento;

        // Email para o candidato
        $subject_candidate = sprintf(__('Documento enviado - %s', 'sisu-docs-manager'), $nome_documento);
        $arquivo_info = !empty($nome_arquivo) ? "\nArquivo: " . $nome_arquivo : '';
        $message_candidate = sprintf(
            __("Olá %s,\n\nSeu documento '%s' foi enviado com sucesso e está aguardando validação.%s\n\nDados da inscrição:\n- Inscrição: %s\n- Curso: %s\n- Campus: %s\n\nVocê receberá uma nova notificação quando o documento for validado.\n\nAtenciosamente,\nEquipe SiSU", 'sisu-docs-manager'),
            $candidate->nome,
            $nome_documento,
            $arquivo_info,
            $candidate->inscricao,
            $candidate->curso_nome,
            $candidate->campus_nome
        );

        // ✅ USAR LÓGICA QUE FUNCIONA: headers como array
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );
        $subject_candidate = '[' . get_bloginfo('name') . '] ' . $subject_candidate;
        
        $result = wp_mail($candidate->email, $subject_candidate, $message_candidate, $headers);
        if (!$result) {
            error_log('SiSU Email Error: Failed to send email to ' . $candidate->email . ' with subject: ' . $subject_candidate);
        }

        // Email para a secretaria (se existir)
        if ($secretaria && !empty($secretaria->email)) {
            $subject_secretary = sprintf(__('Novo documento recebido - %s', 'sisu-docs-manager'), $candidate->nome);
            $arquivo_info = !empty($nome_arquivo) ? "\nArquivo: " . $nome_arquivo : '';
            $message_secretary = sprintf(
                __("Um novo documento foi enviado para validação.\n\nCandidato: %s\nInscrição: %s\nEmail: %s\nCurso: %s\nCampus: %s\nDocumento: %s%s\n\nAcesse o painel administrativo para validar o documento.\n\nAtenciosamente,\nSistema SiSU", 'sisu-docs-manager'),
                $candidate->nome,
                $candidate->inscricao,
                $candidate->email,
                $candidate->curso_nome,
                $candidate->campus_nome,
                $nome_documento,
                $arquivo_info
            );

            // ✅ USAR LÓGICA QUE FUNCIONA: headers como array
            $headers = array(
                'Content-Type: text/plain; charset=UTF-8',
                'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
            );
            $subject_secretary = '[' . get_bloginfo('name') . '] ' . $subject_secretary;
            
            $result = wp_mail($secretaria->email, $subject_secretary, $message_secretary, $headers);
            if (!$result) {
                error_log('SiSU Email Error: Failed to send email to ' . $secretaria->email . ' with subject: ' . $subject_secretary);
            }
        }
    }

    /**
     * Obtém estatísticas de documentos de um candidato.
     *
     * @param int $candidate_id ID do candidato.
     * @return array Estatísticas.
     */
    public function get_candidate_document_stats($candidate_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_documentos';
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'Aprovado' THEN 1 ELSE 0 END) as aprovados,
                SUM(CASE WHEN status = 'Recusado' THEN 1 ELSE 0 END) as recusados,
                SUM(CASE WHEN status = 'Aguardando Validação' THEN 1 ELSE 0 END) as pendentes,
                SUM(CASE WHEN status != 'Não enviado' THEN 1 ELSE 0 END) as enviados
             FROM $table_name WHERE candidato_id = %d",
            $candidate_id
        ), ARRAY_A);

        return $stats;
    }
    
    /**
     * Processa o upload em lote de múltiplos documentos.
     */
    public function handle_batch_upload() {
        // LOG: Início do processamento
        error_log('=== INÍCIO HANDLE_BATCH_UPLOAD ===');
        error_log('POST data: ' . print_r($_POST, true));
        error_log('FILES data: ' . print_r($_FILES, true));
        error_log('COOKIE sisu_candidate_id: ' . (isset($_COOKIE['sisu_candidate_id']) ? $_COOKIE['sisu_candidate_id'] : 'NÃO DEFINIDO'));
        
        // Verificar nonce
        if (!isset($_POST['nonce'])) {
            error_log('ERRO: Nonce não enviado no POST');
            wp_send_json_error(array('message' => __('Erro de segurança: nonce ausente.', 'sisu-docs-manager')));
        }
        
        error_log('Nonce recebido: ' . $_POST['nonce']);
        
        if (!wp_verify_nonce($_POST['nonce'], 'sisu_upload_batch')) {
            error_log('ERRO: Nonce inválido');
            wp_send_json_error(array('message' => __('Erro de segurança.', 'sisu-docs-manager')));
        }
        
        error_log('Nonce validado com sucesso');

        // Verificar se o candidato está logado via cookie
        if (!isset($_COOKIE['sisu_candidate_id']) || empty($_COOKIE['sisu_candidate_id'])) {
            error_log('ERRO: Candidato não está logado (cookie ausente)');
            wp_send_json_error(array('message' => __('Você precisa estar logado.', 'sisu-docs-manager')));
        }

        $candidate_id = intval($_COOKIE['sisu_candidate_id']);
        error_log('Candidate ID do cookie: ' . $candidate_id);

        // Verificar se o sistema está ativo
        if (!$this->is_system_active()) {
            wp_send_json_error(array('message' => __('O sistema não está recebendo documentos no momento.', 'sisu-docs-manager')));
        }

        // Verificar se há arquivos
        if (empty($_FILES) || !isset($_FILES['files'])) {
            error_log('ERRO: Nenhum arquivo foi enviado');
            wp_send_json_error(array('message' => __('Nenhum documento foi selecionado.', 'sisu-docs-manager')));
        }
        
        error_log('Total de arquivos em $_FILES[files]: ' . count($_FILES['files']['name']));
        error_log('$_FILES completo: ' . print_r($_FILES, true));
        error_log('$_POST completo: ' . print_r($_POST, true));
        
        // Obter lista de tipos de documentos do POST
        if (!isset($_POST['types']) || !is_array($_POST['types'])) {
            error_log('ERRO: types[] não foi enviado');
            wp_send_json_error(array('message' => __('Erro ao processar tipos de documentos.', 'sisu-docs-manager')));
        }
        
        $document_types = $_POST['types'];
        error_log('Tipos de documentos recebidos: ' . print_r($document_types, true));

        $uploaded_count = 0;
        $error_count = 0;
        $errors = array();

        // Processar cada documento usando formato de array padrão
        // Os arquivos vêm como $_FILES['files']['name'][0], $_FILES['files']['name'][1], etc.
        foreach ($_FILES['files']['name'] as $key => $filename) {
            error_log('Processando arquivo índice: ' . $key);
            
            // Obter tipo de documento correspondente
            if (!isset($document_types[$key])) {
                error_log('AVISO: Tipo de documento não encontrado para índice ' . $key);
                continue;
            }
            
            $tipo_documento = $document_types[$key];
            error_log('Tipo de documento: ' . $tipo_documento);
            
            // Verificar se o arquivo foi enviado
            if (empty($filename) || $_FILES['files']['error'][$key] === UPLOAD_ERR_NO_FILE) {
                error_log('Arquivo vazio ou não enviado');
                continue;
            }

            // Verificar se houve erro no upload
            if ($_FILES['files']['error'][$key] !== UPLOAD_ERR_OK) {
                $error_count++;
                $errors[] = sprintf(__('Erro ao enviar %s', 'sisu-docs-manager'), $tipo_documento);
                error_log('Erro no upload: ' . $_FILES['files']['error'][$key]);
                continue;
            }

            // Preparar dados do arquivo no formato esperado
            $file = array(
                'name' => $_FILES['files']['name'][$key],
                'type' => $_FILES['files']['type'][$key],
                'tmp_name' => $_FILES['files']['tmp_name'][$key],
                'error' => $_FILES['files']['error'][$key],
                'size' => $_FILES['files']['size'][$key]
            );
            
            error_log('Dados do arquivo: ' . print_r($file, true));

            // Validar arquivo
            $validation_result = $this->validate_uploaded_file($file);
            if (!$validation_result['valid']) {
                $error_count++;
                $errors[] = sprintf(__('%s: %s', 'sisu-docs-manager'), $tipo_documento, $validation_result['message']);
                continue;
            }

            // Processar upload
            $upload_result = $this->process_file_upload($file, $candidate_id, $tipo_documento);
            if (!$upload_result['success']) {
                $error_count++;
                $errors[] = sprintf(__('%s: %s', 'sisu-docs-manager'), $tipo_documento, $upload_result['message']);
                continue;
            }

            $uploaded_count++;
        }

        if ($uploaded_count === 0) {
            wp_send_json_error(array(
                'message' => __('Nenhum documento foi enviado com sucesso.', 'sisu-docs-manager'),
                'errors' => $errors
            ));
        }

        // Enviar email consolidado com todos os documentos enviados
        $email_notifier = new Sisu_Email_Notifier();
        $email_notifier->notify_all_documents_status($candidate_id);

        $message = sprintf(
            __('%d documento(s) enviado(s) com sucesso!', 'sisu-docs-manager'),
            $uploaded_count
        );

        if ($error_count > 0) {
            $message .= ' ' . sprintf(
                __('%d documento(s) com erro.', 'sisu-docs-manager'),
                $error_count
            );
        }

        wp_send_json_success(array(
            'message' => $message,
            'uploaded' => $uploaded_count,
            'errors' => $error_count,
            'error_details' => $errors
        ));
    }
    
    /**
     * Limpa e normaliza uma string para uso em nome de arquivo.
     * 
     * Remove acentos, caracteres especiais e espaços, convertendo para maiúsculas.
     * 
     * @param string $text Texto a ser limpo.
     * @return string Texto limpo e normalizado.
     */
    private function sanitize_filename_part($text) {
        // Garantir que é string
        $text = (string) $text;
        
        // Remover espaços extras
        $text = trim($text);
        
        // Remover acentos usando função do WordPress
        if (function_exists('remove_accents')) {
            $text = remove_accents($text);
        }
        
        // Converter para maiúsculas
        $text = strtoupper($text);
        
        // Remover caracteres especiais, manter apenas letras, números e espaços
        $text = preg_replace('/[^A-Z0-9\s]/', '', $text);
        
        // Substituir espaços por underscores
        $text = preg_replace('/\s+/', '_', $text);
        
        // Remover underscores duplicados
        $text = preg_replace('/_+/', '_', $text);
        
        // Remover underscores no início e fim
        $text = trim($text, '_');
        
        return $text;
    }

    /**
     * Envia email consolidado com todos os documentos enviados.
     */
    public function handle_send_consolidated_email() {
        // Log IMEDIATO antes de qualquer coisa
        $log_file = WP_CONTENT_DIR . '/sisu-debug.log';
        $log_msg = "\n" . date('Y-m-d H:i:s') . " - ========================================\n";
        $log_msg .= date('Y-m-d H:i:s') . " - HANDLE_SEND_CONSOLIDATED_EMAIL CHAMADO\n";
        $log_msg .= date('Y-m-d H:i:s') . " - \$_POST: " . print_r($_POST, true) . "\n";
        $log_msg .= date('Y-m-d H:i:s') . " - \$_COOKIE: " . print_r($_COOKIE, true) . "\n";
        @file_put_contents($log_file, $log_msg, FILE_APPEND);
        
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sisu_public_nonce')) {
            $log_msg = date('Y-m-d H:i:s') . " - ERRO: Nonce inválido\n";
            file_put_contents($log_file, $log_msg, FILE_APPEND);
            wp_send_json_error(array('message' => __('Erro de segurança.', 'sisu-docs-manager')));
        }

        // Verificar se o candidato está logado via cookie
        if (!isset($_COOKIE['sisu_candidate_id']) || empty($_COOKIE['sisu_candidate_id'])) {
            $log_msg = date('Y-m-d H:i:s') . " - ERRO: Candidato não logado\n";
            file_put_contents($log_file, $log_msg, FILE_APPEND);
            wp_send_json_error(array('message' => __('Você precisa estar logado.', 'sisu-docs-manager')));
        }

        $candidate_id = intval($_COOKIE['sisu_candidate_id']);
        $log_msg = date('Y-m-d H:i:s') . " - Candidate ID: {$candidate_id}\n";
        file_put_contents($log_file, $log_msg, FILE_APPEND);
        
        // Obter lista de tipos de documentos enviados
        if (!isset($_POST['document_types'])) {
            $log_msg = date('Y-m-d H:i:s') . " - ERRO: document_types não foi enviado\n";
            file_put_contents($log_file, $log_msg, FILE_APPEND);
            wp_send_json_error(array('message' => __('Erro ao processar documentos.', 'sisu-docs-manager')));
        }
        
        $document_types = json_decode(stripslashes($_POST['document_types']), true);
        $log_msg = date('Y-m-d H:i:s') . " - Tipos de documentos: " . print_r($document_types, true) . "\n";
        file_put_contents($log_file, $log_msg, FILE_APPEND);
        
        // Enviar email consolidado
        $this->send_consolidated_notification($candidate_id, $document_types);
        
        $log_msg = date('Y-m-d H:i:s') . " - Email consolidado processado\n\n";
        file_put_contents($log_file, $log_msg, FILE_APPEND);
        
        wp_send_json_success(array(
            'message' => __('Email consolidado enviado com sucesso!', 'sisu-docs-manager')
        ));
    }

    /**
     * Envia notificação consolidada por email sobre múltiplos documentos.
     *
     * @param int $candidate_id ID do candidato.
     * @param array $document_types Lista de tipos de documentos enviados.
     */
    private function send_consolidated_notification($candidate_id, $uploaded_docs) {
        // Buscar dados do candidato
        global $wpdb;
        $candidatos_table = $wpdb->prefix . 'sisu_candidatos';
        $cursos_table = $wpdb->prefix . 'sisu_cursos';
        $campus_table = $wpdb->prefix . 'sisu_campus';
        $secretarias_table = $wpdb->prefix . 'sisu_secretarias';
        
        $candidate = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, cur.nome as curso_nome, cam.nome as campus_nome
             FROM $candidatos_table c
             LEFT JOIN $cursos_table cur ON c.curso_id = cur.id
             LEFT JOIN $campus_table cam ON c.campus_id = cam.id
             WHERE c.id = %d", $candidate_id
        ));

        if (!$candidate) {
            return;
        }

        // Buscar email da secretaria
        $secretaria = $wpdb->get_row($wpdb->prepare(
            "SELECT email FROM $secretarias_table WHERE curso_id = %d AND campus_id = %d",
            $candidate->curso_id, $candidate->campus_id
        ));
        
        // Criar lista de documentos com nomes dos arquivos
        $lista_documentos = array();
        foreach ($uploaded_docs as $doc) {
            $lista_documentos[] = '- ' . $doc['nome'] . ' (' . $doc['arquivo'] . ')';
        }
        $documentos_text = implode("\n", $lista_documentos);
        
        $total_documentos = count($uploaded_docs);

        // Email para o candidato
        $subject_candidate = sprintf(__('Documentos enviados - %d documento(s)', 'sisu-docs-manager'), $total_documentos);
        $message_candidate = sprintf(
            __("Olá %s,\n\nSeus documentos foram enviados com sucesso e estão aguardando validação.\n\nDocumentos enviados:\n%s\n\nDados da inscrição:\n- Inscrição: %s\n- Curso: %s\n- Campus: %s\n\nVocê receberá uma nova notificação quando os documentos forem validados.\n\nAtenciosamente,\nEquipe SiSU", 'sisu-docs-manager'),
            $candidate->nome,
            $documentos_text,
            $candidate->inscricao,
            $candidate->curso_nome,
            $candidate->campus_nome
        );

        $result = wp_mail($candidate->email, $subject_candidate, $message_candidate);
        if (!$result) {
            $headers = 'From: ' . get_option('admin_email') . "\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            mail($candidate->email, $subject_candidate, $message_candidate, $headers);
        }

        // Email para a secretaria (se existir)
        if ($secretaria && !empty($secretaria->email)) {
            $subject_secretary = sprintf(__('Novos documentos recebidos - %s', 'sisu-docs-manager'), $candidate->nome);
            $message_secretary = sprintf(
                __("Novos documentos foram enviados para validação.\n\nCandidato: %s\nInscrição: %s\nEmail: %s\nCurso: %s\nCampus: %s\n\nDocumentos enviados:\n%s\n\nAcesse o painel administrativo para validar os documentos.\n\nAtenciosamente,\nSistema SiSU", 'sisu-docs-manager'),
                $candidate->nome,
                $candidate->inscricao,
                $candidate->email,
                $candidate->curso_nome,
                $candidate->campus_nome,
                $documentos_text
            );

            $result = wp_mail($secretaria->email, $subject_secretary, $message_secretary);
            if (!$result) {
                $headers = 'From: ' . get_option('admin_email') . "\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                mail($secretaria->email, $subject_secretary, $message_secretary, $headers);
            }
        }
        
    }
}
