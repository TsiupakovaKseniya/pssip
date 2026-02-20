// Проверка правильности email с использованием test()
function validateEmail() {
	const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
	const email = document.getElementById('email3').value;
  
	// Проверяем соответствие введённого email регулярному выражению
	const isValid = emailRegex.test(email);//метод
	document.getElementById('emailResult3').innerText = 
	  isValid ? "Адрес электронной почты действителен." : "Адрес электронной почты недействителен.";
  }	
  
  // Извлечение номера телефона с новой маской +375(33)333-33-33 с использованием exec()
  function extractPhoneNumber() {
	// Новое регулярное выражение для формата +375(33)333-33-33
	const phoneRegex = /^\+375\((\d{2})\)(\d{3})-(\d{2})-(\d{2})$/;
	const phone = document.getElementById('phone3').value;
  
	// Применяем регулярное выражение для извлечения частей номера телефона
	const result = phoneRegex.exec(phone);//метод
	if (result) {
	 document.getElementById('phoneResult3').innerText = 
		`Код страны: +375, Код оператора: ${result[1]}, Номер: ${result[2]}-${result[3]}-${result[4]}`;
} else {
	document.getElementById('phoneResult3').innerText = "Номер телефона недействителен.";
	}
 }
  

  // Разделение строки на фрукты с помощью split()
  //function splitFruits() {
	//const fruitsText = document.getElementById('fruits3').value;

	// Разделяем строку по знакам ",", ";" или "|" с использованием регулярного выражения
	//const fruits = fruitsText.split(/[,;|]\s*/);//метод
	//document.getElementById('fruitResult3').innerText = `Фрукты: ${fruits.join(', ')}`;
  //}



// Разделение строки на фрукты с помощью split()
function splitFruits() {
	const input = document.getElementById("fruits3").value.trim();
	const result = document.getElementById("fruitResult3");

	// Проверка на пустое поле
	if (input === "") {
		result.textContent = "Поле не должно быть пустым.";
		result.style.color = "red";
		return;
	}

	// Проверка на наличие хотя бы одного допустимого разделителя
	if (!input.includes(",") && !input.includes(";") && !input.includes("|")) {
		result.textContent = "Используйте хотя бы один из разделителей: , ; или |";
		result.style.color = "red";
		return;
	}

	// Разделение строки по всем допустимым разделителям
	const fruits = input.split(/[,;|]/).map(f => f.trim()).filter(f => f !== "");

	// Проверка каждого фрукта на корректность (только буквы и пробелы)
	const invalid = fruits.filter(fruit => !/^[А-Яа-яЁёA-Za-z\s]+$/.test(fruit));
	if (invalid.length > 0) {
		result.textContent = "Некорректные названия фруктов: " + invalid.join(", ");
		result.style.color = "red";
		return;
	}

	// Вывод результата
	result.innerHTML = "Разделенные фрукты:<br>" + fruits.join("<br>");
	result.style.color = "green";
}



  // Поиск дат в тексте с использованием match()
  function findDates() {
	const text = document.getElementById('text3').value;
	const dateRegex = /\d{2}\/\d{2}\/\d{4}/g;
  
	// Находим все даты в формате дд/мм/гггг в тексте
	const dates = text.match(dateRegex);//метод
	document.getElementById('dateResult3').innerText = dates ? `Найденные даты: ${dates.join(', ')}` : "Даты не найдены";
  }


