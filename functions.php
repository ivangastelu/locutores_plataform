<?php
/**
 * Funciones generales para el sistema de gestión de audios
 */

// Directorio base para almacenar los audios
define('UPLOAD_DIR', 'uploads/');

/**
 * Obtiene todas las carpetas (grupos) en el directorio de subidas
 * @return array Lista de carpetas
 */
function getGroups() {
    $groups = [];
    
    // Crear la carpeta de uploads si no existe
    if (!file_exists(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0777, true);
        return $groups;
    }
    
    // Obtener todas las carpetas dentro del directorio de uploads
    $items = scandir(UPLOAD_DIR);
    
    foreach ($items as $item) {
        if ($item != '.' && $item != '..' && is_dir(UPLOAD_DIR . $item)) {
            $groups[] = $item;
        }
    }
    
    return $groups;
}

/**
 * Obtiene todos los archivos de audio en un grupo específico
 * @param string $group Nombre del grupo (carpeta)
 * @return array Lista de archivos de audio
 */
function getAudiosFromGroup($group) {
    $audios = [];
    $path = UPLOAD_DIR . $group . '/';
    
    // Verificar si la carpeta existe
    if (!file_exists($path)) {
        return $audios;
    }
    
    // Obtener todos los archivos de la carpeta
    $items = scandir($path);
    
    // Extensiones de audio permitidas
    $allowedExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac'];
    
    foreach ($items as $item) {
        if ($item != '.' && $item != '..' && is_file($path . $item)) {
            $extension = pathinfo($item, PATHINFO_EXTENSION);
            if (in_array(strtolower($extension), $allowedExtensions)) {
                $audios[] = [
                    'name' => $item,
                    'path' => $path . $item,
                    'url' => $path . $item,
                    'size' => filesize($path . $item),
                    'modified' => filemtime($path . $item)
                ];
            }
        }
    }
    
    // Ordenar archivos por fecha de modificación (más reciente primero)
    usort($audios, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    return $audios;
}

/**
 * Crea un nuevo grupo (carpeta)
 * @param string $groupName Nombre del grupo
 * @return bool True si se creó correctamente, False si hubo error
 */
function createGroup($groupName) {
    // Limpiar el nombre del grupo para evitar problemas con caracteres especiales
    $groupName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $groupName);
    
    // Verificar que el nombre no esté vacío
    if (empty($groupName)) {
        return false;
    }
    
    $path = UPLOAD_DIR . $groupName;
    
    // Crear la carpeta si no existe
    if (!file_exists($path)) {
        return mkdir($path, 0777, true);
    }
    
    return true;
}

/**
 * Sube un archivo de audio a un grupo específico
 * @param array $file Datos del archivo (_FILES)
 * @param string $group Nombre del grupo
 * @return array Resultado del proceso ['success' => bool, 'message' => string]
 */
function uploadAudio($file, $group) {
    $result = [
        'success' => false,
        'message' => ''
    ];
    
    // Verificar que se ha subido un archivo
    if (!isset($file) || $file['error'] != UPLOAD_ERR_OK) {
        $result['message'] = 'Error al subir el archivo: ' . getUploadErrorMessage($file['error']);
        return $result;
    }

    if (!empty($_POST['group_title'])) {
        saveGroupTitle($group, $_POST['group_title']);
    }
    
    // Verificar que el grupo existe o crearlo
    if (!createGroup($group)) {
        $result['message'] = 'Error al crear el grupo';
        return $result;
    }
    
    // Extensiones de audio permitidas
    $allowedExtensions = ['mp3', 'wav', 'ogg', 'm4a', 'aac'];
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    // Verificar la extensión del archivo
    if (!in_array($fileExtension, $allowedExtensions)) {
        $result['message'] = 'Tipo de archivo no permitido. Se permiten: ' . implode(', ', $allowedExtensions);
        return $result;
    }
    
    // Generar un nombre de archivo seguro y único
    $originalFilename = pathinfo($file['name'], PATHINFO_FILENAME);
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    
    // Sanitizar el nombre del archivo
    $safeFilename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalFilename);
    
    // Asegurarse de que el nombre no esté vacío
    if (empty($safeFilename)) {
        $safeFilename = 'audio_' . time();
    }
    
    // Añadir timestamp para evitar sobreescribir archivos
    $uniqueFilename = $safeFilename . '_' . time() . '.' . $extension;
    $destination = UPLOAD_DIR . $group . '/' . $uniqueFilename;
    
    // Mover el archivo subido a la carpeta de destino
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $result['success'] = true;
        $result['message'] = 'Archivo ' . htmlspecialchars($file['name']) . ' subido correctamente';
    } else {
        $result['message'] = 'Error al guardar el archivo';
    }
    
    return $result;
}

