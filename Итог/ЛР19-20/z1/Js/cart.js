let cart = JSON.parse(localStorage.getItem('cart')) || [];
let products = [];

// Загрузка данных из JSON
async function loadProducts() {
    try {
        const response = await fetch('./data.json');
        const data = await response.json();
        products = data.products;
        updatePopularModals();
        updateCartUI();
    } catch (error) {
        console.error('Ошибка загрузки данных:', error);
    }
}

// Обновление популярных моделей из JSON
function updatePopularModals() {
    const wrapper = document.querySelector('.popular-modal__wrapper');
    if (!wrapper) return;

    wrapper.innerHTML = products.map(product => `
            <div class="popular-modal__item">
                <div class="popular-modal__img">
                    <img src="${product.image}" alt="${product.name}" style="cursor: pointer;" onclick="openProductModal(${product.id})">
                </div>
                <div class="popular-modal__info">
                    <a style="cursor: pointer;" onclick="openProductModal(${product.id})">${product.name}<br>Длина ${product.length}</a>
                    <div class="popular-modal__price"> 
                        <p>${product.oldPrice.toLocaleString()} ₽</p>
                        <p>${product.price.toLocaleString()} ₽</p>
                    </div>
                </div>
                <button class="popular-modal__plus" onclick="addToCart(${product.id})">+</button>
            </div>
        `).join('');
}

// Функции корзины
function addToCart(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;

    const existingItem = cart.find(item => item.id === productId);

    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            ...product,
            quantity: 1
        });
    }

    saveCart();
    updateCartUI();
    showAddToCartMessage(product.name);
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    saveCart();
    updateCartUI();
}

function updateQuantity(productId, change) {
    const item = cart.find(item => item.id === productId);
    if (item) {
        item.quantity += change;
        if (item.quantity <= 0) {
            removeFromCart(productId);
        } else {
            saveCart();
            updateCartUI();
        }
    }
}

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

function updateCartUI() {
    const cartCount = document.getElementById('cartCount');
    const cartItems = document.getElementById('cartItems');
    const cartTotal = document.getElementById('cartTotal');

    if (cartCount) {
        cartCount.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
    }

    if (cartItems) {
        if (cart.length === 0) {
            cartItems.innerHTML = '<div class="cart__empty">Корзина пуста</div>';
        } else {
            cartItems.innerHTML = cart.map(item => `
                    <div class="cart__item">
                        <img src="${item.image}" alt="${item.name}" class="cart__item-image">
                        <div class="cart__item-info">
                            <div class="cart__item-name">${item.name}</div>
                            <div class="cart__item-price">${item.price.toLocaleString()} ₽</div>
                            <div class="cart__item-controls">
                                <div class="cart__item-quantity">
                                    <button class="cart__quantity-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
                                    <span>${item.quantity}</span>
                                    <button class="cart__quantity-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
                                </div>
                                <button class="cart__remove" onclick="removeFromCart(${item.id})">Удалить</button>
                            </div>
                        </div>
                    </div>
                `).join('');
        }
    }

    if (cartTotal) {
        const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        cartTotal.textContent = total.toLocaleString();
    }
}

function toggleCart() {
    const cart = document.getElementById('cart');
    cart.classList.toggle('active');
}

function closeCart() {
    const cart = document.getElementById('cart');
    cart.classList.remove('active');
}

function checkout() {
    if (cart.length === 0) {
        alert('Корзина пуста!');
        return;
    }

    const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const orderDetails = cart.map(item =>
        `${item.name} - ${item.quantity} шт. - ${(item.price * item.quantity).toLocaleString()} ₽`
    ).join('\n');

    alert(`Заказ оформлен!\n\n${orderDetails}\n\nИтого: ${total.toLocaleString()} ₽\n\nСпасибо за заказ!`);

    // Очистка корзины после оформления
    cart = [];
    saveCart();
    updateCartUI();
    closeCart();
}

function showAddToCartMessage(productName) {
    // Создаем временное сообщение
    const message = document.createElement('div');
    message.style.cssText = `
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #27ae60;
            color: white;
            padding: 20px 40px;
            border-radius: 5px;
            z-index: 1002;
            font-weight: bold;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        `;
    message.textContent = `Товар "${productName}" добавлен в корзину!`;

    document.body.appendChild(message);

    setTimeout(() => {
        document.body.removeChild(message);
    }, 2000);
}

