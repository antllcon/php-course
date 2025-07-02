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
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
          rel="stylesheet">
    <link rel="stylesheet" href="/src/App/View/show_user.css">
</head>
<body>

<?php if ($user['avatar_path']) : ?>
    <img src="/public<?= htmlspecialchars($user['avatar_path']) ?>" alt="Avatar" style="width: 150px;">
<?php endif; ?>

<div class="user-profile-container">
    <h2>Профиль пользователя</h2>
    <ul class="user-details">
        <li><span>Фамилия:</span> <span><?= htmlspecialchars($user['last_name']) ?></span></li>
        <li><span>Имя:</span> <span><?= htmlspecialchars($user['first_name']) ?></span></li>
        <li><span>Отчество:</span> <span><?= htmlspecialchars($user['middle_name']) ?></span></li>
        <li><span>Пол:</span> <span><?= htmlspecialchars($user['gender']) ?></span></li>
        <li><span>Дата рождения:</span> <span><?= htmlspecialchars($user['birth_date']) ?></span></li>
        <li><span>Email:</span> <span><?= htmlspecialchars($user['email']) ?></span></li>
        <li><span>Телефон:</span> <span><?= htmlspecialchars($user['phone']) ?></span></li>
    </ul>
    <button onclick="window.location.href='/register'">Зарегистрировать нового пользователя</button>
</div>

</body>
</html>
