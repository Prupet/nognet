<?php
require 'conexion.php';

if (isset($_GET['alumno_id'])) {
    $stmt = $conn->prepare("SELECT tipo_usuario FROM alumnos WHERE id = ?");
    $stmt->bind_param("i", $_GET['alumno_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    echo json_encode($data);
}
?>
