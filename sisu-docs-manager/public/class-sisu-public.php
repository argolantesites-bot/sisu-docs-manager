<?php

/**
 * A funcionalidade específica do lado público do plugin.
 *
 * Define o nome do plugin, versão e dois exemplos de hooks para como
 * enfileirar o CSS e JavaScript específicos do lado público.
 */
class Sisu_Public {

    /**
     * O ID deste plugin.
     */
    private $plugin_name;

    /**
     * A versão deste plugin.
     */
    private $version;

    /**
     * Inicializa a classe e define suas propriedades.
     *
     * @param string $plugin_name O nome deste plugin.
     * @param string $version     A versão deste plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Registra os arquivos de estilo para o lado público.
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, SISU_DOCS_MANAGER_URL . 'public/css/sisu-public.css', array(), $this->version, 'all');
    }

    /**
     * Registra os arquivos JavaScript para o lado público.
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, SISU_DOCS_MANAGER_URL . 'public/js/sisu-public.js', array('jquery'), $this->version, false);
        
        // Localizar script para AJAX
        wp_localize_script($this->plugin_name, 'sisu_public_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisu_public_nonce'),
        ));
    }

    /**
     * Inicializa os shortcodes.
     */
    public function init_shortcodes() {
        add_shortcode('sisu_login', array($this, 'shortcode_login'));
        add_shortcode('sisu_dashboard', array($this, 'shortcode_dashboard'));
    }

    /**
     * Obtém o ID do candidato logado via cookie.
     *
     * @return int|false ID do candidato ou false se não estiver logado.
     */
    private function get_candidate_id() {
        if (isset($_COOKIE['sisu_candidate_id']) && !empty($_COOKIE['sisu_candidate_id'])) {
            return intval($_COOKIE['sisu_candidate_id']);
        }
        return false;
    }

    /**
     * Define o ID do candidato logado via cookie.
     *
     * @param int $candidate_id ID do candidato.
     */
    private function set_candidate_id($candidate_id) {
        setcookie('sisu_candidate_id', $candidate_id, time() + (86400 * 7), '/', '', is_ssl(), true);
        $_COOKIE['sisu_candidate_id'] = $candidate_id;
    }

    /**
     * Remove o cookie do candidato.
     */
    private function clear_candidate_id() {
        setcookie('sisu_candidate_id', '', time() - 3600, '/', '', is_ssl(), true);
        unset($_COOKIE['sisu_candidate_id']);
    }

    /**
     * Shortcode para o formulário de login do candidato.
     *
     * @param array $atts Atributos do shortcode.
     * @return string HTML do formulário de login.
     */
    public function shortcode_login($atts) {
        $atts = shortcode_atts(array(
            'redirect' => '',
        ), $atts, 'sisu_login');

        ob_start();
        include SISU_DOCS_MANAGER_PATH . 'public/partials/public-login.php';
        return ob_get_clean();
    }

