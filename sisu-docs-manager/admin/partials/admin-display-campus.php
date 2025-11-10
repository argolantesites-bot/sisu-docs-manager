<?php
/**
 * Template para gestão de campus.
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
    if ($_POST['action'] === 'add_campus' && wp_verify_nonce($_POST['sisu_nonce'], 'sisu_admin_action')) {
        $nome = sanitize_text_field($_POST['nome']);
        
        if (!empty($nome)) {
            $result = $database->insert_campus(array('nome' => $nome));
            if ($result) {
                $message = __('Campus adicionado com sucesso!', 'sisu-docs-manager');
                $message_type = 'success';
            } else {
                $message = __('Erro ao adicionar campus. Verifique se o nome já não existe.', 'sisu-docs-manager');
                $message_type = 'error';
            }
        } else {
            $message = __('Nome do campus é obrigatório.', 'sisu-docs-manager');
            $message_type = 'error';
        }
    }
    
    if ($_POST['action'] === 'delete_campus' && wp_verify_nonce($_POST['sisu_nonce'], 'sisu_admin_action')) {
        $campus_id = intval($_POST['campus_id']);
        
        global $wpdb;
        $result = $wpdb->delete($wpdb->prefix . 'sisu_campus', array('id' => $campus_id), array('%d'));
        
        if ($result) {
            $message = __('Campus removido com sucesso!', 'sisu-docs-manager');
            $message_type = 'success';
        } else {
            $message = __('Erro ao remover campus.', 'sisu-docs-manager');
            $message_type = 'error';
        }
    }
    
    if ($_POST['action'] === 'edit_campus' && wp_verify_nonce($_POST['sisu_nonce'], 'sisu_admin_action')) {
        $campus_id = intval($_POST['campus_id']);
        $nome = sanitize_text_field($_POST['nome']);
        
        if (!empty($nome)) {
            global $wpdb;
            $result = $wpdb->update(
                $wpdb->prefix . 'sisu_campus',
                array('nome' => $nome),
                array('id' => $campus_id),
                array('%s'),
                array('%d')
            );
            
            if ($result !== false) {
                $message = __('Campus atualizado com sucesso!', 'sisu-docs-manager');
                $message_type = 'success';
            } else {
                $message = __('Erro ao atualizar campus.', 'sisu-docs-manager');
                $message_type = 'error';
            }
        } else {
            $message = __('Nome do campus é obrigatório.', 'sisu-docs-manager');
            $message_type = 'error';
        }
    }
}

// Obter lista de campus
$campus_list = $database->get_campus();
?>

<div class="wrap sisu-admin-content">
    <h1><?php _e('Gestão de Campus', 'sisu-docs-manager'); ?></h1>
    
    <?php if (!empty($message)): ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <div class="sisu-admin-grid">
        <!-- Formulário de Adição -->
        <div class="sisu-admin-card">
            <h2><?php _e('Adicionar Novo Campus', 'sisu-docs-manager'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('sisu_admin_action', 'sisu_nonce'); ?>
                <input type="hidden" name="action" value="add_campus">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="nome"><?php _e('Nome do Campus', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="nome" name="nome" class="regular-text" required 
                                   placeholder="<?php _e('Ex: Campus Central', 'sisu-docs-manager'); ?>">
                            <p class="description"><?php _e('Digite o nome completo do campus.', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Adicionar Campus', 'sisu-docs-manager'); ?>">
                </p>
            </form>
        </div>

        <!-- Lista de Campus -->
        <div class="sisu-admin-card">
            <h2><?php _e('Campus Cadastrados', 'sisu-docs-manager'); ?></h2>
            
            <?php if (empty($campus_list)): ?>
                <div class="sisu-empty-state">
                    <p><?php _e('Nenhum campus cadastrado ainda.', 'sisu-docs-manager'); ?></p>
                    <p><?php _e('Adicione o primeiro campus usando o formulário ao lado.', 'sisu-docs-manager'); ?></p>
                </div>
            <?php else: ?>
                <div class="sisu-table-container">
                    <table class="wp-list-table widefat fixed striped sisu-table">
                        <thead>
                            <tr>
                                <th scope="col" class="manage-column column-name"><?php _e('Nome', 'sisu-docs-manager'); ?></th>
                                <th scope="col" class="manage-column column-cursos"><?php _e('Cursos', 'sisu-docs-manager'); ?></th>
                                <th scope="col" class="manage-column column-created"><?php _e('Criado em', 'sisu-docs-manager'); ?></th>
                                <th scope="col" class="manage-column column-actions"><?php _e('Ações', 'sisu-docs-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($campus_list as $campus): 
                                // Contar cursos do campus
                                global $wpdb;
                                $cursos_count = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM {$wpdb->prefix}sisu_cursos WHERE campus_id = %d",
                                    $campus->id
                                ));
                            ?>
                                <tr>
                                    <td class="column-name">
                                        <strong><?php echo esc_html($campus->nome); ?></strong>
                                    </td>
                                    <td class="column-cursos">
                                        <span class="count-badge"><?php echo intval($cursos_count); ?></span>
                                        <?php echo _n('curso', 'cursos', $cursos_count, 'sisu-docs-manager'); ?>
                                    </td>
                                    <td class="column-created">
                                        <?php echo date_i18n('d/m/Y H:i', strtotime($campus->created_at)); ?>
                                    </td>
                                    <td class="column-actions">
                                        <button type="button" class="button button-small" 
                                                onclick="editCampus(<?php echo $campus->id; ?>, '<?php echo esc_js($campus->nome); ?>')">
                                            <?php _e('Editar', 'sisu-docs-manager'); ?>
                                        </button>
                                        
                                        <?php if ($cursos_count == 0): ?>
                                            <form method="post" style="display: inline;" 
                                                  onsubmit="return confirm('<?php _e('Tem certeza que deseja remover este campus?', 'sisu-docs-manager'); ?>')">
                                                <?php wp_nonce_field('sisu_admin_action', 'sisu_nonce'); ?>
                                                <input type="hidden" name="action" value="delete_campus">
                                                <input type="hidden" name="campus_id" value="<?php echo $campus->id; ?>">
                                                <input type="submit" class="button button-small button-link-delete" 
                                                       value="<?php _e('Remover', 'sisu-docs-manager'); ?>">
                                            </form>
                                        <?php else: ?>
                                            <span class="description"><?php _e('Não é possível remover (possui cursos)', 'sisu-docs-manager'); ?></span>
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
</div>

<!-- Modal de Edição -->
<div id="edit-campus-modal" class="sisu-modal" style="display: none;">
    <div class="sisu-modal-content">
        <div class="sisu-modal-header">
            <h3><?php _e('Editar Campus', 'sisu-docs-manager'); ?></h3>
            <button type="button" class="sisu-modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('sisu_admin_action', 'sisu_nonce'); ?>
            <input type="hidden" name="action" value="edit_campus">
            <input type="hidden" name="campus_id" id="edit_campus_id">
            
            <div class="sisu-modal-body">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="edit_nome"><?php _e('Nome do Campus', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="edit_nome" name="nome" class="regular-text" required>
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
function editCampus(id, nome) {
    document.getElementById('edit_campus_id').value = id;
    document.getElementById('edit_nome').value = nome;
    document.getElementById('edit-campus-modal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('edit-campus-modal').style.display = 'none';
}

// Fechar modal ao clicar fora
window.onclick = function(event) {
    var modal = document.getElementById('edit-campus-modal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

<style>
.sisu-admin-grid {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 20px;
    margin-top: 20px;
}

.sisu-admin-card {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
}

.sisu-admin-card h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.sisu-empty-state {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.sisu-table-container {
    overflow-x: auto;
}

.count-badge {
    background: #0073aa;
    color: white;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: bold;
    margin-right: 5px;
}

.sisu-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.sisu-modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    border: 1px solid #888;
    border-radius: 4px;
    width: 80%;
    max-width: 600px;
}

.sisu-modal-header {
    padding: 20px;
    border-bottom: 1px solid #eee;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sisu-modal-header h3 {
    margin: 0;
}

.sisu-modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.sisu-modal-close:hover {
    color: #000;
}

.sisu-modal-body {
    padding: 20px;
}

.sisu-modal-footer {
    padding: 20px;
    border-top: 1px solid #eee;
    text-align: right;
}

.sisu-modal-footer .button {
    margin-left: 10px;
}

@media (max-width: 768px) {
    .sisu-admin-grid {
        grid-template-columns: 1fr;
    }
    
    .sisu-modal-content {
        width: 95%;
        margin: 10% auto;
    }
}
</style>
