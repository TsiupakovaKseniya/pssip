document.addEventListener('DOMContentLoaded', function () {
    const testBtn = document.querySelector('.test__btn');

    // Создаем элемент подсказки
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = 'Узнать сейчас';
    document.body.appendChild(tooltip);

    // Показываем подсказку при наведении
    testBtn.addEventListener('mouseenter', function (e) {
        const rect = this.getBoundingClientRect();
        tooltip.style.display = 'block';
        tooltip.style.left = `${rect.left + rect.width / 2 - tooltip.offsetWidth / 2}px`;
        tooltip.style.top = `${rect.top - tooltip.offsetHeight - 5}px`;
    });

    // Скрываем подсказку при уходе курсора
    testBtn.addEventListener('mouseleave', function () {
        tooltip.style.display = 'none';
    });
});