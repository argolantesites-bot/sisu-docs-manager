<?php
/**
 * Template para gestão de cursos.
 */

// Verificar permissões
if (!current_user_can('manage_sisu_system')) {
    wp_die(__('Você não tem permissão para acessar esta página.', 'sisu-docs-manager'));
}

$database = new Sisu_Database();

// Processar ações
$message = '';
$message_type = '';

if (isset($_POST['action'])) {
    if ($_POST['action'] === 'add_curso' && wp_verify_nonce($_POST['sisu_nonce'], 'sisu_admin_action')) {
        $nome = sanitize_text_field($_POST['nome']);
        $campus_id = intval($_POST['campus_id']);
        
        if (!empty($nome) && $campus_id > 0) {
            $result = $database->insert_curso(array(
                'nome' => $nome,
                'campus_id' => $campus_id
            ));
            
            if ($result) {
                $message = __('Curso adicionado com sucesso!', 'sisu-docs-manager');
                $message_type = 'success';
            } else {
                $message = __('Erro ao adicionar curso.', 'sisu-docs-manager');
                $message_type = 'error';
            }
        } else {
            $message = __('Nome do curso e campus são obrigatórios.', 'sisu-docs-manager');
            $message_type = 'error';
        }
    }
    
    if ($_POST['action'] === 'delete_curso' && wp_verify_nonce($_POST['sisu_nonce'], 'sisu_admin_action')) {
        $curso_id = intval($_POST['curso_id']);
        
        global $wpdb;
        $result = $wpdb->delete($wpdb->prefix . 'sisu_cursos', array('id' => $curso_id), array('%d'));
        
        if ($result) {
            $message = __('Curso removido com sucesso!', 'sisu-docs-manager');
            $message_type = 'success';
        } else {
            $message = __('Erro ao remover curso.', 'sisu-docs-manager');
            $message_type = 'error';
        }
    }
    
    if ($_POST['action'] === 'edit_curso' && wp_verify_nonce($_POST['sisu_nonce'], 'sisu_admin_action')) {
        $curso_id = intval($_POST['curso_id']);
        $nome = sanitize_text_field($_POST['nome']);
        $campus_id = intval($_POST['campus_id']);
        
        if (!empty($nome) && $campus_id > 0) {
            global $wpdb;
            $result = $wpdb->update(
                $wpdb->prefix . 'sisu_cursos',
                array(
                    'nome' => $nome,
                    'campus_id' => $campus_id
                ),
                array('id' => $curso_id),
                array('%s', '%d'),
                array('%d')
            );
            
            if ($result !== false) {
                $message = __('Curso atualizado com sucesso!', 'sisu-docs-manager');
                $message_type = 'success';
            } else {
                $message = __('Erro ao atualizar curso.', 'sisu-docs-manager');
                $message_type = 'error';
            }
        } else {
            $message = __('Nome do curso e campus são obrigatórios.', 'sisu-docs-manager');
            $message_type = 'error';
        }
    }
}

// Obter listas
$campus_list = $database->get_campus();
$cursos_list = $database->get_cursos();

// Filtros
$campus_filter = isset($_GET['campus_filter']) ? intval($_GET['campus_filter']) : 0;
if ($campus_filter > 0) {
    $cursos_list = array_filter($cursos_list, function($curso) use ($campus_filter) {
        return $curso->campus_id == $campus_filter;
    });
}
?>

