<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$tarea_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$successMsg = "";
$errorMsg = "";

if (!$tarea_id) {
    header("Location: index.php");
    exit();
}

$taskQuery = $pdo->prepare("
    SELECT t.*, c.id AS curso_id, c.nombre AS curso_nombre, m.nombre AS materia_nombre 
    FROM tareas t
    JOIN cursos c ON t.curso_id = c.id
    JOIN materias m ON c.materia_id = m.id
    WHERE t.id = ?
");
$taskQuery->execute([$tarea_id]);
$tarea = $taskQuery->fetch();

if (!$tarea) {
    die("La tarea no existe.");
}

$curso_id = $tarea['curso_id'];

$roleQuery = $pdo->prepare("
    SELECT cu.id AS curso_usuario_id, r.nombre AS rol_nombre 
    FROM curso_usuarios cu
    JOIN roles r ON cu.rol_id = r.id
    WHERE cu.curso_id = ? AND cu.usuario_id = ?
");
$roleQuery->execute([$curso_id, $usuario_id]);
$userRoleData = $roleQuery->fetch();

if (!$userRoleData) {
    die("No tienes acceso a esta tarea.");
}

$userRole = $userRoleData['rol_nombre'];
$cursoUsuarioId = $userRoleData['curso_usuario_id'];
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['editar_tarea']) && $userRole === 'docente') {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $fecha_limite = $_POST['fecha_limite'];
    $puntaje_maximo = floatval($_POST['puntaje_maximo']);

    try {
        $updTarea = $pdo->prepare("UPDATE tareas SET titulo = ?, descripcion = ?, fecha_limite = ?, puntaje_maximo = ? WHERE id = ?");
        $updTarea->execute([$titulo, $descripcion, $fecha_limite, $puntaje_maximo, $tarea_id]);
        $successMsg = "¡Tarea actualizada correctamente!";
        $taskQuery->execute([$tarea_id]);
        $tarea = $taskQuery->fetch();
    } catch (\PDOException $e) {
        $errorMsg = "Error al actualizar la tarea.";
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enviar_entrega']) && $userRole === 'alumno') {
    $contenido_texto = trim($_POST['contenido_texto']);

    try {
        $insertEntrega = $pdo->prepare("
            INSERT INTO entregas (tarea_id, curso_usuario_id, contenido_texto, entregado) 
            VALUES (?, ?, ?, TRUE)
            ON DUPLICATE KEY UPDATE contenido_texto = ?, entregado_en = CURRENT_TIMESTAMP
        ");
        $insertEntrega->execute([$tarea_id, $cursoUsuarioId, $contenido_texto, $contenido_texto]);
        $successMsg = "¡Tu tarea ha sido enviada correctamente!";
    } catch (\PDOException $e) {
        $errorMsg = "Error al enviar la entrega.";
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['calificar_entrega']) && $userRole === 'docente') {
    $entrega_id = intval($_POST['entrega_id']);
    $nota = floatval($_POST['nota']);
    $feedback = trim($_POST['feedback']);

    try {
        $stmtCalificar = $pdo->prepare("
            INSERT INTO calificaciones (entrega_id, curso_usuario_id, nota, feedback) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE nota = ?, feedback = ?, calificado_en = CURRENT_TIMESTAMP
        ");
        $stmtCalificar->execute([$entrega_id, $cursoUsuarioId, $nota, $feedback, $nota, $feedback]);
        $successMsg = "¡Calificación guardada con éxito!";
    } catch (\PDOException $e) {
        $errorMsg = "Error al guardar la calificación.";
    }
}

$miEntrega = null;
$miCalificacion = null;
if ($userRole === 'alumno') {
    $entQuery = $pdo->prepare("SELECT * FROM entregas WHERE tarea_id = ? AND curso_usuario_id = ?");
    $entQuery->execute([$tarea_id, $cursoUsuarioId]);
    $miEntrega = $entQuery->fetch();

    if ($miEntrega) {
        $calQuery = $pdo->prepare("SELECT * FROM calificaciones WHERE entrega_id = ?");
        $calQuery->execute([$miEntrega['id']]);
        $miCalificacion = $calQuery->fetch();
    }
}

$entregasAlumnos = [];
if ($userRole === 'docente') {
    $allEntQuery = $pdo->prepare("
        SELECT e.*, u.nombre AS alumno_name, c.nota, c.feedback, c.calificado_en 
        FROM entregas e
        JOIN curso_usuarios cu ON e.curso_usuario_id = cu.id
        JOIN usuarios u ON cu.usuario_id = u.id
        LEFT JOIN calificaciones c ON e.id = c.entrega_id
        WHERE e.tarea_id = ?
    ");
    $allEntQuery->execute([$tarea_id]);
    $entregasAlumnos = $allEntQuery->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vanguard - <?= htmlspecialchars($tarea['titulo']) ?></title>
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

    <nav class="gov-nav">
        <div class="gov-container">
            <ul class="gov-nav-list">
                <li class="gov-nav-item"><a href="index.php">HOME</a></li>
                <li class="gov-nav-item"><a href="classes.php?curso_id=<?= $curso_id ?>">VOLVER A CLASE</a></li>
            </ul>
        </div>
    </nav>

    <main class="gov-container" style="margin-top: 2rem;">
        <div class="gov-card">
            <h2><?= htmlspecialchars($tarea['titulo']) ?></h2>
            <p class="gov-hint">Materia: <strong><?= htmlspecialchars($tarea['materia_nombre']) ?></strong> | Curso: <?= htmlspecialchars($tarea['curso_nombre']) ?></p>
            <p class="gov-hint">Fecha límite: <strong><?= htmlspecialchars($tarea['fecha_limite']) ?></strong> | Puntaje Máximo: <?= htmlspecialchars($tarea['puntaje_maximo']) ?></p>
            
            <hr style="margin: 1.5rem 0; border:0; border-top:1px solid var(--gov-border-color);">

            <div style="background: #f9f9f9; padding: 1rem; border-radius: 4px; margin-bottom: 2rem;">
                <h4>Descripción de la tarea:</h4>
                <p><?= nl2br(htmlspecialchars($tarea['descripcion'])) ?></p>
            </div>

            <?php if (!empty($successMsg)): ?>
                <div class="gov-alert" style="background:#d4edda; color:#155724; padding:10px; margin-bottom:15px; border-radius:4px;"><?= $successMsg ?></div>
            <?php endif; ?>
            <?php if (!empty($errorMsg)): ?>
                <div class="gov-alert" style="background:#f8d7da; color:#721c24; padding:10px; margin-bottom:15px; border-radius:4px;"><?= $errorMsg ?></div>
            <?php endif; ?>


            <?php if ($userRole === 'alumno'): ?>
                <div class="gov-card" style="border: 1px solid var(--gov-border-color);">
                    <h3>Tu Entrega</h3>
                    <form action="task_view.php?id=<?= $tarea_id ?>" method="POST">
                        <div class="gov-form-group">
                            <label class="gov-label">Respuesta / Enlace de tu trabajo:</label>
                            <textarea name="contenido_texto" class="gov-input" rows="4" required placeholder="Escribe tu respuesta aquí o pega el enlace..."><?= $miEntrega ? htmlspecialchars($miEntrega['contenido_texto']) : '' ?></textarea>
                        </div>
                        <button type="submit" name="enviar_entrega" class="gov-btn gov-btn-primary"><?= $miEntrega ? 'Actualizar Entrega' : 'Entregar Tarea' ?></button>
                        <?php if ($miEntrega): ?>
                            <span class="gov-badge" style="background: #28a745; color: #fff; margin-left: 10px;">Enviado el <?= $miEntrega['entregado_en'] ?></span>
                        <?php endif; ?>
                    </form>

                    <?php if ($miCalificacion): ?>
                        <div style="margin-top: 1.5rem; padding: 1rem; background: #e7f3fe; border-left: 4px solid #2196F3; border-radius: 4px;">
                            <h4 style="margin:0 0 5px 0; color: #0d47a1;">Calificación Obtenida</h4>
                            <p style="margin: 0; font-size: 1.2rem; font-weight: bold;">Nota: <?= htmlspecialchars($miCalificacion['nota']) ?> / <?= htmlspecialchars($tarea['puntaje_maximo']) ?></p>
                            <?php if (!empty($miCalificacion['feedback'])): ?>
                                <p style="margin: 5px 0 0 0;"><strong>Feedback del profesor:</strong> <?= htmlspecialchars($miCalificacion['feedback']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>


            <?php elseif ($userRole === 'docente'): ?>
                

                <details style="margin-bottom: 2rem; background: #f4f4f4; padding: 1rem; border-radius: 4px; border: 1px solid #ddd;">
                    <summary style="cursor: pointer; font-weight: bold;">Editar detalles de esta tarea</summary>
                    <form action="task_view.php?id=<?= $tarea_id ?>" method="POST" style="margin-top: 1rem;">
                        <div class="gov-form-group">
                            <label class="gov-label">Título:</label>
                            <input type="text" name="titulo" class="gov-input" value="<?= htmlspecialchars($tarea['titulo']) ?>" required>
                        </div>
                        <div class="gov-form-group">
                            <label class="gov-label">Descripción:</label>
                            <textarea name="descripcion" class="gov-input" rows="3" required><?= htmlspecialchars($tarea['descripcion']) ?></textarea>
                        </div>
                        <div style="display: flex; gap: 1rem;">
                            <div class="gov-form-group" style="flex: 1;">
                                <label class="gov-label">Fecha Límite:</label>
                                <input type="datetime-local" name="fecha_limite" class="gov-input" value="<?= date('Y-m-d\TH:i', strtotime($tarea['fecha_limite'])) ?>" required>
                            </div>
                            <div class="gov-form-group" style="width: 150px;">
                                <label class="gov-label">Puntaje Máximo:</label>
                                <input type="number" step="0.01" name="puntaje_maximo" class="gov-input" value="<?= htmlspecialchars($tarea['puntaje_maximo']) ?>" required>
                            </div>
                        </div>
                        <button type="submit" name="editar_tarea" class="gov-btn gov-btn-primary">Guardar Cambios</button>
                    </form>
                </details>


                <div class="gov-card" style="border: 1px solid var(--gov-border-color);">
                    <h3>Entregas de los Alumnos</h3>
                    <?php if (count($entregasAlumnos) > 0): ?>
                        <?php foreach ($entregasAlumnos as $ent): ?>
                            <div style="background: #fff; border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
                                <h4 style="margin-top:0; color: #333;"><?= htmlspecialchars($ent['alumno_name']) ?> <span class="gov-hint" style="font-size: 0.85rem;">(Entregado: <?= $ent['entregado_en'] ?>)</span></h4>
                                <p style="background: #fdfdfd; padding: 0.75rem; border: 1px solid #eee;"><?= nl2br(htmlspecialchars($ent['contenido_texto'])) ?></p>

                                <form action="task_view.php?id=<?= $tarea_id ?>" method="POST" style="margin-top: 1rem; display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
                                    <input type="hidden" name="entrega_id" value="<?= $ent['id'] ?>">
                                    <div class="gov-form-group" style="margin-bottom:0; width: 140px;">
                                        <label class="gov-label">Nota (max <?= $tarea['puntaje_maximo'] ?>):</label>
                                        <input type="number" step="0.01" max="<?= $tarea['puntaje_maximo'] ?>" name="nota" class="gov-input" value="<?= $ent['nota'] ?? '' ?>" required>
                                    </div>
                                    <div class="gov-form-group" style="margin-bottom:0; flex: 1; min-width: 200px;">
                                        <label class="gov-label">Feedback / Comentarios:</label>
                                        <input type="text" name="feedback" class="gov-input" value="<?= $ent['feedback'] ?? '' ?>" placeholder="Comentarios para el alumno...">
                                    </div>
                                    <button type="submit" name="calificar_entrega" class="gov-btn gov-btn-primary" style="height: 40px;">Guardar Nota</button>
                                </form>
                                <?php if (isset($ent['nota'])): ?>
                                    <p class="gov-badge" style="background: #28a745; color: #fff; margin-top: 8px; display: inline-block; padding: 3px 8px; border-radius: 3px;">Calificado: <?= $ent['nota'] ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="gov-hint">Ningun alumno ha enviado su entrega para esta tarea todavía.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </main>

</body>
</html>
