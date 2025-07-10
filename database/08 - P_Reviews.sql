use reqscapetest_db

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
	   AND id_usuario_creacion = p_id_usuario;


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
	   AND id_usuario_creacion = p_id_usuario;


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
        WHERE (jug.id_tipo = 2 or jug.id_tipo = 1)
        and jug.id_jugador != p_id_usuario
        ORDER BY nombres ASC;
    -- END;
END //
DELIMITER ;


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
	   WHERE codigo_partida = p_codigo_partida;


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
	   AND id_usuario_creacion = p_id_usuario;


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
        r.es_ambiguo
        
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

DELIMITER //

DROP PROCEDURE IF EXISTS sp_get_partidas_estudiante_revisor //
CREATE PROCEDURE sp_get_partidas_estudiante_revisor(
    -- Parámetros de entrada
    IN p_id_usuario INT,
    -- Parámetros de salida
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500)
)
BEGIN
    -- Declaración de variables locales
    DECLARE v_usuario_existe INT DEFAULT 0;
    
    -- Manejo de errores SQL
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            @sqlstate = RETURNED_SQLSTATE,
            @errno = MYSQL_ERRNO,
            @text = MESSAGE_TEXT;
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = CONCAT('Error en la ejecución del procedimiento: ', @text, ' (', @errno, ')');
    END;

    -- Inicialización de variables de salida
    SET p_codigo_retorno = 0;
    SET p_mensaje_retorno = 'Proceso iniciado correctamente';

    -- Validar que el usuario existe
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'El ID de usuario no es válido';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ID de usuario inválido';
    END IF;

    SELECT COUNT(*) INTO v_usuario_existe
    FROM jugadores 
    WHERE id_jugador = p_id_usuario;

    IF v_usuario_existe = 0 THEN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'El usuario no existe';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuario no existe';
    END IF;

    -- Obtener las partidas con la información requerida
     SELECT 
        p.codigo_partida as code,
        p.fecha_creacion as createdAt,
        m.codigo as tipo,
        COUNT(DISTINCT pj.id_jugador) as totalStudents
    FROM partidas p
    INNER JOIN modalidades m ON p.id_modalidad = m.id_modalidad
    LEFT JOIN partidas_jugadores pj ON p.id_partida = pj.id_partida
    WHERE pj.id_jugador = p_id_usuario and pj.isRevisor = 1
    GROUP BY p.id_partida, p.codigo_partida, p.fecha_creacion, m.codigo
    ORDER BY p.fecha_creacion DESC;

    -- Si llegamos aquí, todo se ejecutó correctamente
    SET p_codigo_retorno = 1;
    SET p_mensaje_retorno = 'Partidas obtenidas exitosamente';

