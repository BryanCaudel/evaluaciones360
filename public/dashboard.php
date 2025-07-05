<?php 
session_start();
require_once '../src/config/database.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['rol'], ['super_admin', 'admin_empresa'])) {
    header("Location: index.php");
    exit;
}

$usuario = $_SESSION['user'];
$rol = $_SESSION['rol'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['empresa_id'])) $_SESSION['empresa_id'] = $_POST['empresa_id'];
    if (isset($_POST['modo'])) $_SESSION['modo'] = $_POST['modo'];
}

$empresa_id = $rol === 'admin_empresa' ? $_SESSION['empresa_id'] : ($_SESSION['empresa_id'] ?? null);
$modo = $_SESSION['modo'] ?? 'usuarios';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
<header>
    <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
    <h1>Evaluaciones 360°</h1>
</header>

<div class="sidebar" id="sidebar">
    <div class="usuario">
        Bienvenido, <?= htmlspecialchars($usuario) ?>
        <span><?= htmlspecialchars($rol) ?></span>
    </div>
    <a class="button" href="agregar_usuario.php">+ Añadir Usuario</a>
    <a class="button" href="programar_evaluacion.php">+ Programar Evaluación</a>
    <?php if ($rol === 'super_admin'): ?>
        <a class="button" href="agregar_empresa.php">+ Agregar Empresa</a>
    <?php endif; ?>
    <?php if ($empresa_id): ?>
        <form method="POST">
            <input type="hidden" name="modo" value="<?= $modo === 'usuarios' ? 'evaluaciones' : 'usuarios' ?>">
            <button class="button" type="submit">
                <?= $modo === 'usuarios' ? 'Ver Evaluaciones' : 'Ver Usuarios' ?>
            </button>
        </form>
    <?php endif; ?>
    <a class="button logout" href="logout.php">Cerrar Sesión</a>
</div>

