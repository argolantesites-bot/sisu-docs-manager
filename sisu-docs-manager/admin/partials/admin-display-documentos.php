<?php
/**
 * Página de gerenciamento de documentos do SiSU Docs Manager - Versão 2.
 */

// Verificar permissões
if (!current_user_can('validate_sisu_documents')) {
    wp_die(__('Você não tem permissão para acessar esta página.', 'sisu-docs-manager'));
}

$database = new Sisu_Database();

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Atualização em lote de todos os documentos do candidato
    if (isset($_POST['update_all_status']) && wp_verify_nonce($_POST['_wpnonce'], 'sisu_update_all_documents_status')) {
        $candidato_id = intval($_POST['candidato_id']);
        $documentos_status = isset($_POST['documentos_status']) ? $_POST['documentos_status'] : array();
        $documentos_obs = isset($_POST['documentos_obs']) ? $_POST['documentos_obs'] : array();
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($documentos_status as $doc_id => $status) {
            $doc_id = intval($doc_id);
            $status = sanitize_text_field($status);
            $observacoes = isset($documentos_obs[$doc_id]) ? sanitize_textarea_field($documentos_obs[$doc_id]) : '';
            
            $result = $database->update_documento_status($doc_id, $status, $observacoes, get_current_user_id());
            
            if ($result) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
        
        if ($success_count > 0) {
            echo '<div class="sisu-alert sisu-alert-success"><p>' . sprintf(__('%d documentos atualizados com sucesso!', 'sisu-docs-manager'), $success_count) . '</p></div>';
            
            // Enviar email consolidado com todos os status
            $email_notifier = new Sisu_Email_Notifier();
            $email_notifier->notify_all_documents_status($candidato_id);
        }
        
        if ($error_count > 0) {
            echo '<div class="sisu-alert sisu-alert-error"><p>' . sprintf(__('Erro ao atualizar %d documentos.', 'sisu-docs-manager'), $error_count) . '</p></div>';
        }
    }
}

// Obter filtros
$candidato_id = isset($_GET['candidato_id']) ? intval($_GET['candidato_id']) : null;
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$tipo_filter = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';

// Verificar se o usuário é secretário e filtrar por curso
$current_user = wp_get_current_user();
$user_secretaria = null;
$curso_filter = '';

// Se for secretário, buscar sua secretaria
if (in_array('sisu_secretario', $current_user->roles)) {
    $user_secretaria = $database->get_secretaria_by_user_email($current_user->user_email);
    
    if ($user_secretaria) {
        $curso_filter = $user_secretaria->curso_id;
    }
}

// Construir query para documentos
global $wpdb;

$filters = array();
if ($candidato_id) {
    $filters['candidato_id'] = $candidato_id;
}
if ($curso_filter) {
    $filters['curso_id'] = $curso_filter;
}
if ($status_filter) {
    $filters['status'] = $status_filter;
}
if ($tipo_filter) {
    $filters['tipo_documento'] = $tipo_filter;
}

$documentos = $database->get_documentos_with_filters($filters);

// Obter tipos de documentos
$tipos_documentos = $database->get_tipos_documentos();

// Se estiver visualizando documentos de um candidato específico
$candidato_info = null;
if ($candidato_id) {
    $candidato_info = $wpdb->get_row($wpdb->prepare(
        "SELECT c.*, cur.nome as curso_nome, cam.nome as campus_nome
         FROM {$wpdb->prefix}sisu_candidatos c
         LEFT JOIN {$wpdb->prefix}sisu_cursos cur ON c.curso_id = cur.id
         LEFT JOIN {$wpdb->prefix}sisu_campus cam ON c.campus_id = cam.id
         WHERE c.id = %d", $candidato_id
    ));
}
?>

