//Вывод значений полей формы в alert
// Функция для отображения данных из Local Storage

function displayLocalStorageData() {
    const storedData = localStorage.getItem('orderFormData');
    if (storedData) {
        const data = JSON.parse(storedData);
        const outputDiv = document.getElementById('output');
        outputDiv.innerHTML = `
            <h3>Данные из Local Storage:</h3>
            <p><strong>Имя:</strong> ${data.name}</p>
            <p><strong>Телефон:</strong> ${data.phone}</p>
            <p><strong>Размер:</strong> ${data.size}</p>
            <p><strong>Доставка:</strong> ${data.delivery}</p>
            <p><strong>Адрес:</strong> ${data.address}</p>
        `;
    }
}



// При загрузке страницы показать данные из Local Storage, если они есть
window.addEventListener('load', function () {
    displayLocalStorageData();
});




//Валидация полей пользовательской формы
function validateForm() {
    let isValid = true;

    // Сброс ошибок
    document.querySelectorAll('.error').forEach(error => {
        error.textContent = '';
    });

    // Проверка имени
    const name = document.getElementById('name').value.trim();
    if (name === '') {
        document.getElementById('nameError').textContent = 'Поле "Имя" обязательно для заполнения';
        isValid = false;
    }

    // Проверка телефона
    const phone = document.getElementById('phone').value.trim();
    if (phone === '') {
        document.getElementById('phoneError').textContent = 'Поле "Телефон" обязательно для заполнения';
        isValid = false;
    }

    // Проверка размера
    const size = document.getElementById('size').value;
    if (size === '') {
        document.getElementById('sizeError').textContent = 'Необходимо выбрать размер';
        isValid = false;
    }

    // Проверка доставки
    const delivery = document.getElementById('delivery').value;
    if (delivery === '') {
        document.getElementById('deliveryError').textContent = 'Необходимо выбрать способ доставки';
        isValid = false;
    }

    // Проверка адреса (теперь всегда отображается, но не обязательное поле)
    const address = document.getElementById('address').value.trim();
    // Адрес не обязателен, поэтому нет проверки на пустоту

    return isValid;
}




//Проверка на соответствие нужному формату с использованием регулярных выражений
function validateFormWithRegex() {
    let isValid = true;

    // Проверка имени (только буквы и пробелы)
    const name = document.getElementById('name').value.trim();
    const nameRegex = /^[A-Za-zА-Яа-яЁё\s]+$/;
    if (name !== '' && !nameRegex.test(name)) {
        document.getElementById('nameError').textContent = 'Имя может содержать только буквы и пробелы';
        isValid = false;
    }

    // Проверка телефона 
    const phone = document.getElementById('phone').value.trim();
    const phoneRegex = /^(\+375)?\(?\d{2}\)?[\s-]?\d{3}[\s-]?\d{2}[\s-]?\d{2}$/;
    if (phone !== '' && !phoneRegex.test(phone)) {
        document.getElementById('phoneError').textContent = 'Телефон должен быть в формате +375(XX)XXX-XX-XX';
        isValid = false;
    }

    return isValid;
}



//JSON
// Функция для сохранения данных формы в JSON формате
function saveFormDataToJSON(formData) {
    const jsonData = JSON.stringify(formData, null, 2);
    console.log('Данные формы в JSON формате:');
    console.log(jsonData);

    // Сохранение JSON в Local Storage
    localStorage.setItem('orderFormJSON', jsonData);

    return jsonData;
}

// Обработчик отправки формы
document.getElementById('orderForm').addEventListener('submit', function (event) {
    event.preventDefault();

    // Проверка на пустые поля
    if (!validateForm()) {
        return;
    }

    // Проверка формата с помощью регулярных выражений
    if (!validateFormWithRegex()) {
        return;
    }

    // Получение значений полей формы
    const name = document.getElementById('name').value;
    const phone = document.getElementById('phone').value;
    const size = document.getElementById('size').value;
    const delivery = document.getElementById('delivery').value;
    const address = document.getElementById('address').value;

    // Вывод в alert
    alert(`Данные формы:\nИмя: ${name}\nТелефон: ${phone}\nРазмер: ${size}\nДоставка: ${delivery}\nАдрес: ${address}`);

    // Сохранение в Local Storage
    const formData = {
        name: name,
        phone: phone,
        size: size,
        delivery: delivery,
        address: address,
        timestamp: new Date().toISOString()
    };

    localStorage.setItem('orderFormData', JSON.stringify(formData));

    // Сохранение в JSON формате
    saveFormDataToJSON(formData);

    // Вывод данных из Local Storage на экран
    displayLocalStorageData();
});

// При загрузке страницы показать данные из Local Storage, если они есть
//window.addEventListener('load', function () {
  //  displayLocalStorageData();
//});