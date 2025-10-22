<?php
// index.php

require_once 'db_config.php';

// --- Configuración de cabeceras HTTP para la API ---
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // CORS: Necesario para desarrollo
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar la solicitud OPTIONS (necesario para algunas peticiones de navegador/Postman)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- Lógica de Routing ---
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Definir la ruta base de la API
$base_path = '/recetario_api';

// Eliminar la ruta base y limpiar la URI
$path = trim(str_replace($base_path, '', $uri), '/');

// Si la ruta está vacía, establecer una ruta por defecto
if (empty($path)) {
    $path = 'home';
}

// Dividir la ruta en segmentos
$path_parts = explode('/', $path);
$resource = array_shift($path_parts); // Obtener el primer segmento como recurso

// Obtener el ID si existe en la URL
$id = !empty($path_parts) ? array_shift($path_parts) : null;

// Determinar el método HTTP
$method = $_SERVER['REQUEST_METHOD'];

// Logging para debug
error_log("URI recibida: " . $uri);
error_log("Path procesado: " . $path);
error_log("Resource: " . $resource);
error_log("Method: " . $method);

// Conectar a la base de datos
$db = connectDB();

// Manejo básico de rutas
switch ($resource) {
    case 'categorias':
        if ($method === 'GET') {
            if ($id) {
                // Obtener recetas de una categoría específica (por nombre o ID)
                $query = "
                    SELECT 
                        r.id, r.nombre, r.descripcion, r.imagen_url,
                        r.dificultad, r.tiempo_preparacion, r.favoritos_count,
                        u.nombre_usuario as autor,
                        COALESCE(AVG(v.puntuacion), 0) as rating_promedio,
                        c.nombre as categoria_nombre
                    FROM Recetas r
                    LEFT JOIN Usuarios u ON r.autor_id = u.id
                    LEFT JOIN Valoraciones v ON r.id = v.receta_id
                    INNER JOIN Recetas_Categorias rc ON r.id = rc.receta_id
                    INNER JOIN Categorias c ON rc.categoria_id = c.id
                    WHERE c.nombre = ? OR c.id = ?
                    GROUP BY r.id
                    ORDER BY r.favoritos_count DESC, rating_promedio DESC
                ";
                $stmt = $db->prepare($query);
                $stmt->execute([$id, $id]);
                $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($recetas);
            } else {
                // Obtener todas las categorías con conteo de recetas
                $query = "
                    SELECT 
                        c.id, c.nombre,
                        COUNT(rc.receta_id) as total_recetas
                    FROM Categorias c
                    LEFT JOIN Recetas_Categorias rc ON c.id = rc.categoria_id
                    GROUP BY c.id, c.nombre
                    ORDER BY c.nombre
                ";
                $stmt = $db->query($query);
                $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($categorias);
            }
        }
        break;

    case 'ingredientes':
        if ($method === 'GET') {
            if (isset($_GET['q'])) {
                // Buscar ingredientes por término
                $termino = '%' . $_GET['q'] . '%';
                $query = "SELECT * FROM Ingredientes WHERE nombre LIKE ? ORDER BY nombre LIMIT 20";
                $stmt = $db->prepare($query);
                $stmt->execute([$termino]);
                $ingredientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($ingredientes);
            } else {
                // Obtener todos los ingredientes
                $query = "SELECT * FROM Ingredientes ORDER BY nombre";
                $stmt = $db->query($query);
                $ingredientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($ingredientes);
            }
        }
        break;

    case 'usuarios':
        if ($method === 'GET') {
            if ($id) {
                // Obtener perfil de usuario específico
                $query = "
                    SELECT 
                        u.*,
                        COUNT(r.id) as total_recetas,
                        COALESCE(AVG(v.puntuacion), 0) as rating_promedio
                    FROM Usuarios u
                    LEFT JOIN Recetas r ON u.id = r.autor_id
                    LEFT JOIN Valoraciones v ON r.id = v.receta_id
                    WHERE u.id = ?
                    GROUP BY u.id
                ";
                $stmt = $db->prepare($query);
                $stmt->execute([$id]);
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($usuario) {
                    // Obtener las recetas del usuario
                    $stmtRecetas = $db->prepare("
                        SELECT 
                            r.id, r.nombre, r.descripcion, r.imagen_url,
                            r.dificultad, r.favoritos_count,
                            COALESCE(AVG(v.puntuacion), 0) as rating_promedio
                        FROM Recetas r
                        LEFT JOIN Valoraciones v ON r.id = v.receta_id
                        WHERE r.autor_id = ?
                        GROUP BY r.id
                        ORDER BY r.favoritos_count DESC
                    ");
                    $stmtRecetas->execute([$id]);
                    $usuario['recetas'] = $stmtRecetas->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode($usuario);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Usuario no encontrado']);
                }
            } else {
                // Obtener todos los usuarios con estadísticas
                $query = "
                    SELECT 
                        u.id, u.nombre_usuario, u.nombre_completo, u.email,
                        u.fecha_registro, COUNT(r.id) as total_recetas
                    FROM Usuarios u
                    LEFT JOIN Recetas r ON u.id = r.autor_id
                    GROUP BY u.id
                    ORDER BY total_recetas DESC, u.nombre_usuario
                ";
                $stmt = $db->query($query);
                $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($usuarios);
            }
        } elseif ($method === 'POST') {
            // Crear nuevo usuario
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validar datos requeridos
            if (empty($data['nombre_usuario']) || empty($data['email'])) {
                http_response_code(400);
                echo json_encode(['error' => 'nombre_usuario y email son requeridos']);
                break;
            }
            
            try {
                // Verificar si el usuario o email ya existen
                $stmtCheck = $db->prepare("SELECT id FROM Usuarios WHERE nombre_usuario = ? OR email = ?");
                $stmtCheck->execute([$data['nombre_usuario'], $data['email']]);
                
                if ($stmtCheck->fetch()) {
                    http_response_code(409);
                    echo json_encode(['error' => 'El nombre de usuario o email ya existe']);
                    break;
                }
                
                // Crear usuario
                $stmt = $db->prepare("
                    INSERT INTO Usuarios (nombre_usuario, email, nombre_completo)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $data['nombre_usuario'],
                    $data['email'],
                    $data['nombre_completo'] ?? null
                ]);
                
                $usuario_id = $db->lastInsertId();
                
                // Obtener el usuario creado
                $stmtGet = $db->prepare("SELECT * FROM Usuarios WHERE id = ?");
                $stmtGet->execute([$usuario_id]);
                $usuario = $stmtGet->fetch(PDO::FETCH_ASSOC);
                
                http_response_code(201);
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuario creado exitosamente',
                    'usuario' => $usuario
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Error al crear usuario: ' . $e->getMessage()]);
            }
        } elseif ($method === 'PUT' && $id) {
            // Actualizar usuario existente
            $data = json_decode(file_get_contents('php://input'), true);
            
            try {
                // Verificar si el usuario existe
                $stmtCheck = $db->prepare("SELECT id FROM Usuarios WHERE id = ?");
                $stmtCheck->execute([$id]);
                
                if (!$stmtCheck->fetch()) {
                    http_response_code(404);
                    echo json_encode(['error' => 'Usuario no encontrado']);
                    break;
                }
                
                // Verificar si el nuevo nombre_usuario o email ya existen (excluyendo el usuario actual)
                if (!empty($data['nombre_usuario']) || !empty($data['email'])) {
                    $checkQuery = "SELECT id FROM Usuarios WHERE (nombre_usuario = ? OR email = ?) AND id != ?";
                    $stmtCheckDup = $db->prepare($checkQuery);
                    $stmtCheckDup->execute([
                        $data['nombre_usuario'] ?? '',
                        $data['email'] ?? '',
                        $id
                    ]);
                    
                    if ($stmtCheckDup->fetch()) {
                        http_response_code(409);
                        echo json_encode(['error' => 'El nombre de usuario o email ya existe']);
                        break;
                    }
                }
                
                // Construir query de actualización dinámicamente
                $updates = [];
                $params = [];
                
                if (isset($data['nombre_usuario'])) {
                    $updates[] = "nombre_usuario = ?";
                    $params[] = $data['nombre_usuario'];
                }
                if (isset($data['email'])) {
                    $updates[] = "email = ?";
                    $params[] = $data['email'];
                }
                if (isset($data['nombre_completo'])) {
                    $updates[] = "nombre_completo = ?";
                    $params[] = $data['nombre_completo'];
                }
                
                if (empty($updates)) {
                    http_response_code(400);
                    echo json_encode(['error' => 'No hay datos para actualizar']);
                    break;
                }
                
                $params[] = $id; // Para el WHERE
                
                $query = "UPDATE Usuarios SET " . implode(', ', $updates) . " WHERE id = ?";
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                
                // Obtener el usuario actualizado
                $stmtGet = $db->prepare("SELECT * FROM Usuarios WHERE id = ?");
                $stmtGet->execute([$id]);
                $usuario = $stmtGet->fetch(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuario actualizado exitosamente',
                    'usuario' => $usuario
                ]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Error al actualizar usuario: ' . $e->getMessage()]);
            }
        } elseif ($method === 'DELETE' && $id) {
            // Eliminar usuario
            try {
                $db->beginTransaction();
                
                // Verificar si el usuario existe
                $stmtCheck = $db->prepare("SELECT id FROM Usuarios WHERE id = ?");
                $stmtCheck->execute([$id]);
                
                if (!$stmtCheck->fetch()) {
                    $db->rollBack();
                    http_response_code(404);
                    echo json_encode(['error' => 'Usuario no encontrado']);
                    break;
                }
                
                // Verificar si el usuario tiene recetas
                $stmtRecetas = $db->prepare("SELECT COUNT(*) as total FROM Recetas WHERE autor_id = ?");
                $stmtRecetas->execute([$id]);
                $totalRecetas = $stmtRecetas->fetch(PDO::FETCH_ASSOC)['total'];
                
                if ($totalRecetas > 0) {
                    // Opción 1: No permitir eliminar si tiene recetas
                    $db->rollBack();
                    http_response_code(409);
                    echo json_encode([
                        'error' => 'No se puede eliminar el usuario porque tiene recetas asociadas',
                        'total_recetas' => $totalRecetas
                    ]);
                    break;
                    
                    // Opción 2: Eliminar todo en cascada (descomenta si prefieres esto)
                    /*
                    // Eliminar valoraciones del usuario
                    $db->prepare("DELETE FROM Valoraciones WHERE usuario_id = ?")->execute([$id]);
                    
                    // Eliminar comentarios del usuario
                    $db->prepare("DELETE FROM Comentarios WHERE usuario_id = ?")->execute([$id]);
                    
                    // Eliminar favoritos del usuario
                    $db->prepare("DELETE FROM Favoritos WHERE usuario_id = ?")->execute([$id]);
                    
                    // Las recetas se mantendrán pero con autor_id = NULL (por el ON DELETE SET NULL)
                    */
                }
                
                // Eliminar valoraciones del usuario
                $db->prepare("DELETE FROM Valoraciones WHERE usuario_id = ?")->execute([$id]);
                
                // Eliminar comentarios del usuario
                $db->prepare("DELETE FROM Comentarios WHERE usuario_id = ?")->execute([$id]);
                
                // Eliminar favoritos del usuario
                $db->prepare("DELETE FROM Favoritos WHERE usuario_id = ?")->execute([$id]);
                
                // Eliminar usuario
                $stmt = $db->prepare("DELETE FROM Usuarios WHERE id = ?");
                $stmt->execute([$id]);
                
                $db->commit();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Usuario eliminado exitosamente'
                ]);
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Error al eliminar usuario: ' . $e->getMessage()]);
            }
        }
        break;

    case 'utensilios':
        if ($method === 'GET') {
            $query = "SELECT * FROM Utensilios ORDER BY nombre";
            $stmt = $db->query($query);
            $utensilios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($utensilios);
        }
        break;

    case 'buscar':
        if ($method === 'GET') {
            $conditions = [];
            $params = [];
            
            // Construir query base
            $query = "
                SELECT DISTINCT
                    r.id, r.nombre, r.descripcion, r.imagen_url,
                    r.dificultad, r.tiempo_preparacion, r.favoritos_count,
                    u.nombre_usuario as autor,
                    COALESCE(AVG(v.puntuacion), 0) as rating_promedio
                FROM Recetas r
                LEFT JOIN Usuarios u ON r.autor_id = u.id
                LEFT JOIN Valoraciones v ON r.id = v.receta_id
                LEFT JOIN Recetas_Categorias rc ON r.id = rc.receta_id
                LEFT JOIN Recetas_Ingredientes ri ON r.id = ri.receta_id
            ";
            
            // Filtro por término de búsqueda
            if (!empty($_GET['q'])) {
                $conditions[] = "(r.nombre LIKE ? OR r.descripcion LIKE ?)";
                $termino = '%' . $_GET['q'] . '%';
                $params[] = $termino;
                $params[] = $termino;
            }
            
            // Filtro por categoría (por nombre o ID)
            if (!empty($_GET['categoria'])) {
                $query .= " LEFT JOIN Categorias cat ON rc.categoria_id = cat.id";
                $conditions[] = "(cat.nombre = ? OR cat.id = ?)";
                $params[] = $_GET['categoria'];
                $params[] = $_GET['categoria'];
            }
            
            // Filtro por dificultad
            if (!empty($_GET['dificultad'])) {
                $conditions[] = "r.dificultad = ?";
                $params[] = $_GET['dificultad'];
            }
            
            // Filtro por tiempo máximo
            if (!empty($_GET['tiempo_max'])) {
                $conditions[] = "r.tiempo_preparacion <= ?";
                $params[] = $_GET['tiempo_max'];
            }
            
            // Filtro por ingredientes (por nombre)
            if (!empty($_GET['ingredientes'])) {
                $ingredientes = explode(',', $_GET['ingredientes']);
                $placeholders = str_repeat('?,', count($ingredientes) - 1) . '?';
                $query .= " LEFT JOIN Ingredientes ing ON ri.ingrediente_id = ing.id";
                $conditions[] = "ing.nombre IN ($placeholders)";
                $params = array_merge($params, $ingredientes);
            }
            
            // Agregar condiciones WHERE si existen
            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }
            
            $query .= " GROUP BY r.id ORDER BY r.favoritos_count DESC, rating_promedio DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($resultados);
        }
        break;

    case 'recetas':
        if ($method === 'GET') {
            // Manejar sub-rutas especiales
            if ($id === 'destacadas') {
                // GET /recetas/destacadas - Recetas con más favoritos
                $query = "
                    SELECT 
                        r.id, r.nombre, r.descripcion, r.imagen_url,
                        r.dificultad, r.tiempo_preparacion, r.favoritos_count,
                        u.nombre_usuario as autor,
                        COALESCE(AVG(v.puntuacion), 0) as rating_promedio
                    FROM Recetas r
                    LEFT JOIN Usuarios u ON r.autor_id = u.id
                    LEFT JOIN Valoraciones v ON r.id = v.receta_id
                    GROUP BY r.id
                    ORDER BY r.favoritos_count DESC, rating_promedio DESC
                    LIMIT 12
                ";
                $stmt = $db->query($query);
                $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($recetas);
            } elseif ($id === 'populares') {
                // GET /recetas/populares - Recetas mejor valoradas
                $query = "
                    SELECT 
                        r.id, r.nombre, r.descripcion, r.imagen_url,
                        r.dificultad, r.tiempo_preparacion, r.favoritos_count,
                        u.nombre_usuario as autor,
                        COALESCE(AVG(v.puntuacion), 0) as rating_promedio,
                        COUNT(v.id) as total_valoraciones
                    FROM Recetas r
                    LEFT JOIN Usuarios u ON r.autor_id = u.id
                    LEFT JOIN Valoraciones v ON r.id = v.receta_id
                    GROUP BY r.id
                    HAVING total_valoraciones >= 1
                    ORDER BY rating_promedio DESC, total_valoraciones DESC
                    LIMIT 12
                ";
                $stmt = $db->query($query);
                $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($recetas);
            } elseif ($id === 'recientes') {
                // GET /recetas/recientes - Recetas más recientes
                $query = "
                    SELECT 
                        r.id, r.nombre, r.descripcion, r.imagen_url,
                        r.dificultad, r.tiempo_preparacion, r.favoritos_count,
                        u.nombre_usuario as autor,
                        COALESCE(AVG(v.puntuacion), 0) as rating_promedio
                    FROM Recetas r
                    LEFT JOIN Usuarios u ON r.autor_id = u.id
                    LEFT JOIN Valoraciones v ON r.id = v.receta_id
                    GROUP BY r.id
                    ORDER BY r.id DESC
                    LIMIT 12
                ";
                $stmt = $db->query($query);
                $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($recetas);
            } elseif ($id === 'por-ingredientes') {
                // GET /recetas/por-ingredientes?ingredientes=harina,huevo,leche - Recetas que contengan ingredientes específicos
                if (!empty($_GET['ingredientes'])) {
                    $ingredientes = explode(',', $_GET['ingredientes']);
                    // Limpiar espacios en blanco
                    $ingredientes = array_map('trim', $ingredientes);
                    $placeholders = str_repeat('?,', count($ingredientes) - 1) . '?';
                    
                    $query = "
                        SELECT 
                            r.id, r.nombre, r.descripcion, r.imagen_url,
                            r.dificultad, r.tiempo_preparacion, r.favoritos_count,
                            u.nombre_usuario as autor,
                            COALESCE(AVG(v.puntuacion), 0) as rating_promedio,
                            COUNT(DISTINCT i.id) as ingredientes_coincidentes,
                            GROUP_CONCAT(DISTINCT i.nombre) as ingredientes_encontrados
                        FROM Recetas r
                        LEFT JOIN Usuarios u ON r.autor_id = u.id
                        LEFT JOIN Valoraciones v ON r.id = v.receta_id
                        INNER JOIN Recetas_Ingredientes ri ON r.id = ri.receta_id
                        INNER JOIN Ingredientes i ON ri.ingrediente_id = i.id
                        WHERE i.nombre IN ($placeholders)
                        GROUP BY r.id
                        ORDER BY ingredientes_coincidentes DESC, r.favoritos_count DESC
                    ";
                    $stmt = $db->prepare($query);
                    $stmt->execute($ingredientes);
                    $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Convertir ingredientes_encontrados en array
                    foreach ($recetas as &$receta) {
                        $receta['ingredientes_encontrados'] = explode(',', $receta['ingredientes_encontrados']);
                    }
                    
                    echo json_encode($recetas);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => 'Parámetro ingredientes requerido']);
                }
            } elseif ($id) {
                // Obtener una receta específica con sus relaciones
                $query = "
                    SELECT 
                        r.*,
                        u.nombre_usuario as autor
                    FROM Recetas r
                    LEFT JOIN Usuarios u ON r.autor_id = u.id
                    WHERE r.id = ?
                ";
                $stmt = $db->prepare($query);
                $stmt->execute([$id]);
                $receta = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($receta) {
                    // Obtener los ingredientes con sus cantidades
                    $stmtIngredientes = $db->prepare("
                        SELECT i.id, i.nombre, ri.cantidad
                        FROM Recetas_Ingredientes ri
                        INNER JOIN Ingredientes i ON ri.ingrediente_id = i.id
                        WHERE ri.receta_id = ?
                    ");
                    $stmtIngredientes->execute([$id]);
                    $receta['ingredientes'] = $stmtIngredientes->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Obtener los pasos ordenados
                    $stmtPasos = $db->prepare("
                        SELECT * FROM Pasos 
                        WHERE receta_id = ? 
                        ORDER BY numero_paso
                    ");
                    $stmtPasos->execute([$id]);
                    $receta['pasos'] = $stmtPasos->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Obtener las categorías
                    $stmtCategorias = $db->prepare("
                        SELECT c.id, c.nombre
                        FROM Recetas_Categorias rc
                        INNER JOIN Categorias c ON rc.categoria_id = c.id
                        WHERE rc.receta_id = ?
                    ");
                    $stmtCategorias->execute([$id]);
                    $receta['categorias'] = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Obtener los utensilios
                    $stmtUtensilios = $db->prepare("
                        SELECT u.id, u.nombre
                        FROM Recetas_Utensilios ru
                        INNER JOIN Utensilios u ON ru.utensilio_id = u.id
                        WHERE ru.receta_id = ?
                    ");
                    $stmtUtensilios->execute([$id]);
                    $receta['utensilios'] = $stmtUtensilios->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo json_encode($receta);
                } else {
                    http_response_code(404);
                    echo json_encode(['error' => 'Receta no encontrada']);
                }
            } else {
                // Obtener todas las recetas con información básica
                $query = "
                    SELECT 
                        r.id, r.nombre, r.descripcion, r.imagen_url,
                        r.dificultad, u.nombre_usuario as autor
                    FROM Recetas r
                    LEFT JOIN Usuarios u ON r.autor_id = u.id
                    ORDER BY r.id DESC
                ";
                $stmt = $db->query($query);
                $recetas = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($recetas);
            }
        } elseif ($method === 'POST') {
            // Crear nueva receta
            $data = json_decode(file_get_contents('php://input'), true);
            
            try {
                $db->beginTransaction();
                
                // Insertar receta
                $stmt = $db->prepare("
                    INSERT INTO Recetas (nombre, descripcion, imagen_url, porciones, 
                                        tiempo_preparacion, tiempo_coccion, dificultad, autor_id)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $data['nombre'],
                    $data['descripcion'] ?? null,
                    $data['imagen_url'] ?? null,
                    $data['porciones'] ?? null,
                    $data['tiempo_preparacion'] ?? null,
                    $data['tiempo_coccion'] ?? null,
                    $data['dificultad'] ?? 'Media',
                    $data['autor_id']
                ]);
                
                $receta_id = $db->lastInsertId();
                
                // Insertar ingredientes (buscar o crear)
                if (!empty($data['ingredientes'])) {
                    $stmtBuscar = $db->prepare("SELECT id FROM Ingredientes WHERE nombre = ?");
                    $stmtCrear = $db->prepare("INSERT INTO Ingredientes (nombre) VALUES (?)");
                    $stmtRelacion = $db->prepare("INSERT INTO Recetas_Ingredientes (receta_id, ingrediente_id, cantidad) VALUES (?, ?, ?)");
                    
                    foreach ($data['ingredientes'] as $ing) {
                        $stmtBuscar->execute([$ing['nombre']]);
                        $ingrediente = $stmtBuscar->fetch(PDO::FETCH_ASSOC);
                        
                        if ($ingrediente) {
                            $ingrediente_id = $ingrediente['id'];
                        } else {
                            $stmtCrear->execute([$ing['nombre']]);
                            $ingrediente_id = $db->lastInsertId();
                        }
                        
                        $stmtRelacion->execute([$receta_id, $ingrediente_id, $ing['cantidad']]);
                    }
                }
                
                // Insertar pasos
                if (!empty($data['pasos'])) {
                    $stmtPaso = $db->prepare("INSERT INTO Pasos (receta_id, numero_paso, descripcion_paso) VALUES (?, ?, ?)");
                    foreach ($data['pasos'] as $index => $paso) {
                        $stmtPaso->execute([$receta_id, $index + 1, $paso['descripcion']]);
                    }
                }
                
                // Insertar categorías (buscar o crear)
                if (!empty($data['categorias'])) {
                    $stmtBuscar = $db->prepare("SELECT id FROM Categorias WHERE nombre = ?");
                    $stmtCrear = $db->prepare("INSERT INTO Categorias (nombre) VALUES (?)");
                    $stmtRelacion = $db->prepare("INSERT INTO Recetas_Categorias (receta_id, categoria_id) VALUES (?, ?)");
                    
                    foreach ($data['categorias'] as $cat_nombre) {
                        $stmtBuscar->execute([$cat_nombre]);
                        $categoria = $stmtBuscar->fetch(PDO::FETCH_ASSOC);
                        
                        if ($categoria) {
                            $categoria_id = $categoria['id'];
                        } else {
                            $stmtCrear->execute([$cat_nombre]);
                            $categoria_id = $db->lastInsertId();
                        }
                        
                        $stmtRelacion->execute([$receta_id, $categoria_id]);
                    }
                }
                
                // Insertar utensilios (buscar o crear)
                if (!empty($data['utensilios'])) {
                    $stmtBuscar = $db->prepare("SELECT id FROM Utensilios WHERE nombre = ?");
                    $stmtCrear = $db->prepare("INSERT INTO Utensilios (nombre) VALUES (?)");
                    $stmtRelacion = $db->prepare("INSERT INTO Recetas_Utensilios (receta_id, utensilio_id) VALUES (?, ?)");
                    
                    foreach ($data['utensilios'] as $uten_nombre) {
                        $stmtBuscar->execute([$uten_nombre]);
                        $utensilio = $stmtBuscar->fetch(PDO::FETCH_ASSOC);
                        
                        if ($utensilio) {
                            $utensilio_id = $utensilio['id'];
                        } else {
                            $stmtCrear->execute([$uten_nombre]);
                            $utensilio_id = $db->lastInsertId();
                        }
                        
                        $stmtRelacion->execute([$receta_id, $utensilio_id]);
                    }
                }
                
                $db->commit();
                
                http_response_code(201);
                echo json_encode(['success' => true, 'id' => $receta_id, 'message' => 'Receta creada exitosamente']);
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Error al crear receta: ' . $e->getMessage()]);
            }
        } elseif ($method === 'PUT' && $id) {
            // Actualizar receta existente
            $data = json_decode(file_get_contents('php://input'), true);
            
            try {
                $db->beginTransaction();
                
                // Actualizar datos básicos de la receta
                $stmt = $db->prepare("
                    UPDATE Recetas SET 
                        nombre = ?, descripcion = ?, imagen_url = ?, porciones = ?,
                        tiempo_preparacion = ?, tiempo_coccion = ?, dificultad = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $data['nombre'],
                    $data['descripcion'] ?? null,
                    $data['imagen_url'] ?? null,
                    $data['porciones'] ?? null,
                    $data['tiempo_preparacion'] ?? null,
                    $data['tiempo_coccion'] ?? null,
                    $data['dificultad'] ?? 'Media',
                    $id
                ]);
                
                // Actualizar ingredientes (buscar o crear)
                if (isset($data['ingredientes'])) {
                    $db->prepare("DELETE FROM Recetas_Ingredientes WHERE receta_id = ?")->execute([$id]);
                    
                    $stmtBuscar = $db->prepare("SELECT id FROM Ingredientes WHERE nombre = ?");
                    $stmtCrear = $db->prepare("INSERT INTO Ingredientes (nombre) VALUES (?)");
                    $stmtRelacion = $db->prepare("INSERT INTO Recetas_Ingredientes (receta_id, ingrediente_id, cantidad) VALUES (?, ?, ?)");
                    
                    foreach ($data['ingredientes'] as $ing) {
                        $stmtBuscar->execute([$ing['nombre']]);
                        $ingrediente = $stmtBuscar->fetch(PDO::FETCH_ASSOC);
                        
                        if ($ingrediente) {
                            $ingrediente_id = $ingrediente['id'];
                        } else {
                            $stmtCrear->execute([$ing['nombre']]);
                            $ingrediente_id = $db->lastInsertId();
                        }
                        
                        $stmtRelacion->execute([$id, $ingrediente_id, $ing['cantidad']]);
                    }
                }
                
                // Actualizar pasos
                if (isset($data['pasos'])) {
                    $db->prepare("DELETE FROM Pasos WHERE receta_id = ?")->execute([$id]);
                    $stmtPaso = $db->prepare("INSERT INTO Pasos (receta_id, numero_paso, descripcion_paso) VALUES (?, ?, ?)");
                    foreach ($data['pasos'] as $index => $paso) {
                        $stmtPaso->execute([$id, $index + 1, $paso['descripcion']]);
                    }
                }
                
                // Actualizar categorías (buscar o crear)
                if (isset($data['categorias'])) {
                    $db->prepare("DELETE FROM Recetas_Categorias WHERE receta_id = ?")->execute([$id]);
                    
                    $stmtBuscar = $db->prepare("SELECT id FROM Categorias WHERE nombre = ?");
                    $stmtCrear = $db->prepare("INSERT INTO Categorias (nombre) VALUES (?)");
                    $stmtRelacion = $db->prepare("INSERT INTO Recetas_Categorias (receta_id, categoria_id) VALUES (?, ?)");
                    
                    foreach ($data['categorias'] as $cat_nombre) {
                        $stmtBuscar->execute([$cat_nombre]);
                        $categoria = $stmtBuscar->fetch(PDO::FETCH_ASSOC);
                        
                        if ($categoria) {
                            $categoria_id = $categoria['id'];
                        } else {
                            $stmtCrear->execute([$cat_nombre]);
                            $categoria_id = $db->lastInsertId();
                        }
                        
                        $stmtRelacion->execute([$id, $categoria_id]);
                    }
                }
                
                // Actualizar utensilios (buscar o crear)
                if (isset($data['utensilios'])) {
                    $db->prepare("DELETE FROM Recetas_Utensilios WHERE receta_id = ?")->execute([$id]);
                    
                    $stmtBuscar = $db->prepare("SELECT id FROM Utensilios WHERE nombre = ?");
                    $stmtCrear = $db->prepare("INSERT INTO Utensilios (nombre) VALUES (?)");
                    $stmtRelacion = $db->prepare("INSERT INTO Recetas_Utensilios (receta_id, utensilio_id) VALUES (?, ?)");
                    
                    foreach ($data['utensilios'] as $uten_nombre) {
                        $stmtBuscar->execute([$uten_nombre]);
                        $utensilio = $stmtBuscar->fetch(PDO::FETCH_ASSOC);
                        
                        if ($utensilio) {
                            $utensilio_id = $utensilio['id'];
                        } else {
                            $stmtCrear->execute([$uten_nombre]);
                            $utensilio_id = $db->lastInsertId();
                        }
                        
                        $stmtRelacion->execute([$id, $utensilio_id]);
                    }
                }
                
                $db->commit();
                
                echo json_encode(['success' => true, 'message' => 'Receta actualizada exitosamente']);
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Error al actualizar receta: ' . $e->getMessage()]);
            }
        } elseif ($method === 'DELETE' && $id) {
            // Eliminar receta
            try {
                $db->beginTransaction();
                
                // Eliminar relaciones
                $db->prepare("DELETE FROM Recetas_Ingredientes WHERE receta_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM Recetas_Categorias WHERE receta_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM Recetas_Utensilios WHERE receta_id = ?")->execute([$id]);
                $db->prepare("DELETE FROM Pasos WHERE receta_id = ?")->execute([$id]);
                
                // Eliminar receta
                $stmt = $db->prepare("DELETE FROM Recetas WHERE id = ?");
                $stmt->execute([$id]);
                
                if ($stmt->rowCount() > 0) {
                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Receta eliminada exitosamente']);
                } else {
                    $db->rollBack();
                    http_response_code(404);
                    echo json_encode(['error' => 'Receta no encontrada']);
                }
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Error al eliminar receta: ' . $e->getMessage()]);
            }
        }
        break;

    case 'valoraciones':
        if ($method === 'GET' && !empty($path_parts)) {
            // GET /valoraciones/{receta_id} - Obtener valoraciones de una receta
            $receta_id = $id;
            $query = "
                SELECT 
                    v.*, u.nombre_usuario
                FROM Valoraciones v
                LEFT JOIN Usuarios u ON v.usuario_id = u.id
                WHERE v.receta_id = ?
                ORDER BY v.fecha_valoracion DESC
            ";
            $stmt = $db->prepare($query);
            $stmt->execute([$receta_id]);
            $valoraciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calcular estadísticas
            $stats = [
                'total' => count($valoraciones),
                'promedio' => 0,
                'distribucion' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]
            ];
            
            if (!empty($valoraciones)) {
                $suma = 0;
                foreach ($valoraciones as $val) {
                    $suma += $val['puntuacion'];
                    $stats['distribucion'][$val['puntuacion']]++;
                }
                $stats['promedio'] = round($suma / count($valoraciones), 2);
            }
            
            echo json_encode([
                'valoraciones' => $valoraciones,
                'estadisticas' => $stats
            ]);
        } elseif ($method === 'POST') {
            // POST /valoraciones - Crear nueva valoración
            $data = json_decode(file_get_contents('php://input'), true);
            
            try {
                // Verificar si ya existe una valoración del usuario para esta receta
                $stmtCheck = $db->prepare("SELECT id FROM Valoraciones WHERE receta_id = ? AND usuario_id = ?");
                $stmtCheck->execute([$data['receta_id'], $data['usuario_id']]);
                
                if ($stmtCheck->fetch()) {
                    // Actualizar valoración existente
                    $stmt = $db->prepare("
                        UPDATE Valoraciones SET 
                            puntuacion = ?, comentario = ?, fecha_valoracion = CURRENT_TIMESTAMP
                        WHERE receta_id = ? AND usuario_id = ?
                    ");
                    $stmt->execute([
                        $data['puntuacion'],
                        $data['comentario'] ?? null,
                        $data['receta_id'],
                        $data['usuario_id']
                    ]);
                    $message = 'Valoración actualizada exitosamente';
                } else {
                    // Crear nueva valoración
                    $stmt = $db->prepare("
                        INSERT INTO Valoraciones (receta_id, usuario_id, puntuacion, comentario)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $data['receta_id'],
                        $data['usuario_id'],
                        $data['puntuacion'],
                        $data['comentario'] ?? null
                    ]);
                    $message = 'Valoración creada exitosamente';
                }
                
                http_response_code(201);
                echo json_encode(['success' => true, 'message' => $message]);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Error al procesar valoración: ' . $e->getMessage()]);
            }
        }
        break;

    case 'comentarios':
        if ($method === 'GET' && !empty($path_parts)) {
            // GET /comentarios/{receta_id} - Obtener comentarios de una receta
            $receta_id = $id;
            $query = "
                SELECT 
                    c.*, u.nombre_usuario
                FROM Comentarios c
                LEFT JOIN Usuarios u ON c.usuario_id = u.id
                WHERE c.receta_id = ?
                ORDER BY c.fecha_comentario DESC
            ";
            $stmt = $db->prepare($query);
            $stmt->execute([$receta_id]);
            $comentarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($comentarios);
        } elseif ($method === 'POST') {
            // POST /comentarios - Crear nuevo comentario
            $data = json_decode(file_get_contents('php://input'), true);
            
            try {
                $stmt = $db->prepare("
                    INSERT INTO Comentarios (receta_id, usuario_id, texto)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([
                    $data['receta_id'],
                    $data['usuario_id'],
                    $data['texto']
                ]);
                
                http_response_code(201);
                echo json_encode(['success' => true, 'message' => 'Comentario creado exitosamente']);
            } catch (Exception $e) {
                http_response_code(500);
                echo json_encode(['error' => 'Error al crear comentario: ' . $e->getMessage()]);
            }
        }
        break;

    case 'favoritos':
        if ($method === 'GET' && $id) {
            // GET /favoritos/{usuario_id} - Obtener favoritos de un usuario
            $query = "
                SELECT 
                    r.id, r.nombre, r.descripcion, r.imagen_url,
                    r.dificultad, r.favoritos_count,
                    u.nombre_usuario as autor,
                    f.fecha_agregado,
                    COALESCE(AVG(v.puntuacion), 0) as rating_promedio
                FROM Favoritos f
                INNER JOIN Recetas r ON f.receta_id = r.id
                LEFT JOIN Usuarios u ON r.autor_id = u.id
                LEFT JOIN Valoraciones v ON r.id = v.receta_id
                WHERE f.usuario_id = ?
                GROUP BY r.id
                ORDER BY f.fecha_agregado DESC
            ";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $favoritos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($favoritos);
        } elseif ($method === 'POST') {
            // POST /favoritos - Agregar a favoritos
            $data = json_decode(file_get_contents('php://input'), true);
            
            try {
                $db->beginTransaction();
                
                // Verificar si ya está en favoritos
                $stmtCheck = $db->prepare("SELECT id FROM Favoritos WHERE usuario_id = ? AND receta_id = ?");
                $stmtCheck->execute([$data['usuario_id'], $data['receta_id']]);
                
                if (!$stmtCheck->fetch()) {
                    // Agregar a favoritos
                    $stmt = $db->prepare("INSERT INTO Favoritos (usuario_id, receta_id) VALUES (?, ?)");
                    $stmt->execute([$data['usuario_id'], $data['receta_id']]);
                    
                    // Incrementar contador en la receta
                    $stmtUpdate = $db->prepare("UPDATE Recetas SET favoritos_count = favoritos_count + 1 WHERE id = ?");
                    $stmtUpdate->execute([$data['receta_id']]);
                    
                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Agregado a favoritos']);
                } else {
                    $db->rollBack();
                    echo json_encode(['success' => false, 'message' => 'Ya está en favoritos']);
                }
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Error al agregar a favoritos: ' . $e->getMessage()]);
            }
        } elseif ($method === 'DELETE' && $id) {
            // DELETE /favoritos/{receta_id}?usuario_id={id} - Quitar de favoritos
            $usuario_id = $_GET['usuario_id'] ?? null;
            
            if (!$usuario_id) {
                http_response_code(400);
                echo json_encode(['error' => 'usuario_id requerido']);
                break;
            }
            
            try {
                $db->beginTransaction();
                
                $stmt = $db->prepare("DELETE FROM Favoritos WHERE usuario_id = ? AND receta_id = ?");
                $stmt->execute([$usuario_id, $id]);
                
                if ($stmt->rowCount() > 0) {
                    // Decrementar contador en la receta
                    $stmtUpdate = $db->prepare("UPDATE Recetas SET favoritos_count = GREATEST(favoritos_count - 1, 0) WHERE id = ?");
                    $stmtUpdate->execute([$id]);
                    
                    $db->commit();
                    echo json_encode(['success' => true, 'message' => 'Removido de favoritos']);
                } else {
                    $db->rollBack();
                    http_response_code(404);
                    echo json_encode(['error' => 'No estaba en favoritos']);
                }
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(500);
                echo json_encode(['error' => 'Error al remover de favoritos: ' . $e->getMessage()]);
            }
        }
        break;

    case 'stats':
        if ($method === 'GET') {
            // Obtener estadísticas generales
            $stats = [];
            
            // Total de recetas
            $stmt = $db->query("SELECT COUNT(*) as total FROM Recetas");
            $stats['total_recetas'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total de usuarios
            $stmt = $db->query("SELECT COUNT(*) as total FROM Usuarios");
            $stats['total_usuarios'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total de categorías
            $stmt = $db->query("SELECT COUNT(*) as total FROM Categorias");
            $stats['total_categorias'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Total de ingredientes
            $stmt = $db->query("SELECT COUNT(*) as total FROM Ingredientes");
            $stats['total_ingredientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Recetas más populares (top 5)
            $stmt = $db->query("
                SELECT r.id, r.nombre, r.favoritos_count
                FROM Recetas r
                ORDER BY r.favoritos_count DESC
                LIMIT 5
            ");
            $stats['recetas_populares'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode($stats);
        }
        break;
    
    // Ruta no encontrada
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Ruta no encontrada']);
        break;
}
?>