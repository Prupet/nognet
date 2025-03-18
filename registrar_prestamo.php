<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "alumnos";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Función para agregar préstamos de múltiples equipos
function agregarPrestamo($conn, $alumno_id, $equipos, $fecha_prestamo, $fecha_devolucion) {
    $conn->begin_transaction(); // Iniciar transacción

    try {
        foreach ($equipos as $equipo) {
            // Registrar el préstamo para cada equipo
            $stmt = $conn->prepare("INSERT INTO prestamos (alumno_id, equipo, fecha_prestamo, fecha_devolucion) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $alumno_id, $equipo, $fecha_prestamo, $fecha_devolucion);
            
            if (!$stmt->execute()) {
                throw new Exception("Error al agregar el préstamo para el equipo: " . $equipo . " - " . $stmt->error);
            }

            // Actualizar el estado del equipo a "no disponible"
            $stmt_update = $conn->prepare("UPDATE equipos SET disponible = FALSE WHERE nombre = ?");
            $stmt_update->bind_param("s", $equipo);
            if (!$stmt_update->execute()) {
                throw new Exception("Error al actualizar el estado del equipo: " . $equipo . " - " . $stmt_update->error);
            }

            $stmt->close();
            $stmt_update->close();
        }

        $conn->commit(); // Confirmar transacción
        return true;
    } catch (Exception $e) {
        $conn->rollback(); // Revertir transacción en caso de error
        return $e->getMessage();
    }
}

// Procesar el formulario de registrar préstamo
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $alumno_id = intval($_POST['alumno_id']);
    $equipos = $_POST['equipo']; // Array de equipos seleccionados
    $fecha_prestamo = date('Y-m-d H:i:s'); // Fecha actual (formato: yyyy-mm-dd hh:mm:ss)
    $fecha_devolucion = htmlspecialchars($_POST['fecha_devolucion']); // Fecha de devolución del formulario

    $resultado = agregarPrestamo($conn, $alumno_id, $equipos, $fecha_prestamo, $fecha_devolucion);

    if ($resultado === true) {
        $cantidad_equipos = count($equipos);
        echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>
        Se registraron " . $cantidad_equipos . " préstamo(s) correctamente.
        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
        " . $resultado . "
        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
        </div>";
    }
}

