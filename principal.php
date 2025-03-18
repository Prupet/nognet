<?php
session_start();

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: index.php");
    exit();
}

// Configuración de la base de datos
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "alumnos";

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Obtener nivel de acceso del usuario
$usuario = $_SESSION['usuario'];
$nivel = $_SESSION['nivel']; // 1 = Admin, 2 = Moderador, 3 = Usuario

// Generar token CSRF si no existe
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Buscar personas
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Consulta SQL con seguridad contra inyección
if (!empty($search)) {
    $stmt = $conn->prepare("SELECT * FROM alumnos WHERE LOWER(nombre) LIKE LOWER(?)");
    $searchParam = "%$search%";
    $stmt->bind_param("s", $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $sql = "SELECT * FROM alumnos";
    $result = $conn->query($sql);
}

// Función para agregar alumno (solo Admin y Moderador)
function agregarAlumno($conn, $nombre, $curso, $dni) {
    if (empty($nombre) || empty($curso) || empty($dni)) {
        return "Todos los campos son obligatorios.";
    }

    $stmt = $conn->prepare("INSERT INTO alumnos (nombre, curso, dni) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $nombre, $curso, $dni);

    if ($stmt->execute()) {
        return true;
    } else {
        return "Error al agregar alumno: " . $stmt->error;
    }
}

// Procesar formulario de agregar alumno (solo si el nivel lo permite)
if ($_SERVER["REQUEST_METHOD"] == "POST" && ($nivel == 1 || $nivel == 2)) {
    // Validar token CSRF
    if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Token CSRF inválido.");
    }

    $nombre = $_POST['nombre'];
    $curso = $_POST['curso'];
    $dni = $_POST['dni'];

    $resultado = agregarAlumno($conn, $nombre, $curso, $dni);
    if ($resultado === true) {
        echo "<script>alert('Alumno agregado correctamente'); window.location='principal.php';</script>";
    } else {
        echo "<script>alert('$resultado');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Informática Nogués</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2><i class="fa-solid fa-users"></i> Alumnos</h2>
        <a href="logout.php" class="btn btn-danger"><i class="fa-solid fa-sign-out-alt"></i> Cerrar sesión</a>
    </div>

    <p><strong>Bienvenido, <?php echo htmlspecialchars($usuario); ?> (Nivel <?php echo $nivel; ?>)</strong></p>

    <!-- Formulario de búsqueda -->
    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" class="form-control" name="search" placeholder="Buscar por nombre" value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-search"></i> Buscar</button>
        </div>
    </form>
    
    <!-- Tabla de alumnos -->
    <table class="table table-striped table-bordered">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Curso</th>
                <th>DNI</th>
                <th>Ver</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>" . htmlspecialchars($row['id']) . "</td>
                        <td>" . htmlspecialchars($row['nombre']) . "</td>
                        <td>" . htmlspecialchars($row['curso']) . "</td>
                        <td>" . htmlspecialchars($row['dni']) . "</td>
                        <td><a href='ver_persona.php?id=" . $row['id'] . "' class='btn btn-info'><i class='fa-solid fa-eye'></i> Ver</a></td>
                    </tr>";
                }
            } else {
                echo "<tr><td colspan='5' class='text-center'>No hay alumnos registrados</td></tr>";
            }
            ?>
        </tbody>
    </table>
    
    <!-- Contenedor para el formulario y la imagen -->
    <div class="row mt-4">
        <!-- Formulario para agregar alumno (izquierda) -->
        <?php if ($nivel == 1 || $nivel == 2): ?>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="card-title mb-0"><i class="fa-solid fa-user-plus"></i> Agregar Alumno</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" required placeholder="Nombre">
                            </div>
                            <div class="mb-3">
                                <label for="curso" class="form-label">Curso</label>
                                <input type="text" class="form-control" id="curso" name="curso" required placeholder="Curso">
                            </div>
                            <div class="mb-3">
                                <label for="dni" class="form-label">DNI</label>
                                <input type="text" class="form-control" id="dni" name="dni" required placeholder="DNI">
                            </div>
                            <button type="submit" class="btn btn-success w-100"><i class="fa-solid fa-plus"></i> Agregar</button>
                        </form>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="col-md-6">
                <p class="text-danger">No tienes permiso para agregar alumnos.</p>
            </div>
        <?php endif; ?>

        <!-- Imagen (derecha) -->
        <div class="col-md-6">
            <div class="text-center">
                <img src="https://i.postimg.cc/HLxFRXqd/1099.jpg/500x300" alt="Imagen de ejemplo" class="img-fluid rounded">
                <p class="mt-2"></p>
            </div>
        </div>
    </div>

    <!-- Botón para registrar préstamo de equipo -->
    <div class="text-center mt-4">
        <a href="registrar_prestamo.php" class="btn btn-warning"><i class="fa-solid fa-laptop"></i> Registrar préstamo de equipo</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>