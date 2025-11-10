<?php

/**
 * A classe principal do plugin.
 *
 * Esta é usada para definir internacionalização, hooks específicos do admin
 * e hooks específicos do site público.
 */
class Sisu_Docs_Manager {

    /**
     * O loader que é responsável por manter e registrar todos os hooks que alimentam
     * o plugin.
     */
    protected $loader;

    /**
     * O identificador único deste plugin.
     */
    protected $plugin_name;

    /**
     * A versão atual do plugin.
     */
    protected $version;

    /**
     * Define a funcionalidade principal do plugin.
     */
    public function __construct() {
        if (defined('SISU_DOCS_MANAGER_VERSION')) {
            $this->version = SISU_DOCS_MANAGER_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'sisu-docs-manager';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    /**
     * Carrega as dependências necessárias para este plugin.
     */
    private function load_dependencies() {
        /**
         * A classe responsável por orquestrar as ações e filtros do
         * núcleo do plugin.
         */
        require_once SISU_DOCS_MANAGER_PATH . 'includes/class-sisu-loader.php';

        /**
         * A classe responsável por definir a internacionalização
         * do plugin.
         */
        require_once SISU_DOCS_MANAGER_PATH . 'includes/class-sisu-i18n.php';

        /**
         * A classe responsável por definir todas as ações que
         * ocorrem na área administrativa.
         */
        require_once SISU_DOCS_MANAGER_PATH . 'admin/class-sisu-admin.php';

        /**
         * A classe responsável por definir todas as ações que
         * ocorrem no lado público do site.
         */
        require_once SISU_DOCS_MANAGER_PATH . 'public/class-sisu-public.php';

        /**
         * A classe responsável por gerenciar o banco de dados.
         */
        require_once SISU_DOCS_MANAGER_PATH . 'includes/class-sisu-database.php';

        /**
         * A classe responsável por importar dados CSV.
         */
        require_once SISU_DOCS_MANAGER_PATH . 'includes/class-sisu-csv-importer.php';

        /**
         * A classe responsável por enviar notificações por email.
         */
        require_once SISU_DOCS_MANAGER_PATH . 'includes/class-sisu-email-notifier.php';

        /**
         * A classe responsável por gerenciar documentos.
         */
        require_once SISU_DOCS_MANAGER_PATH . 'includes/class-sisu-document-handler.php';

        $this->loader = new Sisu_Loader();
    }

    /**
     * Define a localização para este plugin para internacionalização.
     */
    private function set_locale() {
        $plugin_i18n = new Sisu_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Registra todos os hooks relacionados à funcionalidade da área administrativa
     * do plugin.
     */
    private function define_admin_hooks() {
        $plugin_admin = new Sisu_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'admin_init');
        
        // Instanciar Sisu_Email_Notifier para registrar hooks de email
        new Sisu_Email_Notifier();
    }

    /**
     * Registra todos os hooks relacionados à funcionalidade do lado público
     * do plugin.
     */
    private function define_public_hooks() {
        $plugin_public = new Sisu_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('init', $plugin_public, 'init_shortcodes');
        
        // Hooks AJAX para candidatos
        $this->loader->add_action('wp_ajax_sisu_candidate_login', $plugin_public, 'process_candidate_login');
        $this->loader->add_action('wp_ajax_nopriv_sisu_candidate_login', $plugin_public, 'process_candidate_login');
        $this->loader->add_action('wp_ajax_sisu_candidate_logout', $plugin_public, 'process_candidate_logout');
        $this->loader->add_action('wp_ajax_nopriv_sisu_candidate_logout', $plugin_public, 'process_candidate_logout');
        
        // Hooks AJAX para upload de documentos
        $this->loader->add_action('wp_ajax_sisu_upload_document', $plugin_public, 'process_upload_document');
        $this->loader->add_action('wp_ajax_nopriv_sisu_upload_document', $plugin_public, 'process_upload_document');
        $this->loader->add_action('wp_ajax_sisu_remove_document', $plugin_public, 'process_remove_document');
        $this->loader->add_action('wp_ajax_nopriv_sisu_remove_document', $plugin_public, 'process_remove_document');
    }

    /**
     * Executa o loader para executar todos os hooks com WordPress.
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * O nome do plugin usado para identificá-lo exclusivamente no contexto do
     * WordPress e para definir funcionalidade de internacionalização.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * A referência à classe que orquestra os hooks com o plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Recupera o número da versão do plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