    /**
     * Shortcode para o dashboard do candidato.
     *
     * @param array $atts Atributos do shortcode.
     * @return string HTML do dashboard.
     */
    public function shortcode_dashboard($atts) {
        $atts = shortcode_atts(array(), $atts, 'sisu_dashboard');

        // Verificar se o usuário está logado
        if (!$this->is_candidate_logged_in()) {
            return '<p>' . __('Você precisa estar logado para acessar esta área.', 'sisu-docs-manager') . '</p>';
        }

        ob_start();
        include SISU_DOCS_MANAGER_PATH . 'public/partials/public-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Verifica se o candidato está logado.
     *
     * @return bool True se estiver logado, false caso contrário.
     */
    private function is_candidate_logged_in() {
        return $this->get_candidate_id() !== false;
    }

    /**
     * Processa o login do candidato.
     */
    public function process_candidate_login() {
        try {
            // Verificar se é uma requisição POST
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                wp_send_json_error(array(
                    'message' => __('Método de requisição inválido.', 'sisu-docs-manager')
                ));
                return;
            }

            // Verificar nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sisu_public_nonce')) {
                wp_send_json_error(array(
                    'message' => __('Erro de segurança.', 'sisu-docs-manager')
                ));
                return;
            }

            // Verificar se os campos foram enviados
            if (empty($_POST['email_or_cpf']) || empty($_POST['inscricao'])) {
                wp_send_json_error(array(
                    'message' => __('Por favor, preencha todos os campos.', 'sisu-docs-manager')
                ));
                return;
            }

            $email_or_cpf = sanitize_text_field($_POST['email_or_cpf']);
            $inscricao = sanitize_text_field($_POST['inscricao']);

            // Remover formatação do CPF se houver
            $cpf_clean = preg_replace('/[^0-9]/', '', $email_or_cpf);

            // Buscar candidato no banco de dados
            global $wpdb;
            $table_name = $wpdb->prefix . 'sisu_candidatos';
            
            // Verificar se a tabela existe
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                wp_send_json_error(array(
                    'message' => __('Erro interno: tabela de candidatos não encontrada.', 'sisu-docs-manager')
                ));
                return;
            }
            
