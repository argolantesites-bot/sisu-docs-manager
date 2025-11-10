<?php
/**
 * Página de gerenciamento de candidatos do SiSU Docs Manager.
 */

// Verificar permissões
if (!current_user_can('manage_sisu_candidates')) {
    wp_die(__('Você não tem permissão para acessar esta página.', 'sisu-docs-manager'));
}

$database = new Sisu_Database();
$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['import_csv']) && wp_verify_nonce($_POST['_wpnonce'], 'sisu_import_csv')) {
        // Processar importação CSV
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
            require_once plugin_dir_path(dirname(__FILE__)) . '../includes/class-sisu-csv-importer.php';
            $importer = new Sisu_CSV_Importer();
            $clear_existing = isset($_POST['clear_existing']) && $_POST['clear_existing'] === '1';
            $report = $importer->import_candidates($_FILES['csv_file']['tmp_name'], $clear_existing);
            
            echo '<div class="sisu-alert sisu-alert-info">';
            echo '<h3>' . __('Relatório de Importação', 'sisu-docs-manager') . '</h3>';
            echo '<p><strong>' . __('Total de linhas processadas:', 'sisu-docs-manager') . '</strong> ' . $report['total_rows'] . '</p>';
            echo '<p><strong>' . __('Importações bem-sucedidas:', 'sisu-docs-manager') . '</strong> ' . $report['successful_imports'] . '</p>';
            echo '<p><strong>' . __('Importações falharam:', 'sisu-docs-manager') . '</strong> ' . $report['failed_imports'] . '</p>';
            
            if (!empty($report['errors'])) {
                echo '<h4>' . __('Erros:', 'sisu-docs-manager') . '</h4>';
                echo '<ul>';
                foreach ($report['errors'] as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul>';
            }
            
            if (!empty($report['warnings'])) {
                echo '<h4>' . __('Avisos:', 'sisu-docs-manager') . '</h4>';
                echo '<ul>';
                foreach ($report['warnings'] as $warning) {
                    echo '<li>' . esc_html($warning) . '</li>';
                }
                echo '</ul>';
            }
            echo '</div>';
        }
    }
}

// Obter filtros
$filters = array();
if (isset($_GET['campus_id']) && !empty($_GET['campus_id'])) {
    $filters['campus_id'] = intval($_GET['campus_id']);
}
if (isset($_GET['curso_id']) && !empty($_GET['curso_id'])) {
    $filters['curso_id'] = intval($_GET['curso_id']);
}
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = sanitize_text_field($_GET['search']);
}

// Obter candidatos
$candidatos = $database->get_candidatos($filters);

// Garantir que todos os candidatos têm documentos padrão
foreach ($candidatos as $candidato) {
    $database->create_default_documents($candidato->id);
}

// Obter listas para filtros
$campus_list = $database->get_campus();
$cursos_list = $database->get_cursos();
?>

