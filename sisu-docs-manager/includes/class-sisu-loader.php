<?php

/**
 * Registra todas as ações e filtros para o plugin.
 *
 * Mantém uma lista de todas as ações e filtros que são registrados
 * ao longo do plugin, e registra-os com o WordPress quando
 * apropriado.
 */
class Sisu_Loader {

    /**
     * O array de ações registradas com WordPress.
     */
    protected $actions;

    /**
     * O array de filtros registrados com WordPress.
     */
    protected $filters;

    /**
     * Inicializa as coleções usadas para manter as ações e filtros.
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Adiciona uma nova ação à coleção a ser registrada com WordPress.
     *
     * @param string $hook          O nome da ação do WordPress que está sendo registrada.
     * @param object $component     Uma referência à instância do objeto no qual a ação está definida.
     * @param string $callback      O nome da definição de função no $component.
     * @param int    $priority      Opcional. A prioridade na qual a função deve ser executada. Padrão é 10.
     * @param int    $accepted_args Opcional. O número de argumentos que devem ser passados para o $callback. Padrão é 1.
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Adiciona um novo filtro à coleção a ser registrada com WordPress.
     *
     * @param string $hook          O nome do filtro do WordPress que está sendo registrado.
     * @param object $component     Uma referência à instância do objeto no qual o filtro está definido.
     * @param string $callback      O nome da definição de função no $component.
     * @param int    $priority      Opcional. A prioridade na qual a função deve ser executada. Padrão é 10.
     * @param int    $accepted_args Opcional. O número de argumentos que devem ser passados para o $callback. Padrão é 1.
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Um método utilitário que é usado para registrar as ações e filtros em um único
     * array.
     *
     * @param array  $hooks         A coleção de hooks que está sendo registrada (ou seja, ações ou filtros).
     * @param string $hook          O nome do hook do WordPress que está sendo registrado.
     * @param object $component     Uma referência à instância do objeto no qual o hook está definido.
     * @param string $callback      O nome da definição de função no $component.
     * @param int    $priority      A prioridade na qual a função deve ser executada.
     * @param int    $accepted_args O número de argumentos que devem ser passados para o $callback.
     * @return array                A coleção de ações e filtros registrados com WordPress.
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );

        return $hooks;
    }

    /**
     * Registra os filtros e ações com WordPress.
     */
    public function run() {
        foreach ($this->filters as $hook) {
            add_filter($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }

        foreach ($this->actions as $hook) {
            add_action($hook['hook'], array($hook['component'], $hook['callback']), $hook['priority'], $hook['accepted_args']);
        }
    }
}
