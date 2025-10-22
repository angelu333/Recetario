-- Relacionar ingredientes existentes con la receta de Panqueques (id=1)
-- Estos IDs corresponden a los ingredientes que ya insertaste
INSERT INTO Recetas_Ingredientes (receta_id, ingrediente_id, cantidad) VALUES 
(1, 1, '2 tazas'),        -- Harina de trigo
(1, 2, '2 unidades'),     -- Huevo
(1, 3, '2 cucharadas'),   -- Azúcar
(1, 4, '1.5 tazas'),      -- Leche
(1, 5, '50 gramos');      -- Mantequilla

-- Relacionar categorías existentes con la receta de Panqueques
INSERT INTO Recetas_Categorias (receta_id, categoria_id) VALUES 
(1, 1),  -- Postres
(1, 2);  -- Repostería

-- Relacionar utensilios existentes con la receta de Panqueques
INSERT INTO Recetas_Utensilios (receta_id, utensilio_id) VALUES 
(1, 1),  -- Batidora
(1, 2),  -- Molde redondo
(1, 3);  -- Cuchara
