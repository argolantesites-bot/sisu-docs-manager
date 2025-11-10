<?php
/**
 * Template do dashboard do candidato - Versão 2 com upload em lote.
 */

// Verificar se está logado via cookie
if (!isset($_COOKIE['sisu_candidate_id']) || empty($_COOKIE['sisu_candidate_id'])) {
    echo '<div class="sisu-alert sisu-alert-error">';
    echo '<p>' . __('Você precisa estar logado para acessar esta área.', 'sisu-docs-manager') . '</p>';
    echo '</div>';
    return;
}

$candidate_id = intval($_COOKIE['sisu_candidate_id']);
$candidate_data = get_transient('sisu_candidate_data_' . $candidate_id);

// Se não tiver no transient, buscar do banco
if (!$candidate_data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'sisu_candidatos';
    $candidate_data = $wpdb->get_row($wpdb->prepare(
        "SELECT c.*, cur.nome as curso_nome, cam.nome as campus_nome
         FROM $table_name c
         LEFT JOIN {$wpdb->prefix}sisu_cursos cur ON c.curso_id = cur.id
         LEFT JOIN {$wpdb->prefix}sisu_campus cam ON c.campus_id = cam.id
         WHERE c.id = %d", $candidate_id
    ));
    
    if ($candidate_data) {
        set_transient('sisu_candidate_data_' . $candidate_id, $candidate_data, 7 * DAY_IN_SECONDS);
    } else {
        echo '<div class="sisu-alert sisu-alert-error">';
        echo '<p>' . __('Erro ao carregar dados do candidato.', 'sisu-docs-manager') . '</p>';
        echo '</div>';
        return;
    }
}

$database = new Sisu_Database();

// Obter documentos do candidato
$documentos = $database->get_documentos_candidato($candidate_data->id);
$tipos_documentos = $database->get_tipos_documentos();

// Obter configurações
$options = get_option('sisu_docs_manager_options', array());
$sistema_ativo = isset($options['sistema_ativo']) && $options['sistema_ativo'];

// Verificar se está no período ativo
$no_periodo = false;
if (!empty($options['data_inicio']) && !empty($options['data_fim'])) {
    $agora = current_time('timestamp');
    $inicio = strtotime($options['data_inicio']);
    $fim = strtotime($options['data_fim']);
    $no_periodo = ($agora >= $inicio && $agora <= $fim);
}

$pode_enviar = $sistema_ativo && $no_periodo;

// Organizar documentos por tipo
$documentos_por_tipo = array();
foreach ($documentos as $doc) {
    $documentos_por_tipo[$doc->tipo_documento] = $doc;
}

// Estatísticas dos documentos
$stats = array(
    'total' => count($tipos_documentos),
    'enviados' => 0,
    'aprovados' => 0,
    'recusados' => 0,
    'pendentes' => 0
);

foreach ($documentos as $doc) {
    if ($doc->status !== 'Não enviado') {
        $stats['enviados']++;
    }
    if ($doc->status === 'Aprovado') {
        $stats['aprovados']++;
    } elseif ($doc->status === 'Recusado') {
        $stats['recusados']++;
    } elseif ($doc->status === 'Aguardando Validação') {
        $stats['pendentes']++;
    }
}

$progresso = $stats['total'] > 0 ? round(($stats['aprovados'] / $stats['total']) * 100) : 0;
?>

