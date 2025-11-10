<?php
/**
 * Página principal do painel administrativo do SiSU Docs Manager.
 */

// Verificar permissões
if (!current_user_can('manage_sisu_system')) {
    wp_die(__('Você não tem permissão para acessar esta página.', 'sisu-docs-manager'));
}

// Obter estatísticas
$database = new Sisu_Database();
global $wpdb;

$stats = array(
    'total_candidatos' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sisu_candidatos"),
    'total_documentos' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sisu_documentos WHERE status != 'Não enviado'"),
    'documentos_pendentes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sisu_documentos WHERE status = 'Aguardando Validação'"),
    'documentos_aprovados' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sisu_documentos WHERE status = 'Aprovado'"),
    'documentos_recusados' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sisu_documentos WHERE status = 'Recusado'"),
    'total_campus' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sisu_campus"),
    'total_cursos' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sisu_cursos"),
    'total_secretarias' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sisu_secretarias")
);

// Obter configurações do sistema
$options = get_option('sisu_docs_manager_options', array());
$sistema_ativo = isset($options['sistema_ativo']) && $options['sistema_ativo'];
?>

<div class="sisu-admin-container">
    <div class="sisu-admin-header">
        <h1><?php _e('SiSU Docs Manager - Painel Principal', 'sisu-docs-manager'); ?></h1>
        <p><?php _e('Sistema de gerenciamento de documentação para candidatos do SiSU.', 'sisu-docs-manager'); ?></p>
    </div>

    <!-- Status do Sistema -->
    <div class="sisu-admin-content sisu-mb-20">
        <h2><?php _e('Status do Sistema', 'sisu-docs-manager'); ?></h2>
        <div style="display: flex; align-items: center; gap: 15px;">
            <div class="sisu-status <?php echo $sistema_ativo ? 'sisu-status-aprovado' : 'sisu-status-recusado'; ?>">
                <?php echo $sistema_ativo ? __('ATIVO', 'sisu-docs-manager') : __('INATIVO', 'sisu-docs-manager'); ?>
            </div>
            <?php if ($sistema_ativo): ?>
                <span><?php _e('O sistema está recebendo documentos dos candidatos.', 'sisu-docs-manager'); ?></span>
            <?php else: ?>
                <span><?php _e('O sistema não está recebendo documentos. Configure as datas de ativação.', 'sisu-docs-manager'); ?></span>
            <?php endif; ?>
            <a href="<?php echo admin_url('admin.php?page=sisu-configuracoes'); ?>" class="sisu-btn sisu-btn-primary">
                <?php _e('Configurar', 'sisu-docs-manager'); ?>
            </a>
        </div>
    </div>

    <!-- Estatísticas Gerais -->
    <div class="sisu-admin-content sisu-mb-20">
        <h2><?php _e('Estatísticas Gerais', 'sisu-docs-manager'); ?></h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
            
            <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; text-align: center;">
                <h3 style="margin: 0; font-size: 2em; color: #0073aa;"><?php echo number_format($stats['total_candidatos']); ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;"><?php _e('Total de Candidatos', 'sisu-docs-manager'); ?></p>
            </div>

            <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; text-align: center;">
                <h3 style="margin: 0; font-size: 2em; color: #46b450;"><?php echo number_format($stats['documentos_aprovados']); ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;"><?php _e('Documentos Aprovados', 'sisu-docs-manager'); ?></p>
            </div>

            <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; text-align: center;">
                <h3 style="margin: 0; font-size: 2em; color: #ffb900;"><?php echo number_format($stats['documentos_pendentes']); ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;"><?php _e('Documentos Pendentes', 'sisu-docs-manager'); ?></p>
            </div>

            <div style="background: #f9f9f9; padding: 20px; border-radius: 5px; text-align: center;">
                <h3 style="margin: 0; font-size: 2em; color: #dc3232;"><?php echo number_format($stats['documentos_recusados']); ?></h3>
                <p style="margin: 5px 0 0 0; color: #666;"><?php _e('Documentos Recusados', 'sisu-docs-manager'); ?></p>
            </div>

        </div>
    </div>

    <!-- Ações Rápidas -->
    <div class="sisu-admin-content sisu-mb-20">
        <h2><?php _e('Ações Rápidas', 'sisu-docs-manager'); ?></h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
            
            <div style="border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
                <h3><?php _e('Importar Candidatos', 'sisu-docs-manager'); ?></h3>
                <p><?php _e('Importe uma lista de candidatos a partir de um arquivo CSV.', 'sisu-docs-manager'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=sisu-candidatos&action=import'); ?>" class="sisu-btn sisu-btn-primary">
                    <?php _e('Importar CSV', 'sisu-docs-manager'); ?>
                </a>
            </div>

            <div style="border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
                <h3><?php _e('Validar Documentos', 'sisu-docs-manager'); ?></h3>
                <p><?php _e('Revise e valide os documentos enviados pelos candidatos.', 'sisu-docs-manager'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=sisu-documentos'); ?>" class="sisu-btn sisu-btn-primary">
                    <?php _e('Ver Documentos', 'sisu-docs-manager'); ?>
                </a>
            </div>

            <div style="border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
                <h3><?php _e('Gerenciar Usuários', 'sisu-docs-manager'); ?></h3>
                <p><?php _e('Adicione secretários e coordenadores ao sistema.', 'sisu-docs-manager'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=sisu-usuarios'); ?>" class="sisu-btn sisu-btn-primary">
                    <?php _e('Gerenciar Usuários', 'sisu-docs-manager'); ?>
                </a>
            </div>

            <div style="border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
                <h3><?php _e('Configurar Sistema', 'sisu-docs-manager'); ?></h3>
                <p><?php _e('Configure datas de ativação e outras opções do sistema.', 'sisu-docs-manager'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=sisu-configuracoes'); ?>" class="sisu-btn sisu-btn-primary">
                    <?php _e('Configurações', 'sisu-docs-manager'); ?>
                </a>
            </div>

        </div>
    </div>

    <!-- Estrutura do Sistema -->
    <div class="sisu-admin-content">
        <h2><?php _e('Estrutura do Sistema', 'sisu-docs-manager'); ?></h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
            
            <div style="background: #f0f8ff; padding: 15px; border-radius: 5px; text-align: center;">
                <h4 style="margin: 0; color: #0073aa;"><?php echo number_format($stats['total_campus']); ?></h4>
                <p style="margin: 5px 0 0 0;"><?php _e('Campus', 'sisu-docs-manager'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=sisu-campus'); ?>" class="sisu-btn sisu-btn-secondary sisu-btn-small">
                    <?php _e('Gerenciar', 'sisu-docs-manager'); ?>
                </a>
            </div>

            <div style="background: #f0f8ff; padding: 15px; border-radius: 5px; text-align: center;">
                <h4 style="margin: 0; color: #0073aa;"><?php echo number_format($stats['total_cursos']); ?></h4>
                <p style="margin: 5px 0 0 0;"><?php _e('Cursos', 'sisu-docs-manager'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=sisu-cursos'); ?>" class="sisu-btn sisu-btn-secondary sisu-btn-small">
                    <?php _e('Gerenciar', 'sisu-docs-manager'); ?>
                </a>
            </div>

            <div style="background: #f0f8ff; padding: 15px; border-radius: 5px; text-align: center;">
                <h4 style="margin: 0; color: #0073aa;"><?php echo number_format($stats['total_secretarias']); ?></h4>
                <p style="margin: 5px 0 0 0;"><?php _e('Secretarias', 'sisu-docs-manager'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=sisu-secretarias'); ?>" class="sisu-btn sisu-btn-secondary sisu-btn-small">
                    <?php _e('Gerenciar', 'sisu-docs-manager'); ?>
                </a>
            </div>

        </div>
    </div>
</div>