/**
 * Formatea el tamaño de archivo en bytes a un formato legible
 * @param int $bytes Tamaño en bytes
 * @return string Tamaño formateado
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * Elimina recursivamente un directorio y todo su contenido
 * @param string $dir Ruta del directorio a eliminar
 * @return bool True si se eliminó correctamente, False si hubo error
 */
function deleteDirectory($dir) {
    if (!file_exists($dir)) {
        return true;
    }
    
    if (!is_dir($dir)) {
        return unlink($dir);
    }
    
    // Eliminar todos los archivos y subdirectorios dentro del directorio
    foreach (scandir($dir) as $item) {
        if ($item == '.' || $item == '..') {
            continue;
        }
        
        if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
            return false;
        }
    }
    
    // Eliminar el directorio vacío
    return rmdir($dir);
}

/**
 * Elimina un grupo (carpeta) y todos sus archivos
 * @param string $groupName Nombre del grupo a eliminar
 * @return array Resultado del proceso ['success' => bool, 'message' => string]
 */
function deleteGroup($groupName) {
    $result = [
        'success' => false,
        'message' => ''
    ];
    
    // Verificar que el nombre no esté vacío
    if (empty($groupName)) {
        $result['message'] = 'Nombre de grupo no válido';
        return $result;
    }
    
    // Ruta completa al directorio del grupo
    $path = UPLOAD_DIR . $groupName;
    
    // Verificar que el directorio existe
    if (!file_exists($path) || !is_dir($path)) {
        $result['message'] = 'El grupo no existe';
        return $result;
    }
    
    // Intentar eliminar el directorio y todo su contenido
    if (deleteDirectory($path)) {
        $result['success'] = true;
        $result['message'] = 'Grupo eliminado correctamente';
    } else {
        $result['message'] = 'Error al eliminar el grupo';
    }
    
    return $result;
}

/**
 * Obtiene un mensaje de error basado en el código de error de subida
 * 
 * @param int $errorCode Código de error de $_FILES['file']['error']
 * @return string Mensaje de error descriptivo
 */
function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'El archivo excede el tamaño máximo permitido por el servidor.';
        case UPLOAD_ERR_FORM_SIZE:
            return 'El archivo excede el tamaño máximo permitido por el formulario.';
        case UPLOAD_ERR_PARTIAL:
            return 'El archivo solo se subió parcialmente.';
        case UPLOAD_ERR_NO_FILE:
            return 'No se seleccionó ningún archivo para subir.';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Falta la carpeta temporal del servidor.';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Error al escribir el archivo en el disco.';
        case UPLOAD_ERR_EXTENSION:
            return 'Una extensión de PHP detuvo la subida del archivo.';
        default:
            return 'Error desconocido en la subida del archivo.';
    }
}

/**
 * Guarda el título personalizado de un grupo en un archivo title.txt dentro de la carpeta del grupo
 */
function saveGroupTitle(string $group, string $title): void {
    $dir = UPLOAD_DIR . $group . '/';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    file_put_contents($dir . 'title.txt', trim($title));
}

/**
 * Recupera el título personalizado de un grupo, o si no existe, devuelve el nombre del grupo
 */
function getGroupTitle(string $group): string {
    $file = UPLOAD_DIR . $group . '/title.txt';
    if (file_exists($file)) {
        return trim(file_get_contents($file));
    }
    return $group;
}
?>