<main>
<?php if ($rol === 'super_admin'): ?>
    <form method="POST" class="empresa-form">
        <label>Selecciona una empresa:</label>
        <select name="empresa_id" onchange="this.form.submit()" required>
            <option value="">--Selecciona--</option>
            <?php
            $empresas = $conexion->query("SELECT id, nombre FROM empresas");
            while ($e = $empresas->fetch_assoc()):
            ?>
                <option value="<?= $e['id'] ?>" <?= ($empresa_id == $e['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($e['nombre']) ?>
                </option>
            <?php endwhile; ?>
        </select>
        <input type="hidden" name="modo" value="<?= $modo ?>">
    </form>
<?php endif; ?>

<?php if ($empresa_id): ?>
    <?php if ($modo === 'usuarios'): ?>
        <h2>Usuarios de la Empresa</h2>
        <table>
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Código Evaluación</th>
                    <th>Evaluaciones</th>
                    <th>Gráfico</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $stmt = $conexion->prepare("
                SELECT u.id AS usuario_id, u.user, r.nombre AS rol, e.id AS evaluacion_id, e.codigo_unico
                FROM usuarios u
                INNER JOIN roles r ON u.rol_id = r.id
                LEFT JOIN evaluaciones e ON u.id = e.evaluado_id
                WHERE u.empresa_id = ?
                ORDER BY FIELD(r.nombre, 'admin_empresa', 'evaluado', 'super_admin') DESC
            ");
            $stmt->bind_param("i", $empresa_id);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()):
                $total_eval = 0;
                $nombres = [];

                if ($row['evaluacion_id']) {
                    $q = $conexion->prepare("SELECT evaluador_nombre FROM respuestas WHERE evaluacion_id = ? AND relacion != 'Autoevaluación' GROUP BY evaluador_nombre, relacion");
                    $q->bind_param("i", $row['evaluacion_id']);
                    $q->execute();
                    $res = $q->get_result();
                    while ($r = $res->fetch_assoc()) {
                        $nombres[] = $r['evaluador_nombre'];
                    }

                    $q2 = $conexion->prepare("SELECT 1 FROM autoevaluaciones WHERE evaluacion_id = ? LIMIT 1");
                    $q2->bind_param("i", $row['evaluacion_id']);
                    $q2->execute();
                    if ($q2->get_result()->num_rows > 0) {
                        $nombres[] = "Autoevaluación";
                    }

                    $total_eval = count($nombres);
                }
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['user']) ?></td>
                    <td><?= htmlspecialchars($row['rol']) ?></td>
                    <td>
                        <?php if ($row['rol'] === 'evaluado'): ?>
                            <?php if ($row['codigo_unico']): ?>
                                <?= htmlspecialchars($row['codigo_unico']) ?>
                            <?php else: ?>
                                <form method="POST" action="programar_evaluacion.php">
                                    <input type="hidden" name="preselect_user" value="<?= $row['usuario_id'] ?>">
                                    <button class="btn-asignar" type="submit">Asignar Evaluación</button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($rol === 'super_admin' && $total_eval > 0): ?>
                            <span class="ver-evaluadores" data-nombres="<?= htmlspecialchars(implode(', ', $nombres)) ?>">
                                <?= $total_eval ?>
                            </span>
                        <?php else: ?>
                            <?= $total_eval ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($row['rol'] === 'evaluado' && $total_eval > 0): ?>
                            <form method="POST" action="grafico_resultados.php">
                                <input type="hidden" name="evaluacion_id" value="<?= $row['evaluacion_id'] ?>">
                                <button class="btn-ver" type="submit">Ver gráfico</button>
                            </form>
                        <?php else: ?>
                            <em>No disponible</em>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" action="editar_usuario.php">
                            <input type="hidden" name="usuario_id" value="<?= $row['usuario_id'] ?>">
                            <button class="btn-editar" type="submit">Editar</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
        <div id="flotante" class="flotante" style="display:none;"></div>
    <?php else: ?>
        <h2>Evaluaciones Programadas</h2>
        <table>
            <thead>
                <tr>
                    <th>Evaluado</th>
                    <th>Fecha</th>
                    <th>Código</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $stmt = $conexion->prepare("SELECT e.id, e.nombre_evaluado, e.fecha, e.codigo_unico FROM evaluaciones e WHERE e.empresa_id = ? ORDER BY e.fecha DESC");
            $stmt->bind_param("i", $empresa_id);
            $stmt->execute();
            $res = $stmt->get_result();

            while ($row = $res->fetch_assoc()):
                // Validar si hay respuestas o autoevaluaciones
                $hay_datos = false;

                $q1 = $conexion->prepare("SELECT 1 FROM respuestas WHERE evaluacion_id = ? LIMIT 1");
                $q1->bind_param("i", $row['id']);
                $q1->execute();
                if ($q1->get_result()->num_rows > 0) $hay_datos = true;

                $q2 = $conexion->prepare("SELECT 1 FROM autoevaluaciones WHERE evaluacion_id = ? LIMIT 1");
                $q2->bind_param("i", $row['id']);
                $q2->execute();
                if ($q2->get_result()->num_rows > 0) $hay_datos = true;
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['nombre_evaluado']) ?></td>
                    <td>
                        <form method="POST" action="actualizar_fecha.php" style="display:inline;">
                            <input type="hidden" name="evaluacion_id" value="<?= $row['id'] ?>">
                            <input type="date" name="nueva_fecha" value="<?= $row['fecha'] ?>" required>
                            <button type="submit" class="btn-guardar">Guardar</button>
                        </form>
                    </td>
                    <td><?= htmlspecialchars($row['codigo_unico']) ?></td>
                    <td>
                        <?php if ($hay_datos): ?>
                            <form class="btn-ver" method="POST" action="grafico_resultados.php" style="display:inline;">
                                <input type="hidden" name="evaluacion_id" value="<?= $row['id'] ?>">
                                <button class="btn-ver" type="submit">Ver gráfico</button>
                            </form>
                        <?php else: ?>
                            <em>No disponible</em>
                        <?php endif; ?>
                        <form method="POST" action="cancelar_evaluacion.php" style="display:inline;" onsubmit="return confirm('¿Cancelar esta evaluación?')">
                            <input type="hidden" name="id" value="<?= $row['id'] ?>">
                            <button class="btn-cancelar" type="submit">Cancelar</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table>

        <div class="codigo-temp-box">
            <h3>Códigos Temporales Activos</h3>
            <table>
                <thead>
                    <tr>
                        <th>Nombre Evaluador</th>
                        <th>Relación</th>
                        <th>Evaluado</th>
                        <th>Código</th>
                        <th>Fecha y Hora de Creación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $query = "
                        SELECT s.codigo_temp, s.nombre, s.relacion, s.creado_en, u.user AS evaluado
                        FROM sesiones_evaluadores s
                        INNER JOIN evaluaciones e ON s.evaluacion_id = e.id
                        LEFT JOIN usuarios u ON e.evaluado_id = u.id
                        LEFT JOIN evaluacion_preguntas ep ON s.evaluacion_id = ep.evaluacion_id
                        LEFT JOIN (
                            SELECT evaluacion_id, evaluacion_pregunta_id, evaluador_nombre, relacion
                            FROM respuestas
                        ) r ON ep.evaluacion_id = r.evaluacion_id AND ep.id = r.evaluacion_pregunta_id AND s.nombre = r.evaluador_nombre AND s.relacion = r.relacion
                        LEFT JOIN (
                            SELECT evaluacion_id, evaluacion_pregunta_id
                            FROM autoevaluaciones
                        ) a ON ep.evaluacion_id = a.evaluacion_id AND ep.id = a.evaluacion_pregunta_id AND s.relacion = 'Autoevaluación'
                        WHERE s.evaluacion_id IN (
                            SELECT id FROM evaluaciones WHERE empresa_id = ?
                        )
                        GROUP BY s.codigo_temp, s.nombre, s.relacion, s.creado_en, u.user
                        HAVING COUNT(ep.id) > (
                            CASE 
                                WHEN s.relacion = 'Autoevaluación' THEN COUNT(a.evaluacion_pregunta_id)
                                ELSE COUNT(r.evaluacion_pregunta_id)
                            END
                        )
                    ";

                    $temp = $conexion->prepare($query);
                    $temp->bind_param("i", $empresa_id);
                    $temp->execute();
                    $resTemp = $temp->get_result();
                    while ($row = $resTemp->fetch_assoc()):
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($row['nombre']) ?></td>
                            <td><?= htmlspecialchars($row['relacion']) ?></td>
                            <td><?= htmlspecialchars($row['evaluado']) ?: '<em>Desconocido</em>' ?></td>
                            <td><?= htmlspecialchars($row['codigo_temp']) ?></td>
                            <td><?= date("d/m/Y H:i", strtotime($row['creado_en'])) ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php else: ?>
    <p><strong>Selecciona una empresa para ver los datos.</strong></p>
<?php endif; ?>
</main>

<script>
function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    const toggle = document.querySelector(".menu-toggle");
    sidebar.classList.toggle("open");
    toggle.classList.toggle("moved");
}

document.addEventListener("DOMContentLoaded", () => {
    document.querySelectorAll('.ver-evaluadores').forEach(el => {
        el.addEventListener('click', () => {
            const box = document.getElementById('flotante');
            box.innerText = el.dataset.nombres;
            box.style.display = 'block';
            box.style.top = el.getBoundingClientRect().top + window.scrollY + 30 + "px";
            box.style.left = el.getBoundingClientRect().left + "px";
        });
    });

    document.addEventListener("click", function(e) {
        const flotante = document.getElementById("flotante");
        if (!e.target.classList.contains("ver-evaluadores") && e.target.id !== "flotante") {
            flotante.style.display = "none";
        }
    });
});
</script>
</body>
</html>
