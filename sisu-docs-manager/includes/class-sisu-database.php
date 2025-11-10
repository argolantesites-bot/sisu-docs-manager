<?php

/**
 * Classe responsável por gerenciar o banco de dados do plugin.
 *
 * Esta classe define e cria as tabelas necessárias para o funcionamento
 * do plugin SiSU Docs Manager.
 */
class Sisu_Database {

    /**
     * Cria todas as tabelas necessárias para o plugin.
     */
    public function create_tables() {
        $this->create_campus_table();
        $this->create_cursos_table();
        $this->create_secretarias_table();
        $this->create_candidatos_table();
        $this->create_documentos_table();
        $this->create_configuracoes_table();
    }

    /**
     * Cria a tabela de campus.
     */
    private function create_campus_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sisu_campus';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nome varchar(255) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY nome (nome)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Cria a tabela de cursos.
     */
    private function create_cursos_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sisu_cursos';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nome varchar(255) NOT NULL,
            campus_id mediumint(9) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY campus_id (campus_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Cria a tabela de secretarias.
     */
    private function create_secretarias_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sisu_secretarias';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            nome varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            curso_id mediumint(9) NOT NULL,
            campus_id mediumint(9) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY curso_id (curso_id),
            KEY campus_id (campus_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Cria a tabela de candidatos.
     */
    private function create_candidatos_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sisu_candidatos';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            inscricao varchar(50) NOT NULL,
            nome varchar(255) NOT NULL,
            email varchar(255) NOT NULL,
            cpf varchar(14) NOT NULL,
            data_nascimento date NOT NULL,
            sexo varchar(10) NOT NULL,
            telefone1 varchar(20),
            telefone2 varchar(20),
            uf varchar(2) NOT NULL,
            municipio varchar(255) NOT NULL,
            campus_id mediumint(9) NOT NULL,
            turno varchar(50),
            formacao varchar(100),
            modalidade varchar(255),
            data_cadastro datetime DEFAULT CURRENT_TIMESTAMP,
            curso_id mediumint(9) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY inscricao (inscricao),
            UNIQUE KEY cpf (cpf),
            KEY email (email),
            KEY campus_id (campus_id),
            KEY curso_id (curso_id),
            KEY modalidade (modalidade)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Cria a tabela de documentos.
     */
    private function create_documentos_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sisu_documentos';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            candidato_id mediumint(9) NOT NULL,
            tipo_documento varchar(100) NOT NULL,
            nome_documento varchar(255),
            obrigatorio tinyint(1) DEFAULT 0,
            nome_arquivo varchar(255),
            caminho_arquivo varchar(500),
            tamanho_arquivo int(11),
            status enum('Aprovado','Recusado','Aguardando Validação','Não enviado') DEFAULT 'Não enviado',
            data_cadastro datetime DEFAULT CURRENT_TIMESTAMP,
            data_envio datetime,
            data_atualizacao_status datetime,
            observacoes text,
            usuario_validacao bigint(20) unsigned,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY candidato_id (candidato_id),
            KEY tipo_documento (tipo_documento),
            KEY status (status),
            KEY usuario_validacao (usuario_validacao)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Cria a tabela de configurações.
     */
    private function create_configuracoes_table() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sisu_configuracoes';

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            chave varchar(100) NOT NULL,
            valor longtext,
            descricao text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY chave (chave)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Obtém todos os campus.
     *
     * @return array Lista de campus.
     */
    public function get_campus() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_campus';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY nome");
    }

    /**
     * Obtém todos os cursos.
     *
     * @param int $campus_id ID do campus (opcional).
     * @return array Lista de cursos.
     */
    public function get_cursos($campus_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_cursos';
        $campus_table = $wpdb->prefix . 'sisu_campus';
        
        $sql = "SELECT c.*, ca.nome as campus_nome 
                FROM $table_name c 
                LEFT JOIN $campus_table ca ON c.campus_id = ca.id";
        
        if ($campus_id) {
            $sql .= $wpdb->prepare(" WHERE c.campus_id = %d", $campus_id);
        }
        
        $sql .= " ORDER BY ca.nome, c.nome";
        
        return $wpdb->get_results($sql);
    }

    /**
     * Obtém todas as secretarias.
     *
     * @return array Lista de secretarias.
     */
    public function get_secretarias() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_secretarias';
        $cursos_table = $wpdb->prefix . 'sisu_cursos';
        $campus_table = $wpdb->prefix . 'sisu_campus';
        
        $sql = "SELECT s.*, c.nome as curso_nome, ca.nome as campus_nome 
                FROM $table_name s 
                LEFT JOIN $cursos_table c ON s.curso_id = c.id 
                LEFT JOIN $campus_table ca ON s.campus_id = ca.id 
                ORDER BY ca.nome, c.nome, s.nome";
        
        return $wpdb->get_results($sql);
    }

