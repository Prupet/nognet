<?php
$conn = new mysqli("localhost", "root", "", "alumnos");
if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}

$usuario = "REGRicardo";
$clave = password_hash("ppllnn001", PASSWORD_DEFAULT);
$nivel = 1;

$stmt = $conn->prepare("INSERT INTO usuarios (usuario, clave, nivel) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $usuario, $clave, $nivel);

if ($stmt->execute()) {
    echo "Usuario creado correctamente";
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>
