<?php

/**
 * Gerencia as capabilities (permissões) do plugin SiSU Docs Manager.
 */
class Sisu_Capability_Manager {

    /**
     * Remove capabilities antigas e adiciona as novas.
     */
    public static function refresh_capabilities() {
        // Remover capabilities antigas
        self::remove_old_capabilities();
        
        // Adicionar capabilities corretas
        self::add_correct_capabilities();
    }

    /**
     * Remove capabilities antigas que podem estar causando conflito.
     */
    private static function remove_old_capabilities() {
        $admin_role = get_role('administrator');
        if ($admin_role) {
            // Remover capabilities antigas
            $old_caps = array(
                'sisu_manage_all',
                'sisu_manage_documents', 
                'sisu_view_candidates',
                'sisu_manage_secretaries',
                'sisu_manage_courses',
                'sisu_manage_campus',
                'sisu_manage_settings'
            );
            
            foreach ($old_caps as $cap) {
                $admin_role->remove_cap($cap);
            }
        }
    }

    /**
     * Adiciona as capabilities corretas.
     */
    private static function add_correct_capabilities() {
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('manage_sisu_system');
            $admin_role->add_cap('manage_sisu_candidates');
            $admin_role->add_cap('validate_sisu_documents');
            $admin_role->add_cap('view_sisu_reports');
        }
    }

    /**
     * Verifica se o usuário atual tem acesso ao sistema SiSU.
     */
    public static function current_user_can_access_sisu() {
        return current_user_can('manage_sisu_system') || 
               current_user_can('manage_sisu_candidates') || 
               current_user_can('validate_sisu_documents');
    }

    /**
     * Remove todas as roles e capabilities do SiSU (usado na desativação).
     */
    public static function cleanup_capabilities() {
        // Remover roles personalizados
        remove_role('sisu_secretario');
        remove_role('sisu_coordenador');
        remove_role('sisu_candidato');

        // Remover capabilities do administrador
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $caps_to_remove = array(
                'manage_sisu_system',
                'manage_sisu_candidates',
                'validate_sisu_documents',
                'view_sisu_reports',
                // Capabilities antigas também
                'sisu_manage_all',
                'sisu_manage_documents', 
                'sisu_view_candidates',
                'sisu_manage_secretaries',
                'sisu_manage_courses',
                'sisu_manage_campus',
                'sisu_manage_settings'
            );
            
            foreach ($caps_to_remove as $cap) {
                $admin_role->remove_cap($cap);
            }
        }
    }
}