<div class="wrap sisu-admin-content">
    <h1><?php _e('Gestão de Cursos', 'sisu-docs-manager'); ?></h1>
    
    <?php if (!empty($message)): ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <?php if (empty($campus_list)): ?>
        <div class="notice notice-warning">
            <p><?php _e('Você precisa cadastrar pelo menos um campus antes de adicionar cursos.', 'sisu-docs-manager'); ?></p>
            <p><a href="<?php echo admin_url('admin.php?page=sisu-campus'); ?>" class="button"><?php _e('Gerenciar Campus', 'sisu-docs-manager'); ?></a></p>
        </div>
    <?php else: ?>

    <div class="sisu-admin-grid">
        <!-- Formulário de Adição -->
        <div class="sisu-admin-card">
            <h2><?php _e('Adicionar Novo Curso', 'sisu-docs-manager'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('sisu_admin_action', 'sisu_nonce'); ?>
                <input type="hidden" name="action" value="add_curso">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="nome"><?php _e('Nome do Curso', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="nome" name="nome" class="regular-text" required 
                                   placeholder="<?php _e('Ex: Engenharia de Software', 'sisu-docs-manager'); ?>">
                            <p class="description"><?php _e('Digite o nome completo do curso.', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="campus_id"><?php _e('Campus', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <select id="campus_id" name="campus_id" class="regular-text" required>
                                <option value=""><?php _e('Selecione um campus', 'sisu-docs-manager'); ?></option>
                                <?php foreach ($campus_list as $campus): ?>
                                    <option value="<?php echo $campus->id; ?>"><?php echo esc_html($campus->nome); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Selecione o campus onde o curso será oferecido.', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Adicionar Curso', 'sisu-docs-manager'); ?>">
                </p>
            </form>
        </div>

        <!-- Lista de Cursos -->
        <div class="sisu-admin-card">
            <div class="sisu-card-header">
                <h2><?php _e('Cursos Cadastrados', 'sisu-docs-manager'); ?></h2>
                
                <!-- Filtros -->
                <div class="sisu-filters">
                    <form method="get" action="">
                        <input type="hidden" name="page" value="sisu-cursos">
                        <select name="campus_filter" onchange="this.form.submit()">
                            <option value="0"><?php _e('Todos os campus', 'sisu-docs-manager'); ?></option>
                            <?php foreach ($campus_list as $campus): ?>
                                <option value="<?php echo $campus->id; ?>" <?php selected($campus_filter, $campus->id); ?>>
                                    <?php echo esc_html($campus->nome); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
            </div>
            
            <?php if (empty($cursos_list)): ?>
                <div class="sisu-empty-state">
                    <p><?php _e('Nenhum curso cadastrado ainda.', 'sisu-docs-manager'); ?></p>
                    <p><?php _e('Adicione o primeiro curso usando o formulário ao lado.', 'sisu-docs-manager'); ?></p>
                </div>
            <?php else: ?>
                <div class="sisu-table-container">
                    <table class="wp-list-table widefat fixed striped sisu-table">
                        <thead>
                            <tr>
                                <th scope="col" class="manage-column column-name"><?php _e('Nome', 'sisu-docs-manager'); ?></th>
                                <th scope="col" class="manage-column column-campus"><?php _e('Campus', 'sisu-docs-manager'); ?></th>
                                <th scope="col" class="manage-column column-candidatos"><?php _e('Candidatos', 'sisu-docs-manager'); ?></th>
                                <th scope="col" class="manage-column column-created"><?php _e('Criado em', 'sisu-docs-manager'); ?></th>
                                <th scope="col" class="manage-column column-actions"><?php _e('Ações', 'sisu-docs-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cursos_list as $curso): 
                                // Contar candidatos do curso
                                global $wpdb;
                                $candidatos_count = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}sisu_candidatos WHERE curso_id = %d",
                                    $curso->id
                                ));
                            ?>
                                <tr>
                                    <td class="column-name">
                                        <strong><?php echo esc_html($curso->nome); ?></strong>
                                    </td>
                                    <td class="column-campus">
                                        <?php echo esc_html($curso->campus_nome); ?>
                                    </td>
                                    <td class="column-candidatos">
                                        <span class="count-badge"><?php echo intval($candidatos_count); ?></span>
                                        <?php echo _n('candidato', 'candidatos', $candidatos_count, 'sisu-docs-manager'); ?>
                                    </td>
                                    <td class="column-created">
                                        <?php echo date_i18n('d/m/Y H:i', strtotime($curso->created_at)); ?>
                                    </td>
                                    <td class="column-actions">
                                        <button type="button" class="button button-small" 
                                                onclick="editCurso(<?php echo $curso->id; ?>, '<?php echo esc_js($curso->nome); ?>', <?php echo $curso->campus_id; ?>)">
                                            <?php _e('Editar', 'sisu-docs-manager'); ?>
                                        </button>
                                        
                                        <?php if ($candidatos_count == 0): ?>
                                            <form method="post" style="display: inline;" 
                                                  onsubmit="return confirm('<?php _e('Tem certeza que deseja remover este curso?', 'sisu-docs-manager'); ?>')">
                                                <?php wp_nonce_field('sisu_admin_action', 'sisu_nonce'); ?>
                                                <input type="hidden" name="action" value="delete_curso">
                                                <input type="hidden" name="curso_id" value="<?php echo $curso->id; ?>">
                                                <input type="submit" class="button button-small button-link-delete" 
                                                       value="<?php _e('Remover', 'sisu-docs-manager'); ?>">
                                            </form>
                                        <?php else: ?>
                                            <span class="description"><?php _e('Não é possível remover (possui candidatos)', 'sisu-docs-manager'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php endif; ?>
</div>

<!-- Modal de Edição -->
<div id="edit-curso-modal" class="sisu-modal" style="display: none;">
    <div class="sisu-modal-content">
        <div class="sisu-modal-header">
            <h3><?php _e('Editar Curso', 'sisu-docs-manager'); ?></h3>
            <button type="button" class="sisu-modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('sisu_admin_action', 'sisu_nonce'); ?>
            <input type="hidden" name="action" value="edit_curso">
            <input type="hidden" name="curso_id" id="edit_curso_id">
            
            <div class="sisu-modal-body">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="edit_nome"><?php _e('Nome do Curso', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="edit_nome" name="nome" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="edit_campus_id"><?php _e('Campus', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <select id="edit_campus_id" name="campus_id" class="regular-text" required>
                                <option value=""><?php _e('Selecione um campus', 'sisu-docs-manager'); ?></option>
                                <?php foreach ($campus_list as $campus): ?>
                                    <option value="<?php echo $campus->id; ?>"><?php echo esc_html($campus->nome); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="sisu-modal-footer">
                <button type="button" class="button" onclick="closeEditModal()"><?php _e('Cancelar', 'sisu-docs-manager'); ?></button>
                <input type="submit" class="button-primary" value="<?php _e('Salvar Alterações', 'sisu-docs-manager'); ?>">
            </div>
        </form>
    </div>
</div>

<script>
function editCurso(id, nome, campusId) {
    document.getElementById('edit_curso_id').value = id;
    document.getElementById('edit_nome').value = nome;
    document.getElementById('edit_campus_id').value = campusId;
    document.getElementById('edit-curso-modal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('edit-curso-modal').style.display = 'none';
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    var modal = document.getElementById('edit-curso-modal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<style>
.sisu-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.sisu-card-header h2 {
    margin: 0;
}

.sisu-filters select {
    padding: 5px 10px;
    border: 1px solid #ddd;
    border-radius: 3px;
}
</style>
