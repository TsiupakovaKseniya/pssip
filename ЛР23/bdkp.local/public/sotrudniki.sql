-- Создание базы данных
CREATE DATABASE IF NOT EXISTS sotrudniki 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE sotrudniki;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'helper') NOT NULL DEFAULT 'helper'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица должностей
CREATE TABLE IF NOT EXISTS Positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица подразделений 
CREATE TABLE IF NOT EXISTS Departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Таблица графиков работ
CREATE TABLE IF NOT EXISTS WorkSchedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        schedule_type ENUM('players','staff','coaches') NOT NULL,
        department_id INT DEFAULT NULL,
        day_of_week ENUM('Понедельник','Вторник','Среда','Четверг','Пятница','Суббота','Воскресенье') NOT NULL,
        training_1 VARCHAR(100) DEFAULT NULL,
        training_2 VARCHAR(100) DEFAULT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


     CREATE TABLE IF NOT EXISTS AdminSchedules (
        id INT AUTO_INCREMENT PRIMARY KEY,
        schedule_date DATE NOT NULL,
        status ENUM('Рабочий','Выходной') NOT NULL DEFAULT 'Рабочий'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;





-- Таблица команд
CREATE TABLE IF NOT EXISTS Teams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    city VARCHAR(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Начальные данные команд
INSERT INTO Teams (name, city) VALUES
('Легион','Обухово'),
('Борисов-БГУФК', 'Борисов'),
('Западный Буг', 'Брест'),
('Коммунальник-Могилёв', 'Могилёв'),
('Марко-ВГТУ', 'Витебск'),
('Минск', 'Минск'),
('Столица', 'Минск'),
('Шахтер', 'Солигорск'),
('Энергия', 'Гомель'),
('Атлант', 'Поставы'),
('Борисов-БГУФК-2', 'Борисов'),
('Газовик-Гомель', 'Гомель'),
('ДЮСШ ВК Брест', 'Брест'),
('ДЮСШ-Шахтер', 'Солигорск'),
('Коммунальник-Могилёв-2', 'Могилёв'),
('Легион-2', 'Обухово'),
('Марко-ВГТУ-2', 'Витебск'),
('РГУОР-2009','Минск'),
('СДЮШОР ВК Минск', 'Минск'),
('СДЮШОР-Виктория-Западный Буг', 'Брест'),
('Энергия-2', 'Брест')
ON DUPLICATE KEY UPDATE name=VALUES(name);

-- Таблица протоколов матчей
CREATE TABLE IF NOT EXISTS MatchProtocols (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_datetime DATETIME NOT NULL,
    home_team_id INT NOT NULL,
    away_team_id INT NOT NULL,
    result VARCHAR(20) DEFAULT NULL COMMENT '',
    city VARCHAR(100) DEFAULT NULL,
    hall VARCHAR(150) DEFAULT NULL,
    duration VARCHAR(10) DEFAULT NULL COMMENT '' ,
    FOREIGN KEY (home_team_id) REFERENCES Teams(id) ON DELETE RESTRICT,
    FOREIGN KEY (away_team_id) REFERENCES Teams(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Таблица сетов
CREATE TABLE IF NOT EXISTS MatchSets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    match_id INT NOT NULL,
    set_number TINYINT NOT NULL COMMENT '',
    home_score TINYINT NOT NULL DEFAULT 0,
    away_score TINYINT NOT NULL DEFAULT 0,
    duration VARCHAR(10) DEFAULT NULL COMMENT '',
    FOREIGN KEY (match_id) REFERENCES MatchProtocols(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;




-- Таблица сотрудников 
CREATE TABLE IF NOT EXISTS Employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(200) NOT NULL,
    birth_date DATE NOT NULL,
    gender ENUM('M', 'F') NOT NULL,
    department_id INT NOT NULL,
    position_id INT NOT NULL,
    rate DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    contract_number VARCHAR(50) NOT NULL UNIQUE,
    hire_date DATE NOT NULL,
    
    FOREIGN KEY (department_id) REFERENCES Departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (position_id) REFERENCES Positions(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE Employees 
    ADD COLUMN contract_end_date DATE NULL AFTER hire_date,
    ADD COLUMN education ENUM('высшее', 'среднее', 'базовое', 'среднее специальное') 
        NOT NULL DEFAULT 'среднее' AFTER contract_end_date;

CREATE TABLE IF NOT EXISTS CurrentEmployees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    time_in TIME,
    time_out TIME,
    total_hours DECIMAL(5,2),
    FOREIGN KEY (employee_id) REFERENCES Employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS Vacations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    full_name VARCHAR(200) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    type VARCHAR(50) NOT NULL,
    FOREIGN KEY (employee_id) REFERENCES Employees(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS SickLeaves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    full_name VARCHAR(200) NOT NULL,
    sick_leave_number VARCHAR(50) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    FOREIGN KEY (employee_id) REFERENCES Employees(id) ON DELETE CASCADE
);

-- Пользователи
INSERT INTO users (username, password, role) VALUES 
('admin', 'admin123', 'admin'),
('helper', 'helper123', 'helper');

-- Должности
INSERT INTO Positions (name) VALUES
('Председатель'),
('Инспектор по кадрам'),
('Секретарь'),
('Врач спортивной медицины'),
('Тренер'),
('Спортсмен-инструктор'),
('Стажер спортсмена-инструктора'),
('Оператор видеозаписи'),
('Главный тренер');

-- Подразделения
INSERT INTO Departments (name) VALUES
('ВК Легион'),
('ВК Легион-2'),
('Административно-управленческий персонал');