<div class="sisu-admin-container">
    <div class="sisu-admin-header">
        <h1><?php _e('Gerenciar Documentos', 'sisu-docs-manager'); ?>
            <?php if ($curso_filter && $user_secretaria): ?>
                <small style="font-size: 0.6em; display: block; margin-top: 5px;">
                    <?php echo esc_html($database->get_curso_name_by_id($curso_filter) . ' - ' . $database->get_campus_name_by_id($user_secretaria->campus_id)); ?>
                </small>
            <?php endif; ?>
        </h1>
        <?php if ($candidato_info): ?>
            <div style="background: #f0f8ff; padding: 15px; border-radius: 5px; margin-top: 15px;">
                <h3 style="margin: 0 0 10px 0;"><?php echo esc_html($candidato_info->nome ?? 'N/A'); ?></h3>
                <p style="margin: 0;"><strong><?php _e('Inscrição:', 'sisu-docs-manager'); ?></strong> <?php echo esc_html($candidato_info->inscricao ?? 'N/A'); ?></p>
                <p style="margin: 0;"><strong><?php _e('Email:', 'sisu-docs-manager'); ?></strong> <?php echo esc_html($candidato_info->email ?? 'N/A'); ?></p>
                <p style="margin: 0;"><strong><?php _e('Curso:', 'sisu-docs-manager'); ?></strong> <?php echo esc_html($candidato_info->curso_nome ?? 'N/A'); ?></p>
                <p style="margin: 0;"><strong><?php _e('Campus:', 'sisu-docs-manager'); ?></strong> <?php echo esc_html($candidato_info->campus_nome ?? 'N/A'); ?></p>
                <a href="<?php echo admin_url('admin.php?page=sisu-documentos'); ?>" class="sisu-btn sisu-btn-secondary" style="margin-top: 10px;">
                    <?php _e('← Voltar para todos os documentos', 'sisu-docs-manager'); ?>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="sisu-admin-content">
        <!-- Filtros -->
        <?php if (!$candidato_id): ?>
        <form method="get" style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
            <input type="hidden" name="page" value="sisu-documentos">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                
                <div>
                    <label for="status"><?php _e('Status:', 'sisu-docs-manager'); ?></label>
                    <select name="status" id="status">
                        <option value=""><?php _e('Todos os Status', 'sisu-docs-manager'); ?></option>
                        <option value="Não enviado" <?php selected($status_filter, 'Não enviado'); ?>><?php _e('Não enviado', 'sisu-docs-manager'); ?></option>
                        <option value="Aguardando Validação" <?php selected($status_filter, 'Aguardando Validação'); ?>><?php _e('Aguardando Validação', 'sisu-docs-manager'); ?></option>
                        <option value="Aprovado" <?php selected($status_filter, 'Aprovado'); ?>><?php _e('Aprovado', 'sisu-docs-manager'); ?></option>
                        <option value="Recusado" <?php selected($status_filter, 'Recusado'); ?>><?php _e('Recusado', 'sisu-docs-manager'); ?></option>
                    </select>
                </div>

                <div>
                    <label for="tipo"><?php _e('Tipo de Documento:', 'sisu-docs-manager'); ?></label>
                    <select name="tipo" id="tipo">
                        <option value=""><?php _e('Todos os Tipos', 'sisu-docs-manager'); ?></option>
                        <?php foreach ($tipos_documentos as $tipo_key => $tipo_nome): ?>
                            <option value="<?php echo $tipo_key; ?>" <?php selected($tipo_filter, $tipo_key); ?>>
                                <?php echo esc_html($tipo_nome ?? 'N/A'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 10px;">
                    <input type="submit" class="sisu-btn sisu-btn-primary" value="<?php _e('Filtrar', 'sisu-docs-manager'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=sisu-documentos'); ?>" class="sisu-btn sisu-btn-secondary">
                        <?php _e('Limpar', 'sisu-docs-manager'); ?>
                    </a>
                </div>

            </div>
        </form>
        <?php endif; ?>

        <!-- Lista de Candidatos -->
        <?php if (!$candidato_id): ?>
            <?php
            // Agrupar candidatos únicos
            $candidatos_unicos = array();
            foreach ($documentos as $doc) {
                if (!isset($candidatos_unicos[$doc->candidato_id])) {
                    $candidatos_unicos[$doc->candidato_id] = array(
                        'id' => $doc->candidato_id,
                        'nome' => $doc->candidato_nome,
                        'inscricao' => $doc->inscricao,
                        'email' => $doc->email,
                        'curso' => $doc->curso_nome,
                        'campus' => $doc->campus_nome,
                        'documentos' => array()
                    );
                }
                $candidatos_unicos[$doc->candidato_id]['documentos'][] = $doc;
            }
            ?>
            
            <h2><?php _e('Candidatos com Documentos', 'sisu-docs-manager'); ?> (<?php echo count($candidatos_unicos); ?>)</h2>
            
            <?php if (empty($candidatos_unicos)): ?>
                <div class="sisu-alert sisu-alert-warning">
                    <p><?php _e('Nenhum candidato encontrado com os filtros aplicados.', 'sisu-docs-manager'); ?></p>
                </div>
            <?php else: ?>
                <table class="sisu-table">
                    <thead>
                        <tr>
                            <th><?php _e('Inscrição', 'sisu-docs-manager'); ?></th>
                            <th><?php _e('Nome', 'sisu-docs-manager'); ?></th>
                            <th><?php _e('Email', 'sisu-docs-manager'); ?></th>
                            <th><?php _e('Curso', 'sisu-docs-manager'); ?></th>
                            <th><?php _e('Campus', 'sisu-docs-manager'); ?></th>
                            <th><?php _e('Status Documentos', 'sisu-docs-manager'); ?></th>
                            <th><?php _e('Ações', 'sisu-docs-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($candidatos_unicos as $candidato): 
                        // Calcular estatísticas dos documentos
                        $stats = array(
                            'total' => count($candidato['documentos']),
                            'aprovados' => 0,
                            'recusados' => 0,
                            'pendentes' => 0,
                            'nao_enviados' => 0
                        );
                        
                        foreach ($candidato['documentos'] as $doc) {
                            switch ($doc->status) {
                                case 'Aprovado': $stats['aprovados']++; break;
                                case 'Recusado': $stats['recusados']++; break;
                                case 'Aguardando Validação': $stats['pendentes']++; break;
                                case 'Não enviado': $stats['nao_enviados']++; break;
                            }
                        }
                    ?>
                        <tr>
                            <td><?php echo esc_html($candidato['inscricao'] ?? 'N/A'); ?></td>
                            <td><?php echo esc_html($candidato['nome'] ?? 'N/A'); ?></td>
                            <td><?php echo esc_html($candidato['email'] ?? 'N/A'); ?></td>
                            <td><?php echo esc_html($candidato['curso'] ?? 'N/A'); ?></td>
                            <td><?php echo esc_html($candidato['campus'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="sisu-status sisu-status-aprovado"><?php echo $stats['aprovados']; ?></span>/
                                <span class="sisu-status sisu-status-aguardando-validacao"><?php echo $stats['pendentes']; ?></span>/
                                <span class="sisu-status sisu-status-recusado"><?php echo $stats['recusados']; ?></span>/
                                <span class="sisu-status sisu-status-nao-enviado"><?php echo $stats['nao_enviados']; ?></span>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=sisu-documentos&candidato_id=' . $candidato['id']); ?>" class="sisu-btn sisu-btn-small sisu-btn-primary">
                                    <?php _e('Ver Documentos', 'sisu-docs-manager'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="sisu-legend" style="margin-top: 20px; padding: 10px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9;">
                    <strong>Legenda de Status:</strong><br>
                    <span class="sisu-status sisu-status-aprovado" style="padding: 2px 5px; margin-right: 5px;"></span> <?php _e('Aprovado', 'sisu-docs-manager'); ?><br>
                    <span class="sisu-status sisu-status-aguardando-validacao" style="padding: 2px 5px; margin-right: 5px;"></span> <?php _e('Aguardando Validação', 'sisu-docs-manager'); ?><br>
                    <span class="sisu-status sisu-status-recusado" style="padding: 2px 5px; margin-right: 5px;"></span> <?php _e('Recusado', 'sisu-docs-manager'); ?><br>
                    <span class="sisu-status sisu-status-nao-enviado" style="padding: 2px 5px; margin-right: 5px;"></span> <?php _e('Não Enviado', 'sisu-docs-manager'); ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- Visualização de documentos de um candidato específico -->
            <h2><?php _e('Documentos do Candidato', 'sisu-docs-manager'); ?></h2>
            
            <?php if (empty($documentos)): ?>
                <div class="sisu-alert sisu-alert-warning">
                    <p><?php _e('Nenhum documento encontrado para este candidato.', 'sisu-docs-manager'); ?></p>
                </div>
            <?php else: ?>
                <!-- Formulário único para atualizar todos os documentos -->
                <form method="post" id="sisu-update-all-form">
                    <?php wp_nonce_field('sisu_update_all_documents_status'); ?>
                    <input type="hidden" name="candidato_id" value="<?php echo esc_attr($candidato_id); ?>">
                    
                    <div style="display: grid; gap: 20px;">
                        <?php foreach ($documentos as $documento): ?>
                            <!-- Item do Documento -->
                            <div style="border: 1px solid #eee; border-radius: 3px; padding: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 20px;">
                                    
                                    <div style="flex: 1;">
                                        <h4 style="margin: 0 0 10px 0;"><?php echo esc_html($documento->nome_documento ?? $tipos_documentos[$documento->tipo_documento ?? ''] ?? ($documento->tipo_documento ?? 'N/A')); ?></h4>
                                        
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 15px;">
                                            <div>
                                                <strong><?php _e('Status Atual:', 'sisu-docs-manager'); ?></strong><br>
                                                <span class="sisu-status sisu-status-<?php echo strtolower(str_replace(' ', '-', $documento->status ?? 'nao-enviado')); ?>">
                                                    <?php echo esc_html($documento->status ?? 'Não enviado'); ?>
                                                </span>
                                            </div>
                                            
                                            <?php if ($documento->nome_arquivo): ?>
                                            <div>
                                                <strong><?php _e('Arquivo:', 'sisu-docs-manager'); ?></strong><br>
                                                <a href="<?php echo wp_upload_dir()['baseurl'] . '/sisu-docs/' . esc_attr($documento->caminho_arquivo ?? ''); ?>" target="_blank">
                                                    <?php echo esc_html($documento->nome_arquivo ?? 'N/A'); ?>
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if ($documento->data_envio): ?>
                                            <div>
                                                <strong><?php _e('Data de Envio:', 'sisu-docs-manager'); ?></strong><br>
                                                <?php echo date_i18n('d/m/Y H:i', strtotime($documento->data_envio ?? 'now')); ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <?php if ($documento->observacoes): ?>
                                        <div style="margin-top: 15px; padding: 10px; background: #f9f9f9; border-left: 3px solid #ddd;">
                                            <strong><?php _e('Observações Anteriores:', 'sisu-docs-manager'); ?></strong><br>
                                            <?php echo esc_html($documento->observacoes ?? 'N/A'); ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <div style="flex-shrink: 0; min-width: 250px;">
                                        <label><strong><?php _e('Novo Status:', 'sisu-docs-manager'); ?></strong></label>
                                        <select name="documentos_status[<?php echo esc_attr($documento->id); ?>]" style="width: 100%; margin-bottom: 5px;">
                                            <option value="Aprovado" <?php selected($documento->status, 'Aprovado'); ?>><?php _e('Aprovado', 'sisu-docs-manager'); ?></option>
                                            <option value="Recusado" <?php selected($documento->status, 'Recusado'); ?>><?php _e('Recusado', 'sisu-docs-manager'); ?></option>
                                            <option value="Aguardando Validação" <?php selected($documento->status, 'Aguardando Validação'); ?>><?php _e('Aguardando Validação', 'sisu-docs-manager'); ?></option>
                                            <option value="Não enviado" <?php selected($documento->status, 'Não enviado'); ?>><?php _e('Não enviado', 'sisu-docs-manager'); ?></option>
                                        </select>
                                        <label><strong><?php _e('Observações:', 'sisu-docs-manager'); ?></strong></label>
                                        <textarea name="documentos_obs[<?php echo esc_attr($documento->id); ?>]" placeholder="<?php _e('Observações (opcional)', 'sisu-docs-manager'); ?>" style="width: 100%; margin-bottom: 5px;" rows="3"><?php echo esc_textarea($documento->observacoes ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Botão único para atualizar todos os status -->
                    <div style="margin-top: 30px; padding: 20px; background: #f0f8ff; border-radius: 5px; text-align: center;">
                        <p style="margin: 0 0 15px 0; font-size: 14px; color: #666;">
                            <?php _e('Ao clicar no botão abaixo, todos os status serão atualizados e um email consolidado será enviado ao candidato e à secretaria.', 'sisu-docs-manager'); ?>
                        </p>
                        <input type="submit" name="update_all_status" class="sisu-btn sisu-btn-primary sisu-btn-large" value="<?php _e('ATUALIZAR STATUS', 'sisu-docs-manager'); ?>" style="font-size: 16px; padding: 12px 30px; font-weight: bold;">
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .sisu-admin-container {
        max-width: 95%;
        margin-right: auto;
        margin-left: auto;
        padding: 20px;
    }
    .sisu-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
        table-layout: auto;
    }
    .sisu-table th,
    .sisu-table td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
        word-wrap: break-word;
    }
    .sisu-table th {
        background-color: #f2f2f2;
    }
    .sisu-status {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 0.85em;
        font-weight: bold;
        color: #fff;
    }
    .sisu-status-aprovado {
        background-color: #28a745;
    }
    .sisu-status-aguardando-validacao {
        background-color: #ffc107;
        color: #333;
    }
    .sisu-status-recusado {
        background-color: #dc3545;
    }
    .sisu-status-nao-enviado {
        background-color: #6c757d;
    }
    .sisu-btn-large {
        font-size: 18px !important;
        padding: 15px 40px !important;
    }
</style>
