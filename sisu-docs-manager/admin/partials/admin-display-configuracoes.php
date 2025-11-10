<?php
/**
 * Página de configurações do SiSU Docs Manager.
 */

// Verificar permissões
if (!current_user_can('manage_sisu_system')) {
    wp_die(__('Você não tem permissão para acessar esta página.', 'sisu-docs-manager'));
}

// Processar formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    if (wp_verify_nonce($_POST['_wpnonce'], 'sisu_configuracoes')) {
        // Atualizar opções
        $options = array(
            'sistema_ativo' => isset($_POST['sisu_docs_manager_options']['sistema_ativo']) ? 1 : 0,
            'data_inicio' => sanitize_text_field($_POST['sisu_docs_manager_options']['data_inicio']),
            'data_fim' => sanitize_text_field($_POST['sisu_docs_manager_options']['data_fim']),
            'max_file_size' => intval($_POST['sisu_docs_manager_options']['max_file_size']),
            'email_notifications' => isset($_POST['sisu_docs_manager_options']['email_notifications']) ? 1 : 0,
            'email_admin' => sanitize_email($_POST['sisu_docs_manager_options']['email_admin']),
            'mensagem_sistema_inativo' => sanitize_textarea_field($_POST['sisu_docs_manager_options']['mensagem_sistema_inativo']),
            'mensagem_boas_vindas' => sanitize_textarea_field($_POST['sisu_docs_manager_options']['mensagem_boas_vindas']),
        );
        
        update_option('sisu_docs_manager_options', $options);
        
        echo '<div class="sisu-alert sisu-alert-success">';
        echo '<p>' . __('Configurações salvas com sucesso!', 'sisu-docs-manager') . '</p>';
        echo '</div>';
    }
}

// Obter configurações atuais
$options = get_option('sisu_docs_manager_options', array());
$defaults = array(
    'sistema_ativo' => false,
    'data_inicio' => '',
    'data_fim' => '',
    'max_file_size' => 10485760, // 10MB
    'email_notifications' => true,
    'email_admin' => get_option('admin_email'),
    'mensagem_sistema_inativo' => 'O sistema de recebimento de documentos não está ativo no momento. Aguarde o período de envio.',
    'mensagem_boas_vindas' => 'Bem-vindo ao sistema de envio de documentos do SiSU. Envie seus documentos conforme solicitado.',
);
$options = wp_parse_args($options, $defaults);

// Verificar se o sistema deve estar ativo baseado nas datas
$sistema_deve_estar_ativo = false;
if (!empty($options['data_inicio']) && !empty($options['data_fim'])) {
    $agora = current_time('timestamp');
    $inicio = strtotime($options['data_inicio']);
    $fim = strtotime($options['data_fim']);
    
    if ($agora >= $inicio && $agora <= $fim) {
        $sistema_deve_estar_ativo = true;
    }
}
?>

