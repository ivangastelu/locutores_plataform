<?php
// Incluir archivo de funciones
require_once 'functions.php';

// Verificar si hay mensaje de eliminación exitosa
$deletedMessage = '';
if (isset($_GET['deleted']) && $_GET['deleted'] === 'true' && isset($_GET['group'])) {
    $deletedMessage = 'El grupo "' . htmlspecialchars($_GET['group']) . '" ha sido eliminado correctamente.';
}

// Obtener todos los grupos (carpetas)
$groups = getGroups();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Audios - Listado</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        .audio-card {
            transition: transform 0.2s;
        }
        .audio-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .group-header {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h1 class="mb-0">Sistema de Gestión de Audios</h1>
                    <a href="upload.php" class="btn btn-primary">
                        <i class="fas fa-upload me-2"></i>Subir Audios
                    </a>
                </div>
                <hr>
            </div>
        </div>
        
        <?php if (!empty($deletedMessage)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $deletedMessage; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (empty($groups)): ?>
            <div class="alert alert-info" role="alert">
                No hay grupos de audios disponibles. 
                <a href="upload.php" class="alert-link">Crea tu primer grupo y sube audios</a>.
            </div>
        <?php else: ?>
            <?php foreach ($groups as $group): ?>
                <?php 
                    // Obtener audios del grupo actual
                    $audios = getAudiosFromGroup($group);
                    
                    // Si no hay audios en este grupo, continuar con el siguiente
                    if (empty($audios)) {
                        continue;
                    }
                ?>
                
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="group-header p-3 mb-3 rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h3><?php echo htmlspecialchars($group); ?></h3>
                                    <small class="text-muted"><?php echo count($audios); ?> archivo(s)</small>
                                </div>
                                <div>
                                    <a href="delete_group.php?group=<?php echo urlencode($group); ?>" 
                                       class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash me-1"></i>Eliminar Grupo
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php foreach ($audios as $audio): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="card audio-card h-100">
                                <div class="card-body">
                                    <h5 class="card-title text-truncate"><?php echo htmlspecialchars($audio['name']); ?></h5>
                                    <p class="card-text text-muted mb-2">
                                        <small><?php echo formatFileSize($audio['size']); ?></small>
                                    </p>
                                    
                                    <div class="mt-3">
                                        <audio controls class="w-100 audio-player">
                                            <source src="<?php echo htmlspecialchars($audio['url']); ?>" type="audio/mpeg">
                                            Tu navegador no soporta el elemento de audio.
                                        </audio>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <small class="text-muted">
                                        Modificado: <?php echo date('d/m/Y H:i', $audio['modified']); ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Bootstrap 5 JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script para manejar la reproducción de un solo audio a la vez -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Obtener todos los elementos de audio
            const audioPlayers = document.querySelectorAll('.audio-player');
            
            // Función para detener todos los audios excepto el actual
            function stopOtherAudios(currentAudio) {
                audioPlayers.forEach(audio => {
                    if (audio !== currentAudio) {
                        audio.pause();
                        audio.currentTime = 0;
                    }
                });
            }
            
            // Agregar event listener a cada reproductor de audio
            audioPlayers.forEach(audio => {
                audio.addEventListener('play', function() {
                    stopOtherAudios(this);
                });
            });
        });
    </script>
</body>
</html>