<?php
// Incluir archivo de funciones
require_once 'functions.php';

// Variable para los mensajes de respuesta
$message = [
    'type' => '',
    'text' => ''
];

// Procesar formulario de subida si se ha enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar si se está creando un nuevo grupo o usando uno existente
    $group = '';
    
    if (!empty($_POST['new_group'])) {
        // Usar el nuevo grupo
        $group = $_POST['new_group'];
    } elseif (!empty($_POST['existing_group'])) {
        // Usar un grupo existente
        $group = $_POST['existing_group'];
    }
    
    // Verificar que se ha especificado un grupo
    if (empty($group)) {
        $message = [
            'type' => 'danger',
            'text' => 'Debes especificar un grupo para subir los audios.'
        ];
    } 
    // Verificar que se han subido archivos
    elseif (!isset($_FILES['audio_files']) || empty($_FILES['audio_files']['name'][0])) {
        $message = [
            'type' => 'danger',
            'text' => 'Debes seleccionar al menos un archivo de audio para subir.'
        ];
    }
    else {
        // Contar cuántos archivos se han subido
        $fileCount = count($_FILES['audio_files']['name']);
        
        // Verificar que no excede el límite de 20 archivos
        if ($fileCount > 20) {
            $message = [
                'type' => 'danger',
                'text' => 'No puedes subir más de 20 archivos a la vez.'
            ];
        } else {
            // Variables para seguimiento de resultados
            $successCount = 0;
            $errorFiles = [];
            
            // Procesar cada archivo
            for ($i = 0; $i < $fileCount; $i++) {
                // Crear un array con la estructura de un único archivo para la función uploadAudio
                $singleFile = [
                    'name' => $_FILES['audio_files']['name'][$i],
                    'type' => $_FILES['audio_files']['type'][$i],
                    'tmp_name' => $_FILES['audio_files']['tmp_name'][$i],
                    'error' => $_FILES['audio_files']['error'][$i],
                    'size' => $_FILES['audio_files']['size'][$i]
                ];
                
                // Solo procesar si no hay error de subida para este archivo específico
                if ($singleFile['error'] == UPLOAD_ERR_OK) {
                    $result = uploadAudio($singleFile, $group);
                    
                    if ($result['success']) {
                        $successCount++;
                    } else {
                        $errorFiles[] = $singleFile['name'] . ' - ' . $result['message'];
                    }
                } else {
                    $errorFiles[] = $singleFile['name'] . ' - Error en la subida del archivo';
                }
            }
            
            // Generar mensaje final basado en los resultados
            if ($successCount > 0) {
                $message['type'] = ($successCount == $fileCount) ? 'success' : 'warning';
                $message['text'] = "Se han subido correctamente $successCount de $fileCount archivos.";
                
                if (!empty($errorFiles)) {
                    $message['text'] .= " Los siguientes archivos no se pudieron subir: <ul>";
                    foreach ($errorFiles as $errorFile) {
                        $message['text'] .= "<li>" . htmlspecialchars($errorFile) . "</li>";
                    }
                    $message['text'] .= "</ul>";
                }
            } else {
                $message = [
                    'type' => 'danger',
                    'text' => 'No se pudo subir ningún archivo. Por favor, inténtalo de nuevo.'
                ];
            }
        }
    }
}

