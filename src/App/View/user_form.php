<?php
?>

<html lang="ru">
<head>
    <title>App</title>
    <meta charset="UTF-8">
</head>
<body>
<h1>Регистрация</h1>

<form action="../../../create_task_action.php" method="POST">
    <p>Название задачи:</p>
    <label>
        <input type="text" name="title" title="Название задачи">
    </label>
    <button type="submit">Создать задачу</button>
</form>

</body>
</html>