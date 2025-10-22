-- Script para crear todas las tablas del recetario
-- Ejecuta este script en tu base de datos MySQL

-- Crear base de datos si no existe
CREATE DATABASE IF NOT EXISTS recetario_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE recetario_db;

-- 1. Tabla Usuarios
CREATE TABLE IF NOT EXISTS Usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    nombre_completo VARCHAR(100),
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Tabla Categorias
CREATE TABLE IF NOT EXISTS Categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) UNIQUE NOT NULL
);

-- 3. Tabla Ingredientes
CREATE TABLE IF NOT EXISTS Ingredientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL
);

-- 4. Tabla Utensilios
CREATE TABLE IF NOT EXISTS Utensilios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) UNIQUE NOT NULL
);

-- 5. Tabla Recetas
CREATE TABLE IF NOT EXISTS Recetas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    imagen_url VARCHAR(500),
    porciones INT,
    tiempo_preparacion INT, -- en minutos
    tiempo_coccion INT, -- en minutos
    dificultad ENUM('Baja', 'Media', 'Alta') DEFAULT 'Media',
    autor_id INT,
    favoritos_count INT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (autor_id) REFERENCES Usuarios(id) ON DELETE SET NULL
);

-- 6. Tabla Pasos
CREATE TABLE IF NOT EXISTS Pasos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receta_id INT NOT NULL,
    numero_paso INT NOT NULL,
    descripcion_paso TEXT NOT NULL,
    FOREIGN KEY (receta_id) REFERENCES Recetas(id) ON DELETE CASCADE
);

-- 7. Tabla Recetas_Ingredientes (Intermedia)
CREATE TABLE IF NOT EXISTS Recetas_Ingredientes (
    receta_id INT,
    ingrediente_id INT,
    cantidad VARCHAR(100),
    unidad_medida VARCHAR(50),
    PRIMARY KEY (receta_id, ingrediente_id),
    FOREIGN KEY (receta_id) REFERENCES Recetas(id) ON DELETE CASCADE,
    FOREIGN KEY (ingrediente_id) REFERENCES Ingredientes(id) ON DELETE CASCADE
);

-- 8. Tabla Recetas_Utensilios (Intermedia)
CREATE TABLE IF NOT EXISTS Recetas_Utensilios (
    receta_id INT,
    utensilio_id INT,
    PRIMARY KEY (receta_id, utensilio_id),
    FOREIGN KEY (receta_id) REFERENCES Recetas(id) ON DELETE CASCADE,
    FOREIGN KEY (utensilio_id) REFERENCES Utensilios(id) ON DELETE CASCADE
);

-- 9. Tabla Recetas_Categorias (Intermedia)
CREATE TABLE IF NOT EXISTS Recetas_Categorias (
    receta_id INT,
    categoria_id INT,
    PRIMARY KEY (receta_id, categoria_id),
    FOREIGN KEY (receta_id) REFERENCES Recetas(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES Categorias(id) ON DELETE CASCADE
);

-- 10. Tabla Valoraciones
CREATE TABLE IF NOT EXISTS Valoraciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receta_id INT NOT NULL,
    usuario_id INT NOT NULL,
    puntuacion INT CHECK (puntuacion >= 1 AND puntuacion <= 5),
    comentario TEXT,
    fecha_valoracion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (receta_id) REFERENCES Recetas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES Usuarios(id) ON DELETE CASCADE,
    UNIQUE KEY unique_valoracion (receta_id, usuario_id)
);

-- 11. Tabla Comentarios
CREATE TABLE IF NOT EXISTS Comentarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    receta_id INT NOT NULL,
    usuario_id INT NOT NULL,
    texto TEXT NOT NULL,
    fecha_comentario TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (receta_id) REFERENCES Recetas(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES Usuarios(id) ON DELETE CASCADE
);

-- 12. Tabla Favoritos
CREATE TABLE IF NOT EXISTS Favoritos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    receta_id INT NOT NULL,
    fecha_agregado TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES Usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (receta_id) REFERENCES Recetas(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorito (usuario_id, receta_id)
);

