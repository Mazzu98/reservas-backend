# Guía para correr el proyecto

### Requisitos previos
Antes de comenzar, asegúrate de tener instalados los siguientes requisitos:

- **PHP 8**: Necesario para ejecutar el proyecto.
- **Composer 2**: Utilizado para gestionar las dependencias de PHP.
- **Base de datos**: Asegúrate de tener una base de datos corriendo y configurada.


### Pasos para configurar y ejecutar el proyecto:

1. **Clonar el repositorio:**
    ```bash
    git clone https://github.com/Mazzu98/reservas-backend.git
    ```

2. **Moverse al directorio del proyecto:**
    ```bash
    cd reservas-backend
    ```

3. **Configurar el archivo `.env`:**
    - Crea el archivo `.env` usando la configuración de `.env.test`.
    - Cambia los datos de conexión a la base de datos según tu configuración.

4. **Ejecutar el script de inicialización:**
    ```bash
    ./init.sh
    ```

5. **Iniciar el servidor:**
    ```bash
    php artisan serve
    ```

¡Listo! Ahora el proyecto debería estar corriendo.

**Correr tests**
```bash
    php artisan test
```

**Documentacion Swagger**
Una vez este levantado el servidor, deberia encontrarse en [localhost:8000/api/documentation](http://localhost:8000/api/documentation)