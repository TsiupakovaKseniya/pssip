CREATE DATABASE IF NOT EXISTS phones_variant6 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

USE phones_variant6;

CREATE TABLE IF NOT EXISTS Abonenty (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    familiya          VARCHAR(100) NOT NULL,
    imya              VARCHAR(100) NOT NULL,
    otchestvo         VARCHAR(100),
    data_rozhdeniya   DATE,
    telefon           VARCHAR(30) NOT NULL,
    nomer_pasporta    VARCHAR(30)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;