<?php

/**
 * Disparado durante a desativação do plugin.
 *
 * Esta classe define todo o código necessário para executar durante a desativação do plugin.
 */
class Sisu_Deactivator {

    /**
     * Método executado na desativação do plugin.
     *
     * Remove roles personalizados e limpa configurações temporárias.
     */
    public static function deactivate() {
        // Incluir o gerenciador de capabilities
        require_once SISU_DOCS_MANAGER_PATH . 'includes/class-sisu-capability-manager.php';
        
        // Limpar capabilities e roles
        Sisu_Capability_Manager::cleanup_capabilities();
        
        // Limpar tarefas agendadas (se houver)
        self::clear_scheduled_tasks();
    }



    /**
     * Limpa tarefas agendadas do plugin.
     */
    private static function clear_scheduled_tasks() {
        // Remover tarefas agendadas (cron jobs) se houver
        wp_clear_scheduled_hook('sisu_daily_cleanup');
        wp_clear_scheduled_hook('sisu_email_reminders');
    }
}
