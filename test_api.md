# API Recetario - Documentación para Postman

## Configuración Base
- **URL Base**: `http://localhost/recetario_api`
- **Content-Type**: `application/json` (para POST/PUT)

---

## 📋 CATEGORÍAS

### Obtener todas las categorías
```
GET http://localhost/recetario_api/categorias
```

### Obtener recetas de una categoría (por nombre o ID)
```
GET http://localhost/recetario_api/categorias/Postres
GET http://localhost/recetario_api/categorias/1
```

**Ejemplos prácticos:**
- `GET /categorias/Postres` - Recetas de la categoría "Postres"
- `GET /categorias/Desayunos` - Recetas de la categoría "Desayunos"
- `GET /categorias/Platos%20Principales` - Recetas de "Platos Principales" (URL encoded)

---

## 🥕 INGREDIENTES

### Obtener todos los ingredientes
```
GET http://localhost/recetario_api/ingredientes
```

### Buscar ingredientes
```
GET http://localhost/recetario_api/ingredientes?q=harina
```

---

## 👥 USUARIOS

### Obtener todos los usuarios
```
GET http://localhost/recetario_api/usuarios
```

### Obtener perfil de usuario específico
```
GET http://localhost/recetario_api/usuarios/1
```

### Crear nuevo usuario
```
POST http://localhost/recetario_api/usuarios
Content-Type: application/json

{
    "nombre_usuario": "chef_carlos",
    "email": "carlos@ejemplo.com",
    "nombre_completo": "Carlos Martínez"
}
```

**Campos requeridos:**
- `nombre_usuario` (único)
- `email` (único)

**Campos opcionales:**
- `nombre_completo`

### Actualizar usuario
```
PUT http://localhost/recetario_api/usuarios/1
Content-Type: application/json

{
    "nombre_usuario": "chef_carlos_nuevo",
    "email": "carlos_nuevo@ejemplo.com",
    "nombre_completo": "Carlos Martínez López"
}
```

**Ejemplos de actualización parcial:**
```json
// Solo cambiar nombre completo
{
    "nombre_completo": "Carlos Martínez López"
}

// Solo cambiar email
{
    "email": "nuevo_email@ejemplo.com"
}
```

### Eliminar usuario
```
DELETE http://localhost/recetario_api/usuarios/1
```

**Importante**: 
- No se puede eliminar un usuario que tenga recetas asociadas
- Se eliminan automáticamente sus valoraciones, comentarios y favoritos

---

## 🔍 BÚSQUEDA AVANZADA

### Búsqueda completa con filtros
```
GET http://localhost/recetario_api/buscar?q=panqueques&categoria=Postres&dificultad=Media&tiempo_max=30&ingredientes=harina,huevo,leche
```

### Ejemplos de filtros individuales:
- Por término: `?q=panqueques`
- Por categoría: `?categoria=Postres` *(por nombre o ID)*
- Por dificultad: `?dificultad=Media`
- Por tiempo máximo: `?tiempo_max=30`
- Por ingredientes: `?ingredientes=harina,huevo,leche` *(por nombre, separados por comas)*

### Ejemplos de búsquedas útiles:
```
# Buscar recetas de postres con chocolate
GET http://localhost/recetario_api/buscar?q=chocolate&categoria=Postres

# Buscar recetas fáciles que se hagan en menos de 20 minutos
GET http://localhost/recetario_api/buscar?dificultad=Baja&tiempo_max=20

# Buscar recetas que contengan pollo y arroz
GET http://localhost/recetario_api/buscar?ingredientes=pollo,arroz

# Búsqueda completa: postres con chocolate, fáciles, rápidos
GET http://localhost/recetario_api/buscar?q=chocolate&categoria=Postres&dificultad=Baja&tiempo_max=30

# Buscar desayunos con huevo
GET http://localhost/recetario_api/buscar?categoria=Desayunos&ingredientes=huevo

# Buscar platos principales con pollo, rápidos
GET http://localhost/recetario_api/buscar?categoria=Platos%20Principales&ingredientes=pollo&tiempo_max=45
```

---

## 🍽️ RECETAS

### Obtener todas las recetas
```
GET http://localhost/recetario_api/recetas
```

### Obtener receta específica
```
GET http://localhost/recetario_api/recetas/1
```

### Recetas destacadas (más favoritos)
```
GET http://localhost/recetario_api/recetas/destacadas
```

### Recetas populares (mejor valoradas)
```
GET http://localhost/recetario_api/recetas/populares
```

### Recetas recientes
```
GET http://localhost/recetario_api/recetas/recientes
```

### Recetas por ingredientes específicos
```
GET http://localhost/recetario_api/recetas/por-ingredientes?ingredientes=harina,huevo,leche
```