    /**
     * Obtém todos os candidatos.
     *
     * @param array $filters Filtros para a consulta.
     * @return array Lista de candidatos.
     */
    public function get_candidatos($filters = array()) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_candidatos';
        $cursos_table = $wpdb->prefix . 'sisu_cursos';
        $campus_table = $wpdb->prefix . 'sisu_campus';
        
        $sql = "SELECT ca.*, c.nome as curso_nome, cam.nome as campus_nome 
                FROM $table_name ca 
                INNER JOIN $cursos_table c ON ca.curso_id = c.id 
                INNER JOIN $campus_table cam ON ca.campus_id = cam.id";
        
        $where_conditions = array();
        $where_values = array();
        
        if (!empty($filters['campus_id'])) {
            $where_conditions[] = "ca.campus_id = %d";
            $where_values[] = $filters['campus_id'];
        }
        
        if (!empty($filters['curso_id'])) {
            $where_conditions[] = "ca.curso_id = %d";
            $where_values[] = $filters['curso_id'];
        }
        
        if (!empty($filters['search'])) {
            $where_conditions[] = "(ca.nome LIKE %s OR ca.email LIKE %s OR ca.cpf LIKE %s OR ca.inscricao LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($filters['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        if (!empty($where_conditions)) {
            $sql .= " WHERE " . implode(" AND ", $where_conditions);
        }
        
