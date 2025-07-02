<html lang="ru">
<head>
    <title>App</title>
    <meta charset="UTF-8">
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&display=swap"
          rel="stylesheet">
    <link rel="stylesheet" href="/src/App/View/common.css">
    <link rel="stylesheet" href="/src/App/View/register.css">
</head>
<body>
<h1>Регистрация</h1>
<div class="form-container">
    <form action="/register/save" method="POST" enctype="multipart/form-data">
        <div class="avatar-upload">
            <div class="avatar-preview" id="avatarPreview">
                <span>Фото</span>
            </div>
            <input type="file" name="avatar" id="avatarUpload" accept="image/*" style="display: none;">
            <button type="button" onclick="document.getElementById('avatarUpload').click()">Выбрать фото</button>
        </div>

        <div class="name-fields">
            <div class="form-group">
                <label for="last_name">Фамилия</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>

            <div class="form-group">
                <label for="first_name">Имя</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>
        </div>

        <div class="form-group">
            <label for="middle_name">Отчество</label>
            <input type="text" id="middle_name" name="middle_name">
        </div>

        <div class="form-group">
            <label>Пол</label>
            <div class="radio-group">
                <div class="radio-option">
                    <input type="radio" id="male" name="gender" value="male" required>
                    <label for="male">Мужской</label>
                </div>
                <div class="radio-option">
                    <input type="radio" id="female" name="gender" value="female">
                    <label for="female">Женский</label>
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="birth_date">Дата рождения</label>
            <input type="date" id="birth_date" name="birth_date" required>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required>
        </div>

        <div class="form-group">
            <label for="phone">Телефон</label>
            <input type="tel" id="phone" name="phone" placeholder="+7 (XXX) XXX-XX-XX">
        </div>

        <button type="submit">Зарегистрироваться</button>
    </form>

    <script>
        // Превью аватарки
        document.getElementById('avatarUpload').addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (event) {
                    const preview = document.getElementById('avatarPreview');
                    preview.innerHTML = '';
                    const img = document.createElement('img');
                    img.src = event.target.result;
                    preview.appendChild(img);
                }
                reader.readAsDataURL(file);
            }
        });

        // Маска для телефона
        document.getElementById('phone').addEventListener('input', function (e) {
            let x = e.target.value.replace(/\D/g, '').match(/(\d{0,1})(\d{0,3})(\d{0,3})(\d{0,2})(\d{0,2})/);
            e.target.value = !x[2] ? x[1] : '+' + x[1] + ' (' + x[2] + ') ' + x[3] + (x[4] ? '-' + x[4] : '') + (x[5] ? '-' + x[5] : '');
        });
    </script>
</body>
</html>