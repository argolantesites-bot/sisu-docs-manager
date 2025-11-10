<?php
/**
 * Template para gestão de secretarias.
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
    if ($_POST['action'] === 'add_secretaria' && wp_verify_nonce($_POST['sisu_nonce'], 'sisu_admin_action')) {
        $nome = sanitize_text_field($_POST['nome']);
        $email = sanitize_email($_POST['email']);
        $curso_id = intval($_POST['curso_id']);
        $campus_id = intval($_POST['campus_id']);
        
        if (!empty($nome) && !empty($email) && $curso_id > 0 && $campus_id > 0) {
            $result = $database->insert_secretaria(array(
                'nome' => $nome,
                'email' => $email,
                'curso_id' => $curso_id,
                'campus_id' => $campus_id
            ));
            
            if ($result) {
                $message = __('Secretaria adicionada com sucesso!', 'sisu-docs-manager');
                $message_type = 'success';
            } else {
                $message = __('Erro ao adicionar secretaria.', 'sisu-docs-manager');
                $message_type = 'error';
            }
        } else {
            $message = __('Todos os campos são obrigatórios.', 'sisu-docs-manager');
            $message_type = 'error';
        }
    }
    
    if ($_POST['action'] === 'delete_secretaria' && wp_verify_nonce($_POST['sisu_nonce'], 'sisu_admin_action')) {
        $secretaria_id = intval($_POST['secretaria_id']);
        
        global $wpdb;
        $result = $wpdb->delete($wpdb->prefix . 'sisu_secretarias', array('id' => $secretaria_id), array('%d'));
        
        if ($result) {
            $message = __('Secretaria removida com sucesso!', 'sisu-docs-manager');
            $message_type = 'success';
        } else {
            $message = __('Erro ao remover secretaria.', 'sisu-docs-manager');
            $message_type = 'error';
        }
    }
    
    if ($_POST['action'] === 'edit_secretaria' && wp_verify_nonce($_POST['sisu_nonce'], 'sisu_admin_action')) {
        $secretaria_id = intval($_POST['secretaria_id']);
        $nome = sanitize_text_field($_POST['nome']);
        $email = sanitize_email($_POST['email']);
        $curso_id = intval($_POST['curso_id']);
        $campus_id = intval($_POST['campus_id']);
        
        if (!empty($nome) && !empty($email) && $curso_id > 0 && $campus_id > 0) {
            global $wpdb;
            $result = $wpdb->update(
                $wpdb->prefix . 'sisu_secretarias',
                array(
                    'nome' => $nome,
                    'email' => $email,
                    'curso_id' => $curso_id,
                    'campus_id' => $campus_id
                ),
                array('id' => $secretaria_id),
                array('%s', '%s', '%d', '%d'),
                array('%d')
            );
            
            if ($result !== false) {
                $message = __('Secretaria atualizada com sucesso!', 'sisu-docs-manager');
                $message_type = 'success';
            } else {
                $message = __('Erro ao atualizar secretaria.', 'sisu-docs-manager');
                $message_type = 'error';
            }
        } else {
            $message = __('Todos os campos são obrigatórios.', 'sisu-docs-manager');
            $message_type = 'error';
        }
    }
}

// Obter listas
$campus_list = $database->get_campus();
$cursos_list = $database->get_cursos();
$secretarias_list = $database->get_secretarias();

// Filtros
$campus_filter = isset($_GET['campus_filter']) ? intval($_GET['campus_filter']) : 0;
if ($campus_filter > 0) {
    $secretarias_list = array_filter($secretarias_list, function($secretaria) use ($campus_filter) {
        return $secretaria->campus_id == $campus_filter;
    });
}
?>

<div class="wrap sisu-admin-content">
    <h1><?php _e('Gestão de Secretarias', 'sisu-docs-manager'); ?></h1>
    
    <?php if (!empty($message)): ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <?php if (empty($campus_list) || empty($cursos_list)): ?>
        <div class="notice notice-warning">
            <p><?php _e('Você precisa cadastrar pelo menos um campus e um curso antes de adicionar secretarias.', 'sisu-docs-manager'); ?></p>
            <p>
                <a href="<?php echo admin_url('admin.php?page=sisu-campus'); ?>" class="button"><?php _e('Gerenciar Campus', 'sisu-docs-manager'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=sisu-cursos'); ?>" class="button"><?php _e('Gerenciar Cursos', 'sisu-docs-manager'); ?></a>
            </p>
        </div>
    <?php else: ?>

    <div class="sisu-admin-grid">
        <!-- Formulário de Adição -->
        <div class="sisu-admin-card">
            <h2><?php _e('Adicionar Nova Secretaria', 'sisu-docs-manager'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('sisu_admin_action', 'sisu_nonce'); ?>
                <input type="hidden" name="action" value="add_secretaria">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="nome"><?php _e('Nome da Secretaria', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="nome" name="nome" class="regular-text" required 
                                   placeholder="<?php _e('Ex: Secretaria de Engenharia', 'sisu-docs-manager'); ?>">
                            <p class="description"><?php _e('Digite o nome da secretaria.', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="email"><?php _e('Email', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="email" name="email" class="regular-text" required 
                                   placeholder="<?php _e('secretaria@instituicao.edu.br', 'sisu-docs-manager'); ?>">
                            <p class="description"><?php _e('Email para receber notificações de documentos.', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="campus_id"><?php _e('Campus', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <select id="campus_id" name="campus_id" class="regular-text" required onchange="updateCursos()">
                                <option value=""><?php _e('Selecione um campus', 'sisu-docs-manager'); ?></option>
                                <?php foreach ($campus_list as $campus): ?>
                                    <option value="<?php echo $campus->id; ?>"><?php echo esc_html($campus->nome); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="curso_id"><?php _e('Curso', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <select id="curso_id" name="curso_id" class="regular-text" required>
                                <option value=""><?php _e('Primeiro selecione um campus', 'sisu-docs-manager'); ?></option>
                            </select>
                            <p class="description"><?php _e('Curso que esta secretaria irá gerenciar.', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Adicionar Secretaria', 'sisu-docs-manager'); ?>">
                </p>
            </form>
        </div>

        <!-- Lista de Secretarias -->
        <div class="sisu-admin-card">
            <div class="sisu-card-header">
                <h2><?php _e('Secretarias Cadastradas', 'sisu-docs-manager'); ?></h2>
                
                <!-- Filtros -->
                <div class="sisu-filters">
                    <form method="get" action="">
                        <input type="hidden" name="page" value="sisu-secretarias">
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
            
            <?php if (empty($secretarias_list)): ?>
                <div class="sisu-empty-state">
                    <p><?php _e('Nenhuma secretaria cadastrada ainda.', 'sisu-docs-manager'); ?></p>
                    <p><?php _e('Adicione a primeira secretaria usando o formulário ao lado.', 'sisu-docs-manager'); ?></p>
                </div>
            <?php else: ?>
                <div class="sisu-table-container">
                    <table class="wp-list-table widefat fixed striped sisu-table">
                        <thead>
                            <tr>
                                <th scope="col" class="manage-column column-name"><?php _e('Nome', 'sisu-docs-manager'); ?></th>
                                <th scope="col" class="manage-column column-email"><?php _e('Email', 'sisu-docs-manager'); ?></th>
                                <th scope="col" class="manage-column column-curso"><?php _e('Curso', 'sisu-docs-manager'); ?></th>
                                <th scope="col" class="manage-column column-campus"><?php _e('Campus', 'sisu-docs-manager'); ?></th>
                                <th scope="col" class="manage-column column-created"><?php _e('Criado em', 'sisu-docs-manager'); ?></th>
                                <th scope="col" class="manage-column column-actions"><?php _e('Ações', 'sisu-docs-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($secretarias_list as $secretaria): ?>
                                <tr>
                                    <td class="column-name">
                                        <strong><?php echo esc_html($secretaria->nome); ?></strong>
                                    </td>
                                    <td class="column-email">
                                        <a href="mailto:<?php echo esc_attr($secretaria->email); ?>"><?php echo esc_html($secretaria->email); ?></a>
                                    </td>
                                    <td class="column-curso">
                                        <?php echo esc_html($secretaria->curso_nome); ?>
                                    </td>
                                    <td class="column-campus">
                                        <?php echo esc_html($secretaria->campus_nome); ?>
                                    </td>
                                    <td class="column-created">
                                        <?php echo date_i18n('d/m/Y H:i', strtotime($secretaria->created_at)); ?>
                                    </td>
                                    <td class="column-actions">
                                        <button type="button" class="button button-small" 
                                                onclick="editSecretaria(<?php echo $secretaria->id; ?>, '<?php echo esc_js($secretaria->nome); ?>', '<?php echo esc_js($secretaria->email); ?>', <?php echo $secretaria->curso_id; ?>, <?php echo $secretaria->campus_id; ?>)">
                                            <?php _e('Editar', 'sisu-docs-manager'); ?>
                                        </button>
                                        
                                        <form method="post" style="display: inline;" 
                                              onsubmit="return confirm('<?php _e('Tem certeza que deseja remover esta secretaria?', 'sisu-docs-manager'); ?>')">
                                            <?php wp_nonce_field('sisu_admin_action', 'sisu_nonce'); ?>
                                            <input type="hidden" name="action" value="delete_secretaria">
                                            <input type="hidden" name="secretaria_id" value="<?php echo $secretaria->id; ?>">
                                            <input type="submit" class="button button-small button-link-delete" 
                                                   value="<?php _e('Remover', 'sisu-docs-manager'); ?>">
                                        </form>
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
<div id="edit-secretaria-modal" class="sisu-modal" style="display: none;">
    <div class="sisu-modal-content">
        <div class="sisu-modal-header">
            <h3><?php _e('Editar Secretaria', 'sisu-docs-manager'); ?></h3>
            <button type="button" class="sisu-modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('sisu_admin_action', 'sisu_nonce'); ?>
            <input type="hidden" name="action" value="edit_secretaria">
            <input type="hidden" name="secretaria_id" id="edit_secretaria_id">
            
            <div class="sisu-modal-body">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="edit_nome"><?php _e('Nome da Secretaria', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="edit_nome" name="nome" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="edit_email"><?php _e('Email', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="edit_email" name="email" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="edit_campus_id"><?php _e('Campus', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <select id="edit_campus_id" name="campus_id" class="regular-text" required onchange="updateEditCursos()">
                                <option value=""><?php _e('Selecione um campus', 'sisu-docs-manager'); ?></option>
                                <?php foreach ($campus_list as $campus): ?>
                                    <option value="<?php echo $campus->id; ?>"><?php echo esc_html($campus->nome); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="edit_curso_id"><?php _e('Curso', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <select id="edit_curso_id" name="curso_id" class="regular-text" required>
                                <option value=""><?php _e('Selecione um curso', 'sisu-docs-manager'); ?></option>
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
// Dados dos cursos para JavaScript
var cursosData = <?php echo json_encode($cursos_list); ?>;

function updateCursos() {
    var campusId = document.getElementById('campus_id').value;
    var cursoSelect = document.getElementById('curso_id');
    
    // Limpar opções
    cursoSelect.innerHTML = '<option value=""><?php _e('Selecione um curso', 'sisu-docs-manager'); ?></option>';
    
    if (campusId) {
        // Filtrar cursos por campus
        var cursosFiltrados = cursosData.filter(function(curso) {
            return curso.campus_id == campusId;
        });
        
        // Adicionar opções
        cursosFiltrados.forEach(function(curso) {
            var option = document.createElement('option');
            option.value = curso.id;
            option.textContent = curso.nome;
            cursoSelect.appendChild(option);
        });
    }
}

function updateEditCursos() {
    var campusId = document.getElementById('edit_campus_id').value;
    var cursoSelect = document.getElementById('edit_curso_id');
    var currentCursoId = cursoSelect.getAttribute('data-current');
    
    // Limpar opções
    cursoSelect.innerHTML = '<option value=""><?php _e('Selecione um curso', 'sisu-docs-manager'); ?></option>';
    
    if (campusId) {
        // Filtrar cursos por campus
        var cursosFiltrados = cursosData.filter(function(curso) {
            return curso.campus_id == campusId;
        });
        
        // Adicionar opções
        cursosFiltrados.forEach(function(curso) {
            var option = document.createElement('option');
            option.value = curso.id;
            option.textContent = curso.nome;
            if (curso.id == currentCursoId) {
                option.selected = true;
            }
            cursoSelect.appendChild(option);
        });
    }
}

function editSecretaria(id, nome, email, cursoId, campusId) {
    document.getElementById('edit_secretaria_id').value = id;
    document.getElementById('edit_nome').value = nome;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_campus_id').value = campusId;
    document.getElementById('edit_curso_id').setAttribute('data-current', cursoId);
    
    updateEditCursos();
    
    document.getElementById('edit-secretaria-modal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('edit-secretaria-modal').style.display = 'none';
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    var modal = document.getElementById('edit-secretaria-modal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>
