<?php

/**
 * Disparado durante a ativação do plugin.
 *
 * Esta classe define todo o código necessário para executar durante a ativação do plugin.
 */
class Sisu_Activator {

    /**
     * Método executado na ativação do plugin.
     *
     * Cria as tabelas necessárias no banco de dados e define as configurações iniciais.
     */
    public static function activate() {
        // Incluir a classe de banco de dados
        require_once SISU_DOCS_MANAGER_PATH . 'includes/class-sisu-database.php';
        
        // Incluir o gerenciador de capabilities
        require_once SISU_DOCS_MANAGER_PATH . 'includes/class-sisu-capability-manager.php';
        
        // Criar as tabelas do banco de dados
        $database = new Sisu_Database();
        $database->create_tables();
        
        // Limpar e recriar capabilities
        Sisu_Capability_Manager::refresh_capabilities();
        
        // Criar roles personalizados
        self::create_custom_roles();
        
        // Criar diretório de uploads
        self::create_upload_directory();
        
        // Definir configurações padrão
        self::set_default_options();
        
        // Limpar documentos excedentes/obsoletos
        self::cleanup_obsolete_documents();
    }

    /**
     * Cria roles personalizados para o plugin.
     */
    private static function create_custom_roles() {
        // Role para Secretário
        add_role('sisu_secretario', 'Secretário SiSU', array(
            'read' => true,
            'validate_sisu_documents' => true,
        ));

        // Role para Coordenador
        add_role('sisu_coordenador', 'Coordenador SiSU', array(
            'read' => true,
            'manage_sisu_candidates' => true,
            'validate_sisu_documents' => true,
            'view_sisu_reports' => true,
        ));

        // Role para Candidato
        add_role('sisu_candidato', 'Candidato SiSU', array(
            'read' => true,
            'sisu_upload_documents' => true,
            'sisu_view_own_data' => true,
        ));

        // Adicionar capacidades ao administrador
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_sisu_system');
            $admin_role->add_cap('manage_sisu_candidates');
            $admin_role->add_cap('validate_sisu_documents');
            $admin_role->add_cap('view_sisu_reports');
        }
    }

    /**
     * Cria o diretório de uploads para documentos.
     */
    private static function create_upload_directory() {
        $upload_dir = wp_upload_dir();
        $sisu_upload_dir = $upload_dir['basedir'] . '/sisu-docs/';
        
        if (!file_exists($sisu_upload_dir)) {
            wp_mkdir_p($sisu_upload_dir);
            
            // Criar arquivo .htaccess para proteger o diretório
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "deny from all\n";
            file_put_contents($sisu_upload_dir . '.htaccess', $htaccess_content);
        }
    }

    /**
     * Define as opções padrão do plugin.
     */
    private static function set_default_options() {
        // Configurações padrão
        $default_options = array(
            'sistema_ativo' => false,
            'data_inicio' => '',
            'data_fim' => '',
            'max_file_size' => 10485760, // 10MB em bytes
            'allowed_file_types' => array('pdf'),
            'email_notifications' => true,
        );

        add_option('sisu_docs_manager_options', $default_options);
    }

    /**
     * Remove documentos excedentes/obsoletos do banco de dados.
     * 
     * Esta função remove documentos que eram usados em versões antigas
     * do plugin mas não são mais necessários.
     */
    private static function cleanup_obsolete_documents() {
        global $wpdb;
        
        // Lista de tipos de documentos obsoletos
        $obsolete_types = array(
            'certificado_conclusao',
            'comprovante_residencia',
            'foto_3x4',
            'historico_escolar',
            'rg',
            'titulo_eleitor'
        );
        
        // Preparar placeholders para a query
        $placeholders = implode(',', array_fill(0, count($obsolete_types), '%s'));
        
        // Executar a limpeza
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}sisu_documentos WHERE tipo_documento IN ($placeholders)",
                $obsolete_types
            )
        );
        
        // Log da operação (opcional)
        error_log('SiSU Docs Manager: Documentos obsoletos removidos durante a ativação.');
    }
}
