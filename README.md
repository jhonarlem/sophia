# 🏪 SOPHIA POS

![PHP](https://img.shields.io/badge/PHP-^8.2-777BB4.svg?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-^11.9-FF2D20.svg?logo=laravel&logoColor=white)
![Filament](https://img.shields.io/badge/FilamentPHP-v3.2-gold.svg?logo=filament)
![Shield](https://img.shields.io/badge/FilamentShield-^3.2-gold.svg)
![Version](https://img.shields.io/badge/Version-1.0.1-blue.svg)
![License](https://img.shields.io/badge/License-Proprietary-red.svg)

## 🎯 Acerca de SOPHIA

**SOPHIA** (Software for Organized Product Handling and Inventory Administration) es una solución POS avanzada diseñada específicamente para el mercado colombiano. Optimizada para la gestión integral de negocios minoristas, especialmente en el sector de electrodomésticos y productos para el hogar.

## 🛠️ Características Principales

### Interfaz y Usabilidad
- ✨ Interfaz moderna y responsiva con FilamentPHP
- 🔒 Sistema robusto de permisos y roles
- 📱 Diseño adaptable para diferentes dispositivos
- 🎨 Tema personalizado y optimizado para uso prolongado
- 🚀 Navegación intuitiva y rápida

### Gestión de Ventas y Facturación
- 🧾 Emisión de facturas en formato voucher (80mm)
- 📄 Facturación digital POS
- 🔢 Control de numeración según resolución POS electrónico
- 💳 Múltiples métodos de pago
- 🏷️ Gestión de precios y descuentos
- 📱 Vista de facturación optimizada multiples dispositivos

### Inventario y Control
- 📦 Control avanzado de stock en tiempo real
- ⚠️ Registro y gestión de pérdidas de productos
- 🔍 Búsqueda y filtrado avanzado de productos
- 💰 Control de caja con apertura y cierre
- 📝 Registro detallado de movimientos
- 🏷️ Gestión de categorías
- 📊 Control de productos en stock mínimo

### Sistema de Crédito
- 👥 Gestión de créditos por cliente
- 💵 Múltiples métodos de pago para créditos
- 📅 Control de pagos
- 📊 Historial crediticio detallado
- 📝 Registro de garantías

### Reportes y Análisis
- 📈 Reportes personalizables con exportación a Excel
- 📊 Análisis de ventas e inventario
- 💰 Reportes de caja y movimientos
- 📉 Análisis de tendencias
- 🎯 Métricas de rendimiento
- 🔄 Generación de notas crédito/débito

### Seguridad y Configuración
- 🔐 Control granular de permisos
- 👥 Gestión avanzada de usuarios y roles
- 📝 Registro de auditoría detallado
- ⚙️ Configuraciones personalizables
- 🔒 Protección contra accesos no autorizados

## 📸 Capturas de Pantalla

### Escritorio

- Modo oscuro
[![Escritorio.png](https://i.postimg.cc/Gh1TZ1Fp/Escritorio.png)](https://postimg.cc/4nB39qFC)

- Modo claro
[![Escritorio-Claro.png](https://i.postimg.cc/Y9qbNWSJ/Escritorio-Claro.png)](https://postimg.cc/B8kT4tHg)

### Módulo de Ventas

- Listado de Ventas
[![Listado-Ventas.png](https://i.postimg.cc/yxNRrh4z/Listado-Ventas.png)](https://postimg.cc/bDKdZtw6)

- Detalles de Venta
[![Ver-Venta.png](https://i.postimg.cc/ZqZCMPpD/Ver-Venta.png)](https://postimg.cc/v4qY1VPW)

### Inventario

- Listado de Ventas
[![Productos.png](https://i.postimg.cc/xdTbdy8N/Productos.png)](https://postimg.cc/k2z51Sx7)

### Clientes
- Formulario de registro
[![Registro-Cliente.png](https://i.postimg.cc/5081dDJB/Registro-Cliente.png)](https://postimg.cc/0M5h7Wbj)

### Inicio de sesión
- Formulario de ingreso
[![Login.png](https://i.postimg.cc/pL3gWkqq/Login.png)](https://postimg.cc/18cWM0xq)

> [!CAUTION]
> ## AVISO LEGAL IMPORTANTE
> Este software está protegido por derechos de autor y es de uso privativo. Queda estrictamente prohibido:
> - La reproducción total o parcial del software
> - La distribución no autorizada
> - El uso sin licencia válida
> - La modificación del código fuente
> - La ingeniería inversa
> 
> El incumplimiento de estas restricciones está sujeto a acciones legales según la legislación colombiana sobre propiedad intelectual y derechos de autor.

>[!NOTE]
**Para obtener autorización de uso, contacte al desarrollador.**

## 🚀 Instalación

### Prerequisitos

- PHP 8.2 o superior
- Composer
- MariaDB/MySQL
- Node.js y NPM

### Pasos de Instalación

1. **Clonar el repositorio**
```bash
git clone https://github.com/gotsudev/sophia.git
cd sophia
```

2. **Instalar dependencias**
```bash
composer install
```

3. **Configurar el entorno**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configurar la base de datos**

Modifica el archivo `.env` con tus credenciales:
```env
DB_CONNECTION=mariadb
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sophia
DB_USERNAME=sophia
DB_PASSWORD=
```

5. **Ejecutar migraciones**
```bash
php artisan migrate
```

6. **Crear usuario administrador**
```bash
php artisan make:filament-user
```

7. **Instalar Shield (Sistema de permisos)**
```bash
php artisan shield:install
```
> [!WARNING]
> ⚠️ **¡ADVERTENCIA IMPORTANTE!**
> 
> El comando `shield:install` sobrescribe las políticas por defecto, afectando específicamente a:
> - `SalePolicy.php`
> - `CreditPolicy.php`
> 
> Para restaurar las políticas personalizadas, es necesario hacer un pull desde el repositorio después de la instalación de Shield.

### Optimización y Configuración Final

1. **Optimizar Filament para producción**
```bash
php artisan filament:optimize
```

2. **Crear enlace simbólico para almacenamiento**
```bash
php artisan storage:link
```
## 💡 Soporte

Para soporte técnico y consultas sobre licenciamiento, contacte directamente al desarrollador.

## 📄 Licencia

Este software está protegido por derechos de autor. Todos los derechos reservados. El uso no autorizado está prohibido y será perseguido legalmente según las leyes colombianas.

---
Desarrollado con ❤️ para el comercio Colombiano