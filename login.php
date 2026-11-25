<?php
session_start();
include 'db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $contraseña = $_POST['password'];

    if (!empty($nombre) && !empty($contraseña)) {
        $sql = "SELECT * FROM usuarios WHERE nombre = ? OR email = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$nombre, $nombre]);
        $user = $stmt->fetch();

        if ($user && password_verify($contraseña, $user['password_hash'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['nombre'] = $user['nombre'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Nombre de usuario o contraseña incorrectos.";
        }
    } else {
        $error = "Por favor, complete todos los campos.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vanguard - Iniciar Sesión</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>

    <header class="gov-header">
        <div class="gov-container">
            <a href="#" class="gov-header-brand">
                <div>
                    <h1 class="gov-header-title gov-brand-title">Vanguard</h1>
                    <div class="gov-header-subtitle">Portal Oficial Educativo "Vanguard"</div>
                </div>
            </a>
        </div>
    </header>

    <main class="gov-container gov-main-spacing">
        <div class="gov-card">
            <h1>Iniciar sesión</h1>
            <p>Ingrese sus credenciales de acceso para entrar al portal educativo.</p>

            <?php if (!empty($error)): ?>
                <div class="gov-alert" style="background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="gov-alert" style="background-color: #d4edda; color: #155724; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                    ¡Cuenta creada con éxito! Ya puede iniciar sesión.
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" novalidate>
                <div class="gov-form-group">
                    <label for="nombre" class="gov-label">Nombre de usuario o Correo</label>
                    <div class="gov-hint">Utilice su nombre de usuario o email registrado.</div>
                    <input type="text" id="nombre" name="nombre" class="gov-input" required autocomplete="username">
                </div>

                <div class="gov-form-group">
                    <label for="password" class="gov-label">Contraseña</label>
                    <input type="password" id="password" name="password" class="gov-input" required autocomplete="current-password">
                </div>

                <button type="submit" class="gov-btn gov-btn-primary">Iniciar sesión</button>
            </form>

            <p class="gov-register-prompt">
                ¿No tiene una cuenta? <a href="register.php">Regístrese aquí</a>
            </p>
        </div>
    </main>

    <footer class="gov-footer">
        <div class="gov-container">
            <p>&copy; 2026 Vanguard. Todos los derechos reservados.</p>
        </div>
    </footer>

</body>
</html>
