-- Script para verificar las tablas existentes en tu base de datos
USE recetario_db;

-- Mostrar todas las tablas
SHOW TABLES;

-- Verificar estructura de cada tabla
DESCRIBE Usuarios;
DESCRIBE Categorias;
DESCRIBE Ingredientes;
DESCRIBE Utensilios;
DESCRIBE Recetas;
DESCRIBE Pasos;
DESCRIBE Recetas_Ingredientes;
DESCRIBE Recetas_Utensilios;
DESCRIBE Recetas_Categorias;
DESCRIBE Valoraciones;
DESCRIBE Comentarios;
DESCRIBE Favoritos;

-- Contar registros en cada tabla
SELECT 'Usuarios' as tabla, COUNT(*) as registros FROM Usuarios
UNION ALL
SELECT 'Categorias', COUNT(*) FROM Categorias
UNION ALL
SELECT 'Ingredientes', COUNT(*) FROM Ingredientes
UNION ALL
SELECT 'Utensilios', COUNT(*) FROM Utensilios
UNION ALL
SELECT 'Recetas', COUNT(*) FROM Recetas
UNION ALL
SELECT 'Pasos', COUNT(*) FROM Pasos
UNION ALL
SELECT 'Recetas_Ingredientes', COUNT(*) FROM Recetas_Ingredientes
UNION ALL
SELECT 'Recetas_Utensilios', COUNT(*) FROM Recetas_Utensilios
UNION ALL
SELECT 'Recetas_Categorias', COUNT(*) FROM Recetas_Categorias
UNION ALL
SELECT 'Valoraciones', COUNT(*) FROM Valoraciones
UNION ALL
SELECT 'Comentarios', COUNT(*) FROM Comentarios
UNION ALL
SELECT 'Favoritos', COUNT(*) FROM Favoritos;