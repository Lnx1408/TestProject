
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";



DROP DATABASE IF EXISTS reqscapetest_db; 
create DATABASE reqscapetest_db; 
use reqscapetest_db;

-- Tabla para los tipos de modalidad
CREATE TABLE modalidades (
    id_modalidad INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(50) NOT NULL,
	codigo VARCHAR(10) UNIQUE NOT NULL,
    descripcion TEXT
);

-- Tabla para el tipo de Usuario: Docente y Estudiante
CREATE TABLE tipo_usuario (
    id_tipo INT PRIMARY KEY AUTO_INCREMENT,
    nombre_tipo VARCHAR(50) UNIQUE NOT NULL,
    codigo VARCHAR(10) UNIQUE NOT NULL
);

-- Tabla para almacenar información de los jugadores
CREATE TABLE jugadores (
    id_jugador INT PRIMARY KEY AUTO_INCREMENT,
    nombres VARCHAR(100) NOT NULL,
	apellidos VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL,
    correo VARCHAR(255) UNIQUE NOT NULL,
	password varchar(255) NOT NULL,
	id_tipo INT NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (id_tipo) REFERENCES tipo_usuario(id_tipo)
);

-- Tabla para las partidas con modalidad
CREATE TABLE partidas (
    id_partida INT PRIMARY KEY AUTO_INCREMENT,
    id_modalidad INT,
	id_usuario_creacion INT NOT NULL,
    codigo_partida VARCHAR(10) UNIQUE NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('activa', 'finalizada') DEFAULT 'activa',
    tiempo_limite INT,
    FOREIGN KEY (id_modalidad) REFERENCES modalidades(id_modalidad),
	FOREIGN KEY (id_usuario_creacion) REFERENCES jugadores(id_jugador)
);

-- Tabla para almacenar los requisitos de cada partida
CREATE TABLE requisitos (
    id_requisito INT PRIMARY KEY AUTO_INCREMENT,
    descripcion TEXT NOT NULL,
    es_ambiguo BOOLEAN NOT NULL,
    retroalimentacion TEXT,
    tipo_requisito VARCHAR(50),
	id_usuario_creador INT NULL
);

CREATE TABLE requisitos_clasificacion_partida (
    id_requisito_partida INT PRIMARY KEY AUTO_INCREMENT,
    id_requisito INT,
    id_partida INT,
    FOREIGN KEY (id_requisito) REFERENCES requisitos(id_requisito),
    FOREIGN KEY (id_partida) REFERENCES partidas(id_partida),
    CONSTRAINT unique_requisito_partida UNIQUE (id_partida, id_requisito)
);


-- Tabla para registrar participación de jugadores en partidas
CREATE TABLE partidas_jugadores (
    id_partida INT,
    id_jugador INT,
    fecha_union TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('en_progreso', 'completado') DEFAULT 'en_progreso',
    intentos_totales INT DEFAULT 0,
    tiempo_total INT DEFAULT 0,
    movimientos_totales INT DEFAULT 0,
    PRIMARY KEY (id_partida, id_jugador),
    FOREIGN KEY (id_partida) REFERENCES partidas(id_partida),
    FOREIGN KEY (id_jugador) REFERENCES jugadores(id_jugador)
);

-- Tabla de intentos actualizada con más métricas
CREATE TABLE intentos (
    id_intento INT PRIMARY KEY AUTO_INCREMENT,
    id_partida INT,
    id_jugador INT,
    numero_intento INT NOT NULL,
    tiempo_intento INT NOT NULL, -- en segundos
    cantidad_movimientos INT NOT NULL,
    total_requisitos INT NOT NULL, -- total de requisitos en este intento
    requisitos_correctos INT NOT NULL,
    requisitos_incorrectos INT NOT NULL,
    precision_general DECIMAL(5,2) NOT NULL,
    precision_aciertos DECIMAL(5,2) NOT NULL,
    precision_errores DECIMAL(5,2) NOT NULL,
    precision_progresiva DECIMAL(5,2) NOT NULL,
    fecha_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_partida, id_jugador) REFERENCES partidas_jugadores(id_partida, id_jugador)
);

