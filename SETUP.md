# 🚀 Guía de Configuración - API Recetario

## Para el desarrollador que clone el repo:

### 1. Configurar Base de Datos
```bash
# Crear la base de datos MySQL
mysql -u root -p < crear_tablas.sql

# O ejecutar manualmente en phpMyAdmin/MySQL Workbench
```

### 2. Configurar Conexión a BD
```bash
# Copiar archivo de configuración
cp db_config.example.php db_config.php

# Editar db_config.php con tus credenciales:
# - DB_HOST: localhost (o tu host)
# - DB_NAME: recetario_db
# - DB_USER: tu_usuario
# - DB_PASS: tu_contraseña
```

### 3. Configurar Servidor Web
- Apuntar el servidor web a la carpeta del proyecto
- URL base debe ser: `http://localhost/recetario_api`
- Asegurar que PHP tenga PDO MySQL habilitado

### 4. Probar la API
```bash
# Verificar que funciona
curl http://localhost/recetario_api/stats

# O abrir en navegador
http://localhost/recetario_api/stats
```

## 📁 Estructura del Proyecto

```
recetario-api/
├── index.php              # API principal
├── db_config.php          # Configuración BD (no en Git)
├── db_config.example.php  # Ejemplo de configuración
├── crear_tablas.sql       # Script para crear BD
├── verificar_tablas.sql   # Script para verificar BD
├── datos_prueba.sql       # Datos de ejemplo
├── test_api.md           # Documentación Postman
├── swagger.yaml          # Documentación OpenAPI
├── swagger.html          # Visualizador Swagger
├── README.md             # Documentación principal
├── SETUP.md              # Esta guía
└── .gitignore            # Archivos ignorados por Git
```

## 🔧 Tecnologías Requeridas

- **PHP**: 7.4 o superior
- **MySQL**: 5.7 o superior
- **Extensiones PHP**: PDO, PDO_MySQL
- **Servidor Web**: Apache/Nginx

## 📋 Endpoints Disponibles

Ver `test_api.md` para documentación completa con ejemplos de Postman.

### Principales:
- `GET /recetas` - Listar recetas
- `GET /buscar?q=...&categoria=...` - Búsqueda avanzada
- `POST /usuarios` - Crear usuario
- `GET /stats` - Estadísticas

## 🐛 Troubleshooting

### Error de conexión a BD:
1. Verificar credenciales en `db_config.php`
2. Asegurar que MySQL esté corriendo
3. Verificar que la BD `recetario_db` existe

### Error 404 en endpoints:
1. Verificar que la URL base sea correcta
2. Revisar configuración del servidor web
3. Asegurar que mod_rewrite esté habilitado (Apache)

### Error de permisos:
1. Dar permisos de lectura/escritura a la carpeta del proyecto
2. Verificar que PHP pueda acceder a los archivos

## 📞 Contacto

Si tienes problemas con la configuración, contacta al equipo de desarrollo.