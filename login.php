<?php
session_start();
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "alumnos";

// Conectar a la BD
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = trim($_POST['usuario']);
    $clave = $_POST['clave'];

    // Buscar usuario en la base de datos
    $stmt = $conn->prepare("SELECT id, usuario, clave, nivel FROM usuarios WHERE usuario = ?");
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        $hash_clave = $row['clave'];

        // Verificar la contraseña
        if (password_verify($clave, $hash_clave)) {
            $_SESSION['usuario'] = $row['usuario'];
            $_SESSION['nivel'] = $row['nivel'];
            header("Location: principal.php");
            exit();
        } else {
            echo "<script>alert('⚠️ Contraseña incorrecta');</script>";
        }
    } else {
        echo "<script>alert('⚠️ Usuario no encontrado');</script>";
    }

    $stmt->close();
}
$conn->close();
?>

