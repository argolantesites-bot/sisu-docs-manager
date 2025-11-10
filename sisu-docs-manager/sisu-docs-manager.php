<?php
/**
 * Plugin Name: SiSU Docs Manager
 * Plugin URI: https://github.com/manus-ai/sisu-docs-manager
 * Description: Plugin para recebimento e gestão de documentação de candidatos do SiSU.
 * Version: 21.0.0
 * Author: Manus AI
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: sisu-docs-manager
 * Domain Path: /languages
 */

// Se este arquivo for chamado diretamente, aborte.
if (!defined('WPINC')) {
    die;
}

/**
 * Versão atual do plugin.
 */
define('SISU_DOCS_MANAGER_VERSION', '20.0.0');

/**
 * Caminho do plugin.
 */
define('SISU_DOCS_MANAGER_PATH', plugin_dir_path(__FILE__));

/**
 * URL do plugin.
 */
define('SISU_DOCS_MANAGER_URL', plugin_dir_url(__FILE__));

/**
 * O código que roda durante a ativação do plugin.
 */
function activate_sisu_docs_manager() {
    require_once SISU_DOCS_MANAGER_PATH . 'includes/class-sisu-activator.php';
    Sisu_Activator::activate();
}

/**
 * O código que roda durante a desativação do plugin.
 */
function deactivate_sisu_docs_manager() {
    require_once SISU_DOCS_MANAGER_PATH . 'includes/class-sisu-deactivator.php';
    Sisu_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_sisu_docs_manager');
register_deactivation_hook(__FILE__, 'deactivate_sisu_docs_manager');

/**
 * A classe principal do plugin que é usada para definir internacionalização,
 * hooks específicos do admin e hooks específicos do site público.
 */
require SISU_DOCS_MANAGER_PATH . 'includes/class-sisu-docs-manager.php';

/**
 * Inicia a execução do plugin.
 */
function run_sisu_docs_manager() {
    $plugin = new Sisu_Docs_Manager();
    $plugin->run();
}
run_sisu_docs_manager();
