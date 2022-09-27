<?php

require_once __DIR__ . '/boot.php';

// Добавим в базу
// print_r($_POST['desc']);

if ($_POST['mark'] == 'add') {
    $stmt = pdo()->prepare("INSERT INTO `tasks` (`name`, `email`, `task`, `description`) VALUES (:username, :email, :task, :descr)");
    $stmt->execute([
        'username' => $_POST['name'],
        'email' => $_POST['email'],
        'task' => $_POST['task'],
        'descr' => htmlspecialchars($_POST['desc']),
    ]);
    // echo "add";
} else {
    $stmt = pdo()->prepare("UPDATE tasks SET status= :status, task= :task WHERE id= :id");
    $stmt->execute([
        'id' => $_POST['id'],
        'status' => $_POST['status'],
        'task' => $_POST['task'],
    ]);
}

header('Location: /');
?>