<?php
/**
 * @var $user
 */
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Пользователь <?= htmlspecialchars($user['first_name']) ?></title>
    <link rel="stylesheet" href="/src/App/View/register.css">
</head>
<body>
<h1>Информация о пользователе</h1>

<?php if ($user['avatar_path']) : ?>
    <img src="/public<?= htmlspecialchars($user['avatar_path']) ?>" alt="Avatar" style="width: 150px;">
<?php endif; ?>

<ul>
    <li>Фамилия: <?= htmlspecialchars($user['last_name']) ?></li>
    <li>Имя: <?= htmlspecialchars($user['first_name']) ?></li>
    <li>Отчество: <?= htmlspecialchars($user['middle_name']) ?></li>
    <li>Пол: <?= htmlspecialchars($user['gender']) ?></li>
    <li>Дата рождения: <?= htmlspecialchars($user['birth_date']) ?></li>
    <li>Email: <?= htmlspecialchars($user['email']) ?></li>
    <li>Телефон: <?= htmlspecialchars($user['phone']) ?></li>
</ul>

<a href="/register">Зарегистрировать нового пользователя</a>
</body>
</html>