-- Insertar datos de ejemplo
-- Usuarios
INSERT IGNORE INTO Usuarios (nombre_usuario, email, nombre_completo) VALUES 
('chef_maria', 'maria@ejemplo.com', 'María González'),
('cocina_facil', 'juan@ejemplo.com', 'Juan Pérez'),
('postres_ana', 'ana@ejemplo.com', 'Ana Rodríguez');

-- Categorías
INSERT IGNORE INTO Categorias (nombre) VALUES 
('Postres'),
('Desayunos'),
('Platos Principales'),
('Aperitivos'),
('Bebidas'),
('Ensaladas'),
('Sopas'),
('Repostería');

-- Ingredientes básicos
INSERT IGNORE INTO Ingredientes (nombre) VALUES 
('Harina de trigo'),
('Huevo'),
('Azúcar'),
('Leche'),
('Mantequilla'),
('Sal'),
('Aceite'),
('Cebolla'),
('Ajo'),
('Tomate'),
('Pollo'),
('Arroz'),
('Queso'),
('Pan'),
('Chocolate');

-- Utensilios básicos
INSERT IGNORE INTO Utensilios (nombre) VALUES 
('Batidora'),
('Molde redondo'),
('Cuchara de madera'),
('Sartén'),
('Olla'),
('Cuchillo'),
('Tabla de cortar'),
('Horno'),
('Licuadora'),
('Colador');

-- Receta de ejemplo
INSERT IGNORE INTO Recetas (nombre, descripcion, porciones, tiempo_preparacion, tiempo_coccion, dificultad, autor_id) VALUES 
('Panqueques Clásicos', 'Deliciosos panqueques esponjosos perfectos para el desayuno', 4, 15, 10, 'Media', 1);

-- Obtener el ID de la receta (asumiendo que es 1)
SET @receta_id = 1;

-- Relacionar ingredientes con la receta
INSERT IGNORE INTO Recetas_Ingredientes (receta_id, ingrediente_id, cantidad) VALUES 
(@receta_id, 1, '2 tazas'),        -- Harina de trigo
(@receta_id, 2, '2 unidades'),     -- Huevo
(@receta_id, 3, '2 cucharadas'),   -- Azúcar
(@receta_id, 4, '1.5 tazas'),      -- Leche
(@receta_id, 5, '50 gramos');      -- Mantequilla

-- Relacionar categorías
INSERT IGNORE INTO Recetas_Categorias (receta_id, categoria_id) VALUES 
(@receta_id, 2),  -- Desayunos
(@receta_id, 8);  -- Repostería

-- Relacionar utensilios
INSERT IGNORE INTO Recetas_Utensilios (receta_id, utensilio_id) VALUES 
(@receta_id, 1),  -- Batidora
(@receta_id, 4),  -- Sartén
(@receta_id, 3);  -- Cuchara de madera

-- Pasos de la receta
INSERT IGNORE INTO Pasos (receta_id, numero_paso, descripcion_paso) VALUES 
(@receta_id, 1, 'Mezclar la harina, azúcar y sal en un bowl grande'),
(@receta_id, 2, 'En otro recipiente, batir los huevos y agregar la leche'),
(@receta_id, 3, 'Combinar los ingredientes húmedos con los secos'),
(@receta_id, 4, 'Derretir la mantequilla y agregarla a la mezcla'),
(@receta_id, 5, 'Cocinar en sartén caliente hasta que estén dorados');

-- Valoración de ejemplo
INSERT IGNORE INTO Valoraciones (receta_id, usuario_id, puntuacion, comentario) VALUES 
(@receta_id, 2, 5, '¡Excelente receta! Quedaron perfectos');

-- Comentario de ejemplo
INSERT IGNORE INTO Comentarios (receta_id, usuario_id, texto) VALUES 
(@receta_id, 3, 'Me encantó esta receta, muy fácil de seguir');

-- Favorito de ejemplo
INSERT IGNORE INTO Favoritos (usuario_id, receta_id) VALUES 
(2, @receta_id),
(3, @receta_id);

-- Actualizar contador de favoritos
UPDATE Recetas SET favoritos_count = (
    SELECT COUNT(*) FROM Favoritos WHERE receta_id = @receta_id
) WHERE id = @receta_id;

SHOW TABLES;