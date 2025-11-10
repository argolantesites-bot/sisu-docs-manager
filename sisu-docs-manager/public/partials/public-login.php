<?php
/**
 * Template de login para candidatos.
 */

// Verificar se já está logado via cookie
if (isset($_COOKIE['sisu_candidate_id']) && !empty($_COOKIE['sisu_candidate_id'])) {
    $redirect_url = isset($atts['redirect']) && !empty($atts['redirect']) ? $atts['redirect'] : home_url('/dashboard-candidato/');
    echo '<script>window.location.href = "' . esc_url($redirect_url) . '";</script>';
    return;
}

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

$pode_acessar = $sistema_ativo && $no_periodo;
?>

<div class="sisu-public-container">
    <div class="sisu-login-form">
        <h2><?php _e('Acesso do Candidato', 'sisu-docs-manager'); ?></h2>
        
        <?php if (!$pode_acessar): ?>
            <div class="sisu-alert sisu-alert-warning">
                <p><?php echo esc_html($options['mensagem_sistema_inativo'] ?? __('O sistema de recebimento de documentos não está ativo no momento.', 'sisu-docs-manager')); ?></p>
                
                <?php if (!empty($options['data_inicio']) && !empty($options['data_fim'])): ?>
                    <p><strong><?php _e('Período de envio:', 'sisu-docs-manager'); ?></strong><br>
                    <?php echo date_i18n('d/m/Y H:i', strtotime($options['data_inicio'])); ?> até 
                    <?php echo date_i18n('d/m/Y H:i', strtotime($options['data_fim'])); ?></p>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="sisu-alert sisu-alert-info">
                <p><?php echo esc_html($options['mensagem_boas_vindas'] ?? __('Bem-vindo ao sistema de envio de documentos do SiSU.', 'sisu-docs-manager')); ?></p>
            </div>
        <?php endif; ?>

        <form id="sisu-login-form" method="post">
            <div class="sisu-form-group">
                <label for="email_or_cpf"><?php _e('E-mail ou CPF:', 'sisu-docs-manager'); ?></label>
                <input type="text" id="email_or_cpf" name="email_or_cpf" required 
                       placeholder="<?php _e('Digite seu e-mail ou CPF', 'sisu-docs-manager'); ?>"
                       <?php echo !$pode_acessar ? 'disabled' : ''; ?>>
            </div>

            <div class="sisu-form-group">
                <label for="inscricao"><?php _e('Número de Inscrição:', 'sisu-docs-manager'); ?></label>
                <input type="text" id="inscricao" name="inscricao" required 
                       placeholder="<?php _e('Digite seu número de inscrição', 'sisu-docs-manager'); ?>"
                       <?php echo !$pode_acessar ? 'disabled' : ''; ?>>
            </div>

            <div class="sisu-form-group">
                <button type="submit" class="sisu-btn sisu-btn-full" <?php echo !$pode_acessar ? 'disabled' : ''; ?>>
                    <span class="login-text"><?php _e('Acessar Sistema', 'sisu-docs-manager'); ?></span>
                    <span class="loading-text sisu-hidden">
                        <span class="sisu-loading"></span>
                        <?php _e('Verificando...', 'sisu-docs-manager'); ?>
                    </span>
                </button>
            </div>
        </form>

        <div id="login-message" class="sisu-hidden"></div>

        <?php if ($pode_acessar): ?>
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #666; font-size: 14px;">
            <p><?php _e('Problemas para acessar?', 'sisu-docs-manager'); ?></p>
            <p><?php _e('Verifique se seus dados estão corretos ou entre em contato com a secretaria do seu curso.', 'sisu-docs-manager'); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#sisu-login-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var loginText = submitBtn.find('.login-text');
        var loadingText = submitBtn.find('.loading-text');
        var messageDiv = $('#login-message');
        
        // Mostrar loading
        submitBtn.prop('disabled', true);
        loginText.addClass('sisu-hidden');
        loadingText.removeClass('sisu-hidden');
        messageDiv.addClass('sisu-hidden');
        
        // Dados do formulário
        var formData = {
            action: 'sisu_candidate_login',
            email_or_cpf: $('#email_or_cpf').val(),
            inscricao: $('#inscricao').val(),
            nonce: '<?php echo wp_create_nonce('sisu_public_nonce'); ?>'
        };
        
        // Enviar requisição AJAX
        $.post(sisu_public_ajax.ajax_url, formData, function(response) {
            if (response.success) {
                messageDiv.removeClass('sisu-hidden')
                          .removeClass('sisu-alert-error')
                          .addClass('sisu-alert sisu-alert-success')
                          .html('<p>' + response.data.message + '</p>');
                
                // Redirecionar após 2 segundos
                setTimeout(function() {
                    window.location.href = response.data.redirect;
                }, 2000);
            } else {
                messageDiv.removeClass('sisu-hidden')
                          .removeClass('sisu-alert-success')
                          .addClass('sisu-alert sisu-alert-error')
                          .html('<p>' + response.data.message + '</p>');
                
                // Restaurar botão
                submitBtn.prop('disabled', false);
                loginText.removeClass('sisu-hidden');
                loadingText.addClass('sisu-hidden');
            }
        }).fail(function() {
            messageDiv.removeClass('sisu-hidden')
                      .removeClass('sisu-alert-success')
                      .addClass('sisu-alert sisu-alert-error')
                      .html('<p><?php _e('Erro de conexão. Tente novamente.', 'sisu-docs-manager'); ?></p>');
            
            // Restaurar botão
            submitBtn.prop('disabled', false);
            loginText.removeClass('sisu-hidden');
            loadingText.addClass('sisu-hidden');
        });
    });
    
    // Formatação automática do CPF
    $('#email_or_cpf').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        if (value.length === 11) {
            // Formatar como CPF
            value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
            $(this).val(value);
        }
    });
    
    // Permitir apenas números na inscrição
    $('#inscricao').on('input', function() {
        var value = $(this).val().replace(/\D/g, '');
        $(this).val(value);
    });
});
</script>
