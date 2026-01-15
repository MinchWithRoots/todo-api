<?php

// CORS headers — для работы из браузера
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Обработка preflight-запросов
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Определяем базовый путь проекта (если лежит в подпапке)
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
if ($scriptDir === '/') {
    $basePath = '';
} else {
    $basePath = rtrim($scriptDir, '/');
}

// Удаляем базовый путь из URI
if (!empty($basePath) && strpos($path, $basePath) === 0) {
    $path = substr($path, strlen($basePath));
}

// Обработка корневого пути — открыть index.html
if ($path === '' || $path === '/') {
    if (file_exists('index.html')) {
        header('Content-Type: text/html; charset=utf-8');
        readfile('index.html');
        exit;
    }
}

// Разбиваем путь на части
$parts = explode('/', trim($path, '/'));

// Определяем маршрут: /tasks или /tasks/{id}
if (isset($parts[0]) && $parts[0] === 'tasks') {
    if (isset($parts[1]) && is_numeric($parts[1])) {
        $taskId = (int)$parts[1];
        handleTaskById($method, $taskId);
    } else {
        handleTasks($method);
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
    exit;
}

// Обработка коллекции задач: GET /tasks, POST /tasks
function handleTasks($method)
{
    global $pdo;

    switch ($method) {
        case 'GET':
            // Получить все задачи
            $stmt = $pdo->query("SELECT * FROM tasks ORDER BY created_at DESC");
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($tasks, JSON_UNESCAPED_UNICODE); // ← чтобы русские буквы не экранировались
            break;

        case 'POST':
            // Создать новую задачу
            $input = json_decode(file_get_contents('php://input'), true);

            // Проверка на валидный JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON']);
                return;
            }

            if (!isset($input['title']) || empty(trim($input['title']))) {
                http_response_code(400);
                echo json_encode(['error' => 'Title is required and cannot be empty']);
                return;
            }

            $title = trim($input['title']);
            $description = isset($input['description']) ? $input['description'] : null;
            $status = isset($input['status']) ? $input['status'] : 'pending';

            // Валидация статуса
            $allowedStatuses = ['pending', 'in_progress', 'completed'];
            if (!in_array($status, $allowedStatuses)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid status. Allowed: pending, in_progress, completed']);
                return;
            }

            $stmt = $pdo->prepare("INSERT INTO tasks (title, description, status) VALUES (?, ?, ?)");
            $stmt->execute([$title, $description, $status]);

            $newId = $pdo->lastInsertId();
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$newId]);
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            http_response_code(201);
            echo json_encode($task, JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}

// Обработка одной задачи: GET/PUT/DELETE /tasks/{id}
function handleTaskById($method, $taskId)
{
    global $pdo;

    // Проверка существования задачи
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        http_response_code(404);
        echo json_encode(['error' => 'Task not found']);
        return;
    }

    switch ($method) {
        case 'GET':
            echo json_encode($task, JSON_UNESCAPED_UNICODE);
            break;

        case 'PUT':
            $input = json_decode(file_get_contents('php://input'), true);

            // Проверка на валидный JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid JSON']);
                return;
            }

            $title = isset($input['title']) ? trim($input['title']) : $task['title'];
            if (empty($title)) {
                http_response_code(400);
                echo json_encode(['error' => 'Title cannot be empty']);
                return;
            }

            $description = isset($input['description']) ? $input['description'] : $task['description'];
            $status = isset($input['status']) ? $input['status'] : $task['status'];

            // Валидация статуса
            $allowedStatuses = ['pending', 'in_progress', 'completed'];
            if (!in_array($status, $allowedStatuses)) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid status. Allowed: pending, in_progress, completed']);
                return;
            }

            $stmt = $pdo->prepare("UPDATE tasks SET title = ?, description = ?, status = ? WHERE id = ?");
            $stmt->execute([$title, $description, $status, $taskId]);

            // Возвращаем обновлённую задачу
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);
            $updatedTask = $stmt->fetch(PDO::FETCH_ASSOC);

            echo json_encode($updatedTask, JSON_UNESCAPED_UNICODE);
            break;

        case 'DELETE':
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$taskId]);

            echo json_encode(['message' => 'Task deleted successfully']);
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }
}