<div class="sisu-public-container">
    <div class="sisu-dashboard">
        <!-- Cabeçalho -->
        <div class="sisu-dashboard-header">
            <h2><?php _e('Painel do Candidato', 'sisu-docs-manager'); ?></h2>
            <a href="#" id="sisu-logout" class="sisu-logout-link"><?php _e('Sair', 'sisu-docs-manager'); ?></a>
        </div>

        <!-- Informações do Candidato -->
        <div class="sisu-candidate-info">
            <h3><?php _e('Suas Informações', 'sisu-docs-manager'); ?></h3>
            <div class="sisu-info-grid">
                <div class="sisu-info-item">
                    <div class="sisu-info-label"><?php _e('Nome:', 'sisu-docs-manager'); ?></div>
                    <div class="sisu-info-value"><?php echo esc_html($candidate_data->nome ?? 'N/A'); ?></div>
                </div>
                <div class="sisu-info-item">
                    <div class="sisu-info-label"><?php _e('Inscrição:', 'sisu-docs-manager'); ?></div>
                    <div class="sisu-info-value"><?php echo esc_html($candidate_data->inscricao ?? 'N/A'); ?></div>
                </div>
                <div class="sisu-info-item">
                    <div class="sisu-info-label"><?php _e('Email:', 'sisu-docs-manager'); ?></div>
                    <div class="sisu-info-value"><?php echo esc_html($candidate_data->email ?? 'N/A'); ?></div>
                </div>
                <div class="sisu-info-item">
                    <div class="sisu-info-label"><?php _e('CPF:', 'sisu-docs-manager'); ?></div>
                    <div class="sisu-info-value"><?php echo esc_html($candidate_data->cpf ?? 'N/A'); ?></div>
                </div>
                <div class="sisu-info-item">
                    <div class="sisu-info-label"><?php _e('Curso:', 'sisu-docs-manager'); ?></div>
                    <div class="sisu-info-value"><?php echo esc_html($candidate_data->curso_nome ?? 'N/A'); ?></div>
                </div>
                <div class="sisu-info-item">
                    <div class="sisu-info-label"><?php _e('Campus:', 'sisu-docs-manager'); ?></div>
                    <div class="sisu-info-value"><?php echo esc_html($candidate_data->campus_nome ?? 'N/A'); ?></div>
                </div>
            </div>
        </div>

        <!-- Status do Sistema -->
        <?php if (!$pode_enviar): ?>
        <div class="sisu-alert sisu-alert-warning">
            <p><?php echo esc_html($options['mensagem_sistema_inativo'] ?? __('O sistema de recebimento de documentos não está ativo no momento.', 'sisu-docs-manager')); ?></p>
            
            <?php if (!empty($options['data_inicio']) && !empty($options['data_fim'])): ?>
                <p><strong><?php _e('Período de envio:', 'sisu-docs-manager'); ?></strong><br>
                <?php echo date_i18n('d/m/Y H:i', strtotime($options['data_inicio'])); ?> até 
                <?php echo date_i18n('d/m/Y H:i', strtotime($options['data_fim'])); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Progresso dos Documentos -->
        <div style="background: #f9f9f9; border: 1px solid #eee; border-radius: 5px; padding: 20px; margin-bottom: 30px;">
            <h3><?php _e('Progresso dos Documentos', 'sisu-docs-manager'); ?></h3>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div style="text-align: center;">
                    <div style="font-size: 2em; font-weight: bold; color: #0073aa;"><?php echo $stats['enviados']; ?>/<?php echo $stats['total']; ?></div>
                    <div style="color: #666; font-size: 0.9em;"><?php _e('Enviados', 'sisu-docs-manager'); ?></div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2em; font-weight: bold; color: #46b450;"><?php echo $stats['aprovados']; ?></div>
                    <div style="color: #666; font-size: 0.9em;"><?php _e('Aprovados', 'sisu-docs-manager'); ?></div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2em; font-weight: bold; color: #ffb900;"><?php echo $stats['pendentes']; ?></div>
                    <div style="color: #666; font-size: 0.9em;"><?php _e('Pendentes', 'sisu-docs-manager'); ?></div>
                </div>
                <div style="text-align: center;">
                    <div style="font-size: 2em; font-weight: bold; color: #dc3232;"><?php echo $stats['recusados']; ?></div>
                    <div style="color: #666; font-size: 0.9em;"><?php _e('Recusados', 'sisu-docs-manager'); ?></div>
                </div>
            </div>

            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span><?php _e('Progresso Geral:', 'sisu-docs-manager'); ?></span>
                    <span style="font-weight: bold;"><?php echo $progresso; ?>%</span>
                </div>
                <div class="sisu-progress">
                    <div class="sisu-progress-bar" style="width: <?php echo $progresso; ?>%;"></div>
                </div>
            </div>
        </div>

        <!-- Formulário de Upload em Lote -->
        <form id="sisu-upload-form" enctype="multipart/form-data">
            <input type="hidden" name="action" value="sisu_upload_documents_batch">
            <input type="hidden" name="candidate_id" value="<?php echo $candidate_data->id; ?>">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('sisu_upload_batch'); ?>">
            
            <!-- Lista de Documentos -->
            <div class="sisu-documents-section">
                <h3><?php _e('Documentos Necessários', 'sisu-docs-manager'); ?></h3>
                
                <?php foreach ($tipos_documentos as $tipo_key => $tipo_nome): 
                    $documento = isset($documentos_por_tipo[$tipo_key]) ? $documentos_por_tipo[$tipo_key] : null;
                    $status = $documento ? ($documento->status ?? 'Não enviado') : 'Não enviado';
                    $pode_enviar_este = $pode_enviar && ($status === 'Não enviado' || $status === 'Recusado');
                    $obrigatorio = $documento && isset($documento->obrigatorio) ? $documento->obrigatorio : true;
                ?>
                    <div class="sisu-document-item" id="document-<?php echo $tipo_key; ?>">
                        <div class="sisu-document-header">
                            <h4 class="sisu-document-title">
                                <?php echo esc_html($tipo_nome ?? 'N/A'); ?>
                                <?php if ($obrigatorio): ?>
                                    <span style="color: red;">*</span>
                                <?php endif; ?>
                            </h4>
                            <span class="sisu-document-status sisu-status-<?php echo strtolower(str_replace(' ', '-', $status)); ?>">
                                <?php echo esc_html($status ?? 'N/A'); ?>
                            </span>
                        </div>

                        <?php if ($documento && !empty($documento->observacoes)): ?>
                            <div style="background: #f0f8ff; border: 1px solid #0073aa; border-radius: 3px; padding: 10px; margin-bottom: 15px;">
                                <strong><?php _e('Observações:', 'sisu-docs-manager'); ?></strong><br>
                                <?php echo nl2br(esc_html($documento->observacoes ?? '')); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($documento && $documento->nome_arquivo): ?>
                            <!-- Arquivo Enviado -->
                            <div class="sisu-file-preview">
                                <div class="sisu-file-icon">📄</div>
                                <div class="sisu-file-info">
                                    <div class="sisu-file-name"><?php echo esc_html($documento->nome_arquivo ?? 'N/A'); ?></div>
                                    <div class="sisu-file-size">
                                        <?php echo size_format($documento->tamanho_arquivo ?? 0); ?> | 
                                        <?php _e('Enviado em:', 'sisu-docs-manager'); ?> <?php echo date_i18n('d/m/Y H:i', strtotime($documento->data_envio ?? 'now')); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($pode_enviar_este): ?>
                            <!-- Campo de Upload -->
                            <div class="sisu-upload-field">
                                <label for="file-<?php echo $tipo_key; ?>" class="sisu-file-label">
                                    <span class="sisu-file-button">📁 <?php _e('Escolher Arquivo', 'sisu-docs-manager'); ?></span>
                                    <span class="sisu-file-name" id="filename-<?php echo $tipo_key; ?>">
                                        <?php _e('Nenhum arquivo selecionado', 'sisu-docs-manager'); ?>
                                    </span>
                                </label>
                                <input type="file" 
                                       id="file-<?php echo $tipo_key; ?>" 
                                       name="documents[<?php echo $tipo_key; ?>]" 
                                       accept=".pdf" 
                                       class="sisu-file-input"
                                       <?php echo $obrigatorio ? 'required' : ''; ?>>
                                <div class="sisu-upload-hint">
                                    <?php _e('Formato: PDF | Tamanho máximo:', 'sisu-docs-manager'); ?> 
                                    <?php echo size_format($options['max_file_size'] ?? 10485760); ?>
                                </div>
                            </div>
                        <?php elseif (!$pode_enviar && $status === 'Não enviado'): ?>
                            <div class="sisu-alert sisu-alert-info">
                                <p><?php _e('O envio de documentos não está disponível no momento.', 'sisu-docs-manager'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($pode_enviar): ?>
                <!-- Botão de Envio -->
                <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f0f8ff; border: 2px solid #0073aa; border-radius: 5px;">
                    <p style="margin-bottom: 15px; font-size: 1.1em;">
                        <strong><?php _e('Atenção:', 'sisu-docs-manager'); ?></strong> 
                        <?php _e('Ao clicar no botão abaixo, todos os documentos selecionados serão enviados e um email de confirmação será enviado para você.', 'sisu-docs-manager'); ?>
                    </p>
                    <button type="button" class="sisu-btn sisu-btn-primary sisu-btn-large" id="submit-all-documents">
                        <span class="submit-text">📤 <?php _e('ENVIAR DOCUMENTAÇÃO', 'sisu-docs-manager'); ?></span>
                        <span class="loading-text sisu-hidden">⏳ <?php _e('Enviando...', 'sisu-docs-manager'); ?></span>
                    </button>
                    <div id="batch-upload-progress" class="sisu-hidden" style="margin-top: 20px;">
                        <div class="sisu-progress">
                            <div class="sisu-progress-bar" style="width: 0%;"></div>
                        </div>
                        <p id="progress-text" style="margin-top: 10px; font-weight: bold;"></p>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- JavaScript externo carregado via sisu-public.js -->

<style>
.sisu-upload-field {
    margin: 15px 0;
}

.sisu-file-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    padding: 10px;
    background: #f9f9f9;
    border: 2px dashed #ccc;
    border-radius: 5px;
    transition: all 0.3s;
}

.sisu-file-label:hover {
    background: #f0f8ff;
    border-color: #0073aa;
}

.sisu-file-button {
    background: #0073aa;
    color: white;
    padding: 8px 15px;
    border-radius: 3px;
    font-weight: bold;
    white-space: nowrap;
}

.sisu-file-name {
    flex: 1;
    color: #666;
}

.sisu-file-input {
    display: none;
}

.sisu-btn-large {
    font-size: 1.2em;
    padding: 15px 40px;
}
</style>
