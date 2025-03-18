<?php session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">
    <div class="card shadow p-4 text-center" style="width: 350px;">
        <!-- Logo -->
        <img src="https://i.postimg.cc/HLxFRXqd/1099.jpg" alt="Logo" class="mx-auto d-block mb-3" style="width: 120px; height: auto;">
        
        <h3 class="mb-3">Iniciar Sesión</h3>
        
        <form action="login.php" method="POST">
            <div class="mb-3 text-start">
                <label class="form-label">Usuario</label>
                <input type="text" name="usuario" class="form-control" required>
            </div>
            <div class="mb-3 text-start">
                <label class="form-label">Contraseña</label>
                <input type="password" name="clave" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Ingresar</button>
        </form>
    </div>
</body>
</html>
