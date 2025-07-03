<?php
declare(strict_types=1);

$firstName = "Степан";
$lastName = "Глухарев";
$birthday = "10-07-2005";

/**
 * @throws Exception
 */
function GetAge($birthday): DateInterval
{
    $birthDate = new DateTime($birthday);
    $currentDate = new DateTime();
    return $currentDate->diff($birthDate);
}

try {
    echo "Привет, меня зовут " . $firstName . " " . $lastName .
        " и мне " . GetAge($birthday)->y . " лет. " .
        "Люблю лес, баню, Россию!";
} catch (Exception $e) {
    echo "Ошибка в методе получения возраста.";
}