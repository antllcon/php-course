<?php
/**
 * @var $user
 */

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Profile</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
          rel="stylesheet">
    <link rel="stylesheet" href="../../../public/assets/css/common.css">
    <link rel="stylesheet" href="../../../public/assets/css/show_user.css">
</head>
<body>

<?php if ($user->getAvatarPath() !== null) : ?>
    <img src="/public<?= htmlspecialchars($user->getAvatarPath()) ?>" alt="Avatar" style="width: 150px;">
<?php endif; ?>

<div class="user-profile-container">
    <h2>Профиль пользователя</h2>
    <ul class="user-details">
        <li><span>Фамилия:</span> <span><?= htmlspecialchars($user->getLastName()) ?></span></li>
        <li><span>Имя:</span> <span><?= htmlspecialchars($user->getFirstName()) ?></span></li>
        <li><span>Отчество:</span> <span><?= htmlspecialchars($user->getMiddleName()) ?></span></li>
        <li><span>Пол:</span> <span><?= htmlspecialchars($user->getGender()) ?></span></li>
        <li><span>Дата рождения:</span> <span><?= htmlspecialchars($user->getBirthDate()) ?></span></li>
        <li><span>Email:</span> <span><?= htmlspecialchars($user->getEmail()) ?></span></li>
        <li><span>Телефон:</span> <span><?= htmlspecialchars($user->getPhone()) ?></span></li>
    </ul>
    <div class="button-group">
        <button onclick="window.location.href='/register'">Создать</button>
        <button onclick="window.location.href='/user/edit'">Изменить</button>
        <button onclick="window.location.href='/user/delete'">Удалить</button>
    </div>
</div>

</body>
</html>
