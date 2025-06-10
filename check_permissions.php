<?php
/**
 * Script para verificar y corregir permisos de directorios y archivos
 * Ejecutar este script cuando tengas problemas de permisos
 */

// Definir directorio base
define('UPLOAD_DIR', 'uploads/');
$baseDir = dirname(__FILE__);

echo "=== Verificador de permisos del sistema de audios ===\n\n";

// Verificar si el directorio base tiene permisos de escritura
echo "Directorio base (" . $baseDir . "): ";
if (is_writable($baseDir)) {
    echo "PERMISOS CORRECTOS\n";
} else {
    echo "SIN PERMISOS DE ESCRITURA\n";
    echo "  → Solución: chmod 755 " . $baseDir . "\n";
}

// Verificar si existe el directorio de uploads
echo "\nDirectorio de uploads (" . UPLOAD_DIR . "): ";
if (!file_exists(UPLOAD_DIR)) {
    echo "NO EXISTE\n";
    echo "  → Creando directorio...\n";
    
    $result = mkdir(UPLOAD_DIR, 0777, true);
    if ($result) {
        echo "  → Directorio creado correctamente\n";
    } else {
        echo "  → ERROR al crear directorio\n";
        echo "  → Solución manual: mkdir -p " . UPLOAD_DIR . " && chmod 777 " . UPLOAD_DIR . "\n";
    }
} else {
    echo "EXISTE\n";
    
    // Verificar permisos
    if (is_writable(UPLOAD_DIR)) {
        echo "  → PERMISOS CORRECTOS\n";
    } else {
        echo "  → SIN PERMISOS DE ESCRITURA\n";
        echo "  → Intentando corregir...\n";
        
        $result = chmod(UPLOAD_DIR, 0777);
        if ($result) {
            echo "  → Permisos corregidos correctamente\n";
        } else {
            echo "  → ERROR al corregir permisos\n";
            echo "  → Solución manual: chmod 777 " . UPLOAD_DIR . "\n";
        }
    }
}

// Verificar grupos existentes
$groups = [];
if (file_exists(UPLOAD_DIR)) {
    $items = scandir(UPLOAD_DIR);
    
    foreach ($items as $item) {
        if ($item != '.' && $item != '..' && is_dir(UPLOAD_DIR . $item)) {
            $groups[] = $item;
        }
    }
}

echo "\nGrupos existentes: " . count($groups) . "\n";
if (count($groups) > 0) {
    foreach ($groups as $group) {
        $groupDir = UPLOAD_DIR . $group;
        echo "  → Grupo '$group': ";
        
        if (is_writable($groupDir)) {
            echo "PERMISOS CORRECTOS\n";
        } else {
            echo "SIN PERMISOS DE ESCRITURA\n";
            echo "    → Intentando corregir...\n";
            
            $result = chmod($groupDir, 0777);
            if ($result) {
                echo "    → Permisos corregidos correctamente\n";
            } else {
                echo "    → ERROR al corregir permisos\n";
                echo "    → Solución manual: chmod 777 " . $groupDir . "\n";
            }
        }
    }
}

// Verificar configuración de PHP
echo "\nConfiguración de PHP relevante:\n";
echo "  → upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "  → post_max_size: " . ini_get('post_max_size') . "\n";
echo "  → max_execution_time: " . ini_get('max_execution_time') . "\n";
echo "  → max_input_time: " . ini_get('max_input_time') . "\n";
echo "  → file_uploads: " . (ini_get('file_uploads') ? 'Habilitado' : 'Deshabilitado') . "\n";

// Verificar si php tiene permisos para ejecutar funciones de sistema
echo "\nPermisos de sistema:\n";
$disabledFunctions = ini_get('disable_functions');
$criticalFunctions = ['mkdir', 'rmdir', 'unlink', 'move_uploaded_file', 'chmod', 'rename'];
echo "  → Funciones deshabilitadas: " . ($disabledFunctions ? $disabledFunctions : 'Ninguna') . "\n";
echo "  → Estado de funciones críticas:\n";

foreach ($criticalFunctions as $function) {
    echo "    → $function: " . (function_exists($function) && !in_array($function, explode(',', $disabledFunctions)) ? 'Disponible' : 'NO DISPONIBLE') . "\n";
}

// Verificar si se puede crear un archivo de prueba
echo "\nPrueba de escritura:\n";
$testFile = UPLOAD_DIR . 'test_' . time() . '.txt';
$result = file_put_contents($testFile, 'Prueba de escritura');

if ($result !== false) {
    echo "  → Se creó correctamente un archivo de prueba\n";
    
    // Eliminar el archivo de prueba
    if (unlink($testFile)) {
        echo "  → Se eliminó correctamente el archivo de prueba\n";
    } else {
        echo "  → ERROR al eliminar el archivo de prueba\n";
        echo "  → Solución manual: rm " . $testFile . "\n";
    }
} else {
    echo "  → ERROR al crear archivo de prueba\n";
    echo "  → Es probable que el usuario del servidor web no tenga permisos suficientes\n";
}

echo "\n=== Recomendaciones finales ===\n";
echo "1. Asegúrate de que el usuario del servidor web (www-data, apache, etc.) tenga permisos\n";
echo "   de escritura en el directorio de la aplicación y en 'uploads/'\n";
echo "2. Verifica que las funciones de PHP necesarias estén habilitadas en php.ini\n";
echo "3. Asegúrate de que el archivo .htaccess esté correctamente configurado\n";
echo "4. Si usas un hosting compartido, consulta con tu proveedor sobre los permisos\n";

echo "\n=== Fin del diagnóstico ===\n";