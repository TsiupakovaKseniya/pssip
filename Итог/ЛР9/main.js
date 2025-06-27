

function Show_Switch(){
	var day = new Date().getDay();
	var dayName;
	switch (day) {
	  case 0:
		dayName = 'Воскресенье';
		break;
	  case 1:
		dayName = 'Понедельник';
		break;
	  case 2:
		dayName = 'Вторник';
		break;
	  case 3:
		dayName = 'Среда';
		break;
	  case 4:
		dayName = 'Четверг';
		break;
	  case 5:
		dayName = 'Пятница';
		break;
	  case 6:
		dayName = 'Суббота';
		break;
	}
	alert("Сегодня " + dayName);
}

function Show_For(){
	var result = '';
    for (var i = 0; i < 5; i++) {
        result += `Итерация ${i}\n`;
    }
    alert(result);
}

function Show_While(){
	var i = 0;
    var result = '';
    while (i < 5) {
        result += `While итерация ${i}\n`;
        i++;
    }
    alert(result);
}

function Show_DoWhile(){
	var i = 0;
    var result = '';
    do {
        result += `Do-While итерация ${i}\n`;
        i++;
    } while (i < 5);
    alert(result);
}

function Show_BreakContinueReturn(){
	const number = prompt("Введите число от 1 до 10");

    if (number === null) {
        alert("Отменено пользователем.");
        return; // Прекратить выполнение функции, если пользователь нажал "Отмена"
    }

    const parsedNumber = parseInt(number, 10);
    if (parsedNumber < 1 || parsedNumber > 10) {
        alert("Пожалуйста, введите действительное число от 1 до 10.");
        return; // Прекратить выполнение функции, если введено недопустимое значение
    }

    var result = '';
    for (var i = 1; i <= 10; i++) {
        if (i === parsedNumber) {
            result += `Число ${i} совпадает с введенным числом.\n`;
            continue; // Пропустить оставшуюся часть цикла для числа, совпадающего с введенным
        }
        if (i > parsedNumber + 2) {
            result += `Остановлено на числе ${i} так как i > ${parsedNumber + 2}.\n`;
            break; // Прекратить выполнение цикла, если i больше введенного числа на 2
        }
        result += `Число ${i}\n`;
    }

    alert(result);
} 

function Show_Confirm(){
	var verstka = confirm("Вы любите верстать?");
	if (verstka) {
        alert("Здорово! Верстка - это круто!");
    } else {
        alert("Ну, каждому своё!");
    }
}

function Show_Promt(){
	var name = prompt ("Введите свое имя!");
	alert("Привет " + name);
}