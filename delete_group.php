<?php
// Incluir archivo de funciones
require_once 'functions.php';
require_once 'csrf_functions.php';

// Iniciar sesión para CSRF
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Variable para los mensajes de respuesta
$message = [
    'type' => '',
    'text' => ''
];

// Obtener el grupo de la URL
$groupName = isset($_GET['group']) ? $_GET['group'] : '';

// Verificar si el grupo existe
$groupExists = false;
if (!empty($groupName)) {
    $groups = getGroups();
    $groupExists = in_array($groupName, $groups);
}

// Procesar la eliminación si se ha confirmado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete']) && $_POST['confirm_delete'] === 'yes') {
    // Verificar token CSRF
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = [
            'type' => 'danger',
            'text' => 'Error de seguridad: token CSRF inválido. Por favor, intente nuevamente.'
        ];
    } else {
        // Eliminar el grupo con verificación de CSRF
        $result = deleteGroup($groupName);
        
        if ($result['success']) {
            // Redirigir al índice con mensaje de éxito
            header('Location: index.php?deleted=true&group=' . urlencode($groupName));
            exit;
        } else {
            $message = [
                'type' => 'danger',
                'text' => $result['message']
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Grupo - Sistema de Audios</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="mb-0">Eliminar Grupo</h1>
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
            <div class="col-lg-6 mx-auto">
                <?php if (!$groupExists && empty($message['text'])): ?>
                    <div class="alert alert-warning" role="alert">
                        El grupo especificado no existe.
                    </div>
                    <div class="text-center mt-4">
                        <a href="index.php" class="btn btn-primary">Volver al Listado</a>
                    </div>
                <?php elseif ($groupExists): ?>
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Eliminación
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <p class="mb-0">
                                    <strong>¡Atención!</strong> Estás a punto de eliminar el grupo 
                                    <strong><?php echo htmlspecialchars($groupName); ?></strong> y todos los audios que contiene.
                                </p>
                                <p class="mb-0 mt-2">
                                    Esta acción no se puede deshacer.
                                </p>
                            </div>
                            
                            <?php
                            // Obtener audios del grupo para mostrar la lista
                            $audios = getAudiosFromGroup($groupName);
                            ?>
                            
                            <?php if (!empty($audios)): ?>
                                <h6 class="mt-4">Audios que serán eliminados (<?php echo count($audios); ?>):</h6>
                                <ul class="list-group mt-2">
                                    <?php foreach ($audios as $audio): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <span>
                                                <i class="fas fa-music me-2 text-danger"></i>
                                                <?php echo htmlspecialchars($audio['name']); ?>
                                            </span>
                                            <span class="badge bg-secondary rounded-pill">
                                                <?php echo formatFileSize($audio['size']); ?>
                                            </span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="alert alert-info mt-3">
                                    Este grupo no contiene audios.
                                </div>
                            <?php endif; ?>
                            
                            <form action="delete_group.php?group=<?php echo urlencode($groupName); ?>" method="post" class="mt-4">
                                <input type="hidden" name="confirm_delete" value="yes">
                                <?php csrfField(); ?>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="index.php" class="btn btn-secondary">
                                        <i class="fas fa-times me-2"></i>Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-trash me-2"></i>Eliminar Permanentemente
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>