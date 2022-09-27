<?php

use LDAP\Result;

require_once __DIR__ . '/boot.php'; ?>
<!DOCTYPE html>
<html>

<head>
  <meta charset="UTF-8">
  <link href="css/bootstrap.min.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
  <script src="js/bootstrap.bundle.min.js"></script>
  <title>Список задач</title>
</head>

<body>
  <?php
  $user = null;

  if (check_auth()) {
    // Получим данные пользователя по сохранённому идентификатору
    $stmt = pdo()->prepare("SELECT * FROM `users` WHERE `id` = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
  }
  ?>
  <!-- Вверхняя панель -->
  <nav class="navbar navbar-expand-lg bg-light mb-3">
    <div class="container">
      <a class="navbar-brand" href="#">Todo</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        </ul>
        <?php if ($user) { ?>

          <span><?= htmlspecialchars($user['username']) ?></span>

          <form class="d-flex ms-2" method="post" action="logout.php">
            <button type="submit" class="btn btn-success">Выйти</button>
          </form>

        <?php } else { ?>

          <?php flash(); ?>

          <form method="post" class="d-flex" action="login.php">
            <input type="text" id="username" name="username" class="form-control me-2" placeholder="Логин" required autofocus>
            <input type="password" id="password" name="password" class="form-control me-2" placeholder="Пароль" required>
            <button class="btn btn-success" type="submit">Войти</button>
          </form>

        <?php } ?>

      </div>
    </div>
  </nav>
  <!-- Тело страницы -->
  <div class="container">
    <h3>
      Список задач
    </h3>

    <?php
    // Поверка, есть ли GET запрос
    if (isset($_GET['pagenum'])) {
      $pagenum = $_GET['pagenum']; // Если да то переменной $pagenum присваиваем его
    } else {
      $pagenum = 1;
    }
    $size_page = 3; // Назначаем количество данных на одной странице
    $offset = ($pagenum - 1) * $size_page; // Вычисляем с какого объекта начать выводить

    // SQL запрос для получения количества тасков
    $count_sql = "SELECT COUNT(*) FROM `tasks`";
    $result = pdo()->prepare($count_sql);
    $result->execute();
    $total_rows = ($result->fetchColumn());
    $total_pages = ceil($total_rows / $size_page);

    //  Все варианты сортировки
    $sort_list = array(
      'name_asc'   => '`name`',
      'name_desc'  => '`name` DESC',
      'email_asc'  => '`email`',
      'email_desc' => '`email` DESC',
      'status_asc'   => '`status`',
      'status_desc'  => '`status` DESC',
    );

    // Проверка GET-переменной сортировка
    $sort = @$_GET['sort'];
    if (array_key_exists($sort, $sort_list)) {
      $sort_sql = $sort_list[$sort];
    } else {
      $sort_sql = reset($sort_list);
    }

    // Функция вывода ссылок 
    function sort_link_th($title, $a, $b)
    {
      $sort = @$_GET['sort'];

      if ($sort == $a) {
        return '<a class="active" href="?sort=' . $b . '">' . $title . ' <i>▲</i></a>';
      } elseif ($sort == $b) {
        return '<a class="active" href="?sort=' . $a . '">' . $title . ' <i>▼</i></a>';
      } else {
        return '<a href="?sort=' . $a . '">' . $title . '</a>';
      }
    }
    ?>
    <!-- Таблица задач -->
    <table class="table table-striped table-sm">
      <thead>
        <tr>
          <th scope="col">#</th>
          <th scope="col"><?= sort_link_th('Имя пользователя', 'name_asc', 'name_desc'); ?></th>
          <th scope="col"><?= sort_link_th('E-mail', 'email_asc', 'email_desc'); ?></th>
          <th scope="col">Задача</th>
          <th scope="col">Описание</th>
          <th scope="col"><?= sort_link_th('Статус', 'status_asc', 'status_desc'); ?></th>
        </tr>
      </thead>
      <tbody>
        <?php
        $_tasks = pdo()->prepare("SELECT * FROM `tasks` ORDER BY {$sort_sql} LIMIT $offset, $size_page ");
        $_tasks->execute();
        while ($tasks = $_tasks->fetch(PDO::FETCH_ASSOC)) {
          echo "<tr>";
          // print_r($tasks);
          echo "<th scope='row'>" . $tasks['id'] . "</th>";
          echo "<td>" . $tasks['name'] . "</td>";
          echo "<td>" . $tasks['email'] . "</td>";
          if ($user) {
        ?>
            <form method="post" action="register.php">
              <td>
                <input type="text" id="task" name="task" class="form-control me-2" placeholder="Задача" required value="<?= $tasks['task'] ?>">
              </td>
              <td>
                <?= $tasks['description'] ?>
              </td>
              <td>
                <input type="checkbox" <?php if ($tasks['status']) echo "checked" ?> class="me-2 check" id=<?= $tasks['id'] ?>>
                <input type="hidden" name="mark" value="edit">
                <input type="hidden" name="id" value="<?= $tasks['id'] ?>">
                <input type="hidden" name="status" id="status_<?= $tasks['id'] ?>" value="<?= $tasks['status'] ?>">
                <button class="btn btn-outline-danger btn-sm" type="submit">ввод </button>
              </td>
            </form>
        <?
          } else {
            echo "<td>" . $tasks['task'] . "</td>";
            echo "<td>" . $tasks['description'] . "</td>";
            echo "<td>";
            echo ($tasks['status']) ? "Выполнено" : "Не выполнено";
            echo "</td>";
          }

          echo "</tr>";
        }
        ?>
        <form method="post" class="d-flex" action="register.php">
          <tr>
            <td>
              <input type="hidden" name="mark" value="add">
            </td>
            <td>
              <input type="text" id="name" name="name" class="form-control me-2" placeholder="Имя" required autofocus>
            </td>
            <td>
              <input type="text" id="email" name="email" class="form-control me-2" placeholder="Email" required>
            </td>
            <td>
              <input type="text" id="task" name="task" class="form-control me-2" placeholder="Задача" required>
            </td>
            <td>
              <input type="text" id="desc" name="desc" class="form-control me-2" placeholder="Описание" required>
            </td>
            <td><button class="btn btn-primary" type="submit">Добавить </button></td>
          </tr>

        </form>
      </tbody>
    </table>

    <!-- Пагинация -->
    <?php
    
    if ($total_pages > 3) {
      ?>
<nav aria-label="Task navigation">
      <ul class="pagination justify-content-center">
        <li class="page-item <?php if ($pagenum <= 1) {
                                echo 'disabled';
                              } ?>">
          <a class="page-link" href="<?php if ($pagenum <= 1) {
                                        echo '#';
                                      } else {
                                        echo "?pagenum=" . ($pagenum - 1);
                                      } ?>" aria-label="Пред.">
            <span aria-hidden="true">&laquo;</span>
          </a>
        </li>
        <?php
        for ($i = 1; $i <= $total_pages; $i++) { ?>
          <li class="page-item <?php if ($pagenum == $i) {
                                  echo 'disabled';
                                } ?>">
            <a class="page-link" href="<?php if ($pagenum === $i) {
                                          echo '#';
                                        } else {
                                          echo "?pagenum=" . $i;
                                        } ?>" aria-label="<?= $i ?>"><?= $i ?></a>
          </li>
        <? } ?>


        <li class="page-item <?php if ($pagenum >= $total_pages) {
                                echo 'disabled';
                              } ?>">
          <a class="page-link" href="<?php if ($pagenum >= $total_pages) {
                                        echo '#';
                                      } else {
                                        echo "?pagenum=" . ($pagenum + 1);
                                      } ?>" aria-label="След.">
            <span aria-hidden="true">&raquo;</span>
          </a>
        </li>
      </ul>
    </nav>
      <?php
    }?>
    
  </div>

</body>
<script src="https://code.jquery.com/jquery-3.6.1.min.js" integrity="sha256-o88AwQnZB+VDvE9tvIXrMQaPlFFSUTR+nldQm1LuPXQ=" crossorigin="anonymous"></script>
<script>
  $(".check").click(function() {
    let checked = $(this).is(':checked') ? 1 : 0;
    $("#status_" + $(this).attr('id')).val(checked);
  })
</script>

</html>