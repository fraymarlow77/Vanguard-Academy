<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['crear_clase'])) {
    $nombre_curso = trim($_POST['nombre_curso']);
    $seccion = trim($_POST['seccion']);
    $materia_id = intval($_POST['materia_id']);
    $division_id = intval($_POST['division_id']);
    $color = $_POST['color'] ?? '#1a73e8';
    
    $codigo_acceso = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO cursos (nombre, seccion, codigo_acceso, materia_id, division_id, color, creador_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$nombre_curso, $seccion, $codigo_acceso, $materia_id, $division_id, $color, $usuario_id]);
        $curso_id = $pdo->lastInsertId();

        $rolDocenteQuery = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'docente'");
        $rolDocenteQuery->execute();
        $rolDocente = $rolDocenteQuery->fetch();

        if ($rolDocente) {
            $cuStmt = $pdo->prepare("INSERT INTO curso_usuarios (curso_id, usuario_id, rol_id) VALUES (?, ?, ?)");
            $cuStmt->execute([$curso_id, $usuario_id, $rolDocente['id']]);
        }

        $pdo->commit();
        $mensaje = "¡Clase creada con éxito! Código de acceso: <strong>$codigo_acceso</strong>";
    } catch (\PDOException $e) {
        $pdo->rollBack();
        $mensaje = "Error al crear la clase.";
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['unirse_clase'])) {
    $codigo_ingresado = trim($_POST['codigo_acceso']);

    $cursoQuery = $pdo->prepare("SELECT id FROM cursos WHERE codigo_acceso = ?");
    $cursoQuery->execute([$codigo_ingresado]);
    $cursoEncontrado = $cursoQuery->fetch();

    if ($cursoEncontrado) {
        $curso_id = $cursoEncontrado['id'];
        
        $rolAlumnoQuery = $pdo->prepare("SELECT id FROM roles WHERE nombre = 'alumno'");
        $rolAlumnoQuery->execute();
        $rolAlumno = $rolAlumnoQuery->fetch();

        try {
            $joinStmt = $pdo->prepare("INSERT IGNORE INTO curso_usuarios (curso_id, usuario_id, rol_id) VALUES (?, ?, ?)");
            $joinStmt->execute([$curso_id, $usuario_id, $rolAlumno['id']]);
            $mensaje = "¡Te has unido a la clase exitosamente!";
        } catch (\PDOException $e) {
            $mensaje = "Error al unirse a la clase.";
        }
    } else {
        $mensaje = "El código de acceso es inválido.";
    }
}

$materias = $pdo->query("SELECT * FROM materias")->fetchAll();
$divisiones = $pdo->query("SELECT d.*, a.nombre AS anio_nombre, e.nombre AS esp_nombre FROM divisiones d JOIN anios a ON d.anio_id = a.id JOIN especialidades e ON d.especialidad_id = e.id")->fetchAll();

