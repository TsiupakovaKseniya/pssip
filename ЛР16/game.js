
class GameObject {
    constructor(x, y) {
        // Публичные поля
        this.x = x;
        this.y = y;

        // Защищенные поля 
        this._protectedField = 'protected';
    }

    // ГЕТТЕР 
    get position() {
        return { x: this.x, y: this.y };
    }

    // СЕТТЕР 
    set position(value) {
        if (value.x >= 0 && value.y >= 0) {
            this.x = value.x;
            this.y = value.y;
        }
    }

    // СТАТИЧЕСКИЙ метод 
    static getGameType() {
        return "Arcade Game";
    }

    // ПОЛИМОРФИЗМ
    draw(ctx) {
        throw new Error("Метод draw должен быть реализован");
    }
}

// НАСЛЕДОВАНИЕ (класс Food наследует от GameObject)
class Food extends GameObject {
    constructor(x, y) {
        super(x, y); //вызов конструктора родительского класса
        this.color = '#ff6347';
        this.size = 20;
    }

    // ПОЛИМОРФИЗМ (переопределение метода draw)
    draw(ctx) {
        ctx.fillStyle = this.color;
        ctx.fillRect(this.x, this.y, this.size, this.size);
    }

    respawn(canvasWidth, canvasHeight, boxSize) {
        this.x = Math.floor(Math.random() * (canvasWidth / boxSize)) * boxSize;
        this.y = Math.floor(Math.random() * (canvasHeight / boxSize)) * boxSize;
    }
}

// НАСЛЕДОВАНИЕ (класс SnakeSegment наследует от GameObject)
class SnakeSegment extends GameObject {
    constructor(x, y, isHead = false) {
        super(x, y);
        this.isHead = isHead;
        this.color = isHead ? '#28a745' : '#218838';
        this.size = 20;
    }

    // ПОЛИМОРФИЗМ (переопределение метода draw)
    draw(ctx) {
        ctx.fillStyle = this.color;
        ctx.fillRect(this.x, this.y, this.size, this.size);
        ctx.strokeStyle = '#000';
        ctx.strokeRect(this.x, this.y, this.size, this.size);
    }
}

// СИНТАКСИС КЛАССА
class SnakeGame {
    constructor(canvasId, startButtonId, scoreId) {//метод
        // Публичные поля
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.startButton = document.getElementById(startButtonId);
        this.scoreDisplay = document.getElementById(scoreId);
        this.boxSize = 20;
        this.score = 0;
        this.gameStarted = false;
        this.dx = this.boxSize;
        this.dy = 0;

        // ИНКАПСУЛЯЦИЯ (данные змеи инкапсулированы в объектах)
        this.snake = [
            new SnakeSegment(160, 160, true),
            new SnakeSegment(140, 160),
            new SnakeSegment(120, 160)
        ];

        // ИНКАПСУЛЯЦИЯ
        this.food = new Food(
            Math.floor(Math.random() * (this.canvas.width / this.boxSize)) * this.boxSize,
            Math.floor(Math.random() * (this.canvas.height / this.boxSize)) * this.boxSize
        );

        // Использование статического метода
        console.log("Тип игры:", GameObject.getGameType());
    }

    // СОЗДАНИЕ ОБЪЕКТОВ КЛАССА 
    static createGame(canvasId, buttonId, scoreId) {
        return new SnakeGame(canvasId, buttonId, scoreId);
    }

    // ОБРАЩЕНИЕ К МЕТОДАМ (через this.)
    init() {
        this.startButton.addEventListener('click', () => this.startGame());
        document.addEventListener('keydown', (event) => this.changeDirection(event));

        //Использование геттера
        const foodPos = this.food.position;
        console.log("Начальная позиция еды:", foodPos);
    }

    startGame() {
        if (!this.gameStarted) {
            this.gameStarted = true;
            this.gameLoop();
        }
    }

    gameLoop() {
        if (!this.gameStarted) return;

        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.moveSnake();
        this.drawGame();
        this.checkCollision();
        setTimeout(() => this.gameLoop(), 100);
    }

    //ИНКАПСУЛЯЦИЯ
    drawGame() {
        // ПОЛИМОРФИЗМ (разные объекты, один интерфейс draw())
        this.food.draw(this.ctx);
        this.snake.forEach(segment => segment.draw(this.ctx));

        this.scoreDisplay.innerText = 'Счёт: ' + this.score;
    }

    moveSnake() {
        const head = this.snake[0];
        const newHead = new SnakeSegment(
            head.x + this.dx,
            head.y + this.dy,
            true
        );

        this.snake.unshift(newHead);

        //ОБРАЩЕНИЕ К ПОЛЯМ (через this.)
        if (newHead.x === this.food.x && newHead.y === this.food.y) {
            this.handleFoodEaten();
        } else {
            this.snake.pop();
        }
    }

    // ИНКАПСУЛЯЦИЯ
    handleFoodEaten() {
        this.score++;
        this.scoreDisplay.innerText = 'Счёт: ' + this.score;
        this.food.respawn(this.canvas.width, this.canvas.height, this.boxSize);

        // Использование сеттера
        this.food.position = {
            x: this.food.x,
            y: this.food.y
        };
    }

    changeDirection(event) {
        if (!this.gameStarted) return;
        const key = event.keyCode;

        if (key === 37 && this.dx === 0) {
            this.dx = -this.boxSize;
            this.dy = 0;
        } else if (key === 38 && this.dy === 0) {
            this.dx = 0;
            this.dy = -this.boxSize;
        } else if (key === 39 && this.dx === 0) {
            this.dx = this.boxSize;
            this.dy = 0;
        } else if (key === 40 && this.dy === 0) {
            this.dx = 0;
            this.dy = this.boxSize;
        }
    }

    checkCollision() {
        const head = this.snake[0];

        // Проверка столкновения со стенами
        if (head.x < 0 || head.y < 0 ||
            head.x >= this.canvas.width || head.y >= this.canvas.height) {
            this.endGame();
            return;
        }

        // Проверка столкновения с телом змеи
        for (let i = 4; i < this.snake.length; i++) {
            if (head.x === this.snake[i].x && head.y === this.snake[i].y) {
                this.endGame();
                return;
            }
        }
    }

    endGame() {
        alert('Игра окончена! Ваш счёт: ' + this.score);
        document.location.reload();
    }
}

// СОЗДАНИЕ ОБЪЕКТА КЛАССА
document.addEventListener('DOMContentLoaded', () => {
    const game = SnakeGame.createGame('gameCanvas', 'startButton', 'score');
    game.init();
});