# 🎮 ReviewPlay

**ReviewPlay** es una aplicación web de reseñas de videojuegos desarrollada con **Laravel (Sanctum)** en el backend y **React** (SPA con React Router DOM) en el frontend. Su objetivo es ofrecer una plataforma moderna y funcional donde los usuarios puedan descubrir, reseñar y seguir juegos y personas con intereses similares.

## 🚀 Características principales

- 🔐 Registro de usuarios con **verificación por email**
- 🕹️ Búsqueda de videojuegos con **filtros dinámicos**
- ⭐ Calificaciones de juegos del **1 al 10**
- 📌 Guardar juegos como **favoritos**
- 🔔 **Notificaciones internas** dentro de la app
- 👥 Posibilidad de **seguir a otros usuarios** y también a juegos
- 🧑‍💼 Personalización del perfil con **biografía** e **imagen recortable**
- 🔍 Búsqueda de usuarios por nombre
- 🧠 Optimización de peticiones: los juegos se almacenan localmente solo si el usuario interactúa con ellos (reseña, seguimiento o favorito)

## 🧱 Tecnologías utilizadas

- **Frontend:** React, Tailwind CSS, React Router DOM
- **Backend:** Laravel 11, Sanctum, Laravel API Resources
- **Autenticación:** API Token con Sanctum
- **Base de datos:** MySQL
- **Otros:** React Cropper (para imágenes), Axios, React Mmodal, React Slick, Sweetalert2 React, RAWG (para los juegos)

## 📸 Capturas

| ![Imagen 1](/screenshots/1.png) | ![Imagen 2](/screenshots/2.png) |
| :-----------------------------: | :-----------------------------: |
| ![Imagen 1](/screenshots/3.png) | ![Imagen 2](/screenshots/4.png) |

## 📎 Enlaces

- [Sitio en vivo](https://reviewplay.brayandev.com/)
- [Repositorio Backend](https://github.com/Pex-Dev/reviewplay)
- [Repositorio Frontend](https://github.com/Pex-Dev/reviewplay-frontend)

## 🚀 Cómo ejecutar este proyecto localmente
Este proyecto tiene un frontend en React y un backend en Laravel, conectados mediante API con autenticación usando Laravel Sanctum.

📦 Requisitos
- Docker instalado

- Composer instalado (solo se necesita para ejecutar composer install una vez al comienzo)

- Una cuenta en RAWGD para la API de juegos

## 🔧Instalación

**Clonar el repositorio:**
```bash
git clone https://github.com/Pex-Dev/reviewplay.git
cd reviewplay
```
**Copiar archivo env**
```bash
cp .env.example .env
```
**Editar archivo .env**

    Abre .env y ajusta estas variables según tu entorno local

**Instalar dependencias de php**
```bash
composer install
```
**Levantar los contenedores Docker con Sail**
```bash
./vendor/bin/sail up -d
```
**Generar la clave de la aplicación para que laravel funcione correctamente**
```bash
./vendor/bin/sail artisan key:generate
```

**Ejecutar migraciones**
```bash
./vendor/bin/sail artisan migrate
```