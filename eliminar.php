<?php
session_start();
include 'db.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}


$usuario_id = $_SESSION['usuario_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {


    $id_clase = $_POST['curso_id'] ?? null;
    
    if ($id_clase) {
        

        $roleQuery = $pdo->prepare("
            SELECT cu.id AS curso_usuario_id, r.nombre AS rol_nombre 
            FROM curso_usuarios cu
            JOIN roles r ON cu.rol_id = r.id
            WHERE cu.curso_id = ? AND cu.usuario_id = ?
        ");
        $roleQuery->execute([$id_clase, $usuario_id]);
        $userRoleData = $roleQuery->fetch();
        

        if ($userRoleData == 'docente') {
            die("No tienes acceso a esta tarea.");
        } else {
            $eliminarQuery = $pdo->prepare("DELETE FROM cursos WHERE id = ?");
            $eliminarQuery->execute([$id_clase]);
        }


    }
    
    header("Location: classes.php");
    exit();
}
?>