// Закрытие корзины при клике вне ее области
document.addEventListener('click', (e) => {
    const cart = document.getElementById('cart');
    const cartIcon = document.querySelector('.cart__icon');

    if (cart && cart.classList.contains('active') &&
        !cart.contains(e.target) &&
        !cartIcon.contains(e.target)) {
        closeCart();
    }
});

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
});




// Функция для сохранения данных в Cookie
//function saveToCookie() {
    //const firstName = document.getElementById('firstName').value;
  //  const lastName = document.getElementById('lastName').value;
    //const email = document.getElementById('email').value;
   // const phone = document.getElementById('phone').value;

    // Сохраняем данные в Cookie на 30 дней
   // const date = new Date();
    //date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000));
    //const expires = "expires=" + date.toUTCString();

    //document.cookie = "callbackFirstName=" + encodeURIComponent(firstName) + ";" + expires + ";path=/";
  //  document.cookie = "callbackLastName=" + encodeURIComponent(lastName) + ";" + expires + ";path=/";
  //  document.cookie = "callbackEmail=" + encodeURIComponent(email) + ";" + expires + ";path=/";
   // document.cookie = "callbackPhone=" + encodeURIComponent(phone) + ";" + expires + ";path=/";

  //  alert('Данные сохранены в Cookie!');
//}

// Функция для загрузки данных из Cookie
//function loadFromCookie() {
   // const cookies = document.cookie.split(';');
    //const data = {};

    //cookies.forEach(cookie => {
        //const [name, value] = cookie.trim().split('=');
    //    data[name] = decodeURIComponent(value);
   // });

    //if (data.callbackFirstName) document.getElementById('firstName').value = data.callbackFirstName;
   // if (data.callbackLastName) document.getElementById('lastName').value = data.callbackLastName;
    //if (data.callbackEmail) document.getElementById('email').value = data.callbackEmail;
  //  if (data.callbackPhone) document.getElementById('phone').value = data.callbackPhone;

    //alert('Данные загружены из Cookie!');
//}

// Функция для очистки Cookie
//function clearCookie() {
    //document.cookie = "callbackFirstName=; expires=Thu, 01 Jan 2026 00:00:00 UTC; path=/;";
    //document.cookie = "callbackLastName=; expires=Thu, 01 Jan 2026 00:00:00 UTC; path=/;";
    //document.cookie = "callbackEmail=; expires=Thu, 01 Jan 2026 00:00:00 UTC; path=/;";
    //document.cookie = "callbackPhone=; expires=Thu, 01 Jan 2026 00:00:00 UTC; path=/;";

    // Очищаем поля формы
    //document.getElementById('firstName').value = '';
    //document.getElementById('lastName').value = '';
    //document.getElementById('email').value = '';
    //document.getElementById('phone').value = '';

    //alert('Cookie очищены!');
//}

// Автозагрузка данных при открытии страницы (опционально)
//document.addEventListener('DOMContentLoaded', function () {

    // loadFromCookie();
//});





// Функция для сохранения данных в Local Storage
function saveToLocalStorage() {
    const firstName = document.getElementById('firstName').value;
    const lastName = document.getElementById('lastName').value;
    const email = document.getElementById('email').value;
    const phone = document.getElementById('phone').value;

    const formData = {
        firstName: firstName,
        lastName: lastName,
        email: email,
        phone: phone,
        timestamp: new Date().toISOString()
    };

    // Сохраняем данные в Local Storage
    localStorage.setItem('callbackFormData', JSON.stringify(formData));

    alert('Данные сохранены в Local Storage!');
    return false; // Предотвращаем отправку формы
}

// Функция для загрузки данных из Local Storage
function loadFromLocalStorage() {
    const savedData = localStorage.getItem('callbackFormData');

    if (savedData) {
        const formData = JSON.parse(savedData);

        document.getElementById('firstName').value = formData.firstName || '';
        document.getElementById('lastName').value = formData.lastName || '';
        document.getElementById('email').value = formData.email || '';
        document.getElementById('phone').value = formData.phone || '';

        alert('Данные загружены из Local Storage!');
    } else {
        alert('В Local Storage нет сохраненных данных!');
    }
}

// Функция для очистки Local Storage
function clearLocalStorage() {
    localStorage.removeItem('callbackFormData');

    // Очищаем поля формы
    document.getElementById('firstName').value = '';
    document.getElementById('lastName').value = '';
    document.getElementById('email').value = '';
    document.getElementById('phone').value = '';

    alert('Local Storage очищен!');
}

// Автозагрузка данных при открытии страницы (опционально)
document.addEventListener('DOMContentLoaded', function () {
    // loadFromLocalStorage(); // Раскомментируйте, если нужно автозаполнение при загрузке
});