<div class="sisu-admin-container">
    <div class="sisu-admin-header">
        <h1><?php _e('Gerenciar Candidatos', 'sisu-docs-manager'); ?></h1>
        <div style="display: flex; gap: 10px; margin-top: 15px;">
            <a href="<?php echo admin_url('admin.php?page=sisu-candidatos&action=import'); ?>" class="sisu-btn sisu-btn-primary">
                <?php _e('Importar CSV', 'sisu-docs-manager'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=sisu-candidatos&action=template'); ?>" class="sisu-btn sisu-btn-secondary">
                <?php _e('Baixar Template CSV', 'sisu-docs-manager'); ?>
            </a>
        </div>
    </div>

    <?php if ($action === 'import'): ?>
        <!-- Formulário de Importação -->
        <div class="sisu-admin-content">
            <h2><?php _e('Importar Candidatos via CSV', 'sisu-docs-manager'); ?></h2>
            
            <div class="sisu-alert sisu-alert-info">
                <h4><?php _e('Instruções para Importação:', 'sisu-docs-manager'); ?></h4>
                <ul>
                    <li><?php _e('O arquivo deve estar no formato CSV (separado por vírgulas)', 'sisu-docs-manager'); ?></li>
                    <li><?php _e('A primeira linha deve conter os cabeçalhos das colunas', 'sisu-docs-manager'); ?></li>
                    <li><?php _e('Os campos obrigatórios são: NO_CAMPUS, NO_CURSO, CO_INSCRICAO_ENEM, NO_INSCRITO, NU_CPF_INSCRITO, DS_EMAIL', 'sisu-docs-manager'); ?></li>
                    <li><?php _e('Campus e Cursos serão criados automaticamente se não existirem', 'sisu-docs-manager'); ?></li>
                    <li><?php _e('Estrutura do CSV: NO_CAMPUS,NO_CURSO,DS_TURNO,DS_FORMACAO,CO_INSCRICAO_ENEM,NO_INSCRITO,NU_CPF_INSCRITO,DT_NASCIMENTO,TP_SEXO,SG_UF_INSCRITO,NO_MUNICIPIO,NU_FONE1,NU_FONE2,DS_EMAIL,MODALIDADE_ESCOLHIDA', 'sisu-docs-manager'); ?></li>
                    <li><?php _e('Baixe o template CSV para ver o formato correto', 'sisu-docs-manager'); ?></li>
                </ul>
            </div>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('sisu_import_csv'); ?>
                <table class="sisu-form-table">
                    <tr>
                        <th><?php _e('Arquivo CSV:', 'sisu-docs-manager'); ?></th>
                        <td>
                            <input type="file" name="csv_file" accept=".csv" required>
                            <p class="description"><?php _e('Selecione o arquivo CSV com os dados dos candidatos.', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Limpar Dados Existentes:', 'sisu-docs-manager'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="clear_existing" value="1">
                                <?php _e('Apagar todos os candidatos e documentos antes de importar', 'sisu-docs-manager'); ?>
                            </label>
                            <p class="description" style="color: #d63638;"><?php _e('ATENÇÃO: Esta ação não pode ser desfeita!', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="import_csv" class="sisu-btn sisu-btn-primary" value="<?php _e('Importar Candidatos', 'sisu-docs-manager'); ?>">
                    <a href="<?php echo admin_url('admin.php?page=sisu-candidatos'); ?>" class="sisu-btn sisu-btn-secondary">
                        <?php _e('Cancelar', 'sisu-docs-manager'); ?>
                    </a>
                </p>
            </form>
        </div>

    <?php elseif ($action === 'template'): ?>
        <?php
        // Gerar e baixar template CSV
        $importer = new Sisu_CSV_Importer();
        $template_path = $importer->generate_csv_template();
        
        if (file_exists($template_path)) {
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="sisu_template.csv"');
            header('Content-Length: ' . filesize($template_path));
            readfile($template_path);
            unlink($template_path); // Remover arquivo temporário
            exit;
        }
        ?>

    <?php else: ?>
        <!-- Lista de Candidatos -->
        <div class="sisu-admin-content">
            <!-- Filtros -->
            <form method="get" style="background: #f9f9f9; padding: 20px; border-radius: 5px; margin-bottom: 20px;">
                <input type="hidden" name="page" value="sisu-candidatos">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; align-items: end;">
                    
                    <div>
                        <label for="campus_id"><?php _e('Campus:', 'sisu-docs-manager'); ?></label>
                        <select name="campus_id" id="campus_id">
                            <option value=""><?php _e('Todos os Campus', 'sisu-docs-manager'); ?></option>
                            <?php foreach ($campus_list as $campus): ?>
                                <option value="<?php echo $campus->id; ?>" <?php selected(isset($filters['campus_id']) ? $filters['campus_id'] : '', $campus->id); ?>>
                                    <?php echo esc_html($campus->nome); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="curso_id"><?php _e('Curso:', 'sisu-docs-manager'); ?></label>
                        <select name="curso_id" id="curso_id">
                            <option value=""><?php _e('Todos os Cursos', 'sisu-docs-manager'); ?></option>
                            <?php foreach ($cursos_list as $curso): ?>
                                <option value="<?php echo $curso->id; ?>" <?php selected(isset($filters['curso_id']) ? $filters['curso_id'] : '', $curso->id); ?>>
                                    <?php echo esc_html($curso->nome . ' - ' . $curso->campus_nome); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="search"><?php _e('Buscar:', 'sisu-docs-manager'); ?></label>
                        <input type="text" name="search" id="search" value="<?php echo isset($filters['search']) ? esc_attr($filters['search']) : ''; ?>" placeholder="<?php _e('Nome, email, CPF ou inscrição', 'sisu-docs-manager'); ?>">
                    </div>

                    <div>
                        <input type="submit" class="sisu-btn sisu-btn-primary" value="<?php _e('Filtrar', 'sisu-docs-manager'); ?>">
                        <a href="<?php echo admin_url('admin.php?page=sisu-candidatos'); ?>" class="sisu-btn sisu-btn-secondary">
                            <?php _e('Limpar', 'sisu-docs-manager'); ?>
                        </a>
                    </div>

                </div>
            </form>

            <!-- Tabela de Candidatos -->
            <h2><?php _e('Lista de Candidatos', 'sisu-docs-manager'); ?> (<?php echo count($candidatos); ?>)</h2>
            
            <?php if (empty($candidatos)): ?>
                <div class="sisu-alert sisu-alert-warning">
                    <p><?php _e('Nenhum candidato encontrado. Importe candidatos via CSV ou ajuste os filtros.', 'sisu-docs-manager'); ?></p>
                </div>
            <?php else: ?>
                <table class="sisu-table">
                    <thead>
                        <tr>
                            <th><?php _e('Inscrição', 'sisu-docs-manager'); ?></th>
                            <th><?php _e('Nome', 'sisu-docs-manager'); ?></th>
                            <th><?php _e('Email', 'sisu-docs-manager'); ?></th>
                            <th><?php _e('CPF', 'sisu-docs-manager'); ?></th>
                            <th><?php _e('Campus', 'sisu-docs-manager'); ?></th>
                            <th><?php _e('Curso', 'sisu-docs-manager'); ?></th>
                            <th><?php _e('Ações', 'sisu-docs-manager'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($candidatos as $candidato): ?>
                            <tr>
                                <td><?php echo esc_html($candidato->inscricao ?? 'N/A'); ?></td>
                                <td><?php echo esc_html($candidato->nome ?? 'N/A'); ?></td>
                                <td><?php echo esc_html($candidato->email ?? 'N/A'); ?></td>
                                <td><?php echo esc_html($candidato->cpf ?? 'N/A'); ?></td>
                                <td><?php echo esc_html($candidato->campus_nome ?? 'N/A'); ?></td>
                                <td><?php echo esc_html($candidato->curso_nome ?? 'N/A'); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=sisu-documentos&candidato_id=' . $candidato->id); ?>" class="sisu-btn sisu-btn-small sisu-btn-primary">
                                        <?php _e('Ver Documentos', 'sisu-docs-manager'); ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Filtro dinâmico de cursos por campus
    $('#campus_id').change(function() {
        var campusId = $(this).val();
        var cursoSelect = $('#curso_id');
        
        // Resetar opções de curso
        cursoSelect.html('<option value=""><?php _e('Todos os Cursos', 'sisu-docs-manager'); ?></option>');
        
        if (campusId) {
            // Filtrar cursos por campus
            <?php foreach ($cursos_list as $curso): ?>
                <?php if ($curso->campus_id): ?>
                    if (campusId == '<?php echo $curso->campus_id; ?>') {
                        cursoSelect.append('<option value="<?php echo $curso->id; ?>"><?php echo esc_js($curso->nome); ?></option>');
                    }
                <?php endif; ?>
            <?php endforeach; ?>
        } else {
            // Mostrar todos os cursos
            <?php foreach ($cursos_list as $curso): ?>
                cursoSelect.append('<option value="<?php echo $curso->id; ?>"><?php echo esc_js($curso->nome . ' - ' . $curso->campus_nome); ?></option>');
            <?php endforeach; ?>
        }
    });
});
</script>