            // Buscar por email, CPF (com ou sem formatação) e inscrição
            $candidate = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE (email = %s OR cpf = %s OR cpf = %s) AND inscricao = %s",
                $email_or_cpf, $email_or_cpf, $cpf_clean, $inscricao
            ));

            if ($candidate) {
                // Armazenar ID do candidato em cookie
                $this->set_candidate_id($candidate->id);
                
                // Armazenar dados do candidato em transient (expira em 7 dias)
                set_transient('sisu_candidate_data_' . $candidate->id, $candidate, 7 * DAY_IN_SECONDS);

                wp_send_json_success(array(
                    'message' => __('Login realizado com sucesso!', 'sisu-docs-manager'),
                    'redirect' => home_url('/dashboard-candidato/')
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Dados de acesso inválidos. Verifique seu e-mail/CPF e número de inscrição.', 'sisu-docs-manager')
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Erro interno do servidor. Tente novamente.', 'sisu-docs-manager')
            ));
        }
    }
    /**
     * Processa o logout do candidato.
     */
    public function process_candidate_logout() {
        $candidate_id = $this->get_candidate_id();
        if ($candidate_id) {
            delete_transient('sisu_candidate_data_' . $candidate_id);
        }
        $this->clear_candidate_id();

        wp_send_json_success(array(
            'message' => __('Logout realizado com sucesso!', 'sisu-docs-manager'),
            'redirect' => home_url('/login-candidato/')
        ));
    }

    /**
     * Processa o upload de documento do candidato.
     */
    public function process_upload_document() {
        try {
            // Verificar se o candidato está logado
            $candidate_id = $this->get_candidate_id();
            if (!$candidate_id) {
                wp_send_json_error(array(
                    'message' => __('Você precisa estar logado para enviar documentos.', 'sisu-docs-manager')
                ));
                return;
            }

            // Verificar nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sisu_public_nonce')) {
                wp_send_json_error(array(
                    'message' => __('Erro de segurança.', 'sisu-docs-manager')
                ));
                return;
            }

            // Verificar se o arquivo foi enviado
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                wp_send_json_error(array(
                    'message' => __('Erro no upload do arquivo.', 'sisu-docs-manager')
                ));
                return;
            }

            $tipo_documento = sanitize_text_field($_POST['tipo_documento']);
            $file = $_FILES['file'];

            // Validar tipo de arquivo
            if ($file['type'] !== 'application/pdf') {
                wp_send_json_error(array(
                    'message' => __('Apenas arquivos PDF são permitidos.', 'sisu-docs-manager')
                ));
                return;
            }

            // Validar tamanho do arquivo
            $options = get_option('sisu_docs_manager_options', array());
            $max_size = $options['max_file_size'] ?? 10485760; // 10MB padrão
            
            if ($file['size'] > $max_size) {
                wp_send_json_error(array(
                    'message' => sprintf(__('O arquivo é muito grande. Tamanho máximo: %s', 'sisu-docs-manager'), size_format($max_size))
                ));
                return;
            }

            // Criar diretório de upload se não existir
            $upload_dir = wp_upload_dir();
            $sisu_upload_dir = $upload_dir['basedir'] . '/sisu-docs/';
            
            if (!file_exists($sisu_upload_dir)) {
                wp_mkdir_p($sisu_upload_dir);
            }

            // Gerar nome único para o arquivo
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $new_filename = $candidate_id . '_' . $tipo_documento . '_' . time() . '.' . $file_extension;
            $file_path = $sisu_upload_dir . $new_filename;

            // Mover arquivo para o diretório de upload
            if (!move_uploaded_file($file['tmp_name'], $file_path)) {
                wp_send_json_error(array(
                    'message' => __('Erro ao salvar o arquivo.', 'sisu-docs-manager')
                ));
                return;
            }

            // Salvar no banco de dados
            $database = new Sisu_Database();
            $result = $database->save_documento($candidate_id, $tipo_documento, array(
                'nome_arquivo' => $file['name'],
                'caminho_arquivo' => $new_filename,
                'tamanho_arquivo' => $file['size'],
                'status' => 'Aguardando Validação'
            ));

            if ($result) {
                // NÃO enviar email individual (será enviado consolidado ao final)
                // $email_notifier = new Sisu_Email_Notifier();
                // $email_notifier->notify_document_uploaded($candidate_id, $tipo_documento);

                wp_send_json_success(array(
                    'message' => __('Documento enviado com sucesso!', 'sisu-docs-manager')
                ));
            } else {
                // Remover arquivo se falhou ao salvar no banco
                unlink($file_path);
                wp_send_json_error(array(
                    'message' => __('Erro ao salvar documento no banco de dados.', 'sisu-docs-manager')
                ));
            }

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Erro interno do servidor. Tente novamente.', 'sisu-docs-manager')
            ));
        }
    }

    /**
     * Processa a remoção de documento do candidato.
     */
    public function process_remove_document() {
        try {
            // Verificar se o candidato está logado
            $candidate_id = $this->get_candidate_id();
            if (!$candidate_id) {
                wp_send_json_error(array(
                    'message' => __('Você precisa estar logado para remover documentos.', 'sisu-docs-manager')
                ));
                return;
            }

            // Verificar nonce
            if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sisu_public_nonce')) {
                wp_send_json_error(array(
                    'message' => __('Erro de segurança.', 'sisu-docs-manager')
                ));
                return;
            }

            $tipo_documento = sanitize_text_field($_POST['tipo_documento']);

            // Buscar documento no banco
            $database = new Sisu_Database();
            $documento = $database->get_documento_candidato($candidate_id, $tipo_documento);

            if (!$documento) {
                wp_send_json_error(array(
                    'message' => __('Documento não encontrado.', 'sisu-docs-manager')
                ));
                return;
            }

            // Verificar se pode remover (apenas se não foi aprovado)
            if ($documento->status === 'Aprovado') {
                wp_send_json_error(array(
                    'message' => __('Não é possível remover um documento já aprovado.', 'sisu-docs-manager')
                ));
                return;
            }

            // Remover arquivo físico
            $upload_dir = wp_upload_dir();
            $file_path = $upload_dir['basedir'] . '/sisu-docs/' . $documento->caminho_arquivo;
            
            if (file_exists($file_path)) {
                unlink($file_path);
            }

            // Remover do banco de dados
            $result = $database->remove_documento($candidate_id, $tipo_documento);

            if ($result) {
                wp_send_json_success(array(
                    'message' => __('Documento removido com sucesso!', 'sisu-docs-manager')
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Erro ao remover documento do banco de dados.', 'sisu-docs-manager')
                ));
            }

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Erro interno do servidor. Tente novamente.', 'sisu-docs-manager')
            ));
        }
    }
}
