    /**
     * NOVO: Processa upload SEQUENCIAL (um arquivo por vez) para evitar bloqueio do ModSecurity
     */
    function processBatchUpload() {
        console.log('Iniciando upload sequencial...');
        console.log('Arquivos selecionados:', selectedFiles);
        
        var $submitBtn = $('#submit-all-documents');
        var $btnText = $submitBtn.find('.submit-text');
        var $btnLoading = $submitBtn.find('.loading-text');
        
        // Desabilitar botão e mostrar loading
        $submitBtn.prop('disabled', true);
        $btnText.addClass('sisu-hidden');
        $btnLoading.removeClass('sisu-hidden');
        
        // Mostrar barra de progresso geral
        var $progressContainer = $('#batch-upload-progress');
        var $progressBar = $progressContainer.find('.sisu-progress-bar');
        $progressContainer.removeClass('sisu-hidden');
        $progressBar.css('width', '0%');
        
        // Converter selectedFiles para array
        var filesArray = [];
        for (var tipoDocumento in selectedFiles) {
            filesArray.push({
                tipo: tipoDocumento,
                file: selectedFiles[tipoDocumento]
            });
        }
        
        var totalFiles = filesArray.length;
        var uploadedCount = 0;
        var errorCount = 0;
        var errors = [];
        
        console.log('Total de arquivos:', totalFiles);
        
        // Função para fazer upload de um arquivo
        function uploadSingleFile(index) {
            if (index >= filesArray.length) {
                // Todos os uploads concluídos
                onAllUploadsComplete();
                return;
            }
            
            var fileData = filesArray[index];
            var tipoDocumento = fileData.tipo;
            var file = fileData.file;
            
            console.log('Enviando arquivo ' + (index + 1) + '/' + totalFiles + ':', tipoDocumento);
            
            // Atualizar progresso
            var progress = ((index) / totalFiles) * 100;
            $progressBar.css('width', progress + '%');
            
            // Preparar FormData para este arquivo
            var formData = new FormData();
            formData.append('action', 'sisu_upload_document');
            formData.append('nonce', sisu_public_ajax.nonce);
            formData.append('tipo_documento', tipoDocumento);
            formData.append('file', file);
            
            // Upload via AJAX
            $.ajax({
                url: sisu_public_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log('Resposta para ' + tipoDocumento + ':', response);
                    
                    if (response.success) {
                        uploadedCount++;
                    } else {
                        errorCount++;
                        errors.push(tipoDocumento + ': ' + (response.data.message || 'Erro desconhecido'));
                    }
                    
                    // Próximo arquivo
                    uploadSingleFile(index + 1);
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    console.error('Erro ao enviar ' + tipoDocumento + ':', textStatus);
                    errorCount++;
                    errors.push(tipoDocumento + ': Erro de conexão');
                    
                    // Próximo arquivo
                    uploadSingleFile(index + 1);
                }
            });
        }
        
        // Função chamada quando todos os uploads terminam
        function onAllUploadsComplete() {
            console.log('Uploads concluídos:', uploadedCount, 'Erros:', errorCount);
            
            // Progresso 100%
            $progressBar.css('width', '100%');
            
            if (uploadedCount > 0) {
                var message = uploadedCount + ' documento(s) enviado(s) com sucesso!';
                showMessage('success', message);
                
                if (errorCount > 0) {
                    var errorHtml = '<ul>';
                    errors.forEach(function(error) {
                        errorHtml += '<li>' + error + '</li>';
                    });
                    errorHtml += '</ul>';
                    showMessage('warning', 'Alguns documentos apresentaram erros:' + errorHtml);
                }
                
                // Recarregar a página após 3 segundos
                setTimeout(function() {
                    location.reload();
                }, 3000);
            } else {
                showMessage('error', 'Nenhum documento foi enviado. Verifique os erros e tente novamente.');
                resetBatchUpload($submitBtn, $btnText, $btnLoading, $progressContainer);
            }
        }
        
        // Iniciar upload do primeiro arquivo
        uploadSingleFile(0);
    }
