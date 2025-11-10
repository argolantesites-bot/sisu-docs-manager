<?php
/**
 * Template para gestão de usuários do sistema SiSU.
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
    if ($_POST['action'] === 'add_user' && wp_verify_nonce($_POST['sisu_nonce'], 'sisu_admin_action')) {
        $nome = sanitize_text_field($_POST['nome']);
        $email = sanitize_email($_POST['email']);
        $senha = $_POST['senha'];
        $tipo = sanitize_text_field($_POST['tipo']);
        $secretaria_id = !empty($_POST['secretaria_id']) ? intval($_POST['secretaria_id']) : null;
        
        if (!empty($nome) && !empty($email) && !empty($senha) && !empty($tipo)) {
            // Criar usuário WordPress
            $user_id = wp_create_user($email, $senha, $email);
            
            if (!is_wp_error($user_id)) {
                // Atualizar dados do usuário
                wp_update_user(array(
                    'ID' => $user_id,
                    'display_name' => $nome,
                    'first_name' => $nome,
                    'role' => 'subscriber' // Role padrão, será alterado pelo meta
                ));
                
                // Adicionar meta dados específicos do SiSU
                update_user_meta($user_id, 'sisu_user_type', $tipo);
                if ($secretaria_id) {
                    update_user_meta($user_id, 'sisu_secretaria_id', $secretaria_id);
                }
                
                // Definir capabilities baseado no tipo
                $user = new WP_User($user_id);
                switch ($tipo) {
                    case 'administrador':
                        $user->add_cap('manage_sisu_system');
                        $user->add_cap('manage_sisu_candidates');
                        $user->add_cap('validate_sisu_documents');
                        $user->add_cap('view_sisu_reports');
                        break;
                    case 'coordenador':
                        $user->add_cap('manage_sisu_candidates');
                        $user->add_cap('validate_sisu_documents');
                        $user->add_cap('view_sisu_reports');
                        break;
                    case 'secretario':
                        $user->add_cap('validate_sisu_documents');
                        break;
                }
                
                $message = __('Usuário criado com sucesso!', 'sisu-docs-manager');
                $message_type = 'success';
            } else {
                $message = $user_id->get_error_message();
                $message_type = 'error';
            }
        } else {
            $message = __('Todos os campos obrigatórios devem ser preenchidos.', 'sisu-docs-manager');
            $message_type = 'error';
        }
    }
    
    if ($_POST['action'] === 'update_user_type' && wp_verify_nonce($_POST['sisu_nonce'], 'sisu_admin_action')) {
        $user_id = intval($_POST['user_id']);
        $tipo = sanitize_text_field($_POST['tipo']);
        $secretaria_id = !empty($_POST['secretaria_id']) ? intval($_POST['secretaria_id']) : null;
        
        if ($user_id > 0 && !empty($tipo)) {
            // Atualizar meta dados
            update_user_meta($user_id, 'sisu_user_type', $tipo);
            if ($secretaria_id) {
                update_user_meta($user_id, 'sisu_secretaria_id', $secretaria_id);
            } else {
                delete_user_meta($user_id, 'sisu_secretaria_id');
            }
            
            // Remover capabilities antigas
            $user = new WP_User($user_id);
            $user->remove_cap('manage_sisu_system');
            $user->remove_cap('manage_sisu_candidates');
            $user->remove_cap('validate_sisu_documents');
            $user->remove_cap('view_sisu_reports');
            
            // Adicionar novas capabilities
            switch ($tipo) {
                case 'administrador':
                    $user->add_cap('manage_sisu_system');
                    $user->add_cap('manage_sisu_candidates');
                    $user->add_cap('validate_sisu_documents');
                    $user->add_cap('view_sisu_reports');
                    break;
                case 'coordenador':
                    $user->add_cap('manage_sisu_candidates');
                    $user->add_cap('validate_sisu_documents');
                    $user->add_cap('view_sisu_reports');
                    break;
                case 'secretario':
                    $user->add_cap('validate_sisu_documents');
                    break;
            }
            
            $message = __('Usuário atualizado com sucesso!', 'sisu-docs-manager');
            $message_type = 'success';
        } else {
            $message = __('Dados inválidos.', 'sisu-docs-manager');
            $message_type = 'error';
        }
    }
    
    if ($_POST['action'] === 'remove_sisu_access' && wp_verify_nonce($_POST['sisu_nonce'], 'sisu_admin_action')) {
        $user_id = intval($_POST['user_id']);
        
        if ($user_id > 0) {
            // Remover meta dados do SiSU
            delete_user_meta($user_id, 'sisu_user_type');
            delete_user_meta($user_id, 'sisu_secretaria_id');
            
            // Remover capabilities
            $user = new WP_User($user_id);
            $user->remove_cap('manage_sisu_system');
            $user->remove_cap('manage_sisu_candidates');
            $user->remove_cap('validate_sisu_documents');
            $user->remove_cap('view_sisu_reports');
            
            $message = __('Acesso ao SiSU removido com sucesso!', 'sisu-docs-manager');
            $message_type = 'success';
        }
    }
}

// Obter usuários do SiSU
$sisu_users = get_users(array(
    'meta_key' => 'sisu_user_type',
    'meta_compare' => 'EXISTS'
));

// Obter usuários WordPress sem acesso ao SiSU
$all_users = get_users(array(
    'exclude' => array_map(function($user) { return $user->ID; }, $sisu_users)
));

// Obter secretarias para o formulário
$secretarias_list = $database->get_secretarias();
?>

<div class="wrap sisu-admin-content">
    <h1><?php _e('Gestão de Usuários', 'sisu-docs-manager'); ?></h1>
    
    <?php if (!empty($message)): ?>
        <div class="notice notice-<?php echo $message_type; ?> is-dismissible">
            <p><?php echo esc_html($message); ?></p>
        </div>
    <?php endif; ?>

    <div class="sisu-admin-grid">
        <!-- Formulário de Adição -->
        <div class="sisu-admin-card">
            <h2><?php _e('Adicionar Novo Usuário', 'sisu-docs-manager'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('sisu_admin_action', 'sisu_nonce'); ?>
                <input type="hidden" name="action" value="add_user">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="nome"><?php _e('Nome Completo', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="nome" name="nome" class="regular-text" required 
                                   placeholder="<?php _e('Ex: João Silva Santos', 'sisu-docs-manager'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="email"><?php _e('Email', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <input type="email" id="email" name="email" class="regular-text" required 
                                   placeholder="<?php _e('usuario@instituicao.edu.br', 'sisu-docs-manager'); ?>">
                            <p class="description"><?php _e('Este email será usado como login.', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="senha"><?php _e('Senha', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <input type="password" id="senha" name="senha" class="regular-text" required 
                                   minlength="6" placeholder="<?php _e('Mínimo 6 caracteres', 'sisu-docs-manager'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="tipo"><?php _e('Tipo de Usuário', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <select id="tipo" name="tipo" class="regular-text" required onchange="toggleSecretariaField()">
                                <option value=""><?php _e('Selecione o tipo', 'sisu-docs-manager'); ?></option>
                                <option value="administrador"><?php _e('Administrador', 'sisu-docs-manager'); ?></option>
                                <option value="coordenador"><?php _e('Coordenador', 'sisu-docs-manager'); ?></option>
                                <option value="secretario"><?php _e('Secretário', 'sisu-docs-manager'); ?></option>
                            </select>
                            <div class="sisu-user-types-info">
                                <p><strong><?php _e('Administrador:', 'sisu-docs-manager'); ?></strong> <?php _e('Acesso total ao sistema', 'sisu-docs-manager'); ?></p>
                                <p><strong><?php _e('Coordenador:', 'sisu-docs-manager'); ?></strong> <?php _e('Gerencia candidatos e valida documentos', 'sisu-docs-manager'); ?></p>
                                <p><strong><?php _e('Secretário:', 'sisu-docs-manager'); ?></strong> <?php _e('Valida documentos de sua secretaria', 'sisu-docs-manager'); ?></p>
                            </div>
                        </td>
                    </tr>
                    <tr id="secretaria_row" style="display: none;">
                        <th scope="row">
                            <label for="secretaria_id"><?php _e('Secretaria', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <select id="secretaria_id" name="secretaria_id" class="regular-text">
                                <option value=""><?php _e('Selecione uma secretaria', 'sisu-docs-manager'); ?></option>
                                <?php foreach ($secretarias_list as $secretaria): ?>
                                    <option value="<?php echo $secretaria->id; ?>">
                                        <?php echo esc_html($secretaria->nome . ' - ' . $secretaria->curso_nome . ' (' . $secretaria->campus_nome . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e('Obrigatório apenas para secretários.', 'sisu-docs-manager'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" class="button-primary" value="<?php _e('Criar Usuário', 'sisu-docs-manager'); ?>">
                </p>
            </form>
        </div>

        <!-- Lista de Usuários SiSU -->
        <div class="sisu-admin-card">
            <h2><?php _e('Usuários com Acesso ao SiSU', 'sisu-docs-manager'); ?></h2>
            
            <?php if (empty($sisu_users)): ?>
                <div class="sisu-empty-state">
                    <p><?php _e('Nenhum usuário com acesso ao SiSU ainda.', 'sisu-docs-manager'); ?></p>
                    <p><?php _e('Crie o primeiro usuário usando o formulário ao lado.', 'sisu-docs-manager'); ?></p>
                </div>
            <?php else: ?>
                <div class="sisu-table-container">
                    <table class="wp-list-table widefat fixed striped sisu-table">
                        <thead>
                            <tr>
                                <th scope="col" class="manage-column column-name"><?php _e('Nome', 'sisu-docs-manager'); ?></th>
                                <th scope="col" class="manage-column column-email"><?php _e('Email', 'sisu-docs-manager'); ?></th>
                                <th scope="col" class="manage-column column-type"><?php _e('Tipo', 'sisu-docs-manager'); ?></th>
                                <th scope="col" class="manage-column column-secretaria"><?php _e('Secretaria', 'sisu-docs-manager'); ?></th>
                                <th scope="col" class="manage-column column-actions"><?php _e('Ações', 'sisu-docs-manager'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sisu_users as $user): 
                                $user_type = get_user_meta($user->ID, 'sisu_user_type', true);
                                $secretaria_id = get_user_meta($user->ID, 'sisu_secretaria_id', true);
                                $secretaria_nome = '';
                                
                                if ($secretaria_id) {
                                    foreach ($secretarias_list as $sec) {
                                        if ($sec->id == $secretaria_id) {
                                            $secretaria_nome = $sec->nome;
                                            break;
                                        }
                                    }
                                }
                                
                                $type_labels = array(
                                    'administrador' => __('Administrador', 'sisu-docs-manager'),
                                    'coordenador' => __('Coordenador', 'sisu-docs-manager'),
                                    'secretario' => __('Secretário', 'sisu-docs-manager')
                                );
                            ?>
                                <tr>
                                    <td class="column-name">
                                        <strong><?php echo esc_html($user->display_name); ?></strong>
                                    </td>
                                    <td class="column-email">
                                        <a href="mailto:<?php echo esc_attr($user->user_email); ?>"><?php echo esc_html($user->user_email); ?></a>
                                    </td>
                                    <td class="column-type">
                                        <span class="sisu-user-type sisu-type-<?php echo $user_type; ?>">
                                            <?php echo isset($type_labels[$user_type]) ? $type_labels[$user_type] : $user_type; ?>
                                        </span>
                                    </td>
                                    <td class="column-secretaria">
                                        <?php echo $secretaria_nome ? esc_html($secretaria_nome) : '-'; ?>
                                    </td>
                                    <td class="column-actions">
                                        <button type="button" class="button button-small" 
                                                onclick="editUser(<?php echo $user->ID; ?>, '<?php echo esc_js($user_type); ?>', '<?php echo $secretaria_id; ?>')">
                                            <?php _e('Editar', 'sisu-docs-manager'); ?>
                                        </button>
                                        
                                        <?php if ($user->ID != get_current_user_id()): ?>
                                            <form method="post" style="display: inline;" 
                                                  onsubmit="return confirm('<?php _e('Tem certeza que deseja remover o acesso ao SiSU deste usuário?', 'sisu-docs-manager'); ?>')">
                                                <?php wp_nonce_field('sisu_admin_action', 'sisu_nonce'); ?>
                                                <input type="hidden" name="action" value="remove_sisu_access">
                                                <input type="hidden" name="user_id" value="<?php echo $user->ID; ?>">
                                                <input type="submit" class="button button-small button-link-delete" 
                                                       value="<?php _e('Remover Acesso', 'sisu-docs-manager'); ?>">
                                            </form>
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

    <!-- Usuários WordPress sem acesso -->
    <?php if (!empty($all_users)): ?>
    <div class="sisu-admin-card" style="margin-top: 20px;">
        <h2><?php _e('Usuários WordPress sem Acesso ao SiSU', 'sisu-docs-manager'); ?></h2>
        <p class="description"><?php _e('Estes usuários já existem no WordPress mas não têm acesso ao sistema SiSU.', 'sisu-docs-manager'); ?></p>
        
        <div class="sisu-table-container">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col"><?php _e('Nome', 'sisu-docs-manager'); ?></th>
                        <th scope="col"><?php _e('Email', 'sisu-docs-manager'); ?></th>
                        <th scope="col"><?php _e('Role WordPress', 'sisu-docs-manager'); ?></th>
                        <th scope="col"><?php _e('Ações', 'sisu-docs-manager'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_users as $user): ?>
                        <tr>
                            <td><?php echo esc_html($user->display_name); ?></td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td><?php echo esc_html(implode(', ', $user->roles)); ?></td>
                            <td>
                                <button type="button" class="button button-small" 
                                        onclick="grantSisuAccess(<?php echo $user->ID; ?>, '<?php echo esc_js($user->display_name); ?>')">
                                    <?php _e('Dar Acesso ao SiSU', 'sisu-docs-manager'); ?>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal de Edição -->
<div id="edit-user-modal" class="sisu-modal" style="display: none;">
    <div class="sisu-modal-content">
        <div class="sisu-modal-header">
            <h3><?php _e('Editar Usuário', 'sisu-docs-manager'); ?></h3>
            <button type="button" class="sisu-modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('sisu_admin_action', 'sisu_nonce'); ?>
            <input type="hidden" name="action" value="update_user_type">
            <input type="hidden" name="user_id" id="edit_user_id">
            
            <div class="sisu-modal-body">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="edit_tipo"><?php _e('Tipo de Usuário', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <select id="edit_tipo" name="tipo" class="regular-text" required onchange="toggleEditSecretariaField()">
                                <option value="administrador"><?php _e('Administrador', 'sisu-docs-manager'); ?></option>
                                <option value="coordenador"><?php _e('Coordenador', 'sisu-docs-manager'); ?></option>
                                <option value="secretario"><?php _e('Secretário', 'sisu-docs-manager'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr id="edit_secretaria_row">
                        <th scope="row">
                            <label for="edit_secretaria_id"><?php _e('Secretaria', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <select id="edit_secretaria_id" name="secretaria_id" class="regular-text">
                                <option value=""><?php _e('Selecione uma secretaria', 'sisu-docs-manager'); ?></option>
                                <?php foreach ($secretarias_list as $secretaria): ?>
                                    <option value="<?php echo $secretaria->id; ?>">
                                        <?php echo esc_html($secretaria->nome . ' - ' . $secretaria->curso_nome . ' (' . $secretaria->campus_nome . ')'); ?>
                                    </option>
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

<!-- Modal de Concessão de Acesso -->
<div id="grant-access-modal" class="sisu-modal" style="display: none;">
    <div class="sisu-modal-content">
        <div class="sisu-modal-header">
            <h3><?php _e('Conceder Acesso ao SiSU', 'sisu-docs-manager'); ?></h3>
            <button type="button" class="sisu-modal-close" onclick="closeGrantModal()">&times;</button>
        </div>
        
        <form method="post" action="">
            <?php wp_nonce_field('sisu_admin_action', 'sisu_nonce'); ?>
            <input type="hidden" name="action" value="update_user_type">
            <input type="hidden" name="user_id" id="grant_user_id">
            
            <div class="sisu-modal-body">
                <p><?php _e('Usuário:', 'sisu-docs-manager'); ?> <strong id="grant_user_name"></strong></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="grant_tipo"><?php _e('Tipo de Usuário', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <select id="grant_tipo" name="tipo" class="regular-text" required onchange="toggleGrantSecretariaField()">
                                <option value=""><?php _e('Selecione o tipo', 'sisu-docs-manager'); ?></option>
                                <option value="administrador"><?php _e('Administrador', 'sisu-docs-manager'); ?></option>
                                <option value="coordenador"><?php _e('Coordenador', 'sisu-docs-manager'); ?></option>
                                <option value="secretario"><?php _e('Secretário', 'sisu-docs-manager'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr id="grant_secretaria_row" style="display: none;">
                        <th scope="row">
                            <label for="grant_secretaria_id"><?php _e('Secretaria', 'sisu-docs-manager'); ?></label>
                        </th>
                        <td>
                            <select id="grant_secretaria_id" name="secretaria_id" class="regular-text">
                                <option value=""><?php _e('Selecione uma secretaria', 'sisu-docs-manager'); ?></option>
                                <?php foreach ($secretarias_list as $secretaria): ?>
                                    <option value="<?php echo $secretaria->id; ?>">
                                        <?php echo esc_html($secretaria->nome . ' - ' . $secretaria->curso_nome . ' (' . $secretaria->campus_nome . ')'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="sisu-modal-footer">
                <button type="button" class="button" onclick="closeGrantModal()"><?php _e('Cancelar', 'sisu-docs-manager'); ?></button>
                <input type="submit" class="button-primary" value="<?php _e('Conceder Acesso', 'sisu-docs-manager'); ?>">
            </div>
        </form>
    </div>
</div>

<script>
function toggleSecretariaField() {
    var tipo = document.getElementById('tipo').value;
    var secretariaRow = document.getElementById('secretaria_row');
    
    if (tipo === 'secretario') {
        secretariaRow.style.display = 'table-row';
        document.getElementById('secretaria_id').required = true;
    } else {
        secretariaRow.style.display = 'none';
        document.getElementById('secretaria_id').required = false;
    }
}

function toggleEditSecretariaField() {
    var tipo = document.getElementById('edit_tipo').value;
    var secretariaRow = document.getElementById('edit_secretaria_row');
    
    if (tipo === 'secretario') {
        secretariaRow.style.display = 'table-row';
    } else {
        secretariaRow.style.display = 'none';
    }
}

function toggleGrantSecretariaField() {
    var tipo = document.getElementById('grant_tipo').value;
    var secretariaRow = document.getElementById('grant_secretaria_row');
    
    if (tipo === 'secretario') {
        secretariaRow.style.display = 'table-row';
        document.getElementById('grant_secretaria_id').required = true;
    } else {
        secretariaRow.style.display = 'none';
        document.getElementById('grant_secretaria_id').required = false;
    }
}

function editUser(userId, tipo, secretariaId) {
    document.getElementById('edit_user_id').value = userId;
    document.getElementById('edit_tipo').value = tipo;
    document.getElementById('edit_secretaria_id').value = secretariaId || '';
    
    toggleEditSecretariaField();
    
    document.getElementById('edit-user-modal').style.display = 'block';
}

function grantSisuAccess(userId, userName) {
    document.getElementById('grant_user_id').value = userId;
    document.getElementById('grant_user_name').textContent = userName;
    document.getElementById('grant_tipo').value = '';
    document.getElementById('grant_secretaria_id').value = '';
    
    toggleGrantSecretariaField();
    
    document.getElementById('grant-access-modal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('edit-user-modal').style.display = 'none';
}

function closeGrantModal() {
    document.getElementById('grant-access-modal').style.display = 'none';
}

// Fechar modais ao clicar fora
window.onclick = function(event) {
    var editModal = document.getElementById('edit-user-modal');
    var grantModal = document.getElementById('grant-access-modal');
    
    if (event.target == editModal) {
        editModal.style.display = 'none';
    }
    if (event.target == grantModal) {
        grantModal.style.display = 'none';
    }
}
</script>

<style>
.sisu-user-types-info {
    margin-top: 10px;
    padding: 10px;
    background: #f9f9f9;
    border-left: 4px solid #0073aa;
    font-size: 13px;
}

.sisu-user-types-info p {
    margin: 5px 0;
}

.sisu-user-type {
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 12px;
    font-weight: bold;
    color: white;
}

.sisu-type-administrador {
    background-color: #dc3232;
}

.sisu-type-coordenador {
    background-color: #ffb900;
}

.sisu-type-secretario {
    background-color: #46b450;
}
</style>
