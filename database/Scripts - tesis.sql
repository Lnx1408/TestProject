-- Obtener jugadores por rol
SELECT j.nombres, j.apellidos, j.correo, t.nombre_tipo, j.estado 
FROM reqscapetest_db.jugadores j 
inner join reqscapetest_db.tipo_usuario t on j.id_tipo = t.id_tipo 
where j.id_tipo = 1;

-- Obtener estudiantes general
SELECT j.nombres, j.apellidos, j.usuario, j.correo, t.nombre_tipo, j.isRevisor
FROM reqscapetest_db.jugadores j 
inner join reqscapetest_db.tipo_usuario t on j.id_tipo = t.id_tipo 
where j.id_tipo = 3;

-- Obtener estudiantes revisores
SELECT j.id_jugador, j.nombres, j.apellidos, j.usuario, j.correo, t.nombre_tipo, j.isRevisor
FROM reqscapetest_db.jugadores j 
inner join reqscapetest_db.tipo_usuario t on j.id_tipo = t.id_tipo 
where j.id_tipo = 3 and j.isRevisor = true;

-- Obtener sugerencias por revisor
SELECT j.usuario, j.nombres, j.apellidos, rs.descripcion as suerencia, r.descripcion as original, (SELECT usuario FROM Jugadores where id_jugador = rs.id_usuario_creador) AS Docente 
FROM reqscapetest_db.requisitos_sugerencias rs 
INNER JOIN reqscapetest_db.jugadores j ON rs.id_usuario_revisor = j.id_jugador
INNER JOIN reqscapetest_db.requisitos r ON rs.id_requisito = r.id_requisito
WHERE rs.id_usuario_revisor = 4;

-- Obtener retroalimentacion por requisito

-- Obtener requisitos originales por usuario
Select * From reqscapetest_db.requisitos where id_usuario_creador = 1;


-- SP Obtener lista de estudiantes subscritos a una partida
DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_reviewers_partida_clasificacion //
CREATE PROCEDURE sp_get_reviewers_partida_clasificacion(
	-- Parámetros de entrada
	IN p_codigo_partida VARCHAR(10),
	IN p_id_usuario INT,
    -- Parámetros de salida
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500)
)
BEGIN
	-- Declaración de variables locales
	DECLARE v_partida_existe INT DEFAULT 0;
	DECLARE v_id_partida INT;

    -- Declaración de variables para manejo de errores
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
		GET DIAGNOSTICS CONDITION 1
					@sqlstate = RETURNED_SQLSTATE,
					@errno = MYSQL_ERRNO,
					@text = MESSAGE_TEXT;
        -- En caso de error SQL
        SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = CONCAT('Error en la ejecución del procedimiento: ', @text, ' (', @errno, ')');
        -- Rollback en caso de haber transacciones
        ROLLBACK;
    END;

    -- Inicialización de variables de salida
    SET p_codigo_retorno = 0;
    SET p_mensaje_retorno = 'Proceso ejecutado correctamente';

	-- Validaciones de parámetros de entrada
	IF p_codigo_partida IS NULL OR p_codigo_partida = '' THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'El código de partida no es válido';
	END IF;

	IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'El ID de usuario no es válido';
	END IF;

	SELECT COUNT(*), id_partida INTO v_partida_existe, v_id_partida
	   FROM partidas 
	   WHERE codigo_partida = p_codigo_partida 
	   AND id_usuario_creacion = p_id_usuario AND id_modalidad = 1;


    -- Validar que la partida existe
    IF v_partida_existe = 0 THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'No existen datos para la partida especificada';
    END IF;
    
	WITH total_requisitos_partida AS (
            SELECT COUNT(*) as requisitos_partida
            FROM requisitos_clasificacion_partida 
            WHERE id_partida = v_id_partida
        )
        SELECT 
            i.id_partida,
            i.id_jugador,
            jug.nombres,
            jug.apellidos,
            jug.correo,
            jug.usuario,
            SUM(i.cantidad_movimientos) as movimientos_totales_jugador,
            SUM(i.requisitos_correctos) as requisitos_clasificados,
            t.requisitos_partida as total_requisitos_partida,
            jug.isRevisor as estado,
            CASE 
                WHEN jug.isRevisor = 1 THEN 'ESTUDIANTE REVISOR'
                ELSE 'ESTUDIANTE'
            END as estado_texto,
            MAX(i.precision_general) as porcentaje_avance_alt,
            jug.fecha_registro as fecha_registro
        FROM intentos i
        CROSS JOIN total_requisitos_partida t
		INNER JOIN jugadores jug ON i.id_jugador = jug.id_jugador
        WHERE i.id_partida = v_id_partida
        GROUP BY i.id_partida, i.id_jugador, t.requisitos_partida
        ORDER BY estado DESC, porcentaje_avance_alt DESC;
    -- END;
END //
DELIMITER ;