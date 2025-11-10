<?php

/**
 * Classe responsável por importar dados de candidatos a partir de arquivos CSV - Versão 3.
 *
 * Esta versão usa a nova estrutura de campos conforme especificado:
 * NO_CAMPUS,NO_CURSO,DS_TURNO,DS_FORMACAO,CO_INSCRICAO_ENEM,NO_INSCRITO,NU_CPF_INSCRITO,
 * DT_NASCIMENTO,TP_SEXO,SG_UF_INSCRITO,NO_MUNICIPIO,NU_FONE1,NU_FONE2,DS_EMAIL,MODALIDADE_ESCOLHIDA
 */
class Sisu_CSV_Importer {

    /**
     * Instância da classe de banco de dados.
     */
    private $database;

    /**
     * Mapeamento de campos do CSV para o banco de dados.
     * Estrutura: 'nome_campo_csv' => 'nome_campo_bd'
     */
    private $field_mapping = array(
        'NO_CAMPUS' => 'campus',
        'NO_CURSO' => 'curso',
        'DS_TURNO' => 'turno',
        'DS_FORMACAO' => 'formacao',
        'CO_INSCRICAO_ENEM' => 'inscricao',
        'NO_INSCRITO' => 'nome',
        'NU_CPF_INSCRITO' => 'cpf',
        'DT_NASCIMENTO' => 'data_nascimento',
        'TP_SEXO' => 'sexo',
        'SG_UF_INSCRITO' => 'uf',
        'NO_MUNICIPIO' => 'municipio',
        'NU_FONE1' => 'telefone1',
        'NU_FONE2' => 'telefone2',
        'DS_EMAIL' => 'email',
        'MODALIDADE_ESCOLHIDA' => 'modalidade'
    );

    /**
     * Relatório de importação.
     */
    private $import_report = array(
        'total_rows' => 0,
        'successful_imports' => 0,
        'failed_imports' => 0,
        'errors' => array(),
        'warnings' => array()
    );

    /**
     * Construtor da classe.
     */
    public function __construct() {
        require_once plugin_dir_path(__FILE__) . 'class-sisu-database.php';
        $this->database = new Sisu_Database();
    }

    /**
     * Importa candidatos a partir de um arquivo CSV.
     *
     * @param string $file_path Caminho para o arquivo CSV.
     * @param bool $clear_existing Se deve limpar dados existentes antes da importação.
     * @return array Relatório de importação.
     */
    public function import_candidates($file_path, $clear_existing = false) {
        // Resetar relatório
        $this->reset_import_report();

        // Limpar dados existentes se solicitado
        if ($clear_existing) {
            $this->clear_existing_data();
        }

        // Verificar se o arquivo existe
        if (!file_exists($file_path)) {
            $this->add_error('Arquivo CSV não encontrado.');
            return $this->import_report;
        }

        // Abrir arquivo CSV
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            $this->add_error('Não foi possível abrir o arquivo CSV.');
            return $this->import_report;
        }

        // Ler cabeçalho
        $header = fgetcsv($handle, 0, ',');
        if (!$header) {
            $this->add_error('Arquivo CSV vazio ou formato inválido.');
            fclose($handle);
            return $this->import_report;
        }

        // Validar cabeçalho
        $column_mapping = $this->validate_and_map_header($header);
        if ($column_mapping === false) {
            fclose($handle);
            return $this->import_report;
        }

        // Processar linhas do CSV
        $row_number = 1;
        while (($data = fgetcsv($handle, 0, ',')) !== false) {
            $row_number++;
            $this->import_report['total_rows']++;

            try {
                $candidate_data = $this->map_row_data($data, $column_mapping);
                $validated_data = $this->validate_candidate_data($candidate_data, $row_number);
                
                if ($validated_data) {
                    $this->import_candidate($validated_data, $row_number);
                }
            } catch (Exception $e) {
                $this->add_error("Linha $row_number: " . $e->getMessage());
                $this->import_report['failed_imports']++;
            }
        }