$coursesQuery = $pdo->prepare("
    SELECT c.*, m.nombre AS materia_nombre, r.nombre AS rol_nombre 
    FROM cursos c
    JOIN curso_usuarios cu ON c.id = cu.curso_id
    JOIN materias m ON c.materia_id = m.id
    JOIN roles r ON cu.rol_id = r.id
    WHERE cu.usuario_id = ?
");
$coursesQuery->execute([$usuario_id]);
$courses = $coursesQuery->fetchAll();

$tasksQuery = $pdo->prepare("
    SELECT t.*, c.nombre AS curso_nombre, m.nombre AS materia_nombre
    FROM tareas t
    JOIN curso_usuarios cu ON t.curso_id = cu.curso_id
    JOIN cursos c ON t.curso_id = c.id
    JOIN materias m ON c.materia_id = m.id
    WHERE cu.usuario_id = ? AND t.fecha_limite >= NOW()
    ORDER BY t.fecha_limite ASC
");
$tasksQuery->execute([$usuario_id]);
$tasks = $tasksQuery->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vanguard - Dashboard</title>
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
        <div class="gov-container" style="display: flex; justify-content: space-between; align-items: center;">
            <ul class="gov-nav-list" style="margin: 0; display: flex; list-style: none; gap: 1rem;">
                <li class="gov-nav-item"><a href="index.php" class="active">INICIO</a></li>
                <li class="gov-nav-item"><a href="classes.php">CLASES</a></li>
                <li class="gov-nav-item"><a href="profile.php">CONFIGURACION</a></li>
            </ul>
            <div>
                <span style="margin-right: 15px; font-weight: bold;"><?= htmlspecialchars($_SESSION['nombre']) ?></span>
                <a href="logout.php" class="gov-btn-secondary" >Cerrar Sesión</a>
            </div>
        </div>
    </nav>

    <main class="gov-container" style="margin-top: 1rem; padding: 70px; display: flow-root;">
        
        <?php if (!empty($mensaje)): ?>
            <div class="gov-alert gov-alert-info" style="margin-bottom: 1.5rem; background: #e2f0d9; color: #385723; padding: 1rem; border-radius: 4px;">
                <?= $mensaje ?>
            </div>
        <?php endif; ?>

        <div style="display: flex; gap: 1rem; margin-bottom: 2rem;">
            <button onclick="document.getElementById('modal-crear').style.display='block'" class="gov-btn gov-btn-primary">+ Crear Clase</button>
            <button onclick="document.getElementById('modal-unirse').style.display='block'" class="gov-btn gov-btn-secondary">Unirse con Código</button>
        </div>


        <div id="modal-crear" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
            <div class="gov-card" style="max-width: 500px; margin: 50px auto; background: #fff; position: relative;">
                <h3>Crear Nueva Clase</h3>
                <form action="index.php" method="POST">
                    <div class="gov-form-group">
                        <label class="gov-label">Nombre del Curso (ej. Comisión 4to A):</label>
                        <input type="text" name="nombre_curso" class="gov-input" required>
                    </div>
                    <div class="gov-form-group">
                        <label class="gov-label">Sección:</label>
                        <input type="text" name="seccion" class="gov-input">
                    </div>
                    <div class="gov-form-group">
                        <label class="gov-label">Materia:</label>
                        <select name="materia_id" class="gov-input" required>
                            <?php foreach ($materias as $mat): ?>
                                <option value="<?= $mat['id'] ?>"><?= htmlspecialchars($mat['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="gov-form-group">
                        <label class="gov-label">División:</label>
                        <select name="division_id" class="gov-input" required>
                            <?php foreach ($divisiones as $div): ?>
                                <option value="<?= $div['id'] ?>"><?= htmlspecialchars($div['anio_nombre'] . ' - ' . $div['esp_nombre'] . ' (' . $div['nombre'] . ')') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="gov-form-group">
                        <label class="gov-label">Color de Tarjeta:</label>
                        <input type="color" name="color" value="#1a73e8" style="width:100%; height:40px;">
                    </div>
                    <button type="submit" name="crear_clase" class="gov-btn gov-btn-primary">Guardar Clase</button>
                    <button type="button" onclick="document.getElementById('modal-crear').style.display='none'" class="gov-btn gov-btn-secondary" style="margin-top:5px;">Cancelar</button>
                </form>
            </div>
        </div>


        <div id="modal-unirse" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000;">
            <div class="gov-card" style="max-width: 400px; margin: 100px auto; background: #fff;">
                <h3>Unirse a Clase</h3>
                <form action="index.php" method="POST">
                    <div class="gov-form-group">
                        <label class="gov-label">Código de Acceso:</label>
                        <input type="text" name="codigo_acceso" class="gov-input" required placeholder="Ej. 8F4B2C">
                    </div>
                    <button type="submit" name="unirse_clase" class="gov-btn gov-btn-primary">Unirse</button>
                    <button type="button" onclick="document.getElementById('modal-unirse').style.display='none'" class="gov-btn gov-btn-secondary" style="margin-top:5px;">Cancelar</button>
                </form>
            </div>
        </div>

        <section class="gov-card">
            <h2 class="gov-card-title">Mis Clases</h2>
            <div class="gov-grid">
                <?php if (count($courses) > 0): ?>
                    <?php foreach ($courses as $course): ?>
                        <article class="gov-card" style="border-left: 5px solid <?= htmlspecialchars($course['color']) ?>;">
                            <h3><a href="classes.php?curso_id=<?= $course['id'] ?>" style="text-decoration:none; color:inherit;"><?= htmlspecialchars($course['materia_nombre']) ?></a></h3>
                            <p class="gov-hint"><?= htmlspecialchars($course['nombre']) ?> (Rol: <?= ucfirst($course['rol_nombre']) ?>)</p>
                            <div class="gov-alert gov-alert-info">
                                Código: <strong><?= htmlspecialchars($course['codigo_acceso']) ?></strong>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No estás inscrito en ninguna clase todavía. ¡Crea una o únete con un código!</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="gov-card" style="margin-top: 2rem;">
            <h2 class="gov-card-title">Próximas Tareas</h2>
            <div class="gov-table-wrapper">
                <table class="gov-table">
                    <thead>
                        <tr>
                            <th>Materia</th>
                            <th>Tarea</th>
                            <th>Fecha Límite</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($tasks) > 0): ?>
                            <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td><?= htmlspecialchars($task['materia_nombre']) ?></td>
                                    <td><a href="task_view.php?id=<?= $task['id'] ?>"><?= htmlspecialchars($task['titulo']) ?></a></td>
                                    <td><span class="gov-badge gov-badge-primary"><?= htmlspecialchars($task['fecha_limite']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3">No hay tareas pendientes próximas.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>

</body>
</html>