**Ejemplos prácticos:**
- `?ingredientes=harina` - Recetas que contengan harina
- `?ingredientes=harina,huevo` - Recetas que contengan harina Y huevo
- `?ingredientes=pollo,arroz,cebolla` - Recetas que contengan cualquiera de estos ingredientes

### Crear nueva receta
```
POST http://localhost/recetario_api/recetas
Content-Type: application/json

{
    "nombre": "Panqueques Deliciosos",
    "descripcion": "Panqueques esponjosos perfectos para el desayuno",
    "imagen_url": "https://ejemplo.com/imagen.jpg",
    "porciones": 4,
    "tiempo_preparacion": 15,
    "tiempo_coccion": 10,
    "dificultad": "Media",
    "autor_id": 1,
    "ingredientes": [
        {
            "nombre": "Harina de trigo",
            "cantidad": "2 tazas"
        },
        {
            "nombre": "Huevo",
            "cantidad": "2 unidades"
        }
    ],
    "pasos": [
        {
            "descripcion": "Mezclar los ingredientes secos"
        },
        {
            "descripcion": "Agregar los huevos y la leche"
        }
    ],
    "categorias": ["Desayunos", "Postres"],
    "utensilios": ["Batidora", "Sartén"]
}
```

### Actualizar receta
```
PUT http://localhost/recetario_api/recetas/1
Content-Type: application/json

{
    "nombre": "Panqueques Actualizados",
    "descripcion": "Nueva descripción",
    "dificultad": "Baja"
}
```

### Eliminar receta
```
DELETE http://localhost/recetario_api/recetas/1
```

---

## ⭐ VALORACIONES

### Obtener valoraciones de una receta
```
GET http://localhost/recetario_api/valoraciones/1
```

### Crear/actualizar valoración
```
POST http://localhost/recetario_api/valoraciones
Content-Type: application/json

{
    "receta_id": 1,
    "usuario_id": 1,
    "puntuacion": 5,
    "comentario": "¡Excelente receta!"
}
```

---

## 💬 COMENTARIOS

### Obtener comentarios de una receta
```
GET http://localhost/recetario_api/comentarios/1
```

### Crear comentario
```
POST http://localhost/recetario_api/comentarios
Content-Type: application/json

{
    "receta_id": 1,
    "usuario_id": 1,
    "texto": "Me encantó esta receta, muy fácil de hacer"
}
```

---

## ❤️ FAVORITOS

### Obtener favoritos de un usuario
```
GET http://localhost/recetario_api/favoritos/1
```

### Agregar a favoritos
```
POST http://localhost/recetario_api/favoritos
Content-Type: application/json

{
    "usuario_id": 1,
    "receta_id": 1
}
```

### Quitar de favoritos
```
DELETE http://localhost/recetario_api/favoritos/1?usuario_id=1
```

---

## 🔧 UTENSILIOS

### Obtener todos los utensilios
```
GET http://localhost/recetario_api/utensilios
```

---

## 📊 ESTADÍSTICAS

### Obtener estadísticas generales
```
GET http://localhost/recetario_api/stats
```

---

## 🚀 PASOS PARA PROBAR EN POSTMAN

1. **Configurar el entorno**:
   - Crear una nueva colección llamada "Recetario API"
   - Configurar variable de entorno `base_url` = `http://localhost/recetario_api`

2. **Probar endpoints básicos primero**:
   - GET `/stats` - Para ver estadísticas generales
   - GET `/categorias` - Para ver categorías disponibles
   - GET `/ingredientes` - Para ver ingredientes disponibles

3. **Probar CRUD de recetas**:
   - GET `/recetas` - Listar todas
   - POST `/recetas` - Crear una nueva
   - GET `/recetas/{id}` - Ver la creada
   - PUT `/recetas/{id}` - Actualizarla
   - DELETE `/recetas/{id}` - Eliminarla

4. **Probar funcionalidades avanzadas**:
   - GET `/buscar` con diferentes filtros
   - POST `/valoraciones` - Crear valoraciones
   - POST `/favoritos` - Agregar favoritos

---

## ⚠️ NOTAS IMPORTANTES

- Asegúrate de que tu servidor local esté corriendo
- Verifica que la base de datos `recetario_db` exista y tenga datos
- Para endpoints POST/PUT, usa `Content-Type: application/json`
- Los IDs en las URLs deben ser números válidos existentes en la BD
- Algunos endpoints requieren que existan usuarios y recetas en la base de datos

---

## 🐛 CÓDIGOS DE RESPUESTA

- **200**: Éxito
- **201**: Creado exitosamente
- **400**: Error en los datos enviados
- **404**: Recurso no encontrado
- **500**: Error interno del servidor