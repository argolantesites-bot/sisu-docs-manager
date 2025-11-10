<?php

/**
 * A funcionalidade específica do admin do plugin.
 *
 * Define o nome do plugin, versão e dois exemplos de hooks para como
 * enfileirar o CSS e JavaScript específicos do admin.
 */
class Sisu_Admin {

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
     * Registra os arquivos de estilo para a área administrativa.
     */
    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, SISU_DOCS_MANAGER_URL . 'admin/css/sisu-admin.css', array(), $this->version, 'all');
    }

    /**
     * Registra os arquivos JavaScript para a área administrativa.
     */
    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, SISU_DOCS_MANAGER_URL . 'admin/js/sisu-admin.js', array('jquery'), $this->version, false);
        
        // Localizar script para AJAX
        wp_localize_script($this->plugin_name, 'sisu_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sisu_nonce'),
        ));
    }

    /**
     * Adiciona o menu administrativo.
     */
    public function add_admin_menu() {
        // Menu principal
        add_menu_page(
            __('SiSU Docs Manager', 'sisu-docs-manager'),
            __('SiSU Docs', 'sisu-docs-manager'),
            'manage_sisu_system',
            'sisu-docs-manager',
            array($this, 'display_admin_page'),
            'dashicons-media-document',
            30
        );

        // Submenu - Dashboard
        add_submenu_page(
            'sisu-docs-manager',
            __('Dashboard', 'sisu-docs-manager'),
            __('Dashboard', 'sisu-docs-manager'),
            'manage_sisu_system',
            'sisu-docs-manager',
            array($this, 'display_admin_page')
        );

        // Submenu - Candidatos
        add_submenu_page(
            'sisu-docs-manager',
            __('Candidatos', 'sisu-docs-manager'),
            __('Candidatos', 'sisu-docs-manager'),
            'manage_sisu_candidates',
            'sisu-candidatos',
            array($this, 'display_candidatos_page')
        );

        // Submenu - Documentos
        add_submenu_page(
            'sisu-docs-manager',
            __('Documentos', 'sisu-docs-manager'),
            __('Documentos', 'sisu-docs-manager'),
            'validate_sisu_documents',
            'sisu-documentos',
            array($this, 'display_documentos_page')
        );

        // Submenu - Campus
        add_submenu_page(
            'sisu-docs-manager',
            __('Campus', 'sisu-docs-manager'),
            __('Campus', 'sisu-docs-manager'),
            'manage_sisu_system',
            'sisu-campus',
            array($this, 'display_campus_page')
        );

        // Submenu - Cursos
        add_submenu_page(
            'sisu-docs-manager',
            __('Cursos', 'sisu-docs-manager'),
            __('Cursos', 'sisu-docs-manager'),
            'manage_sisu_system',
            'sisu-cursos',
            array($this, 'display_cursos_page')
        );

        // Submenu - Secretarias
        add_submenu_page(
            'sisu-docs-manager',
            __('Secretarias', 'sisu-docs-manager'),
            __('Secretarias', 'sisu-docs-manager'),
            'manage_sisu_system',
            'sisu-secretarias',
            array($this, 'display_secretarias_page')
        );

        // Submenu - Usuários
        add_submenu_page(
            'sisu-docs-manager',
            __('Usuários', 'sisu-docs-manager'),
            __('Usuários', 'sisu-docs-manager'),
            'manage_sisu_system',
            'sisu-usuarios',
            array($this, 'display_usuarios_page')
        );

        // Submenu - Configurações
        add_submenu_page(
            'sisu-docs-manager',
            __('Configurações', 'sisu-docs-manager'),
            __('Configurações', 'sisu-docs-manager'),
            'manage_sisu_system',
            'sisu-configuracoes',
            array($this, 'display_configuracoes_page')
        );
    }

    /**
     * Inicialização do admin.
     */
    public function admin_init() {
        // Registrar configurações
        register_setting('sisu_docs_manager_options', 'sisu_docs_manager_options');
        
        // Adicionar seções e campos de configuração
        $this->add_settings_sections();
    }

    /**
     * Adiciona seções de configurações.
     */
    private function add_settings_sections() {
        // Seção de configurações gerais
        add_settings_section(
            'sisu_general_settings',
            __('Configurações Gerais', 'sisu-docs-manager'),
            array($this, 'general_settings_callback'),
            'sisu-configuracoes'
        );

        // Campo de ativação do sistema
        add_settings_field(
            'sistema_ativo',
            __('Sistema Ativo', 'sisu-docs-manager'),
            array($this, 'sistema_ativo_callback'),
            'sisu-configuracoes',
            'sisu_general_settings'
        );

        // Campo de data de início
        add_settings_field(
            'data_inicio',
            __('Data de Início', 'sisu-docs-manager'),
            array($this, 'data_inicio_callback'),
            'sisu-configuracoes',
            'sisu_general_settings'
        );

        // Campo de data de fim
        add_settings_field(
            'data_fim',
            __('Data de Fim', 'sisu-docs-manager'),
            array($this, 'data_fim_callback'),
            'sisu-configuracoes',
            'sisu_general_settings'
        );
    }

    /**
     * Callback para a seção de configurações gerais.
     */
    public function general_settings_callback() {
        echo '<p>' . __('Configure as opções gerais do sistema SiSU Docs Manager.', 'sisu-docs-manager') . '</p>';
    }

    /**
     * Callback para o campo sistema ativo.
     */
    public function sistema_ativo_callback() {
        $options = get_option('sisu_docs_manager_options');
        $checked = isset($options['sistema_ativo']) && $options['sistema_ativo'] ? 'checked' : '';
        echo '<input type="checkbox" name="sisu_docs_manager_options[sistema_ativo]" value="1" ' . $checked . ' />';
        echo '<label for="sistema_ativo">' . __('Ativar sistema de recebimento de documentos', 'sisu-docs-manager') . '</label>';
    }

    /**
     * Callback para o campo data de início.
     */
    public function data_inicio_callback() {
        $options = get_option('sisu_docs_manager_options');
        $value = isset($options['data_inicio']) ? $options['data_inicio'] : '';
        echo '<input type="datetime-local" name="sisu_docs_manager_options[data_inicio]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('Data e hora de início para recebimento de documentos', 'sisu-docs-manager') . '</p>';
    }

    /**
     * Callback para o campo data de fim.
     */
    public function data_fim_callback() {
        $options = get_option('sisu_docs_manager_options');
        $value = isset($options['data_fim']) ? $options['data_fim'] : '';
        echo '<input type="datetime-local" name="sisu_docs_manager_options[data_fim]" value="' . esc_attr($value) . '" />';
        echo '<p class="description">' . __('Data e hora de fim para recebimento de documentos', 'sisu-docs-manager') . '</p>';
    }

    /**
     * Exibe a página principal do admin.
     */
    public function display_admin_page() {
        include_once SISU_DOCS_MANAGER_PATH . 'admin/partials/admin-display-main.php';
    }

    /**
     * Exibe a página de candidatos.
     */
    public function display_candidatos_page() {
        include_once SISU_DOCS_MANAGER_PATH . 'admin/partials/admin-display-candidatos.php';
    }

    /**
     * Exibe a página de documentos.
     */
    public function display_documentos_page() {
        include_once SISU_DOCS_MANAGER_PATH . 'admin/partials/admin-display-documentos.php';
    }

    /**
     * Exibe a página de usuários.
     */
    public function display_usuarios_page() {
        include_once SISU_DOCS_MANAGER_PATH . 'admin/partials/admin-display-usuarios.php';
    }

    /**
     * Exibe a página de secretarias.
     */
    public function display_secretarias_page() {
        include_once SISU_DOCS_MANAGER_PATH . 'admin/partials/admin-display-secretarias.php';
    }

    /**
     * Exibe a página de cursos.
     */
    public function display_cursos_page() {
        include_once SISU_DOCS_MANAGER_PATH . 'admin/partials/admin-display-cursos.php';
    }

    /**
     * Exibe a página de campus.
     */
    public function display_campus_page() {
        include_once SISU_DOCS_MANAGER_PATH . 'admin/partials/admin-display-campus.php';
    }

    /**
     * Exibe a página de configurações.
     */
    public function display_configuracoes_page() {
        include_once SISU_DOCS_MANAGER_PATH . 'admin/partials/admin-display-configuracoes.php';
    }
}
