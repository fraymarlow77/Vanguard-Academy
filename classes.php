<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$curso_id = isset($_GET['curso_id']) ? intval($_GET['curso_id']) : null;
$successMsg = "";
$errorMsg = "";

$coursesQuery = $pdo->prepare("
    SELECT c.id, m.nombre AS materia_nombre, c.nombre AS curso_nombre
    FROM cursos c
    JOIN curso_usuarios cu ON c.id = cu.curso_id
    JOIN materias m ON c.materia_id = m.id
    WHERE cu.usuario_id = ?
");
$coursesQuery->execute([$usuario_id]);
$myCourses = $coursesQuery->fetchAll();

$selectedCourse = null;
$userRole = null;
$cursoUsuarioId = null;
$tasks = [];

if ($curso_id) {
    $detailQuery = $pdo->prepare("
        SELECT c.*, m.nombre AS materia_nombre 
        FROM cursos c 
        JOIN materias m ON c.materia_id = m.id 
        WHERE c.id = ?
    ");
    $detailQuery->execute([$curso_id]);
    $selectedCourse = $detailQuery->fetch();

    $roleQuery = $pdo->prepare("
        SELECT cu.id AS curso_usuario_id, r.nombre AS rol_nombre 
        FROM curso_usuarios cu
        JOIN roles r ON cu.rol_id = r.id
        WHERE cu.curso_id = ? AND cu.usuario_id = ?
    ");
    $roleQuery->execute([$curso_id, $usuario_id]);
    $userRoleData = $roleQuery->fetch();

    if ($userRoleData) {
        $userRole = $userRoleData['rol_nombre']; 
        $cursoUsuarioId = $userRoleData['curso_usuario_id'];
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_tarea']) && $userRole === 'docente') {
        $titulo = trim($_POST['titulo']);
        $descripcion = trim($_POST['descripcion']);
        $fecha_limite = $_POST['fecha_limite'];
        $puntaje_maximo = !empty($_POST['puntaje_maximo']) ? floatval($_POST['puntaje_maximo']) : 10.00;

        try {
            $stmtTarea = $pdo->prepare("INSERT INTO tareas (curso_id, titulo, descripcion, fecha_limite, puntaje_maximo) VALUES (?, ?, ?, ?, ?)");
            $stmtTarea->execute([$curso_id, $titulo, $descripcion, $fecha_limite, $puntaje_maximo]);
            $successMsg = "¡Tarea creada exitosamente!";
        } catch (\PDOException $e) {
            $errorMsg = "Error al crear la tarea.";
        }
    }

    $taskQuery = $pdo->prepare("SELECT * FROM tareas WHERE curso_id = ? ORDER BY creado_en DESC");
    $taskQuery->execute([$curso_id]);
    $tasks = $taskQuery->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vanguard - Classes</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <header class="gov-header">
        <div class="gov-container">
            <a href="index.php" class="gov-header-brand">
                <h1 class="gov-header-title">Vanguard</h1>
            </a>
        </div>
    </header>

    <div class="gov-container" style="display: flex; gap: var(--gov-spacing-md); margin-top: var(--gov-spacing-lg);">
        
        <aside style="width: 280px; flex-shrink: 0;">
            <nav class="gov-nav" style="border: 1px solid var(--gov-border-color); border-radius: var(--gov-border-radius);">
                <ul class="gov-nav-list" style="flex-direction: column;">
                    <li class="gov-nav-item"><a href="index.php">INICIO</a></li>
                    <li class="gov-nav-item"><a href="classes.php" class="active">CLASES</a></li>
                    <li class="gov-nav-item"><a href="profile.php">CONFIGURACION</a></li>
                </ul>
            </nav>

            <div class="gov-card" style="margin-top: 1rem; padding: 0.75rem;">
                <h4 style="margin-top:0;">Tus Materias</h4>
                <?php if (count($myCourses) > 0): ?>
                    <?php foreach ($myCourses as $c): ?>
                        <div style="border-bottom: 1px solid var(--gov-surface-border); padding-bottom: 0.5rem; margin-bottom: 0.5rem;">
                            <a href="classes.php?curso_id=<?= $c['id'] ?>" style="text-decoration: none; color: inherit;">
                                <strong><?= htmlspecialchars($c['materia_nombre']) ?></strong>
                                <p class="gov-hint" style="margin: 0;"><?= htmlspecialchars($c['curso_nombre']) ?></p>
                            </a>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="gov-hint">No estás inscripto en ningún curso.</p>
                <?php endif; ?>
            </div>
        </aside>

        <main style="flex: 1;">
            <div class="gov-card">
                <?php if ($selectedCourse && $userRole): ?>
                    <h2><?= htmlspecialchars($selectedCourse['materia_nombre']) ?></h2>
                    <p class="gov-hint">Curso: <?= htmlspecialchars($selectedCourse['nombre']) ?> | Código: <strong><?= htmlspecialchars($selectedCourse['codigo_acceso']) ?></strong> | Rol: <strong><?= ucfirst($userRole) ?></strong></p>
                    
                    <hr style="margin: 1.5rem 0; border:0; border-top:1px solid var(--gov-border-color);">

                    <?php if (!empty($successMsg)): ?>
                        <div class="gov-alert" style="background:#d4edda; color:#155724; padding:10px; margin-bottom:15px; border-radius:4px;"><?= $successMsg ?></div>
                    <?php endif; ?>
                    <?php if (!empty($errorMsg)): ?>
                        <div class="gov-alert" style="background:#f8d7da; color:#721c24; padding:10px; margin-bottom:15px; border-radius:4px;"><?= $errorMsg ?></div>
                    <?php endif; ?>

                    <?php if ($userRole === 'docente'): ?>
                        <div style="margin-bottom: 2rem; background: #f9f9f9; padding: 1.5rem; border: 1px solid var(--gov-border-color); border-radius: 4px;">
                            <h4 style="margin-top:0;">Crear Nueva Tarea</h4>
                            <form action="classes.php?curso_id=<?= $curso_id ?>" method="POST">
                                <div class="gov-form-group">
                                    <label class="gov-label">Título de la Tarea:</label>
                                    <input type="text" name="titulo" class="gov-input" required>
                                </div>
                                <div class="gov-form-group">
                                    <label class="gov-label">Descripción:</label>
                                    <textarea name="descripcion" class="gov-input" rows="3" required></textarea>
                                </div>
                                <div style="display: flex; gap: 1rem;">
                                    <div class="gov-form-group" style="flex: 1;">
                                        <label class="gov-label">Fecha Límite:</label>
                                        <input type="datetime-local" name="fecha_limite" class="gov-input" required>
                                    </div>
                                    <div class="gov-form-group" style="width: 150px;">
                                        <label class="gov-label">Puntaje Máximo:</label>
                                        <input type="number" step="0.01" name="puntaje_maximo" class="gov-input" value="10.00" required>
                                    </div>
                                </div>
                                <button type="submit" name="crear_tarea" class="gov-btn gov-btn-primary">Publicar Tarea</button>
                            </form>
                        </div> 
                       <form action="eliminar.php" method="POST">
                           <input type="hidden" name="curso_id" value="<?= $curso_id ?>">
                           <button type="submit" name="borrar_curso" class="gov-btn gov-btn-secondary">Eliminar Clase</button>
                       </form>
                    <?php endif; ?>

                    <?php if (count($tasks) > 0): ?>
                        <h3 style="margin-top: 50px">Tareas Asignadas</h3>
                        <?php foreach ($tasks as $tarea): ?>
                            <div class="gov-card" style="border: 1px solid var(--gov-border-color); margin-bottom: 1rem; background: #fff;">
                                <h4><a href="task_view.php?id=<?= $tarea['id'] ?>" style="text-decoration:none; color:var(--gov-primary-color);"><?= htmlspecialchars($tarea['titulo']) ?></a></h4>
                                <p><?= nl2br(htmlspecialchars($tarea['descripcion'])) ?></p>
                                <p class="gov-hint">Fecha límite: <?= htmlspecialchars($tarea['fecha_limite']) ?> | Puntaje máximo: <?= htmlspecialchars($tarea['puntaje_maximo']) ?></p>
                                <a href="task_view.php?id=<?= $tarea['id'] ?>" class="gov-btn gov-btn-secondary" style="font-size: 0.85rem;">Ver Tarea y Entregas</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="gov-hint">No hay tareas pendientes asignadas para esta clase en este momento.</p>
                    <?php endif; ?>

                <?php else: ?>
                    <h2>Detalle de clases</h2>
                    <p>Selecciona una asignatura de la lista lateral para ver el contenido detallado, las tareas y las entregas.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>

</body>
</html>