        $sql .= " ORDER BY ca.nome";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return $wpdb->get_results($sql);
    }



    /**
     * Obtém os tipos de documentos necessários.
     *
     * @return array Lista de tipos de documentos.
     */
    public function get_tipos_documentos() {
        return array(
            'historico_fundamental' => 'Histórico Escolar do Ensino Fundamental',
            'historico_medio' => 'Histórico Escolar do Ensino Médio',
            'diploma_certificado' => 'Diploma ou Certificado de Conclusão do Ensino Médio',
            'quitacao_eleitoral' => 'Certidão de Quitação Eleitoral',
            'certidao_nascimento' => 'Certidão de Nascimento ou Casamento',
            'reservista' => 'Carteira de Reservista ou Certificado de Alistamento Militar',
            'cpf' => 'Cadastro de Pessoa Física – CPF',
            'documento_identificacao' => 'Documento Oficial de Identificação',
            'cie' => 'Carteira de Identidade de Estrangeiro - CIE',
            'declaracao_perfil' => 'Declaração de Perfil Social e Autenticidade dos Documentos Enviados',
            'declaracao_etnia' => 'Declaração de Etnia e Vínculo com Comunidade Indígena'
        );
    }

    /**
     * Insere um novo campus.
     *
     * @param array $data Dados do campus.
     * @return int|false ID do campus inserido ou false em caso de erro.
     */
    public function insert_campus($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_campus';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'nome' => sanitize_text_field($data['nome'])
            ),
            array('%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Insere um novo curso.
     *
     * @param array $data Dados do curso.
     * @return int|false ID do curso inserido ou false em caso de erro.
     */
    public function insert_curso($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_cursos';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'nome' => sanitize_text_field($data['nome']),
                'campus_id' => intval($data['campus_id'])
            ),
            array('%s', '%d')
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Insere uma nova secretaria.
     *
     * @param array $data Dados da secretaria.
     * @return int|false ID da secretaria inserida ou false em caso de erro.
     */
    public function insert_secretaria($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_secretarias';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'nome' => sanitize_text_field($data['nome']),
                'email' => sanitize_email($data['email']),
                'curso_id' => intval($data['curso_id']),
                'campus_id' => intval($data['campus_id'])
            ),
            array('%s', '%s', '%d', '%d')
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Insere um novo candidato.
     *
     * @param array $data Dados do candidato.
     * @return int|false ID do candidato inserido ou false em caso de erro.
     */
    public function insert_candidato($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_candidatos';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'inscricao' => sanitize_text_field($data['inscricao']),
                'nome' => sanitize_text_field($data['nome']),
                'email' => sanitize_email($data['email']),
                'cpf' => sanitize_text_field($data['cpf']),
                'data_nascimento' => sanitize_text_field($data['data_nascimento']),
                'sexo' => sanitize_text_field($data['sexo']),
                'telefone1' => sanitize_text_field($data['telefone1']),
                'telefone2' => sanitize_text_field($data['telefone2']),
                'uf' => sanitize_text_field($data['uf']),
                'municipio' => sanitize_text_field($data['municipio']),
                'campus_id' => intval($data['campus_id']),
                'habilitacao' => sanitize_text_field($data['habilitacao']),
                'turno' => sanitize_text_field($data['turno']),
                'categoria' => sanitize_text_field($data['categoria']),
                'curso_id' => intval($data['curso_id'])
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%d')
        );
        
        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Atualiza o status de um documento.
     *
     * @param int $documento_id ID do documento.
     * @param string $status Novo status.
     * @param string $observacoes Observações (opcional).
     * @param int $usuario_id ID do usuário que fez a validação.
     * @return bool True se atualizado com sucesso, false caso contrário.
     */
    public function update_documento_status($documento_id, $status, $observacoes = '', $usuario_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_documentos';
        
        $data = array(
            'status' => $status,
            'data_atualizacao_status' => current_time('mysql'),
            'observacoes' => $observacoes
        );
        
        $format = array('%s', '%s', '%s');
        
        if ($usuario_id) {
            $data['usuario_validacao'] = $usuario_id;
            $format[] = '%d';
        }
        
        $result = $wpdb->update(
            $table_name,
            $data,
            array('id' => $documento_id),
            $format,
            array('%d')
        );
        
        // Disparar hook para notificação por email
        if ($result !== false) {
            do_action('sisu_document_status_changed', $documento_id, $status, $observacoes);
        }
        
        return $result !== false;
    }

    /**
     * Obtém um candidato por ID.
     *
     * @param int $candidate_id ID do candidato.
     * @return object|null Dados do candidato ou null se não encontrado.
     */
    public function get_candidato_by_id($candidate_id) {
        global $wpdb;
        $candidatos_table = $wpdb->prefix . 'sisu_candidatos';
        $cursos_table = $wpdb->prefix . 'sisu_cursos';
        $campus_table = $wpdb->prefix . 'sisu_campus';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT c.*, cur.nome as curso_nome, cam.nome as campus_nome
             FROM $candidatos_table c
             LEFT JOIN $cursos_table cur ON c.curso_id = cur.id
             LEFT JOIN $campus_table cam ON c.campus_id = cam.id
             WHERE c.id = %d", $candidate_id
        ));
    }

    /**
     * Obtém um documento por ID.
     *
     * @param int $documento_id ID do documento.
     * @return object|null Dados do documento ou null se não encontrado.
     */
    public function get_documento_by_id($documento_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_documentos';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d", $documento_id
        ));
    }

    /**
     * Obtém estatísticas gerais do sistema.
     *
     * @return array Estatísticas.
     */
    public function get_system_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total de candidatos
        $stats['total_candidatos'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sisu_candidatos");
        
        // Total de documentos por status
        $stats['documentos_aprovados'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sisu_documentos WHERE status = 'Aprovado'");
        $stats['documentos_recusados'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sisu_documentos WHERE status = 'Recusado'");
        $stats['documentos_pendentes'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sisu_documentos WHERE status = 'Aguardando Validação'");
        $stats['documentos_nao_enviados'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sisu_documentos WHERE status = 'Não enviado'");
        
        // Total de estruturas
        $stats['total_campus'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sisu_campus");
        $stats['total_cursos'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sisu_cursos");
        $stats['total_secretarias'] = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sisu_secretarias");
        
        return $stats;
    }

    /**
     * Obtém candidatos com documentos pendentes.
     *
     * @param int $days_before_deadline Dias antes do prazo.
     * @return array Lista de candidatos.
     */
    public function get_candidates_with_pending_documents($days_before_deadline = null) {
        global $wpdb;
        $candidatos_table = $wpdb->prefix . 'sisu_candidatos';
        $documentos_table = $wpdb->prefix . 'sisu_documentos';
        $cursos_table = $wpdb->prefix . 'sisu_cursos';
        $campus_table = $wpdb->prefix . 'sisu_campus';

        $sql = "SELECT DISTINCT c.*, cur.nome as curso_nome, cam.nome as campus_nome,
                       COUNT(CASE WHEN d.status IN ('Não enviado', 'Recusado') THEN 1 END) as documentos_pendentes
                FROM $candidatos_table c
                LEFT JOIN $cursos_table cur ON c.curso_id = cur.id
                LEFT JOIN $campus_table cam ON c.campus_id = cam.id
                LEFT JOIN $documentos_table d ON c.id = d.candidato_id
                GROUP BY c.id
                HAVING documentos_pendentes > 0
                ORDER BY c.nome";

        return $wpdb->get_results($sql);
    }



    /**
     * Cria documentos padrão "Não enviado" para um candidato.
     *
     * @param int $candidato_id ID do candidato.
     * @return bool True se criado com sucesso.
     */
 public function create_default_documents($candidato_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_documentos';
        $tipos_documentos = $this->get_tipos_documentos();

        foreach ($tipos_documentos as $tipo => $nome) {
            // Verificar se já existe um documento deste tipo para o candidato
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE candidato_id = %d AND tipo_documento = %s",
                $candidato_id, $tipo
            ));

            if (!$exists) {
                $wpdb->insert(
                    $table_name,
                    array(
                        'candidato_id' => $candidato_id,
                        'tipo_documento' => $tipo,
                        'status' => 'Não enviado',
                        'created_at' => current_time('mysql')
                    ),
                    array('%d', '%s', '%s', '%s')
                );
            }
        }
        return true;
    }

    /**
     * Obtém documentos com filtros, incluindo informações do candidato, curso e campus.
     *
     * @param array $filters Filtros para a consulta (candidato_id, curso_id, status, tipo_documento).
     * @return array Lista de documentos.
     */
    public function get_documentos_with_filters($filters = array()) {
        global $wpdb;
        $documentos_table = $wpdb->prefix . 'sisu_documentos';
        $candidatos_table = $wpdb->prefix . 'sisu_candidatos';
        $cursos_table = $wpdb->prefix . 'sisu_cursos';
        $campus_table = $wpdb->prefix . 'sisu_campus';

        $sql = "SELECT d.*, c.nome as candidato_nome, c.inscricao, c.email,
                       cur.nome as curso_nome, cam.nome as campus_nome
                FROM $documentos_table d
                INNER JOIN $candidatos_table c ON d.candidato_id = c.id
                INNER JOIN $cursos_table cur ON c.curso_id = cur.id
                INNER JOIN $campus_table cam ON c.campus_id = cam.id
                WHERE 1=1";

        $where_values = array();

        if (!empty($filters['candidato_id'])) {
            $sql .= " AND d.candidato_id = %d";
            $where_values[] = $filters['candidato_id'];
        }
        
        if (!empty($filters['curso_id'])) {
            $sql .= " AND c.curso_id = %d";
            $where_values[] = $filters['curso_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND d.status = %s";
            $where_values[] = $filters['status'];
        }

        if (!empty($filters['tipo_documento'])) {
            $sql .= " AND d.tipo_documento = %s";
            $where_values[] = $filters['tipo_documento'];
        }

        $sql .= " ORDER BY c.nome, d.tipo_documento";

        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }

        return $wpdb->get_results($sql);
    }

    /**
     * Obtém documentos de um candidato (incluindo os não enviados).
     *
     * @param int $candidato_id ID do candidato.
     * @return array Lista de documentos.
     */
    public function get_documentos_candidato($candidato_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_documentos';
        
        // Primeiro, garantir que o candidato tem todos os documentos padrão
        $this->create_default_documents($candidato_id);
        
        // Buscar todos os documentos do candidato
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE candidato_id = %d ORDER BY tipo_documento",
            $candidato_id
        ));
    }

    /**
     * Salva um documento enviado pelo candidato.
     *
     * @param int $candidato_id ID do candidato.
     * @param string $tipo_documento Tipo do documento.
     * @param array $file_data Dados do arquivo.
     * @return bool True se salvo com sucesso.
     */
    public function save_documento($candidato_id, $tipo_documento, $file_data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_documentos';
        
        // Verificar se já existe documento deste tipo
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE candidato_id = %d AND tipo_documento = %s",
            $candidato_id, $tipo_documento
        ));
        
        $data = array(
            'nome_arquivo' => $file_data['nome_arquivo'],
            'caminho_arquivo' => $file_data['caminho_arquivo'],
            'tamanho_arquivo' => $file_data['tamanho_arquivo'],
            'status' => $file_data['status'],
            'data_envio' => current_time('mysql')
        );
        
        if ($existing) {
            // Atualizar documento existente
            $result = $wpdb->update(
                $table_name,
                $data,
                array('id' => $existing->id),
                array('%s', '%s', '%d', '%s', '%s'),
                array('%d')
            );
        } else {
            // Criar novo documento
            $data['candidato_id'] = $candidato_id;
            $data['tipo_documento'] = $tipo_documento;
            $data['data_criacao'] = current_time('mysql');
            
            $result = $wpdb->insert(
                $table_name,
                $data,
                array('%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
            );
        }
        
        return $result !== false;
    }

    /**
     * Remove um documento de candidato (volta para "Não enviado").
     *
     * @param int $candidato_id ID do candidato.
     * @param string $tipo_documento Tipo do documento.
     * @return bool True se removido com sucesso.
     */
    public function remove_documento($candidato_id, $tipo_documento) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_documentos';
        
        // Buscar documento
        $documento = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE candidato_id = %d AND tipo_documento = %s",
            $candidato_id, $tipo_documento
        ));
        
        if (!$documento) {
            return false;
        }
        
        // Remover arquivo físico se existir
        if ($documento->caminho_arquivo) {
            $upload_dir = wp_upload_dir();
            $file_path = $upload_dir['basedir'] . '/sisu-docs/' . $documento->caminho_arquivo;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        // Atualizar para "Não enviado" em vez de deletar
        $result = $wpdb->update(
            $table_name,
            array(
                'nome_arquivo' => null,
                'caminho_arquivo' => null,
                'tamanho_arquivo' => null,
                'status' => 'Não enviado',
                'data_envio' => null,
                'observacoes' => null
            ),
            array('id' => $documento->id),
            array('%s', '%s', '%s', '%s', '%s', '%s'),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Obtém o nome de um curso pelo ID.
     *
     * @param int $curso_id ID do curso.
     * @return string|null Nome do curso ou null se não encontrado.
     */
    public function get_curso_name_by_id($curso_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_cursos';
        return $wpdb->get_var($wpdb->prepare("SELECT nome FROM $table_name WHERE id = %d", $curso_id));
    }

    /**
     * Obtém o nome de um campus pelo ID.
     *
     * @param int $campus_id ID do campus.
     * @return string|null Nome do campus ou null se não encontrado.
     */
    public function get_campus_name_by_id($campus_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_campus';
        return $wpdb->get_var($wpdb->prepare("SELECT nome FROM $table_name WHERE id = %d", $campus_id));
    }

    /**
     * Obtém um documento específico de um candidato.
     *
     * @param int $candidato_id ID do candidato.
     * @param string $tipo_documento Tipo do documento.
     * @return object|null Documento ou null se não encontrado.
     */
    public function get_documento_candidato($candidato_id, $tipo_documento) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_documentos';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE candidato_id = %d AND tipo_documento = %s",
            $candidato_id, $tipo_documento
        ));
    }

    /**
     * Obtém uma secretaria pelo email do usuário.
     *
     * @param string $user_email Email do usuário.
     * @return object|null Dados da secretaria ou null se não encontrada.
     */
    public function get_secretaria_by_user_email($user_email) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_secretarias';
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE email = %s",
            $user_email
        ));
    }

    /**
     * Limpa documentos órfãos (arquivos sem registro no banco).
     */
    public function cleanup_orphaned_files() {
        $upload_dir = wp_upload_dir();
        $sisu_dir = $upload_dir['basedir'] . '/sisu-docs/';
        
        if (!is_dir($sisu_dir)) {
            return;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_documentos';
        
        // Obter todos os arquivos registrados no banco
        $registered_files = $wpdb->get_col("SELECT caminho_arquivo FROM $table_name WHERE caminho_arquivo IS NOT NULL");
        
        // Obter todos os arquivos físicos
        $physical_files = array_diff(scandir($sisu_dir), array('.', '..', '.htaccess'));
        
        // Remover arquivos órfãos
        foreach ($physical_files as $file) {
            if (!in_array($file, $registered_files)) {
                unlink($sisu_dir . $file);
            }
        }
    }
}
