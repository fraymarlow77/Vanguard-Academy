<?php
session_start();
include 'db.php';

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm-password'];

    if (empty($nombre) || empty($email) || empty($password)) {
        $error = "Todos los campos son obligatorios.";
    } elseif ($password !== $confirm_password) {
        $error = "Las contraseñas no coinciden.";
    } else {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $sql = "INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nombre, $email, $password_hash]);
            
            header("Location: login.php?success=1");
            exit();
        } catch (\PDOException $e) {
            $error = "Error al registrar el usuario (el email o nombre ya podría estar en uso).";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vanguard - Registro de cuenta</title>
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
            <h1>Registro de cuenta</h1>
            <p>Cree su cuenta oficial para acceder a los servicios de la plataforma.</p>

            <?php if (!empty($error)): ?>
                <div class="gov-alert" style="background-color: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" novalidate>
                <div class="gov-form-group">
                    <label for="nombre" class="gov-label">Nombre de usuario</label>
                    <input type="text" id="nombre" name="nombre" class="gov-input" required autocomplete="name">
                </div>

                <div class="gov-form-group">
                    <label for="email" class="gov-label">Correo electrónico</label>
                    <div class="gov-hint">Utilice su dirección de correo institucional o personal habitual.</div>
                    <input type="email" id="email" name="email" class="gov-input" required autocomplete="email">
                </div>

                <div class="gov-form-group">
                    <label for="password" class="gov-label">Contraseña</label>
                    <div class="gov-hint">Debe tener al menos 8 caracteres.</div>
                    <input type="password" id="password" name="password" class="gov-input" required autocomplete="new-password">
                </div>

                <div class="gov-form-group">
                    <label for="confirm-password" class="gov-label">Confirmar contraseña</label>
                    <input type="password" id="confirm-password" name="confirm-password" class="gov-input" required autocomplete="new-password">
                </div>

                <button type="submit" class="gov-btn gov-btn-primary">Crear cuenta</button>
            </form>

            <p class="gov-register-prompt">
                ¿Ya tiene una cuenta? <a href="login.php">Iniciar sesión</a>
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
