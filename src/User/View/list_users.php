<?php
/**
 * @var User[] $users
 */

use App\User\Model\Entity\User;

?>

    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <title>Список пользователей</title>
        <link rel="icon" href="/public/assets/icons/favicon.ico" type="image/x-icon">
        <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
              rel="stylesheet">
        <link rel="stylesheet" href="/public/assets/css/common.css">
        <link rel="stylesheet" href="/public/assets/css/list_users.css">
    </head>
    <body>
    <h1>Список пользователей</h1>

    <div class="user-list-container">
        <?php if (empty($users)): ?>
            <p class="no-users-message">Пользователей пока нет</p>
        <?php else: ?>
            <ul class="user-list">
                <?php foreach ($users as $user): ?>
                    <li class="user-item">
                        <a href="/user/<?= htmlspecialchars($user->getId()) ?>" class="user-name">
                            <?= htmlspecialchars($user->getLastName() . ' ' . $user->getFirstName() . ' ' . $user->getMiddleName()) ?>
                        </a>
                        <div class="actions">
                            <a href="/user/<?= htmlspecialchars($user->getId()) ?>/edit" class="button edit-button">Изменить</a>
                            <a href="/user/<?= htmlspecialchars($user->getId()) ?>/delete" class="button delete-button">Удалить</a>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <div class="button-group">
            <button onclick="window.location.href='/register'">Зарегистрировать нового пользователя</button>
        </div>
    </div>
    </body>
    </html>
<?php
