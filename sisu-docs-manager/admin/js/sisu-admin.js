/**
 * JavaScript para funcionalidades administrativas do SiSU Docs Manager
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Inicializar funcionalidades
        initDataTables();
        initFormValidation();
        initBulkActions();
        initDocumentStatusUpdate();
        initCSVImport();
        initModalHandlers();
    });

    /**
     * Inicializa DataTables para tabelas administrativas
     */
    function initDataTables() {
        if ($.fn.DataTable) {
            $('.sisu-table').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json'
                },
                pageLength: 25,
                responsive: true,
                order: [[0, 'asc']],
                columnDefs: [
                    { orderable: false, targets: -1 } // Última coluna (ações) não ordenável
                ]
            });
        }
    }

    /**
     * Inicializa validação de formulários
     */
    function initFormValidation() {
        // Validação de CPF
        $('input[name*="cpf"]').on('blur', function() {
            var cpf = $(this).val().replace(/\D/g, '');
            if (cpf.length === 11 && !isValidCPF(cpf)) {
                showAlert('error', 'CPF inválido');
                $(this).focus();
            }
        });

        // Validação de email
        $('input[type="email"]').on('blur', function() {
            var email = $(this).val();
            if (email && !isValidEmail(email)) {
                showAlert('error', 'Email inválido');
                $(this).focus();
            }
        });

        // Validação de datas
        $('input[type="datetime-local"]').on('change', function() {
            var startDate = $('input[name*="data_inicio"]').val();
            var endDate = $('input[name*="data_fim"]').val();
            
            if (startDate && endDate && startDate >= endDate) {
                showAlert('error', 'A data de fim deve ser posterior à data de início');
                $(this).focus();
            }
        });
    }

    /**
     * Inicializa ações em lote
     */
    function initBulkActions() {
        $('#bulk-action-selector-top, #bulk-action-selector-bottom').on('change', function() {
            var action = $(this).val();
            var $button = $(this).siblings('.button');
            
            if (action === '') {
                $button.prop('disabled', true);
            } else {
                $button.prop('disabled', false);
            }
        });

        $('.bulk-action-button').on('click', function(e) {
            var action = $(this).siblings('select').val();
            var checkedItems = $('input[name="bulk-select[]"]:checked');
            
            if (action === '' || checkedItems.length === 0) {
                e.preventDefault();
                showAlert('warning', 'Selecione uma ação e pelo menos um item');
                return false;
            }
            
            if (!confirm('Tem certeza que deseja executar esta ação em ' + checkedItems.length + ' item(s)?')) {
                e.preventDefault();
                return false;
            }
        });

        // Selecionar todos
        $('#select-all-top, #select-all-bottom').on('change', function() {
            var isChecked = $(this).prop('checked');
            $('input[name="bulk-select[]"]').prop('checked', isChecked);
        });
    }

    /**
     * Inicializa atualização de status de documentos
     */
    function initDocumentStatusUpdate() {
        $('.document-status-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('input[type="submit"]');
            var originalText = $submitBtn.val();
            
            // Mostrar loading
            $submitBtn.val('Atualizando...').prop('disabled', true);
            
            $.ajax({
                url: $form.attr('action') || window.location.href,
                type: 'POST',
                data: $form.serialize(),
                success: function(response) {
                    showAlert('success', 'Status atualizado com sucesso!');
                    
                    // Atualizar interface se necessário
                    var newStatus = $form.find('select[name="status"]').val();
                    updateDocumentStatusDisplay($form, newStatus);
                },
                error: function() {
                    showAlert('error', 'Erro ao atualizar status. Tente novamente.');
                },
                complete: function() {
                    $submitBtn.val(originalText).prop('disabled', false);
                }
            });
        });
    }

    /**
     * Inicializa funcionalidades de importação CSV
     */
    function initCSVImport() {
        $('#csv-file-input').on('change', function() {
            var file = this.files[0];
            if (file) {
                // Validar tipo de arquivo
                if (file.type !== 'text/csv' && !file.name.toLowerCase().endsWith('.csv')) {
                    showAlert('error', 'Apenas arquivos CSV são permitidos');
                    $(this).val('');
                    return;
                }
                
                // Validar tamanho (máximo 10MB)
                if (file.size > 10 * 1024 * 1024) {
                    showAlert('error', 'Arquivo muito grande. Máximo 10MB');
                    $(this).val('');
                    return;
                }
                
                // Mostrar informações do arquivo
                $('#file-info').html(
                    '<strong>Arquivo selecionado:</strong> ' + file.name + 
                    ' (' + formatFileSize(file.size) + ')'
                ).show();
            }
        });

        $('#csv-import-form').on('submit', function() {
            var $submitBtn = $(this).find('input[type="submit"]');
            $submitBtn.val('Importando...').prop('disabled', true);
            
            // Mostrar barra de progresso
            $('#import-progress').show();
            
            // Simular progresso (o progresso real seria via AJAX)
            var progress = 0;
            var interval = setInterval(function() {
                progress += 10;
                $('#import-progress-bar').css('width', progress + '%');
                
                if (progress >= 90) {
                    clearInterval(interval);
                }
            }, 500);
        });
    }

    /**
     * Inicializa handlers de modais
     */
    function initModalHandlers() {
        // Abrir modal
        $('[data-modal]').on('click', function(e) {
            e.preventDefault();
            var modalId = $(this).data('modal');
            $('#' + modalId).show();
        });

        // Fechar modal
        $('.modal-close, .modal-overlay').on('click', function() {
            $(this).closest('.modal').hide();
        });

        // Fechar modal com ESC
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) { // ESC
                $('.modal:visible').hide();
            }
        });
    }

    /**
     * Atualiza a exibição do status de um documento
     */
    function updateDocumentStatusDisplay($form, newStatus) {
        var $statusBadge = $form.closest('.document-item').find('.document-status');
        
        // Remover classes de status antigas
        $statusBadge.removeClass('sisu-status-aprovado sisu-status-recusado sisu-status-aguardando sisu-status-nao-enviado');
        
        // Adicionar nova classe e texto
        var statusClass = 'sisu-status-' + newStatus.toLowerCase().replace(/\s+/g, '-');
        $statusBadge.addClass(statusClass).text(newStatus);
    }

    /**
     * Valida CPF
     */
    function isValidCPF(cpf) {
        if (cpf.length !== 11 || /^(\d)\1{10}$/.test(cpf)) {
            return false;
        }
        
        var sum = 0;
        var remainder;
        
        for (var i = 1; i <= 9; i++) {
            sum += parseInt(cpf.substring(i - 1, i)) * (11 - i);
        }
        
        remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.substring(9, 10))) return false;
        
        sum = 0;
        for (var i = 1; i <= 10; i++) {
            sum += parseInt(cpf.substring(i - 1, i)) * (12 - i);
        }
        
        remainder = (sum * 10) % 11;
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cpf.substring(10, 11))) return false;
        
        return true;
    }

    /**
     * Valida email
     */
    function isValidEmail(email) {
        var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    /**
     * Formata tamanho de arquivo
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    /**
     * Mostra alerta
     */
    function showAlert(type, message) {
        var alertClass = 'sisu-alert-' + type;
        var $alert = $('<div class="sisu-alert ' + alertClass + '"><p>' + message + '</p></div>');
        
        // Remover alertas existentes
        $('.sisu-alert').remove();
        
        // Adicionar novo alerta
        $('.sisu-admin-content').first().prepend($alert);
        
        // Auto-remover após 5 segundos
        setTimeout(function() {
            $alert.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Scroll para o topo
        $('html, body').animate({ scrollTop: 0 }, 300);
    }

    /**
     * Confirma ação destrutiva
     */
    window.confirmAction = function(message) {
        return confirm(message || 'Tem certeza que deseja executar esta ação?');
    };

    /**
     * Exporta dados para CSV
     */
    window.exportToCSV = function(tableId, filename) {
        var csv = [];
        var rows = document.querySelectorAll('#' + tableId + ' tr');
        
        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (var j = 0; j < cols.length - 1; j++) { // Excluir última coluna (ações)
                var cellText = cols[j].innerText.replace(/"/g, '""');
                row.push('"' + cellText + '"');
            }
            
            csv.push(row.join(','));
        }
        
        // Download do arquivo
        var csvFile = new Blob([csv.join('\n')], { type: 'text/csv' });
        var downloadLink = document.createElement('a');
        downloadLink.download = filename || 'export.csv';
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    };

    /**
     * Atualiza contador de caracteres
     */
    $('textarea[maxlength]').on('input', function() {
        var maxLength = $(this).attr('maxlength');
        var currentLength = $(this).val().length;
        var remaining = maxLength - currentLength;
        
        var $counter = $(this).siblings('.char-counter');
        if ($counter.length === 0) {
            $counter = $('<div class="char-counter"></div>');
            $(this).after($counter);
        }
        
        $counter.text(remaining + ' caracteres restantes');
        
        if (remaining < 10) {
            $counter.css('color', '#dc3232');
        } else {
            $counter.css('color', '#666');
        }
    });

})(jQuery);
