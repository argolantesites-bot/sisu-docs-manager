<?php

/**
 * Define a funcionalidade de internacionalização.
 *
 * Carrega e define os arquivos de internacionalização para este plugin
 * para que ele esteja pronto para tradução.
 */
class Sisu_i18n {

    /**
     * Carrega o domínio de texto do plugin para tradução.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'sisu-docs-manager',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}
