<?php
/**
 * @var $user User
 * @var $userId int
 * @var $error string|null
 */

use App\User\Model\Entity\User;

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактировать пользователя</title>
    <link rel="icon" href="/public/assets/icons/favicon.ico" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
          rel="stylesheet">
    <link rel="stylesheet" href="/public/assets/css/common.css">
    <link rel="stylesheet" href="/public/assets/css/register.css"> </head>
<body>
<h1>Редактируем профиль</h1>
<div class="form-container">
    <?php if (!empty($error)): ?>
        <div style="color: red; margin-bottom: 15px; text-align: center;"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form action="/user/<?= htmlspecialchars($userId) ?>/edit" method="post" enctype="multipart/form-data">
        <div class="avatar-upload">
            <div class="avatar-preview" id="avatarPreview"
                <?php if ($user->getAvatarPath()): ?>
                    style="background-image: url('/public<?= htmlspecialchars($user->getAvatarPath()) ?>'); background-size: cover; background-position: center;"
                <?php endif; ?>
            >
                <?php if (!$user->getAvatarPath()): ?>
                    <span>Фото</span>
                <?php endif; ?>
            </div>
            <input type="file" name="avatar" id="avatarUpload" accept="image/*" style="display: none;">
            <button type="button" onclick="document.getElementById('avatarUpload').click()">Выбрать фото</button>
            <?php if ($user->getAvatarPath()): ?>
                <button type="button" onclick="removeAvatar()">Удалить фото</button>
                <input type="hidden" name="remove_avatar" id="removeAvatarInput" value="0">
            <?php endif; ?>
        </div>

        <div class="name-fields">
            <div class="form-group">
                <label for="last_name">Фамилия</label>
                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user->getLastName()) ?>" required>
            </div>

            <div class="form-group">
                <label for="first_name">Имя</label>
                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user->getFirstName()) ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label for="middle_name">Отчество</label>
            <input type="text" id="middle_name" name="middle_name" value="<?= htmlspecialchars($user->getMiddleName() ?? '') ?>">
        </div>

        <div class="form-group">
            <label>Пол</label>
            <div class="radio-group">
                <div class="radio-option">
                    <input type="radio" id="male" name="gender" value="male" <?= $user->getGender() === 'male' ? 'checked' : '' ?> required>
                    <label for="male">Мужской</label>
                </div>
                <div class="radio-option">
                    <input type="radio" id="female" name="gender" value="female" <?= $user->getGender() === 'female' ? 'checked' : '' ?>>
                    <label for="female">Женский</label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="birth_date">Дата рождения</label>
            <input type="date" id="birth_date" name="birth_date" value="<?= htmlspecialchars(date('Y-m-d', strtotime($user->getBirthDate()))) ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user->getEmail()) ?>" required>
        </div>

        <div class="form-group">
            <label for="phone">Телефон</label>
            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($user->getPhone() ?? '') ?>" placeholder="+7 (XXX) XXX-XX-XX">
        </div>

        <div class="button-group"> <button type="submit">Сохранить</button>
            <button type="button" onclick="window.location.href='/user/<?= htmlspecialchars($userId) ?>'">Отмена</button>
        </div>
    </form>
</div>

<script>
    // Превью аватарки
    document.getElementById('avatarUpload').addEventListener('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (event) {
                const preview = document.getElementById('avatarPreview');
                preview.style.backgroundImage = `url('${event.target.result}')`;
                preview.style.backgroundSize = 'cover';
                preview.style.backgroundPosition = 'center';
                preview.innerHTML = ''; // Убираем текст "Фото"
                // Если есть кнопка "Удалить фото", убедимся, что она не скрыта
                const removeBtn = document.querySelector('.avatar-upload button:nth-of-type(2)');
                if (removeBtn) removeBtn.style.display = 'block';
                const removeInput = document.getElementById('removeAvatarInput');
                if (removeInput) removeInput.value = '0';
            }
            reader.readAsDataURL(file);
        }
    });

    // Функция для удаления аватарки
    function removeAvatar() {
        const preview = document.getElementById('avatarPreview');
        preview.style.backgroundImage = 'none';
        preview.innerHTML = '<span>Фото</span>'; // Возвращаем текст "Фото"
        const removeInput = document.getElementById('removeAvatarInput');
        if (removeInput) removeInput.value = '1'; // Устанавливаем флаг для удаления
        // Скрываем кнопку "Удалить фото" после нажатия
        const removeBtn = document.querySelector('.avatar-upload button:nth-of-type(2)');
        if (removeBtn) removeBtn.style.display = 'none';
    }


    // Маска для телефона
    document.getElementById('phone').addEventListener('input', function (e) {
        let x = e.target.value.replace(/\D/g, '').match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
        // Добавляем проверку на существование групп, чтобы избежать ошибок для неполных номеров
        let formattedPhone = '';
        if (x) {
            formattedPhone = !x[2] ? x[1] : '+' + x[1] + ' (' + x[2] + ')' + (x[3] ? ' ' + x[3] : '') + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '');
        }
        e.target.value = formattedPhone;
    });

    // Инициализация маски при загрузке страницы для уже существующего номера
    document.addEventListener('DOMContentLoaded', function() {
        const phoneInput = document.getElementById('phone');
        if (phoneInput.value) {
            let x = phoneInput.value.replace(/\D/g, '').match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
            let formattedPhone = '';
            if (x) {
                formattedPhone = !x[2] ? x[1] : '+' + x[1] + ' (' + x[2] + ')' + (x[3] ? ' ' + x[3] : '') + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '');
            }
            phoneInput.value = formattedPhone;
        }
    });
</script>
</body>
</html>