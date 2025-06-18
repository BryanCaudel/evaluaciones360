<?php
session_start();
require_once '../src/config/database.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['rol'], ['super_admin', 'admin_empresa'])) {
    header("Location: index.php");
    exit;
}

$usuario = $_SESSION['user'];
$rol = $_SESSION['rol'];
$empresa_id = $rol === 'admin_empresa' ? $_SESSION['empresa_id'] : ($_GET['empresa_id'] ?? null);
$modo = $_GET['modo'] ?? 'usuarios';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <style>
        .flotante {
            position: absolute;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 5px;
            max-width: 300px;
            z-index: 1000;
        }
        input[type="date"] {
            padding: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }
        .btn-guardar {
            background: #1abc9c;
            color: white;
            padding: 6px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .btn-guardar:hover {
            background: #16a085;
        }
        .codigo-temp-box {
            margin-top: 40px;
            padding: 15px;
            background: #fff;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        .codigo-temp-box h3 {
            margin-top: 0;
        }
    </style>
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
        <a class="button" href="?empresa_id=<?= $empresa_id ?>&modo=<?= $modo === 'usuarios' ? 'evaluaciones' : 'usuarios' ?>">
            <?= $modo === 'usuarios' ? 'Ver Evaluaciones' : 'Ver Usuarios' ?>
        </a>
    <?php endif; ?>
    <a class="button logout" href="logout.php">Cerrar Sesión</a>
</div>

<main>
    <?php if ($rol === 'super_admin'): ?>
        <form method="GET" class="empresa-form">
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
                        // Externos
                        $q = $conexion->prepare("
                            SELECT evaluador_nombre FROM respuestas
                            WHERE evaluacion_id = ? AND relacion != 'Autoevaluación'
                            GROUP BY evaluador_nombre, relacion
                        ");
                        $q->bind_param("i", $row['evaluacion_id']);
                        $q->execute();
                        $res = $q->get_result();
                        while ($r = $res->fetch_assoc()) {
                            $nombres[] = $r['evaluador_nombre'];
                        }

                        // Autoevaluación
                        $q2 = $conexion->prepare("
                            SELECT 1 FROM autoevaluaciones
                            WHERE evaluacion_id = ? LIMIT 1
                        ");
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
                                    <a class="btn-asignar" href="programar_evaluacion.php?preselect_user=<?= $row['usuario_id'] ?>">Asignar Evaluación</a>
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
                                <a class="btn-ver" href="grafico_resultados.php?evaluacion_id=<?= $row['evaluacion_id'] ?>">Ver gráfico</a>
                            <?php else: ?>
                                <em>No disponible</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="btn-editar" href="editar_usuario.php?id=<?= $row['usuario_id'] ?>">Editar</a>
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
                $stmt = $conexion->prepare("
                    SELECT e.id, e.nombre_evaluado, e.fecha, e.codigo_unico
                    FROM evaluaciones e
                    WHERE e.empresa_id = ?
                    ORDER BY e.fecha DESC
                ");
                $stmt->bind_param("i", $empresa_id);
                $stmt->execute();
                $res = $stmt->get_result();

                while ($row = $res->fetch_assoc()):
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
                            <a class="btn-ver" href="grafico_resultados.php?evaluacion_id=<?= $row['id'] ?>">Ver gráfico</a>
                            <a class="btn-cancelar" href="cancelar_evaluacion.php?id=<?= $row['id'] ?>" onclick="return confirm('¿Cancelar esta evaluación?')">Cancelar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>

            <div class="codigo-temp-box">
    <h3>Códigos Temporales Activos</h3>
    <ul>
        <?php
        $query = "
           SELECT s.codigo_temp, s.nombre, s.relacion
    FROM sesiones_evaluadores s
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
    GROUP BY s.codigo_temp, s.nombre, s.relacion
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
            <li><strong><?= htmlspecialchars($row['nombre']) ?></strong> (<?= $row['relacion'] ?>): <?= $row['codigo_temp'] ?></li>
        <?php endwhile; ?>
    </ul>
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
