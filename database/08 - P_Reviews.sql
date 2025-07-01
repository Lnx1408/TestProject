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
    
        SELECT 
            jug.id_jugador,
            jug.nombres,
            jug.apellidos,
            jug.correo,
            jug.usuario,
            pj.isRevisor as estado,
            CASE 
                WHEN pj.isRevisor = 1 THEN 'ESTUDIANTE REVISOR'
                ELSE 'ESTUDIANTE'
            END as estado_texto,
            CASE 
                WHEN pj.estado = 'en_progreso' THEN 'En progreso'
                ELSE 'Completado'
            END as porcentaje_avance_alt,
            jug.fecha_registro as fecha_registro
        FROM partidas_jugadores pj
        INNER JOIN jugadores jug ON pj.id_jugador = jug.id_jugador
        WHERE pj.id_partida = v_id_partida
        ORDER BY nombres ASC;
    -- END;
END //
DELIMITER ;

-- SP Obtener lista de estudiantes subscritos a una partida
DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_teachers_reviewers_clasificacion //
CREATE PROCEDURE sp_get_teachers_reviewers_clasificacion(
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
        SELECT 
            jug.id_jugador,
            jug.nombres,
            jug.apellidos,
            jug.correo,
            jug.usuario,
            jug.estado as estado,
            CASE 
                WHEN jug.id_tipo = 3 THEN 'DOCENTE'
                ELSE 'DOCENTE'
            END as estado_texto,
            jug.estado as porcentaje_avance_alt,
            jug.fecha_registro as fecha_registro
        FROM jugadores jug
        WHERE jug.id_tipo = 2
        and jug.id_jugador != p_id_usuario
        ORDER BY nombres ASC;
    -- END;
END //
DELIMITER ;



use reqscapetest_db
-- SP Obtener lista de estudiantes subscritos a una partida
DELIMITER //
DROP PROCEDURE IF EXISTS sp_update_reviewer //
CREATE PROCEDURE sp_update_reviewer(
	-- Parámetros de entrada
	IN p_codigo_partida VARCHAR(10),
	IN p_id_usuario INT,
    IN p_rol_usuario INT,
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
	    AND id_modalidad = 1;


    -- Validar que la partida existe
    IF v_partida_existe = 0 THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'No existen datos para la partida especificada';
    END IF;
		SET p_codigo_retorno = 0;
        SET p_mensaje_retorno = 'No se pudo actualizar el estado';
        
        UPDATE reqscapetest_db.partidas_jugadores
        SET
		isRevisor = p_rol_usuario
        WHERE (id_partida = v_id_partida) AND (id_jugador = p_id_usuario);
        
	IF ROW_COUNT() > 0 THEN
        SET p_codigo_retorno = 1;
        SET p_mensaje_retorno = 'Estado actualizado correctamente';
    END IF;
END //
DELIMITER ;



use reqscapetest_db
-- SP Obtener lista de estudiantes subscritos a una partida
DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_requisitos_review //
CREATE PROCEDURE sp_get_requisitos_review(
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
		
        SELECT 
        r.id_requisito,
        r.descripcion, 
        CASE 
			WHEN r.es_funcional = 1 THEN 'Funcional'
			ELSE 'No Funcional'
		END as tipo,
        r.es_ambiguo,
        r.retroalimentacion
        
		FROM reqscapetest_db.partidas p
		INNER JOIN reqscapetest_db.requisitos_clasificacion_partida rcp
		ON rcp.id_partida = p.id_partida
		INNER JOIN reqscapetest_db.requisitos r
		ON rcp.id_requisito = r.id_requisito 
		WHERE p.id_partida = v_id_partida
		AND p.id_usuario_creacion = p_id_usuario;
		-- END;
END //
DELIMITER ;