// Procesar la devolución de un préstamo
if (isset($_GET['devolver'])) {
    $prestamo_id = intval($_GET['devolver']);
    
    // Marcar el préstamo como devuelto
    $stmt = $conn->prepare("UPDATE prestamos SET devuelto = TRUE WHERE id = ?");
    $stmt->bind_param("i", $prestamo_id);
    $stmt->execute();
    
    // Obtener el equipo asociado al préstamo
    $sql_equipo = "SELECT equipo FROM prestamos WHERE id = ?";
    $stmt_equipo = $conn->prepare($sql_equipo);
    $stmt_equipo->bind_param("i", $prestamo_id);
    $stmt_equipo->execute();
    $result_equipo = $stmt_equipo->get_result();
    $row_equipo = $result_equipo->fetch_assoc();
    $equipo = $row_equipo['equipo'];
    
    // Actualizar el estado del equipo a "disponible"
    $stmt_update = $conn->prepare("UPDATE equipos SET disponible = TRUE WHERE nombre = ?");
    $stmt_update->bind_param("s", $equipo);
    $stmt_update->execute();
    
    // Redirigir para evitar reenvío del formulario
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Registrar préstamo de equipo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        #equipos-seleccionados {
            background-color: #f8f9fa;
        }
        .btn-sm {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="container mt-4">
    <h2 class="text-center"><i class="fa-solid fa-laptop"></i> Registrar préstamo de equipo</h2>
    
    <form method="POST" class="mb-4">
        <div class="mb-3">
            <label for="alumno_id" class="form-label">Seleccionar alumno</label>
            <select name="alumno_id" class="form-control" required>
                <option value="">Selecciona un alumno</option>
                <?php
                // Obtener todos los alumnos
                $sql_alumnos = "SELECT * FROM alumnos";
                $result_alumnos = $conn->query($sql_alumnos);

                if ($result_alumnos->num_rows > 0) {
                    while ($row = $result_alumnos->fetch_assoc()) {
                        echo "<option value='" . $row['id'] . "'>" . htmlspecialchars($row['nombre']) . "</option>";
                    }
                } else {
                    echo "<option value=''>No hay alumnos registrados</option>";
                }
                ?>
            </select>
        </div>
        
        <div class="mb-3">
            <label class="form-label">Seleccionar equipo(s)</label>
            <div class="input-group">
                <select class="form-select" id="lista-equipos">
                    <option value="">Selecciona un equipo</option>
                    <?php
                    // Obtener todos los equipos disponibles
                    $sql_equipos = "SELECT * FROM equipos WHERE disponible = TRUE";
                    $result_equipos = $conn->query($sql_equipos);

                    if ($result_equipos->num_rows > 0) {
                        while ($row = $result_equipos->fetch_assoc()) {
                            echo '<option value="' . $row['nombre'] . '">' . htmlspecialchars($row['nombre']) . ' (Carro: ' . htmlspecialchars($row['numero_carro']) . ')</option>';
                        }
                    } else {
                        echo '<option value="">No hay equipos disponibles</option>';
                    }
                    ?>
                </select>
                <button type="button" class="btn btn-primary" onclick="agregarEquipo()">Agregar</button>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Equipos seleccionados</label>
            <div id="equipos-seleccionados" class="border p-2" style="min-height: 50px; border-radius: 5px;">
                <!-- Aquí se mostrarán los equipos seleccionados -->
            </div>
        </div>

        <div class="mb-3">
            <label for="fecha_devolucion" class="form-label">Fecha de devolución</label>
            <input type="datetime-local" class="form-control" name="fecha_devolucion" required>
        </div>
        
        <button type="submit" class="btn btn-success"><i class="fa-solid fa-plus"></i> Registrar préstamo</button>
    </form>

    <hr>
    <h3 class="text-center">Préstamos registrados hoy</h3>
    <table class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th><i class="fa-solid fa-id-card"></i> ID</th>
                <th><i class="fa-solid fa-user"></i> Alumno</th>
                <th><i class="fa-solid fa-laptop"></i> Equipo</th>
                <th><i class="fa-solid fa-desktop"></i> Número de Carro</th>
                <th><i class="fa-solid fa-calendar-alt"></i> Fecha de préstamo</th>
                <th><i class="fa-solid fa-calendar-check"></i> Fecha de devolución</th>
                <th><i class="fa-solid fa-rotate-left"></i> Devolución</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Consultar los registros de los préstamos realizados hoy
            $sql_prestamos = "SELECT prestamos.*, alumnos.nombre AS alumno_nombre, equipos.numero_carro 
                              FROM prestamos
                              JOIN alumnos ON prestamos.alumno_id = alumnos.id
                              JOIN equipos ON prestamos.equipo = equipos.nombre
                              WHERE DATE(prestamos.fecha_prestamo) = CURDATE()"; // Filtro por la fecha actual
            $result_prestamos = $conn->query($sql_prestamos);

            if ($result_prestamos->num_rows > 0) {
                while ($row = $result_prestamos->fetch_assoc()) {
                    echo "<tr>
                        <td>" . htmlspecialchars($row['id']) . "</td>
                        <td>" . htmlspecialchars($row['alumno_nombre']) . "</td>
                        <td>" . htmlspecialchars($row['equipo']) . "</td>
                        <td>" . htmlspecialchars($row['numero_carro']) . "</td>
                        <td>" . htmlspecialchars($row['fecha_prestamo']) . "</td>
                        <td>" . htmlspecialchars($row['fecha_devolucion']) . "</td>
                        <td>";
                    if (!$row['devuelto']) {
                        echo "<a href='?devolver=" . $row['id'] . "' class='btn btn-warning btn-sm'>Devolver</a>";
                    } else {
                        echo "Devuelto";
                    }
                    echo "</td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='7' class='text-center'>No hay préstamos registrados hoy</td></tr>";
            }
            ?>
        </tbody>
    </table>

    <a href="principal.php" class="btn btn-primary"><i class="fa-solid fa-arrow-left"></i> Regresar</a>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function agregarEquipo() {
            const lista = document.getElementById('lista-equipos');
            const equipoSeleccionado = lista.value;
            const nombreEquipo = lista.options[lista.selectedIndex].text;

            if (equipoSeleccionado) {
                // Crear un nuevo elemento para el equipo seleccionado
                const nuevoEquipo = document.createElement('div');
                nuevoEquipo.className = 'd-flex justify-content-between align-items-center mb-2';
                nuevoEquipo.innerHTML = `
                    <span>${nombreEquipo}</span>
                    <button type="button" class="btn btn-danger btn-sm" onclick="eliminarEquipo(this)">Eliminar</button>
                    <input type="hidden" name="equipo[]" value="${equipoSeleccionado}">
                `;

                // Agregar el equipo al área de selección
                document.getElementById('equipos-seleccionados').appendChild(nuevoEquipo);

                // Deshabilitar la opción en la lista desplegable
                lista.options[lista.selectedIndex].disabled = true;
                lista.selectedIndex = 0; // Reiniciar la selección
            }
        }

        function eliminarEquipo(boton) {
            const equipo = boton.closest('div');
            const nombreEquipo = equipo.querySelector('input').value;

            // Habilitar la opción en la lista desplegable
            const lista = document.getElementById('lista-equipos');
            for (let option of lista.options) {
                if (option.value === nombreEquipo) {
                    option.disabled = false;
                    break;
                }
            }

            // Eliminar el equipo del área de selección
            equipo.remove();
        }
    </script>
</body>
</html>

<?php
$conn->close();
?>