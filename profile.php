<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nuevo_nombre = trim($_POST['nombre']);
    if (!empty($nuevo_nombre)) {
        $stmt = $pdo->prepare("UPDATE usuarios SET nombre = ? WHERE id = ?");
        $stmt->execute([$nuevo_nombre, $usuario_id]);
        $_SESSION['nombre'] = $nuevo_nombre;
        $msg = "Perfil actualizado correctamente.";
    }
}

$userStmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$userStmt->execute([$usuario_id]);
$user = $userStmt->fetch();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vanguard - Profile / Settings</title>
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
                <li class="gov-nav-item"><a href="index.php">INICIO</a></li>
                <li class="gov-nav-item"><a href="classes.php">CLASES</a></li>
                <li class="gov-nav-item"><a href="profile.php" class="active">CONFIGURACION</a></li>
            </ul>
            <div>
                <span style="margin-right: 15px; font-weight: bold;"><?= htmlspecialchars($_SESSION['nombre']) ?></span>
                <a href="logout.php" class="gov-btn-secondary" >Cerrar Sesión</a>
            </div>
            
        </div>
    </nav>

    <main class="gov-container" style="margin-top: 2rem;">
        <div class="gov-card" style="max-width: 500px; margin: 0 auto;">
            <h2>Configuración de Perfil</h2>
            <?php if ($msg): ?>
                <div class="gov-alert" style="background:#d4edda; color:#155724; padding:10px; margin-bottom:15px; border-radius:4px;"><?= $msg ?></div>
            <?php endif; ?>

            <form action="profile.php" method="POST">
                <div class="gov-form-group">
                    <label class="gov-label">Nombre de usuario:</label>
                    <input type="text" name="nombre" class="gov-input" value="<?= htmlspecialchars($user['nombre']) ?>" required>
                </div>
                <div class="gov-form-group">
                    <label class="gov-label">Correo electrónico (no editable):</label>
                    <input type="email" class="gov-input" value="<?= htmlspecialchars($user['email']) ?>" disabled>
                </div>
                <button type="submit" class="gov-btn gov-btn-primary">Guardar Cambios</button>
            </form>
        </div>
    </main>

</body>
</html>
