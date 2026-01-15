# To-Do List API (на чистом PHP)

Простое RESTful API для управления задачами (To-Do List), написанное на **чистом PHP** с использованием **MySQL**.  
Без фреймворков (Laravel не используется). Подходит для тестовых заданий, обучения или лёгких проектов.

---

##  Возможности

-  Полный CRUD:
  - `POST /tasks` — создать новую задачу
  - `GET /tasks` — получить список всех задач
  - `GET /tasks/{id}` — получить задачу по ID
  - `PUT /tasks/{id}` — обновить задачу
  - `DELETE /tasks/{id}` — удалить задачу
-  Валидация данных:
  - Поле `title` обязательно и не может быть пустым
  - Поле `status` должно быть одним из: `pending`, `in_progress`, `completed`
-  Поддержка CORS — можно использовать с фронтендом (React, Vue, vanilla JS и т.д.)
-  Нет зависимостей — только PHP + MySQL
-  Включён простой HTML-интерфейс для ручного тестирования

---

## Требования

- PHP 7.4+ (с расширениями PDO и MySQL)
- MySQL 5.7+ или MariaDB
- Веб-сервер с поддержкой `.htaccess` (рекомендуется Apache)
- OpenServer, XAMPP, WAMP или аналогичный локальный сервер

---

## Установка

1. **Склонируйте репозиторий**
   ```bash
   git clone https://github.com/MinchWithRoots/todo-api.git
   cd todo-api

2. **Создайте базу данных в MySQL**
   Выполните в phpMyAdmin или через консоль:
   ```sql
   CREATE DATABASE IF NOT EXISTS `to-do` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

3. **Создайте таблицу tasks**
   ```sql
   USE `to-do`;
   CREATE TABLE IF NOT EXISTS tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title TEXT NOT NULL,
    description TEXT,
    status VARCHAR(50) DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
   ) ENGINE=InnoDB CHARSET=utf8mb4;

4. **Настройте подключение к БД**
   Откройте config.php и укажите свои параметры:
   ```php
   $host = 'localhost';
   $dbname = 'to-do';
   $username = 'ваш_пользователь'; // обычно 'root'
   $password = 'ваш_пароль';       // часто пустой в OpenServer/XAMPP

5. **Разместите проект на сервере**
  - Поместите папку проекта в директорию веб-сервера:
  - OpenServer: domains/localhost/todo-api/
  - XAMPP: htdocs/todo-api/
  - Убедитесь, что .htaccess работает (включён mod_rewrite)

6. **Запустите сервер**
  - Запустите Apache через OpenServer/XAMPP
  - Перейдите в браузере:
  - http://localhost/todo-api/ — откроется HTML-интерфейс
  - http://localhost/todo-api/tasks — вернёт JSON со списком задач
