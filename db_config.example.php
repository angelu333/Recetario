<?php
// db_config.example.php
// Copia este archivo como db_config.php y configura tus credenciales

// Define las credenciales de la base de datos
define('DB_HOST', 'localhost'); 
define('DB_NAME', 'recetario_db'); // <--- ¡Nombre de tu BD!
define('DB_USER', 'root'); 
define('DB_PASS', '');    // <--- Configura tu contraseña aquí

function connectDB() {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Manejo de errores detallado
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve resultados como array asociativo
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        // Crea una instancia de PDO y establece la conexión
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        // En caso de error de conexión, detiene la ejecución
        http_response_code(500); 
        die(json_encode(['status' => 'error', 'message' => 'Error de conexión a la BD: ' . $e->getMessage()]));
    }
}
?>