// Obtener todos los grupos (carpetas) para mostrar en el formulario
$groups = getGroups();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Audios - Subir</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        .upload-area {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .upload-area:hover {
            background-color: #f8f9fa;
            border-color: #0d6efd;
        }
        #audio_files {
            display: none;
        }
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
        }
        .file-item:last-child {
            border-bottom: none;
        }
        .remove-file {
            cursor: pointer;
            color: #dc3545;
        }
        #selected_files_count {
            font-weight: bold;
        }
        #files_list {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="mb-0">Subir Audios</h1>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver a Listado
                    </a>
                </div>
                <hr>
            </div>
        </div>
        
        <?php if (!empty($message['text'])): ?>
            <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo $message['text']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Subir Nuevos Audios (máximo 20)</h5>
                    </div>
                    <div class="card-body">
                        <form action="upload.php" method="post" enctype="multipart/form-data">

                            <div class="mb-4">
                            <label for="group_title" class="form-label">Título personalizado para la página de escucha:</label>
                            <input type="text"
                                    name="group_title"
                                    id="group_title"
                                    class="form-control"
                                    placeholder="Ej. Muestras Instrumentales">
                            <small class="form-text text-muted">
                                Si dejas este campo vacío, se usará el nombre del grupo.
                            </small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Selecciona un grupo existente o crea uno nuevo:</label>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="existing_group" class="form-label">Grupo Existente:</label>
                                        <select name="existing_group" id="existing_group" class="form-select">
                                            <option value="">-- Seleccionar grupo --</option>
                                            <?php foreach ($groups as $group): ?>
                                                <option value="<?php echo htmlspecialchars($group); ?>">
                                                    <?php echo htmlspecialchars($group); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="new_group" class="form-label">Nuevo Grupo:</label>
                                        <input type="text" name="new_group" id="new_group" class="form-control" 
                                               placeholder="Nombre del nuevo grupo">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Archivos de Audio (<span id="selected_files_count">0</span>/20):</label>
                                <div class="upload-area" id="upload_area">
                                    <input type="file" name="audio_files[]" id="audio_files" accept="audio/*" multiple>
                                    <div class="py-4">
                                        <i class="fas fa-music fa-3x mb-3 text-primary"></i>
                                        <h5>Haga clic aquí para seleccionar archivos de audio</h5>
                                        <p class="text-muted">o arrastre y suelte los archivos</p>
                                        <p class="text-muted small">Formatos admitidos: MP3, WAV, OGG, M4A, AAC</p>
                                        <p class="text-muted small">Máximo 20 archivos</p>
                                    </div>
                                </div>
                                
                                <div id="file_info" class="mt-3 d-none">
                                    <div class="alert alert-info">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0">Archivos seleccionados:</h6>
                                            <button type="button" class="btn btn-sm btn-outline-danger" id="clear_files">
                                                <i class="fas fa-trash-alt me-1"></i>Limpiar todo
                                            </button>
                                        </div>
                                        <div id="files_list" class="bg-light rounded p-2">
                                            <!-- Los archivos seleccionados se mostrarán aquí -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary" id="submit_button">
                                <i class="fas fa-upload me-2"></i>Subir Audios
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Script para el manejo del área de carga de archivos
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('upload_area');
            const fileInput = document.getElementById('audio_files');
            const fileInfo = document.getElementById('file_info');
            const filesList = document.getElementById('files_list');
            const selectedFilesCount = document.getElementById('selected_files_count');
            const clearFilesBtn = document.getElementById('clear_files');
            const submitButton = document.getElementById('submit_button');
            const existingGroup = document.getElementById('existing_group');
            const newGroup = document.getElementById('new_group');
            
            const MAX_FILES = 20;
            
            // Cuando se selecciona un grupo existente, limpiar el campo de nuevo grupo
            existingGroup.addEventListener('change', function() {
                if (this.value !== '') {
                    newGroup.value = '';
                }
            });
            
            // Cuando se escribe en el campo de nuevo grupo, deseleccionar el grupo existente
            newGroup.addEventListener('input', function() {
                if (this.value !== '') {
                    existingGroup.value = '';
                }
            });
            
            // Al hacer clic en el área de carga, activar el input de archivo
            uploadArea.addEventListener('click', function() {
                fileInput.click();
            });
            
            // Limpiar todos los archivos seleccionados
            clearFilesBtn.addEventListener('click', function() {
                fileInput.value = '';
                filesList.innerHTML = '';
                fileInfo.classList.add('d-none');
                selectedFilesCount.textContent = '0';
                checkSubmitButton();
            });
            
            // Cuando se seleccionan archivos, mostrar info
            fileInput.addEventListener('change', function() {
                updateFilesList();
            });
            
            // Prevenir el comportamiento por defecto del navegador para arrastrar y soltar
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, preventDefaults, false);
                document.body.addEventListener(eventName, preventDefaults, false);
            });
            
            // Resaltar el área cuando se arrastra un archivo sobre ella
            ['dragenter', 'dragover'].forEach(eventName => {
                uploadArea.addEventListener(eventName, highlight, false);
            });
            
            // Quitar el resaltado cuando se sale del área o se suelta el archivo
            ['dragleave', 'drop'].forEach(eventName => {
                uploadArea.addEventListener(eventName, unhighlight, false);
            });
            
            // Cuando se suelta un archivo, manejarlo
            uploadArea.addEventListener('drop', handleDrop, false);
            
            // Función para actualizar la lista de archivos
            function updateFilesList() {
                if (fileInput.files.length > 0) {
                    const files = Array.from(fileInput.files);
                    
                    // Verificar si se excede el límite de archivos
                    if (files.length > MAX_FILES) {
                        alert(`No puedes subir más de ${MAX_FILES} archivos a la vez.`);
                        fileInput.value = '';
                        return;
                    }
                    
                    // Mostrar la información de archivos
                    filesList.innerHTML = '';
                    
                    files.forEach((file, index) => {
                        const fileItem = document.createElement('div');
                        fileItem.className = 'file-item';
                        fileItem.innerHTML = `
                            <div>
                                <i class="fas fa-file-audio me-2 text-primary"></i>
                                <span>${file.name}</span>
                                <span class="text-muted ms-2">${formatFileSize(file.size)}</span>
                            </div>
                            <div>
                                <i class="fas fa-times-circle remove-file" data-index="${index}"></i>
                            </div>
                        `;
                        filesList.appendChild(fileItem);
                    });
                    
                    // Mostrar el contador de archivos
                    selectedFilesCount.textContent = files.length;
                    
                    // Mostrar el bloque de información
                    fileInfo.classList.remove('d-none');
                    
                    // Agregar eventos para eliminar archivos individuales
                    document.querySelectorAll('.remove-file').forEach(btn => {
                        btn.addEventListener('click', function() {
                            removeFile(parseInt(this.getAttribute('data-index')));
                        });
                    });
                } else {
                    fileInfo.classList.add('d-none');
                    selectedFilesCount.textContent = '0';
                }
                
                checkSubmitButton();
            }
            
            // Función para eliminar un archivo específico
            function removeFile(index) {
                // No podemos eliminar un archivo directamente del FileList, así que debemos crear un nuevo DataTransfer
                const dt = new DataTransfer();
                const files = fileInput.files;
                
                for (let i = 0; i < files.length; i++) {
                    if (i !== index) {
                        dt.items.add(files[i]);
                    }
                }
                
                fileInput.files = dt.files;
                updateFilesList();
            }
            
            // Verificar si el botón de envío debe estar habilitado
            function checkSubmitButton() {
                const hasFiles = fileInput.files.length > 0;
                const hasGroup = existingGroup.value !== '' || newGroup.value !== '';
                
                submitButton.disabled = !(hasFiles && hasGroup);
            }
            
            // Monitorear cambios en los campos de grupo
            existingGroup.addEventListener('change', checkSubmitButton);
            newGroup.addEventListener('input', checkSubmitButton);
            
            // Funciones auxiliares
            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            function highlight() {
                uploadArea.classList.add('bg-light');
                uploadArea.style.borderColor = '#0d6efd';
            }
            
            function unhighlight() {
                uploadArea.classList.remove('bg-light');
                uploadArea.style.borderColor = '#ddd';
            }
            
            function handleDrop(e) {
                const dt = e.dataTransfer;
                const newFiles = dt.files;
                
                // Verificar si los archivos son de tipo audio
                const invalidFiles = Array.from(newFiles).filter(file => !file.type.startsWith('audio/'));
                if (invalidFiles.length > 0) {
                    alert('Solo se permiten archivos de audio.');
                    return;
                }
                
                // Verificar si excede el límite de archivos
                if (newFiles.length > MAX_FILES) {
                    alert(`No puedes subir más de ${MAX_FILES} archivos a la vez.`);
                    return;
                }
                
                // Asignar los archivos al input
                fileInput.files = newFiles;
                updateFilesList();
            }
            
            // JavaScript formatFileSize function - puede utilizar la función PHP en el frontend
            function formatFileSize(bytes) {
                if (bytes >= 1073741824) {
                    return (bytes / 1073741824).toFixed(2) + ' GB';
                } else if (bytes >= 1048576) {
                    return (bytes / 1048576).toFixed(2) + ' MB';
                } else if (bytes >= 1024) {
                    return (bytes / 1024).toFixed(2) + ' KB';
                } else {
                    return bytes + ' bytes';
                }
            }
            
            // Inicializar el estado del botón de envío
            checkSubmitButton();
        });
    </script>
</body>
</html>