        fclose($handle);
        return $this->import_report;
    }

    /**
     * Valida e mapeia o cabeçalho do CSV.
     *
     * @param array $header Cabeçalho do CSV.
     * @return array|false Mapeamento de colunas ou false em caso de erro.
     */
    private function validate_and_map_header($header) {
        $column_mapping = array();
        $missing_required = array();

        // Campos obrigatórios
        $required_fields = array('NO_CAMPUS', 'NO_CURSO', 'CO_INSCRICAO_ENEM', 'NO_INSCRITO', 'NU_CPF_INSCRITO', 'DS_EMAIL');

        // Normalizar e mapear colunas
        foreach ($header as $index => $column_name) {
            $normalized_column = trim($column_name);
            
            // Verificar se o campo existe no mapeamento
            if (isset($this->field_mapping[$normalized_column])) {
                $column_mapping[$index] = $this->field_mapping[$normalized_column];
            }
        }

        // Verificar se todos os campos obrigatórios foram encontrados
        foreach ($required_fields as $required_field) {
            $found = false;
            foreach ($header as $index => $column_name) {
                if (trim($column_name) === $required_field) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $missing_required[] = $required_field;
            }
        }

        if (!empty($missing_required)) {
            $this->add_error("Campos obrigatórios não encontrados no CSV: " . implode(', ', $missing_required));
            return false;
        }

        return $column_mapping;
    }

    /**
     * Mapeia os dados de uma linha do CSV.
     *
     * @param array $data Dados da linha.
     * @param array $column_mapping Mapeamento de colunas.
     * @return array Dados mapeados.
     */
    private function map_row_data($data, $column_mapping) {
        $mapped_data = array();

        foreach ($column_mapping as $csv_index => $db_field) {
            $value = isset($data[$csv_index]) ? trim($data[$csv_index]) : '';
            $mapped_data[$db_field] = $value;
        }

        return $mapped_data;
    }

    /**
     * Valida os dados de um candidato.
     *
     * @param array $data Dados do candidato.
     * @param int $row_number Número da linha.
     * @return array|false Dados validados ou false em caso de erro.
     */
    private function validate_candidate_data($data, $row_number) {
        $errors = array();

        // Validar campos obrigatórios
        $required_fields = array('inscricao', 'nome', 'email', 'cpf', 'campus', 'curso');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                $errors[] = "Campo obrigatório vazio: $field";
            }
        }

        // Validar formato do email
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email inválido: " . $data['email'];
        }

        // Validar formato do CPF
        if (!empty($data['cpf'])) {
            $cpf_clean = preg_replace('/[^0-9]/', '', $data['cpf']);
            if (strlen($cpf_clean) !== 11 || !$this->validate_cpf($cpf_clean)) {
                $errors[] = "CPF inválido: " . $data['cpf'];
            }
            $data['cpf'] = $cpf_clean;
        }

        // Validar data de nascimento
        if (!empty($data['data_nascimento'])) {
            $date = DateTime::createFromFormat('d/m/Y', $data['data_nascimento']);
            if (!$date) {
                $date = DateTime::createFromFormat('Y-m-d', $data['data_nascimento']);
            }
            if (!$date) {
                $errors[] = "Data de nascimento inválida: " . $data['data_nascimento'];
            } else {
                $data['data_nascimento'] = $date->format('Y-m-d');
            }
        }

        // Buscar ou criar campus
        $campus_id = $this->find_or_create_campus($data['campus']);
        if (!$campus_id) {
            $errors[] = "Erro ao processar campus: " . $data['campus'];
        } else {
            $data['campus_id'] = $campus_id;
        }

        // Buscar ou criar curso
        if ($campus_id) {
            $curso_id = $this->find_or_create_curso($data['curso'], $campus_id);
            if (!$curso_id) {
                $errors[] = "Erro ao processar curso: " . $data['curso'];
            } else {
                $data['curso_id'] = $curso_id;
            }
        }

        // Se houver erros, adicionar ao relatório
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->add_error("Linha $row_number: $error");
            }
            return false;
        }

        return $data;
    }

    /**
     * Importa um candidato para o banco de dados.
     *
     * @param array $data Dados validados do candidato.
     * @param int $row_number Número da linha.
     */
    private function import_candidate($data, $row_number) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_candidatos';
        
        // Verificar se o candidato já existe
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name WHERE inscricao = %s OR cpf = %s",
            $data['inscricao'], $data['cpf']
        ));

        if ($existing) {
            $this->add_warning("Linha $row_number: Candidato já existe (Inscrição: {$data['inscricao']})");
            return;
        }

        // Preparar dados para inserção
        $insert_data = array(
            'inscricao' => $data['inscricao'],
            'nome' => $data['nome'],
            'email' => $data['email'],
            'cpf' => $data['cpf'],
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'sexo' => $data['sexo'] ?? null,
            'telefone1' => $data['telefone1'] ?? null,
            'telefone2' => $data['telefone2'] ?? null,
            'uf' => $data['uf'] ?? null,
            'municipio' => $data['municipio'] ?? null,
            'campus_id' => $data['campus_id'],
            'curso_id' => $data['curso_id'],
            'turno' => $data['turno'] ?? null,
            'formacao' => $data['formacao'] ?? null,
            'modalidade' => $data['modalidade'] ?? null,
            'data_cadastro' => current_time('mysql')
        );

        // Inserir candidato
        $result = $wpdb->insert($table_name, $insert_data);
        
        if ($result) {
            $candidate_id = $wpdb->insert_id;
            $this->import_report['successful_imports']++;
            
            // Criar registros de documentos baseados na modalidade
            $this->create_documents_by_modality($candidate_id, $data['modalidade'] ?? 'NC', $data['sexo'] ?? 'M');
        } else {
            $this->add_error("Linha $row_number: Erro ao inserir candidato no banco de dados - " . $wpdb->last_error);
            $this->import_report['failed_imports']++;
        }
    }

    /**
     * Cria documentos baseados na modalidade do candidato.
     *
     * @param int $candidate_id ID do candidato.
     * @param string $modalidade Modalidade do candidato.
     * @param string $sexo Sexo do candidato.
     */
    private function create_documents_by_modality($candidate_id, $modalidade, $sexo) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_documentos';

        // Extrair código da modalidade (primeiras letras antes do hífen)
        $modalidade_code = strtoupper(trim(explode('-', $modalidade)[0]));

        // Documentos comuns a todas as modalidades
        $common_docs = array(
            'rg' => array('nome' => 'RG', 'obrigatorio' => true),
            'cpf' => array('nome' => 'CPF', 'obrigatorio' => true),
            'comprovante_residencia' => array('nome' => 'Comprovante de Residência', 'obrigatorio' => true),
            'foto_3x4' => array('nome' => 'Foto 3x4', 'obrigatorio' => true),
            'historico_escolar' => array('nome' => 'Histórico Escolar', 'obrigatorio' => true),
            'certificado_conclusao' => array('nome' => 'Certificado de Conclusão', 'obrigatorio' => true),
            'certidao_nascimento' => array('nome' => 'Certidão de Nascimento/Casamento', 'obrigatorio' => true),
        );

        // Documentos específicos por modalidade
        $modality_docs = array();
        
        switch ($modalidade_code) {
            case 'NC': // Não Cotista
                $modality_docs = array(
                    'titulo_eleitor' => array('nome' => 'Título de Eleitor', 'obrigatorio' => true),
                );
                if ($sexo === 'M') {
                    $modality_docs['reservista'] = array('nome' => 'Certificado de Reservista', 'obrigatorio' => false);
                }
                break;

            case 'EEP': // Egresso de Escola Pública
                $modality_docs = array(
                    'declaracao_escola_publica' => array('nome' => 'Declaração de Escola Pública', 'obrigatorio' => true),
                    'historico_ensino_medio' => array('nome' => 'Histórico do Ensino Médio', 'obrigatorio' => true),
                );
                if ($sexo === 'M') {
                    $modality_docs['reservista'] = array('nome' => 'Certificado de Reservista', 'obrigatorio' => false);
                }
                break;

            case 'PPI': // Preto, Pardo e Indígena
                $modality_docs = array(
                    'autodeclaracao_etnica' => array('nome' => 'Autodeclaração Étnica', 'obrigatorio' => true),
                );
                if ($sexo === 'M') {
                    $modality_docs['reservista'] = array('nome' => 'Certificado de Reservista', 'obrigatorio' => false);
                } else {
                    $modality_docs['outro_documento'] = array('nome' => 'Outro Documento', 'obrigatorio' => false);
                }
                break;

            case 'PCD': // Pessoa com Deficiência
                $modality_docs = array();
                if ($sexo === 'M') {
                    $modality_docs['reservista'] = array('nome' => 'Certificado de Reservista', 'obrigatorio' => false);
                }
                break;

            default:
                // Modalidade não reconhecida, usar NC como padrão
                $modality_docs = array(
                    'titulo_eleitor' => array('nome' => 'Título de Eleitor', 'obrigatorio' => true),
                );
                if ($sexo === 'M') {
                    $modality_docs['reservista'] = array('nome' => 'Certificado de Reservista', 'obrigatorio' => false);
                }
                break;
        }

        // Mesclar documentos comuns com específicos da modalidade
        $all_docs = array_merge($common_docs, $modality_docs);

        // Inserir documentos no banco
        foreach ($all_docs as $doc_key => $doc_info) {
            $wpdb->insert(
                $table_name,
                array(
                    'candidato_id' => $candidate_id,
                    'tipo_documento' => $doc_key,
                    'nome_documento' => $doc_info['nome'],
                    'obrigatorio' => $doc_info['obrigatorio'] ? 1 : 0,
                    'status' => 'Não enviado',
                    'data_cadastro' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%d', '%s', '%s')
            );
        }
    }

    /**
     * Busca ou cria um campus.
     *
     * @param string $campus_name Nome do campus.
     * @return int|false ID do campus ou false em caso de erro.
     */
    private function find_or_create_campus($campus_name) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_campus';
        
        // Buscar campus existente
        $campus = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name WHERE nome = %s",
            $campus_name
        ));

        if ($campus) {
            return $campus->id;
        }

        // Criar novo campus
        $result = $wpdb->insert(
            $table_name,
            array(
                'nome' => $campus_name,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Busca ou cria um curso.
     *
     * @param string $curso_name Nome do curso.
     * @param int $campus_id ID do campus.
     * @return int|false ID do curso ou false em caso de erro.
     */
    private function find_or_create_curso($curso_name, $campus_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sisu_cursos';
        
        // Buscar curso existente
        $curso = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_name WHERE nome = %s AND campus_id = %d",
            $curso_name, $campus_id
        ));

        if ($curso) {
            return $curso->id;
        }

        // Criar novo curso
        $result = $wpdb->insert(
            $table_name,
            array(
                'nome' => $curso_name,
                'campus_id' => $campus_id,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%s')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Valida um CPF.
     *
     * @param string $cpf CPF para validar (apenas números).
     * @return bool True se válido, false caso contrário.
     */
    private function validate_cpf($cpf) {
        // CPF deve ter 11 dígitos
        if (strlen($cpf) != 11) {
            return false;
        }

        // Verificar se todos os dígitos são iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        // Validar primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += intval($cpf[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        if (intval($cpf[9]) != $digit1) {
            return false;
        }

        // Validar segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += intval($cpf[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        return intval($cpf[10]) == $digit2;
    }

    /**
     * Limpa dados existentes do banco.
     */
    private function clear_existing_data() {
        global $wpdb;
        
        $wpdb->query("DELETE FROM {$wpdb->prefix}sisu_documentos");
        $wpdb->query("DELETE FROM {$wpdb->prefix}sisu_candidatos");
        
        $this->add_warning("Dados existentes foram limpos antes da importação.");
    }

    /**
     * Reseta o relatório de importação.
     */
    private function reset_import_report() {
        $this->import_report = array(
            'total_rows' => 0,
            'successful_imports' => 0,
            'failed_imports' => 0,
            'errors' => array(),
            'warnings' => array()
        );
    }

    /**
     * Adiciona um erro ao relatório.
     *
     * @param string $message Mensagem de erro.
     */
    private function add_error($message) {
        $this->import_report['errors'][] = $message;
    }

    /**
     * Adiciona um aviso ao relatório.
     *
     * @param string $message Mensagem de aviso.
     */
    private function add_warning($message) {
        $this->import_report['warnings'][] = $message;
    }
}
