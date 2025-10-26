# 🍽️ API Recetario - Estilo Nestlé

Una API REST completa para un sistema de recetas tipo Nestlé, desarrollada en PHP con MySQL.

## 🚀 Características

- ✅ **CRUD completo** para recetas, usuarios, categorías, ingredientes
- ✅ **Búsqueda avanzada** con múltiples filtros
- ✅ **Sistema de valoraciones** y comentarios
- ✅ **Favoritos** de usuarios
- ✅ **Filtros intuitivos** por nombre (ingredientes y categorías)
- ✅ **Documentación Swagger** incluida

## 📋 Endpoints Principales

### Recetas
- `GET /recetas` - Listar todas las recetas
- `GET /recetas/{id}` - Obtener receta específica
- `GET /recetas/destacadas` - Recetas más populares
- `GET /recetas/populares` - Mejor valoradas
- `GET /recetas/recientes` - Más recientes
- `POST /recetas` - Crear nueva receta
- `PUT /recetas/{id}` - Actualizar receta
- `DELETE /recetas/{id}` - Eliminar receta

### Búsqueda
- `GET /buscar?q={término}&categoria={nombre}&dificultad={nivel}&tiempo_max={minutos}&ingredientes={nombres}`

### Usuarios
- `GET /usuarios` - Listar usuarios
- `GET /usuarios/{id}` - Perfil de usuario
- `POST /usuarios` - Crear usuario
- `PUT /usuarios/{id}` - Actualizar usuario
- `DELETE /usuarios/{id}` - Eliminar usuario

### Otros
- `GET /categorias` - Listar categorías
- `GET /ingredientes` - Listar ingredientes
- `GET /valoraciones/{receta_id}` - Valoraciones de receta
- `GET /favoritos/{usuario_id}` - Favoritos de usuario
- `GET /stats` - Estadísticas generales

## 🛠️ Instalación

### 1. Clonar el repositorio
```bash
git clone [URL_DEL_REPO]
cd recetario-api
```

### 2. Configurar base de datos
```bash
# Crear base de datos MySQL
mysql -u root -p < crear_tablas.sql

# Configurar conexión
cp db_config.example.php db_config.php
# Editar db_config.php con tus credenciales
```

### 3. Configurar servidor web (rápido, usando PHP built-in)
- Asegurar que PHP tenga extensión PDO MySQL habilitada
- Usar el servidor integrado de PHP para desarrollo local

En PowerShell, desde la raíz del proyecto ejecuta:
```powershell
# Levantar servidor PHP en el puerto 8000 y usar router.php para servir archivos
php -S localhost:8000 router.php
```

Luego abre en el navegador:

- API base: `http://localhost:8000/recetario_api/index.php`
- Swagger UI: `http://localhost:8000/recetario_api/swagger.html`

Si prefieres usar Apache/Nginx o Docker, apunta el DocumentRoot a la carpeta del proyecto y configura la URL base como `/recetario_api`.

### 4. Configurar con XAMPP (opción recomendada si ya tenés XAMPP)

1. Copiá el proyecto a la carpeta `htdocs` de XAMPP (puedes usar el script incluido `start-xampp-dev.ps1`):

```powershell
# Abrir PowerShell como administrador en la carpeta del proyecto
.\start-xampp-dev.ps1
```

2. Abrí el XAMPP Control Panel y arrancá Apache y MySQL.

3. Importá la base de datos usando phpMyAdmin (http://localhost/phpmyadmin):

	- Ir a Importar -> elegir `crear_tablas.sql` -> Ejecutar.

4. Accedé a la API y Swagger:

	- Swagger UI: http://localhost/recetario_api/swagger.html
	- API base: http://localhost/recetario_api/index.php

5. Si necesitás cambiar credenciales de la DB, edita `db_config.php` en la carpeta del proyecto (por ejemplo `C:\xampp\htdocs\recetario_api\db_config.php`).

## 📖 Documentación

### Postman
Ver `test_api.md` para ejemplos completos de uso con Postman.

### Swagger
Abrir `swagger.html` en el navegador para documentación interactiva.

## 🗄️ Estructura de Base de Datos

- **Usuarios** - Autores de recetas
- **Recetas** - Recetas principales
- **Categorias** - Postres, Desayunos, etc.
- **Ingredientes** - Ingredientes disponibles
- **Utensilios** - Herramientas de cocina
- **Valoraciones** - Ratings 1-5 estrellas
- **Comentarios** - Comentarios de usuarios
- **Favoritos** - Recetas favoritas
- **Pasos** - Pasos de preparación
- Tablas de relación para many-to-many

## 🔧 Tecnologías

- **Backend**: PHP 7.4+
- **Base de datos**: MySQL 5.7+
- **Arquitectura**: REST API
- **Documentación**: Swagger/OpenAPI 3.0

## 🌟 Características Destacadas

### Búsqueda Intuitiva
```
# Buscar postres con chocolate, fáciles, rápidos
GET /buscar?categoria=Postres&q=chocolate&dificultad=Baja&tiempo_max=30

# Buscar recetas con ingredientes específicos
GET /buscar?ingredientes=pollo,arroz,cebolla
```

### Filtros por Nombre
- **Categorías**: `?categoria=Postres` en lugar de `?categoria=1`
- **Ingredientes**: `?ingredientes=harina,huevo` en lugar de IDs

### Respuestas Enriquecidas
- Recetas incluyen rating promedio, autor, ingredientes con cantidades
- Búsquedas ordenadas por relevancia
- Estadísticas en tiempo real

## 🤝 Contribuir

1. Fork el proyecto
2. Crear rama feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## 📝 Licencia

Este proyecto está bajo la Licencia MIT.

## 👥 Equipo

- **Backend Developer**: [Tu nombre]
- **Frontend/Swagger**: [Nombre del compañero]

## 🐛 Reportar Issues

Si encuentras algún bug o tienes sugerencias, por favor crea un issue en GitHub.

---

**¡Disfruta cocinando con nuestra API! 👨‍🍳👩‍🍳**