<div class="sisu-admin-container">
    <div class="sisu-admin-header">
        <h1><?php _e('Configurações do Sistema', 'sisu-docs-manager'); ?></h1>
        <p><?php _e('Configure as opções gerais do sistema de recebimento de documentos.', 'sisu-docs-manager'); ?></p>
    </div>

    <div class="sisu-admin-content">
        <!-- Status Atual do Sistema -->
        <div style="background: #f0f8ff; border: 1px solid #0073aa; border-radius: 5px; padding: 20px; margin-bottom: 30px;">
            <h3 style="margin: 0 0 15px 0;"><?php _e('Status Atual do Sistema', 'sisu-docs-manager'); ?></h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                <div>
                    <strong><?php _e('Sistema:', 'sisu-docs-manager'); ?></strong><br>
                    <span class="sisu-status <?php echo $options['sistema_ativo'] ? 'sisu-status-aprovado' : 'sisu-status-recusado'; ?>">
                        <?php echo $options['sistema_ativo'] ? __('ATIVO', 'sisu-docs-manager') : __('INATIVO', 'sisu-docs-manager'); ?>
                    </span>
                </div>
                
                <div>
                    <strong><?php _e('Baseado nas Datas:', 'sisu-docs-manager'); ?></strong><br>
                    <span class="sisu-status <?php echo $sistema_deve_estar_ativo ? 'sisu-status-aprovado' : 'sisu-status-aguardando'; ?>">
                        <?php echo $sistema_deve_estar_ativo ? __('DEVE ESTAR ATIVO', 'sisu-docs-manager') : __('FORA DO PERÍODO', 'sisu-docs-manager'); ?>
                    </span>
                </div>
                
                <div>
                    <strong><?php _e('Data/Hora Atual:', 'sisu-docs-manager'); ?></strong><br>
                    <?php echo current_time('d/m/Y H:i:s'); ?>
                </div>
            </div>
            
            <?php if ($options['sistema_ativo'] && !$sistema_deve_estar_ativo): ?>
                <div class="sisu-alert sisu-alert-warning" style="margin-top: 15px;">
                    <p><?php _e('⚠️ Atenção: O sistema está marcado como ativo, mas estamos fora do período configurado para recebimento de documentos.', 'sisu-docs-manager'); ?></p>
                </div>
            <?php elseif (!$options['sistema_ativo'] && $sistema_deve_estar_ativo): ?>
                <div class="sisu-alert sisu-alert-warning" style="margin-top: 15px;">
                    <p><?php _e('⚠️ Atenção: Estamos no período configurado para recebimento, mas o sistema está marcado como inativo.', 'sisu-docs-manager'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Formulário de Configurações -->
        <form method="post">
            <?php wp_nonce_field('sisu_configuracoes'); ?>
            
            <!-- Configurações de Ativação -->
            <div style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                <h3><?php _e('Configurações de Ativação', 'sisu-docs-manager'); ?></h3>
                
                <table class="sisu-form-table">
                    <tr>
                        <th><?php _e('Sistema Ativo:', 'sisu-docs-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="sisu_docs_manager_options[sistema_ativo]" value="1" <?php checked($options['sistema_ativo'], 1); ?>>
                                <?php _e('Ativar sistema de recebimento de documentos', 'sisu-docs-manager'); ?>
                            </label>
                            <p class="description"><?php _e('Marque esta opção para permitir que candidatos enviem documentos.', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Data de Início:', 'sisu-docs-manager'); ?></th>
                        <td>
                            <input type="datetime-local" name="sisu_docs_manager_options[data_inicio]" value="<?php echo esc_attr($options['data_inicio']); ?>">
                            <p class="description"><?php _e('Data e hora de início para recebimento de documentos.', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Data de Fim:', 'sisu-docs-manager'); ?></th>
                        <td>
                            <input type="datetime-local" name="sisu_docs_manager_options[data_fim]" value="<?php echo esc_attr($options['data_fim']); ?>">
                            <p class="description"><?php _e('Data e hora de fim para recebimento de documentos.', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Configurações de Upload -->
            <div style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                <h3><?php _e('Configurações de Upload', 'sisu-docs-manager'); ?></h3>
                
                <table class="sisu-form-table">
                    <tr>
                        <th><?php _e('Tamanho Máximo do Arquivo:', 'sisu-docs-manager'); ?></th>
                        <td>
                            <select name="sisu_docs_manager_options[max_file_size]">
                                <option value="5242880" <?php selected($options['max_file_size'], 5242880); ?>>5 MB</option>
                                <option value="10485760" <?php selected($options['max_file_size'], 10485760); ?>>10 MB</option>
                                <option value="20971520" <?php selected($options['max_file_size'], 20971520); ?>>20 MB</option>
                                <option value="52428800" <?php selected($options['max_file_size'], 52428800); ?>>50 MB</option>
                            </select>
                            <p class="description"><?php _e('Tamanho máximo permitido para cada arquivo enviado.', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Configurações de Email -->
            <div style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                <h3><?php _e('Configurações de Email', 'sisu-docs-manager'); ?></h3>
                
                <table class="sisu-form-table">
                    <tr>
                        <th><?php _e('Notificações por Email:', 'sisu-docs-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="sisu_docs_manager_options[email_notifications]" value="1" <?php checked($options['email_notifications'], 1); ?>>
                                <?php _e('Enviar notificações por email', 'sisu-docs-manager'); ?>
                            </label>
                            <p class="description"><?php _e('Enviar emails quando documentos forem enviados ou validados.', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Email do Administrador:', 'sisu-docs-manager'); ?></th>
                        <td>
                            <input type="email" name="sisu_docs_manager_options[email_admin]" value="<?php echo esc_attr($options['email_admin']); ?>">
                            <p class="description"><?php _e('Email que receberá notificações administrativas.', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Mensagens do Sistema -->
            <div style="background: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                <h3><?php _e('Mensagens do Sistema', 'sisu-docs-manager'); ?></h3>
                
                <table class="sisu-form-table">
                    <tr>
                        <th><?php _e('Mensagem - Sistema Inativo:', 'sisu-docs-manager'); ?></th>
                        <td>
                            <textarea name="sisu_docs_manager_options[mensagem_sistema_inativo]" rows="3"><?php echo esc_textarea($options['mensagem_sistema_inativo']); ?></textarea>
                            <p class="description"><?php _e('Mensagem exibida quando o sistema não está recebendo documentos.', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th><?php _e('Mensagem de Boas-vindas:', 'sisu-docs-manager'); ?></th>
                        <td>
                            <textarea name="sisu_docs_manager_options[mensagem_boas_vindas]" rows="3"><?php echo esc_textarea($options['mensagem_boas_vindas']); ?></textarea>
                            <p class="description"><?php _e('Mensagem exibida no painel do candidato quando o sistema está ativo.', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Informações do Sistema -->
            <div style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 5px; padding: 20px; margin-bottom: 20px;">
                <h3><?php _e('Informações do Sistema', 'sisu-docs-manager'); ?></h3>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div>
                        <strong><?php _e('Versão do Plugin:', 'sisu-docs-manager'); ?></strong><br>
                        <?php echo SISU_DOCS_MANAGER_VERSION; ?>
                    </div>
                    
                    <div>
                        <strong><?php _e('Diretório de Upload:', 'sisu-docs-manager'); ?></strong><br>
                        <?php echo wp_upload_dir()['basedir'] . '/sisu-docs/'; ?>
                    </div>
                    
                    <div>
                        <strong><?php _e('Limite PHP Upload:', 'sisu-docs-manager'); ?></strong><br>
                        <?php echo ini_get('upload_max_filesize'); ?>
                    </div>
                    
                    <div>
                        <strong><?php _e('Limite PHP Post:', 'sisu-docs-manager'); ?></strong><br>
                        <?php echo ini_get('post_max_size'); ?>
                    </div>
                </div>
            </div>

            <p class="submit">
                <input type="submit" name="submit" class="sisu-btn sisu-btn-primary" value="<?php _e('Salvar Configurações', 'sisu-docs-manager'); ?>">
            </p>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Validação de datas
    $('input[name="sisu_docs_manager_options[data_inicio]"], input[name="sisu_docs_manager_options[data_fim]"]').change(function() {
        var dataInicio = $('input[name="sisu_docs_manager_options[data_inicio]"]').val();
        var dataFim = $('input[name="sisu_docs_manager_options[data_fim]"]').val();
        
        if (dataInicio && dataFim && dataInicio >= dataFim) {
            alert('<?php _e('A data de fim deve ser posterior à data de início.', 'sisu-docs-manager'); ?>');
            $(this).focus();
        }
    });
});
</script>
