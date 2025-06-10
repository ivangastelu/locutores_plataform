<?php
// 1) Incluir funciones y obtener los grupos
require_once 'functions.php';
$groups = getGroups();

// 2) Inicializar y calcular qué grupo (si alguno) viene por GET
$selectedGroup = '';
if (isset($_GET['group']) && in_array($_GET['group'], $groups)) {
    $selectedGroup = $_GET['group'];
}

// 3) Definir el título de la página según el grupo o por defecto
if ($selectedGroup !== '') {
    $pageTitle = getGroupTitle($selectedGroup);
} else {
    $pageTitle = 'Muestras de Audios';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
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
        .group-btn {
            margin-bottom: 10px;
            text-align: left;
        }
        .group-header {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
            margin-bottom: 20px;
        }
        .active-group {
            background-color: #e9ecef;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row mb-4">
            <div class="col-12">
                <!-- <h1 class="mb-3">Muestras de Audios</h1> -->
                <h1 class="mb-3"><?php echo htmlspecialchars($pageTitle); ?></h1>
                <hr>
            </div>
        </div>
        
        <?php if (empty($groups)): ?>
            <div class="alert alert-info" role="alert">
                No hay audios disponibles en este momento.
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Sidebar con grupos -->
                <div class="col-md-3 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Categorías</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group">
                                <?php foreach ($groups as $group): ?>
                                    <?php
                                        // Verificar si hay audios en este grupo
                                        $audios = getAudiosFromGroup($group);
                                        if (empty($audios)) {
                                            continue;
                                        }
                                        
                                        // Determinar si este grupo está activo
                                        $isActive = ($selectedGroup === $group);
                                        $activeClass = $isActive ? 'active' : '';
                                    ?>
                                    <a href="?group=<?php echo urlencode($group); ?>" 
                                       class="list-group-item list-group-item-action <?php echo $activeClass; ?>">
                                        <i class="fas fa-folder me-2"></i>
                                        <?php echo htmlspecialchars($group); ?>
                                        <span class="badge bg-primary rounded-pill float-end">
                                            <?php echo count($audios); ?>
                                        </span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Contenido principal -->
                <div class="col-md-9">
                    <?php if (empty($selectedGroup)): ?>
                        <!-- Si no hay grupo seleccionado, mostrar todos los grupos con algunos audios -->
                        <?php foreach ($groups as $group): ?>
                            <?php
                                // Obtener audios del grupo actual
                                $audios = getAudiosFromGroup($group);
                                
                                // Si no hay audios en este grupo, continuar con el siguiente
                                if (empty($audios)) {
                                    continue;
                                }
                                
                                // Limitar a mostrar solo 4 audios por grupo en la vista principal
                                $displayAudios = array_slice($audios, 0, 4);
                            ?>
                            
                            <div class="group-header p-3 rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h3><?php echo htmlspecialchars($group); ?></h3>
                                    <a href="?group=<?php echo urlencode($group); ?>" class="btn btn-sm btn-outline-primary">
                                        Ver todos (<?php echo count($audios); ?>)
                                    </a>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <?php foreach ($displayAudios as $audio): ?>
                                    <div class="col-lg-3 col-md-6 mb-4">
                                        <div class="card audio-card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title text-truncate"><?php echo htmlspecialchars($audio['name']); ?></h5>
                                                
                                                <div class="mt-3">
                                                    <audio controls class="w-100 audio-player">
                                                        <source src="<?php echo htmlspecialchars($audio['url']); ?>" type="audio/mpeg">
                                                        Tu navegador no soporta el elemento de audio.
                                                    </audio>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <!-- Mostrar audios del grupo seleccionado -->
                        <?php
                            $audios = getAudiosFromGroup($selectedGroup);
                        ?>
                        
                        <div class="group-header p-3 rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <h3><?php echo htmlspecialchars($selectedGroup); ?></h3>
                                <span class="badge bg-primary"><?php echo count($audios); ?> archivo(s)</span>
                            </div>
                        </div>
                        
                        <?php if (empty($audios)): ?>
                            <div class="alert alert-info" role="alert">
                                No hay audios disponibles en este grupo.
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($audios as $audio): ?>
                                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                        <div class="card audio-card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title text-truncate"><?php echo htmlspecialchars($audio['name']); ?></h5>
                                                
                                                <div class="mt-3">
                                                    <audio controls class="w-100 audio-player">
                                                        <source src="<?php echo htmlspecialchars($audio['url']); ?>" type="audio/mpeg">
                                                        Tu navegador no soporta el elemento de audio.
                                                    </audio>
                                                </div>
                                            </div>
                                            <div class="card-footer bg-transparent">
                                                <small class="text-muted">
                                                    <?php echo date('d/m/Y', $audio['modified']); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
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