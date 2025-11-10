<?php

/**
 * Classe responsável por enviar notificações por email.
 *
 * Esta classe gerencia todos os tipos de notificações por email
 * do sistema, incluindo confirmações de envio, validações e lembretes.
 */
class Sisu_Email_Notifier {

    /**
     * Instância da classe de banco de dados.
     */
    private $database;

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
        
        // Registrar hook para notificação de mudança de status
        add_action('sisu_document_status_changed', array($this, 'notify_document_status_change'), 10, 3);
    }

    /**
     * Envia notificação quando o status de um documento é alterado.
     *
     * @param int $documento_id ID do documento.
     * @param string $new_status Novo status.
     * @param string $observacoes Observações da validação.
     */
    public function notify_document_status_change($documento_id, $new_status, $observacoes = '') {
        // Verificar se notificações estão ativas
        if (!isset($this->options['email_notifications']) || !$this->options['email_notifications']) {
            return;
        }

        // Buscar dados do documento e candidato
        global $wpdb;
        $documentos_table = $wpdb->prefix . 'sisu_documentos';
        $candidatos_table = $wpdb->prefix . 'sisu_candidatos';
        $cursos_table = $wpdb->prefix . 'sisu_cursos';
        $campus_table = $wpdb->prefix . 'sisu_campus';
        
        $documento_data = $wpdb->get_row($wpdb->prepare(
            "SELECT d.*, c.nome as candidato_nome, c.email as candidato_email, c.inscricao,
                    cur.nome as curso_nome, cam.nome as campus_nome
             FROM $documentos_table d
             LEFT JOIN $candidatos_table c ON d.candidato_id = c.id
             LEFT JOIN $cursos_table cur ON c.curso_id = cur.id
             LEFT JOIN $campus_table cam ON c.campus_id = cam.id
             WHERE d.id = %d", $documento_id
        ));

        if (!$documento_data) {
            return;
        }

        $tipos_documentos = $this->database->get_tipos_documentos();
        $nome_documento = isset($tipos_documentos[$documento_data->tipo_documento]) ? 
                         $tipos_documentos[$documento_data->tipo_documento] : 
                         $documento_data->tipo_documento;

        // Enviar email para o candidato
        $this->send_status_change_email_to_candidate($documento_data, $nome_documento, $new_status, $observacoes);

        // Enviar email para administradores se necessário
        if ($new_status === 'Aguardando Validação') {
            $this->send_validation_request_email($documento_data, $nome_documento);
        }
    }

    /**
     * Envia email para o candidato sobre mudança de status.
     *
     * @param object $documento_data Dados do documento e candidato.
     * @param string $nome_documento Nome do documento.
     * @param string $status Novo status.
     * @param string $observacoes Observações.
     */
    private function send_status_change_email_to_candidate($documento_data, $nome_documento, $status, $observacoes) {
        $subject = '';
        $message = '';
        
        switch ($status) {
            case 'Aprovado':
                $subject = sprintf(__('Documento aprovado - %s', 'sisu-docs-manager'), $nome_documento);
                $message = sprintf(
                    __("Olá %s,\n\nSeu documento '%s' foi APROVADO!\n\nDados da inscrição:\n- Inscrição: %s\n- Curso: %s\n- Campus: %s\n\n%s\n\nParabéns! Continue acompanhando o status dos seus outros documentos.\n\nAtenciosamente,\nEquipe SiSU", 'sisu-docs-manager'),
                    $documento_data->candidato_nome,
                    $nome_documento,
                    $documento_data->inscricao,
                    $documento_data->curso_nome,
                    $documento_data->campus_nome,
                    !empty($observacoes) ? "Observações: " . $observacoes : ''
                );
                break;
                
            case 'Recusado':
                $subject = sprintf(__('Documento recusado - %s', 'sisu-docs-manager'), $nome_documento);
                $message = sprintf(
                    __("Olá %s,\n\nSeu documento '%s' foi RECUSADO e precisa ser reenviado.\n\nDados da inscrição:\n- Inscrição: %s\n- Curso: %s\n- Campus: %s\n\nMotivo da recusa:\n%s\n\nPor favor, corrija o documento conforme as observações e envie novamente através do sistema.\n\nAtenciosamente,\nEquipe SiSU", 'sisu-docs-manager'),
                    $documento_data->candidato_nome,
                    $nome_documento,
                    $documento_data->inscricao,
                    $documento_data->curso_nome,
                    $documento_data->campus_nome,
                    !empty($observacoes) ? $observacoes : __('Não especificado', 'sisu-docs-manager')
                );
                break;
                
            case 'Aguardando Validação':
                $subject = sprintf(__('Documento recebido - %s', 'sisu-docs-manager'), $nome_documento);
                $message = sprintf(
                    __("Olá %s,\n\nSeu documento '%s' foi recebido com sucesso e está aguardando validação.\n\nDados da inscrição:\n- Inscrição: %s\n- Curso: %s\n- Campus: %s\n\nVocê receberá uma nova notificação quando o documento for validado.\n\nAtenciosamente,\nEquipe SiSU", 'sisu-docs-manager'),
                    $documento_data->candidato_nome,
                    $nome_documento,
                    $documento_data->inscricao,
                    $documento_data->curso_nome,
                    $documento_data->campus_nome
                );
                break;
        }

        if (!empty($subject) && !empty($message)) {
            $this->send_email($documento_data->candidato_email, $subject, $message);
        }
    }

    /**
     * Envia email para secretaria solicitando validação.
     *
     * @param object $documento_data Dados do documento e candidato.
     * @param string $nome_documento Nome do documento.
     */
    private function send_validation_request_email($documento_data, $nome_documento) {
        // Buscar email da secretaria
        global $wpdb;
        $secretarias_table = $wpdb->prefix . 'sisu_secretarias';
        
        $secretaria = $wpdb->get_row($wpdb->prepare(
            "SELECT email FROM $secretarias_table 
             WHERE curso_id = (SELECT curso_id FROM {$wpdb->prefix}sisu_candidatos WHERE id = %d)
             AND campus_id = (SELECT campus_id FROM {$wpdb->prefix}sisu_candidatos WHERE id = %d)",
            $documento_data->candidato_id, $documento_data->candidato_id
        ));

        $emails_to_notify = array();
        
        // Adicionar email da secretaria se existir
        if ($secretaria && !empty($secretaria->email)) {
            $emails_to_notify[] = $secretaria->email;
        }
        
        // Adicionar email do administrador
        if (!empty($this->options['email_admin'])) {
            $emails_to_notify[] = $this->options['email_admin'];
        }

        if (empty($emails_to_notify)) {
            return;
        }

        $subject = sprintf(__('Novo documento para validação - %s', 'sisu-docs-manager'), $documento_data->candidato_nome);
        $message = sprintf(
            __("Um novo documento foi enviado e precisa de validação.\n\nCandidato: %s\nInscrição: %s\nEmail: %s\nCurso: %s\nCampus: %s\nDocumento: %s\nData de envio: %s\n\nAcesse o painel administrativo para validar o documento:\n%s\n\nAtenciosamente,\nSistema SiSU", 'sisu-docs-manager'),
            $documento_data->candidato_nome,
            $documento_data->inscricao,
            $documento_data->candidato_email,
            $documento_data->curso_nome,
            $documento_data->campus_nome,
            $nome_documento,
            date_i18n('d/m/Y H:i', strtotime($documento_data->data_envio)),
            admin_url('admin.php?page=sisu-documentos&candidato_id=' . $documento_data->candidato_id)
        );

        foreach ($emails_to_notify as $email) {
            $this->send_email($email, $subject, $message);
        }
    }

    /**
     * Envia email de boas-vindas para um novo candidato.
     *
     * @param int $candidate_id ID do candidato.
     */
    public function send_welcome_email($candidate_id) {
        // Verificar se notificações estão ativas
        if (!isset($this->options['email_notifications']) || !$this->options['email_notifications']) {
            return;
        }

        // Buscar dados do candidato
        global $wpdb;
        $candidatos_table = $wpdb->prefix . 'sisu_candidatos';
        $cursos_table = $wpdb->prefix . 'sisu_cursos';
        $campus_table = $wpdb->prefix . 'sisu_campus';
        
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

        $subject = __('Bem-vindo ao Sistema de Documentos SiSU', 'sisu-docs-manager');
        $message = sprintf(
            __("Olá %s,\n\nSeu cadastro foi incluído no sistema de recebimento de documentos do SiSU.\n\nDados da sua inscrição:\n- Inscrição: %s\n- Curso: %s\n- Campus: %s\n- Email: %s\n\nPara acessar o sistema e enviar seus documentos, utilize:\n- Email ou CPF: %s\n- Número de inscrição: %s\n\nLink de acesso: %s\n\nDocumentos necessários:\n%s\n\nEm caso de dúvidas, entre em contato com a secretaria do seu curso.\n\nAtenciosamente,\nEquipe SiSU", 'sisu-docs-manager'),
            $candidate->nome,
            $candidate->inscricao,
            $candidate->curso_nome,
            $candidate->campus_nome,
            $candidate->email,
            $candidate->email,
            $candidate->inscricao,
            home_url('/login-candidato/'),
            $this->get_required_documents_list()
        );

        $this->send_email($candidate->email, $subject, $message);
    }

    /**
     * Envia lembrete para candidatos com documentos pendentes.
     *
     * @param int $days_before_deadline Dias antes do prazo final.
     */
    public function send_deadline_reminder($days_before_deadline = 3) {
        // Verificar se notificações estão ativas
        if (!isset($this->options['email_notifications']) || !$this->options['email_notifications']) {
            return;
        }

        // Verificar se há data limite configurada
        if (empty($this->options['data_fim'])) {
            return;
        }

        $deadline = strtotime($this->options['data_fim']);
        $reminder_date = $deadline - ($days_before_deadline * 24 * 60 * 60);
        $now = current_time('timestamp');

        // Verificar se é o momento de enviar o lembrete
        if ($now < $reminder_date || $now > $deadline) {
            return;
        }

        // Buscar candidatos com documentos pendentes
        global $wpdb;
        $candidatos_table = $wpdb->prefix . 'sisu_candidatos';
        $documentos_table = $wpdb->prefix . 'sisu_documentos';
        $cursos_table = $wpdb->prefix . 'sisu_cursos';
        $campus_table = $wpdb->prefix . 'sisu_campus';

        $candidates_with_pending = $wpdb->get_results(
            "SELECT DISTINCT c.*, cur.nome as curso_nome, cam.nome as campus_nome,
                    COUNT(CASE WHEN d.status IN ('Não enviado', 'Recusado') THEN 1 END) as documentos_pendentes
             FROM $candidatos_table c
             LEFT JOIN $cursos_table cur ON c.curso_id = cur.id
             LEFT JOIN $campus_table cam ON c.campus_id = cam.id
             LEFT JOIN $documentos_table d ON c.id = d.candidato_id
             GROUP BY c.id
             HAVING documentos_pendentes > 0"
        );

        foreach ($candidates_with_pending as $candidate) {
            $this->send_reminder_email($candidate, $days_before_deadline);
        }
    }

    /**
     * Envia email de lembrete para um candidato.
     *
     * @param object $candidate Dados do candidato.
     * @param int $days_before_deadline Dias antes do prazo.
     */
    private function send_reminder_email($candidate, $days_before_deadline) {
        $subject = sprintf(__('Lembrete: %d dias para envio de documentos', 'sisu-docs-manager'), $days_before_deadline);
        $message = sprintf(
            __("Olá %s,\n\nEste é um lembrete de que você tem apenas %d dias para enviar seus documentos pendentes.\n\nDados da sua inscrição:\n- Inscrição: %s\n- Curso: %s\n- Campus: %s\n\nPrazo final: %s\n\nVocê ainda possui %d documento(s) pendente(s). Acesse o sistema para enviar:\n%s\n\nNão perca o prazo!\n\nAtenciosamente,\nEquipe SiSU", 'sisu-docs-manager'),
            $candidate->nome,
            $days_before_deadline,
            $candidate->inscricao,
            $candidate->curso_nome,
            $candidate->campus_nome,
            date_i18n('d/m/Y H:i', strtotime($this->options['data_fim'])),
            $candidate->documentos_pendentes,
            home_url('/login-candidato/')
        );

        $this->send_email($candidate->email, $subject, $message);
    }

    /**
     * Envia relatório diário para administradores.
     */
    public function send_daily_report() {
        // Verificar se notificações estão ativas
        if (!isset($this->options['email_notifications']) || !$this->options['email_notifications']) {
            return;
        }

        if (empty($this->options['email_admin'])) {
            return;
        }

        // Obter estatísticas do dia
        global $wpdb;
        $documentos_table = $wpdb->prefix . 'sisu_documentos';
        $candidatos_table = $wpdb->prefix . 'sisu_candidatos';

        $today = current_time('Y-m-d');
        
        $stats = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(CASE WHEN DATE(d.data_envio) = %s THEN 1 END) as documentos_enviados_hoje,
                COUNT(CASE WHEN DATE(d.data_atualizacao_status) = %s AND d.status = 'Aprovado' THEN 1 END) as documentos_aprovados_hoje,
                COUNT(CASE WHEN DATE(d.data_atualizacao_status) = %s AND d.status = 'Recusado' THEN 1 END) as documentos_recusados_hoje,
                COUNT(CASE WHEN d.status = 'Aguardando Validação' THEN 1 END) as documentos_pendentes_total,
                COUNT(DISTINCT c.id) as total_candidatos
             FROM $documentos_table d
             LEFT JOIN $candidatos_table c ON d.candidato_id = c.id",
            $today, $today, $today
        ), ARRAY_A);

        if ($stats['documentos_enviados_hoje'] == 0 && $stats['documentos_aprovados_hoje'] == 0 && $stats['documentos_recusados_hoje'] == 0) {
            return; // Não enviar relatório se não houve atividade
        }

        $subject = sprintf(__('Relatório Diário SiSU - %s', 'sisu-docs-manager'), date_i18n('d/m/Y'));
        $message = sprintf(
            __("Relatório de atividades do sistema SiSU para %s:\n\n📊 ESTATÍSTICAS DO DIA:\n- Documentos enviados: %d\n- Documentos aprovados: %d\n- Documentos recusados: %d\n\n📋 SITUAÇÃO GERAL:\n- Documentos pendentes de validação: %d\n- Total de candidatos: %d\n\nAcesse o painel administrativo: %s\n\nAtenciosamente,\nSistema SiSU", 'sisu-docs-manager'),
            date_i18n('d/m/Y'),
            $stats['documentos_enviados_hoje'],
            $stats['documentos_aprovados_hoje'],
            $stats['documentos_recusados_hoje'],
            $stats['documentos_pendentes_total'],
            $stats['total_candidatos'],
            admin_url('admin.php?page=sisu-docs-manager')
        );

        $this->send_email($this->options['email_admin'], $subject, $message);
    }

    /**
     * Obtém lista de documentos necessários.
     *
     * @return string Lista formatada de documentos.
     */
    private function get_required_documents_list() {
        $tipos_documentos = $this->database->get_tipos_documentos();
        $list = '';
        $counter = 1;
        
        foreach ($tipos_documentos as $tipo_nome) {
            $list .= $counter . '. ' . $tipo_nome . "\n";
            $counter++;
        }
        
        return $list;
    }

    /**
     * Envia um email.
     *
     * @param string $to Email do destinatário.
     * @param string $subject Assunto.
     * @param string $message Mensagem.
     * @return bool True se enviado com sucesso.
     */
    private function send_email($to, $subject, $message) {
        // Configurar cabeçalhos
        $headers = array(
            'Content-Type: text/plain; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>'
        );

        // Adicionar prefixo ao assunto
        $subject = '[' . get_bloginfo('name') . '] ' . $subject;

        // Enviar email
        $sent = wp_mail($to, $subject, $message, $headers);

        // Log do envio (opcional)
        if (!$sent) {
            error_log('SiSU Email Error: Failed to send email to ' . $to . ' with subject: ' . $subject);
        }

        return $sent;
    }

    /**
     * Agenda envio de lembretes automáticos.
     */
    public function schedule_reminders() {
        // Agendar lembrete de 3 dias antes do prazo
        if (!wp_next_scheduled('sisu_send_deadline_reminder_3days')) {
            wp_schedule_event(time(), 'daily', 'sisu_send_deadline_reminder_3days');
        }

        // Agendar lembrete de 1 dia antes do prazo
        if (!wp_next_scheduled('sisu_send_deadline_reminder_1day')) {
            wp_schedule_event(time(), 'daily', 'sisu_send_deadline_reminder_1day');
        }

        // Agendar relatório diário
        if (!wp_next_scheduled('sisu_send_daily_report')) {
            wp_schedule_event(strtotime('tomorrow 8:00'), 'daily', 'sisu_send_daily_report');
        }

        // Registrar hooks para os eventos agendados
        add_action('sisu_send_deadline_reminder_3days', array($this, 'send_deadline_reminder_3days'));
        add_action('sisu_send_deadline_reminder_1day', array($this, 'send_deadline_reminder_1day'));
        add_action('sisu_send_daily_report', array($this, 'send_daily_report'));
    }

    /**
     * Envia lembrete de 3 dias.
     */
    public function send_deadline_reminder_3days() {
        $this->send_deadline_reminder(3);
    }

    /**
     * Envia lembrete de 1 dia.
     */
    public function send_deadline_reminder_1day() {
        $this->send_deadline_reminder(1);
    }

    /**
     * Envia notificação quando um documento é enviado pelo candidato.
     *
     * @param int $candidato_id ID do candidato.
     * @param string $tipo_documento Tipo do documento enviado.
     */
    public function notify_document_uploaded($candidato_id, $tipo_documento) {
        // Verificar se notificações estão ativas
        if (!isset($this->options['email_notifications']) || !$this->options['email_notifications']) {
            return;
        }

        // Buscar dados do candidato
        global $wpdb;
        $candidatos_table = $wpdb->prefix . 'sisu_candidatos';
        $cursos_table = $wpdb->prefix . 'sisu_cursos';
        $secretarias_table = $wpdb->prefix . 'sisu_secretarias';

        $candidato = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, cur.nome as curso_nome, s.email as secretaria_email, s.nome as secretaria_nome
             FROM $candidatos_table c
             LEFT JOIN $cursos_table cur ON c.curso_id = cur.id
             LEFT JOIN $secretarias_table s ON cur.id = s.curso_id
             WHERE c.id = %d",
            $candidato_id
        ));

        if (!$candidato) {
            return;
        }

        // Obter nome do tipo de documento
        $tipos_documentos = $this->database->get_tipos_documentos();
        $nome_documento = isset($tipos_documentos[$tipo_documento]) ? $tipos_documentos[$tipo_documento] : $tipo_documento;

        // Enviar email para o candidato
        $this->send_document_upload_confirmation($candidato, $nome_documento);

        // Enviar email para a secretaria
        if ($candidato->secretaria_email) {
            $this->send_document_upload_notification_to_secretary($candidato, $nome_documento);
        }
    }

    /**
     * Envia confirmação de upload para o candidato.
     *
     * @param object $candidato Dados do candidato.
     * @param string $nome_documento Nome do documento.
     */
    private function send_document_upload_confirmation($candidato, $nome_documento) {
        $subject = 'Documento Enviado - SiSU';
        
        $message = "Olá {$candidato->nome},\n\n";
        $message .= "Confirmamos o recebimento do seu documento: {$nome_documento}\n\n";
        $message .= "O documento está agora em análise pela secretaria do curso {$candidato->curso_nome}.\n";
        $message .= "Você será notificado quando a análise for concluída.\n\n";
        $message .= "Atenciosamente,\n";
        $message .= "Equipe SiSU";

        wp_mail($candidato->email, $subject, $message);
    }

    /**
     * Envia notificação de novo documento para a secretaria.
     *
     * @param object $candidato Dados do candidato.
     * @param string $nome_documento Nome do documento.
     */
    private function send_document_upload_notification_to_secretary($candidato, $nome_documento) {
        $subject = 'Novo Documento para Análise - SiSU';
        
        $message = "Olá,\n\n";
        $message .= "Um novo documento foi enviado para análise:\n\n";
        $message .= "Candidato: {$candidato->nome}\n";
        $message .= "Inscrição: {$candidato->inscricao}\n";
        $message .= "Curso: {$candidato->curso_nome}\n";
        $message .= "Documento: {$nome_documento}\n\n";
        $message .= "Acesse o painel administrativo para analisar o documento.\n\n";
        $message .= "Atenciosamente,\n";
        $message .= "Sistema SiSU";

        wp_mail($candidato->secretaria_email, $subject, $message);
    }

    /**
     * Envia notificação consolidada com o status de todos os documentos do candidato.
     *
     * @param int $candidato_id ID do candidato.
     */
    public function notify_all_documents_status($candidato_id) {
        // Verificar se notificações estão ativas
        if (!isset($this->options['email_notifications']) || !$this->options['email_notifications']) {
            return;
        }

        // Buscar dados do candidato e todos os documentos
        global $wpdb;
        $candidatos_table = $wpdb->prefix . 'sisu_candidatos';
        $documentos_table = $wpdb->prefix . 'sisu_documentos';
        $cursos_table = $wpdb->prefix . 'sisu_cursos';
        $campus_table = $wpdb->prefix . 'sisu_campus';
        
        $candidato = $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, cur.nome as curso_nome, cam.nome as campus_nome
             FROM $candidatos_table c
             LEFT JOIN $cursos_table cur ON c.curso_id = cur.id
             LEFT JOIN $campus_table cam ON c.campus_id = cam.id
             WHERE c.id = %d", $candidato_id
        ));

        if (!$candidato) {
            return;
        }

        // Buscar todos os documentos do candidato
        $documentos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $documentos_table WHERE candidato_id = %d ORDER BY tipo_documento", 
            $candidato_id
        ));

        if (empty($documentos)) {
            return;
        }

        $tipos_documentos = $this->database->get_tipos_documentos();

        // Contar status
        $stats = array(
            'total' => count($documentos),
            'aprovados' => 0,
            'recusados' => 0,
            'pendentes' => 0,
            'nao_enviados' => 0
        );

        // Construir lista de documentos
        $documentos_list = "\n";
        foreach ($documentos as $doc) {
            $nome_doc = !empty($doc->nome_documento) ? $doc->nome_documento : 
                       (isset($tipos_documentos[$doc->tipo_documento]) ? $tipos_documentos[$doc->tipo_documento] : $doc->tipo_documento);
            
            $status_emoji = '';
            switch ($doc->status) {
                case 'Aprovado':
                    $stats['aprovados']++;
                    $status_emoji = '✅';
                    break;
                case 'Recusado':
                    $stats['recusados']++;
                    $status_emoji = '❌';
                    break;
                case 'Aguardando Validação':
                    $stats['pendentes']++;
                    $status_emoji = '⏳';
                    break;
                case 'Não enviado':
                    $stats['nao_enviados']++;
                    $status_emoji = '⚠️';
                    break;
            }

            $documentos_list .= sprintf(
                "%s %s - %s\n",
                $status_emoji,
                $nome_doc,
                $doc->status
            );

            if (!empty($doc->observacoes)) {
                $documentos_list .= "   Observações: " . $doc->observacoes . "\n";
            }
            $documentos_list .= "\n";
        }

        // Email para o candidato
        $subject_candidato = __('Atualização do Status dos seus Documentos - SiSU', 'sisu-docs-manager');
        $message_candidato = sprintf(
            __("Olá %s,\n\nSeus documentos foram avaliados. Confira abaixo o status atualizado:\n\nDados da inscrição:\n- Inscrição: %s\n- Curso: %s\n- Campus: %s\n\nResumo dos Documentos:\n- Total: %d\n- Aprovados: %d ✅\n- Aguardando Validação: %d ⏳\n- Recusados: %d ❌\n- Não Enviados: %d ⚠️\n\nDetalhamento:%s\n%s\n\nAcesse o sistema para mais detalhes: %s\n\nAtenciosamente,\nEquipe SiSU", 'sisu-docs-manager'),
            $candidato->nome,
            $candidato->inscricao,
            $candidato->curso_nome,
            $candidato->campus_nome,
            $stats['total'],
            $stats['aprovados'],
            $stats['pendentes'],
            $stats['recusados'],
            $stats['nao_enviados'],
            $documentos_list,
            $stats['recusados'] > 0 ? __("\n⚠️ ATENÇÃO: Você possui documentos recusados que precisam ser reenviados!", 'sisu-docs-manager') : '',
            home_url('/login-candidato/')
        );

        $this->send_email($candidato->email, $subject_candidato, $message_candidato);

        // Email para a secretaria
        $secretaria = $wpdb->get_row($wpdb->prepare(
            "SELECT email FROM {$wpdb->prefix}sisu_secretarias 
             WHERE curso_id = %d AND campus_id = %d",
            $candidato->curso_id, $candidato->campus_id
        ));

        if ($secretaria && !empty($secretaria->email)) {
            $subject_secretaria = sprintf(__('Documentos Atualizados - %s', 'sisu-docs-manager'), $candidato->nome);
            $message_secretaria = sprintf(
                __("Os documentos do candidato foram atualizados.\n\nCandidato: %s\nInscrição: %s\nEmail: %s\nCurso: %s\nCampus: %s\n\nResumo dos Documentos:\n- Total: %d\n- Aprovados: %d ✅\n- Aguardando Validação: %d ⏳\n- Recusados: %d ❌\n- Não Enviados: %d ⚠️\n\nDetalhamento:%s\n\nAcesse o painel administrativo: %s\n\nAtenciosamente,\nSistema SiSU", 'sisu-docs-manager'),
                $candidato->nome,
                $candidato->inscricao,
                $candidato->email,
                $candidato->curso_nome,
                $candidato->campus_nome,
                $stats['total'],
                $stats['aprovados'],
                $stats['pendentes'],
                $stats['recusados'],
                $stats['nao_enviados'],
                $documentos_list,
                admin_url('admin.php?page=sisu-documentos&candidato_id=' . $candidato_id)
            );

            $this->send_email($secretaria->email, $subject_secretaria, $message_secretaria);
        }
    }

}