-- Nueva tabla para registrar requisitos incorrectos por intento
CREATE TABLE intento_requisitos_incorrectos (
    id_intento INT,
    id_requisito INT,
    cantidad_movimientos INT NOT NULL, -- movimientos específicos para este requisito en este intento
    movimientos_toAmbiguo INT NOT NULL, 
    movimientos_toNoAmbiguo INT NOT NULL, 
	es_correcto BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (id_intento, id_requisito),
    FOREIGN KEY (id_intento) REFERENCES intentos(id_intento),
    FOREIGN KEY (id_requisito) REFERENCES requisitos(id_requisito)
);

-- Índices para optimizar consultas
CREATE INDEX idx_intento_fecha ON intentos(fecha_intento);
CREATE INDEX idx_intento_jugador ON intentos(id_jugador);
CREATE INDEX idx_requisitos_incorrectos ON intento_requisitos_incorrectos(id_intento);

-- Tabla para los requisitos de construcción
CREATE TABLE requisitos_construccion (
    id_requisito INT PRIMARY KEY AUTO_INCREMENT,
    requisito_completo TEXT NOT NULL, -- El requisito completo correcto
    nivel_dificultad INT,             -- Para posible progresión de dificultad
	id_usuario_creador INT NULL
);

-- Tabla Transitoria los requisitos de construcción
CREATE TABLE requisitos_construccion_partida (
    id_requisito_partida INT PRIMARY KEY AUTO_INCREMENT,
    id_requisito INT,
    id_partida INT,
    FOREIGN KEY (id_requisito) REFERENCES requisitos_construccion(id_requisito),
	FOREIGN KEY (id_partida) REFERENCES partidas(id_partida)
);


-- Tabla para los fragmentos de requisitos
CREATE TABLE fragmentos_requisito (
    id_fragmento INT PRIMARY KEY AUTO_INCREMENT,
    id_requisito INT,
    texto VARCHAR(100) NOT NULL,
    posicion_correcta INT,   -- NULL si es señuelo
    es_señuelo BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (id_requisito) REFERENCES requisitos_construccion(id_requisito)
);

-- Tabla para registrar intentos en modalidad construcción
CREATE TABLE intentos_construccion (
    id_intento INT PRIMARY KEY AUTO_INCREMENT,
    id_partida INT,
    id_jugador INT,
    id_requisito INT,
    numero_intento INT NOT NULL,
    tiempo_intento INT NOT NULL,      -- en segundos
    fragmentos_correctos INT NOT NULL, -- cantidad de fragmentos en posición correcta
    fragmentos_incorrectos INT NOT NULL,
    señuelos_usados INT NOT NULL,     -- cuántos señuelos intentó usar
    precision_construccion DECIMAL(5,2), -- porcentaje de precisión en la construcción
    fecha_intento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_partida, id_jugador) REFERENCES partidas_jugadores(id_partida, id_jugador),
    FOREIGN KEY (id_requisito) REFERENCES requisitos_construccion(id_requisito)
);

-- Tabla para el detalle de construcción por intento
CREATE TABLE detalle_construccion_intento (
    id_intento INT,
    id_fragmento INT,
    posicion_usada INT NOT NULL,
    tiempo_colocacion INT NOT NULL, -- tiempo que tardó en colocar este fragmento
    cantidad_movimientos INT NOT NULL, -- veces que movió este fragmento
	es_correcto BOOLEAN DEFAULT FALSE,
    PRIMARY KEY (id_intento, id_fragmento),
    FOREIGN KEY (id_intento) REFERENCES intentos_construccion(id_intento),
    FOREIGN KEY (id_fragmento) REFERENCES fragmentos_requisito(id_fragmento)
);
