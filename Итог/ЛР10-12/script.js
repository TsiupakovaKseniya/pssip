

function calculate() {
    const x = parseFloat(document.getElementById('xValue').value);
    const resultDiv = document.getElementById('result');

    try {
        const calculationResult = calculateFormula(x);
        resultDiv.innerHTML = `<strong>Результат:</strong> F(${x}) = ${calculationResult}`;
    } catch (error) {
        alert(error.message);
        resultDiv.innerHTML = '';
    }
}

function calculateFormula(x) {
    try {
        if (isNaN(x)) throw "Ошибка: введите число";
        if (x <= 7) {
            return -3 * x + 9;  // f(x) = -3x + 9 при x <= 7
        } else {
            if (x === 7) throw "Ошибка: деление на ноль (x не может быть 7)";
            return 1 / (x - 7);  // f(x) = 1/(x - 7) при x > 7
        }
    } catch (error) {
        alert(error);
        return null;
    }
}





