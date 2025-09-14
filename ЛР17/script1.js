let clickCount = 0; // Инициализируем счетчик кликов
function countClicks() {
	clickCount++; // Увеличиваем счетчик на 1
    alert("Клик номер " + clickCount); // Выводим номер клика
}

document.addEventListener("DOMContentLoaded", function() {
    const elem = document.getElementById("elem"); // Получаем элемент по id
    elem.onclick = function() {
        alert("Работает!"); // Обработчик события
    };
});

document.addEventListener("DOMContentLoaded", function() {
    const button = document.getElementById("example5"); // Получаем элемент кнопки

    button.addEventListener("click", function() {
        alert("Первый alert!"); // Первый alert
        alert("Второй alert!"); // Второй alert
    });
});

document.addEventListener("DOMContentLoaded", function() {
    const button = document.getElementById("example6"); // Получаем элемент кнопки

    // Определяем функцию-обработчик
    function handleClick() {
        alert("Первый alert!"); // Первый alert
        alert("Второй alert!"); // Второй alert

        // Удаляем обработчик события после первого нажатия
        button.removeEventListener("click", handleClick);
        alert("Обработчик удален!"); // Сообщение о том, что обработчик удален
    }

    // Добавляем обработчик событияё
    button.addEventListener("click", handleClick);
});