END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_requirements_suggestions //
CREATE PROCEDURE sp_get_requirements_suggestions(
    -- Parámetros de entrada
    IN p_id_usuario INT,
    IN p_id_requisito INT,
    -- Parámetros de salida
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500)
)
BEGIN
    -- Declaración de variables locales
    DECLARE v_usuario_existe INT DEFAULT 0;
    
    -- Manejo de errores SQL
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            @sqlstate = RETURNED_SQLSTATE,
            @errno = MYSQL_ERRNO,
            @text = MESSAGE_TEXT;
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = CONCAT('Error en la ejecución del procedimiento: ', @text, ' (', @errno, ')');
    END;

    -- Inicialización de variables de salida
    SET p_codigo_retorno = 0;
    SET p_mensaje_retorno = 'Proceso iniciado correctamente';

    -- Validar que el usuario existe
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'El ID de usuario no es válido';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ID de usuario inválido';
    END IF;

    SELECT COUNT(*) INTO v_usuario_existe
    FROM jugadores 
    WHERE id_jugador = p_id_usuario;

    IF v_usuario_existe = 0 THEN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'El usuario no existe';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuario no existe';
    END IF;

    -- Obtener las partidas con la información requerida
	SELECT rs.id_requisito, rs.descripcion, rs.es_ambiguo, rs.es_funcional, concat(j.nombres, ' ', j.apellidos) as revisor
	FROM
	requisitos_sugerencias rs
	INNER JOIN jugadores j
	ON rs.id_usuario_revisor = j.id_jugador
	WHERE 
    rs.id_requisito = p_id_requisito AND
    rs.id_usuario_creador = p_id_usuario;

    -- Si llegamos aquí, todo se ejecutó correctamente
    SET p_codigo_retorno = 1;
    SET p_mensaje_retorno = 'Partidas obtenidas exitosamente';
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_original_requirement //
CREATE PROCEDURE sp_get_original_requirement(
    -- Parámetros de entrada
    IN p_id_usuario INT,
    IN p_id_requisito INT,
    -- Parámetros de salida
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500)
)
BEGIN
    -- Declaración de variables locales
    DECLARE v_usuario_existe INT DEFAULT 0;
    
    -- Manejo de errores SQL
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            @sqlstate = RETURNED_SQLSTATE,
            @errno = MYSQL_ERRNO,
            @text = MESSAGE_TEXT;
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = CONCAT('Error en la ejecución del procedimiento: ', @text, ' (', @errno, ')');
    END;

    -- Inicialización de variables de salida
    SET p_codigo_retorno = 0;
    SET p_mensaje_retorno = 'Proceso iniciado correctamente';

    -- Validar que el usuario existe
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'El ID de usuario no es válido';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ID de usuario inválido';
    END IF;

    SELECT COUNT(*) INTO v_usuario_existe
    FROM jugadores 
    WHERE id_jugador = p_id_usuario;

    IF v_usuario_existe = 0 THEN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'El usuario no existe';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuario no existe';
    END IF;

    -- Obtener las partidas con la información requerida
	SELECT r.descripcion
	FROM
    requisitos r
    WHERE 
    r.id_requisito = p_id_requisito AND
    r.id_usuario_creador = p_id_usuario;

    -- Si llegamos aquí, todo se ejecutó correctamente
    SET p_codigo_retorno = 1;
    SET p_mensaje_retorno = 'Partidas obtenidas exitosamente';
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS sp_update_original_requirement //
CREATE PROCEDURE sp_update_original_requirement(
	-- Parámetros de entrada
	IN p_id_requisito INT,
    IN p_requisito VARCHAR(500),
	IN es_funcional INT,
    IN es_ambiguo INT,
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
	IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'El ID de usuario no es válido';
	END IF;
    
    -- Validar que la partida existe
		SET p_codigo_retorno = 0;
        SET p_mensaje_retorno = 'No se pudo actualizar el estado';
        
        UPDATE reqscapetest_db.requisitos
        SET
		descripcion = p_requisito
        WHERE (id_requisito = p_id_requisito) AND (id_usuario_creador = p_id_usuario);
        
	IF ROW_COUNT() > 0 THEN
        SET p_codigo_retorno = 1;
        SET p_mensaje_retorno = 'Estado actualizado correctamente';
    END IF;
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS sp_update_teacher_reviewer //
CREATE PROCEDURE sp_update_teacher_reviewer(
	-- Parámetros de entrada
	IN p_codigo_partida VARCHAR(10),
	IN p_id_usuario INT,
    IN p_rol_usuario  VARCHAR(10),
    -- Parámetros de salida
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500)
)
BEGIN
	-- Declaración de variables locales
	DECLARE v_partida_existe INT DEFAULT 0;
    DECLARE v_has_partida_revisor INT DEFAULT 0;
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
	   WHERE codigo_partida = p_codigo_partida;
       
	-- Validar que la el docente este registrado como revisor en la partida
	SELECT COUNT(*) INTO v_has_partida_revisor
	   FROM docente_revisor_partida 
	   WHERE id_partida = v_id_partida and id_docente_revisor = p_id_usuario;


    -- Validar que la partida existe
    IF v_partida_existe = 0 THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'No existen datos para la partida especificada';
    END IF;
		SET p_codigo_retorno = 0;
        SET p_mensaje_retorno = 'No se pudo actualizar el estado';
	
    IF v_has_partida_revisor = 0 THEN
		INSERT INTO reqscapetest_db.docente_revisor_partida
		(id_partida, id_docente_revisor)
		VALUES
		(v_id_partida, p_id_usuario);
    END IF;
    
    IF v_has_partida_revisor > 0 THEN
		UPDATE reqscapetest_db.docente_revisor_partida
        SET rol = p_rol_usuario
        WHERE (id_partida = v_id_partida) AND (id_docente_revisor = p_id_usuario);
    
    END IF;
        
        
	IF ROW_COUNT() > 0 THEN
        SET p_codigo_retorno = 1;
        SET p_mensaje_retorno = 'Estado actualizado correctamente';
    END IF;
END //
DELIMITER ;