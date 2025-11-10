console.log('ARQUIVO CARREGADO - LINHA 1');

(function($) {
    'use strict';
    console.log('IIFE INICIADA');
    
    $(document).ready(function() {
        console.log('DOCUMENT READY');
        
        var selectedFiles = {};
        
        $('input[type="file"]').on('change', function() {
            var file = this.files[0];
            var tipoDocumento = $(this).attr('id').replace('file-', '');
            if (file) {
                selectedFiles[tipoDocumento] = file;
                console.log('Arquivo armazenado:', tipoDocumento);
            }
        });

        $('#submit-all-documents').on('click', function(e) {
            console.log('BOTÃO CLICADO');
            e.preventDefault();
            
            var fileCount = Object.keys(selectedFiles).length;
            if (fileCount === 0) {
                alert('Selecione pelo menos um documento para enviar.');
                return;
            }
            
            processBatchUpload();
        });
        
        function processBatchUpload() {
            console.log('INICIANDO UPLOAD');
            
            var $submitBtn = $('#submit-all-documents');
            $submitBtn.prop('disabled', true);
            $submitBtn.text('⏳ Enviando...');
            
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
            
            function uploadSingleFile(index) {
                if (index >= filesArray.length) {
                    onAllUploadsComplete();
                    return;
                }
                
                var fileData = filesArray[index];
                var tipoDocumento = fileData.tipo;
                var file = fileData.file;
                
                console.log('Fazendo upload do arquivo', index + 1, 'de', totalFiles);
                
                var formData = new FormData();
                formData.append('action', 'sisu_upload_document');
                formData.append('nonce', sisu_public_ajax.nonce);
                formData.append('tipo_documento', tipoDocumento);
                formData.append('file', file);
                
                $.ajax({
                    url: sisu_public_ajax.ajax_url,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    timeout: 300000,
                    success: function(response) {
                        console.log('Upload sucesso:', tipoDocumento);
                        if (response.success) {
                            uploadedCount++;
                        } else {
                            errorCount++;
                        }
                        uploadSingleFile(index + 1);
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.error('Upload erro:', tipoDocumento, textStatus);
                        errorCount++;
                        uploadSingleFile(index + 1);
                    }
                });
            }
            
            function onAllUploadsComplete() {
                console.log('UPLOADS CONCLUÍDOS');
                $submitBtn.prop('disabled', false);
                $submitBtn.text('📤 ENVIAR DOCUMENTAÇÃO');
                
                if (uploadedCount > 0) {
                    alert(uploadedCount + ' documento(s) enviado(s) com sucesso!');
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    alert('Nenhum documento foi enviado. Verifique os erros e tente novamente.');
                }
            }
            
            uploadSingleFile(0);
        }
    });
})(jQuery);
