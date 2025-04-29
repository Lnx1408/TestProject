-- JUEGOS 
DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_partidas_por_estudiante //
CREATE PROCEDURE sp_get_partidas_por_estudiante(
    IN p_id_estudiante INT,

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
    IF p_id_estudiante IS NULL OR p_id_estudiante <= 0 THEN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'El ID de usuario no es válido';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ID de usuario inválido';
    END IF;

    SELECT COUNT(*) INTO v_usuario_existe
    FROM jugadores 
    WHERE id_jugador = p_id_estudiante;

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
    WHERE pj.id_jugador = p_id_estudiante
    GROUP BY p.id_partida, p.codigo_partida, p.fecha_creacion, m.codigo
    ORDER BY p.fecha_creacion DESC;

    -- Si llegamos aquí, todo se ejecutó correctamente
    SET p_codigo_retorno = 1;
    SET p_mensaje_retorno = 'Partidas obtenidas exitosamente';

END //
DELIMITER ;

-- CLASSIFICATION GAME
DELIMITER //
DROP PROCEDURE IF EXISTS sp_stats_for_player_student //
CREATE PROCEDURE sp_stats_for_player_student(
    -- Parámetros de entrada
	IN p_codigo_partida VARCHAR(10),
	IN p_id_jugador INT,
   -- Parámetros de salida
	OUT p_codigo_retorno INT,
	OUT p_mensaje_retorno VARCHAR(500),
    OUT p_intentos INT,
    OUT p_tiempo INT,
	OUT p_nombres VARCHAR(200),
	OUT p_apellidos VARCHAR(200),
	OUT p_correo VARCHAR(200)
)
BEGIN
	-- Declaración de variables locales
	DECLARE v_partida_existe INT DEFAULT 0;
	DECLARE v_id_partida INT;

   -- Manejo de errores SQL
   DECLARE EXIT HANDLER FOR SQLEXCEPTION
   BEGIN
	   GET DIAGNOSTICS CONDITION 1
					@sqlstate = RETURNED_SQLSTATE,
					@errno = MYSQL_ERRNO,
					@text = MESSAGE_TEXT;
       SET p_codigo_retorno = -1;
	   SET p_mensaje_retorno = CONCAT('Error en la ejecución del procedimiento: ', @text, ' (', @errno, ')');
       ROLLBACK;
   END;

   -- Inicialización de variables de salida
   SET p_codigo_retorno = 0;
   SET p_mensaje_retorno = 'Proceso iniciado correctamente';

	-- Validaciones de parámetros de entrada
	IF p_codigo_partida IS NULL OR p_codigo_partida = '' THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'El código de partida no es válido';
	END IF;

	SELECT COUNT(*), id_partida INTO v_partida_existe, v_id_partida
	   FROM partidas 
	   WHERE codigo_partida = p_codigo_partida
       AND id_modalidad = 1;

   -- Validaciones de parámetros de entrada
   IF v_id_partida IS NULL OR v_id_partida <= 0 THEN
       SET p_codigo_retorno = -1;
       SET p_mensaje_retorno = 'El ID de partida no es válido';
   END IF;

   IF v_partida_existe = 0 THEN
		SET p_codigo_retorno = 2;
		SET p_mensaje_retorno = 'No existen datos para la partida especificada';
	ELSE 
		-- Consulta principal
		Select * from intentos
		WHERE id_partida = v_id_partida  -- Aquí va el parámetro del id_partida
		and id_jugador = p_id_jugador ORDER BY numero_intento asc;
	   -- Si llegamos aquí, todo se ejecutó correctamente
       
		SELECT sum(tiempo_intento), COUNT(DISTINCT numero_intento) INTO p_tiempo, p_intentos
		from intentos WHERE id_partida = v_id_partida  -- Aquí va el parámetro del id_partida
		and id_jugador = p_id_jugador ORDER BY numero_intento asc;
       
       SELECT nombres, apellidos, correo INTO p_nombres, p_apellidos, p_correo
       FROM jugadores WHERE id_jugador = p_id_jugador;
       
	   SET p_codigo_retorno = 1;
	   SET p_mensaje_retorno = 'Resultados de consulta generadas exitosamente';
   END IF;    
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS sp_details_for_attempt_student //
CREATE PROCEDURE sp_details_for_attempt_student(
    -- Parámetros de entrada
	IN p_codigo_partida VARCHAR(10),
	IN p_id_jugador INT,
    IN p_id_intento INT,
   -- Parámetros de salida
	OUT p_codigo_retorno INT,
	OUT p_mensaje_retorno VARCHAR(500),
    OUT p_tiempo INT,
	OUT p_pre_aciertos VARCHAR(200),
	OUT p_pre_errores VARCHAR(200)
)
BEGIN
	-- Declaración de variables locales
	DECLARE v_partida_existe INT DEFAULT 0;
	DECLARE v_id_partida INT;

   -- Manejo de errores SQL
   DECLARE EXIT HANDLER FOR SQLEXCEPTION
   BEGIN
	   GET DIAGNOSTICS CONDITION 1
					@sqlstate = RETURNED_SQLSTATE,
					@errno = MYSQL_ERRNO,
					@text = MESSAGE_TEXT;
       SET p_codigo_retorno = -1;
	   SET p_mensaje_retorno = CONCAT('Error en la ejecución del procedimiento: ', @text, ' (', @errno, ')');
       ROLLBACK;
   END;
	
  bloque_principal: BEGIN
   -- Inicialización de variables de salida
   SET p_codigo_retorno = 0;
   SET p_mensaje_retorno = 'Proceso iniciado correctamente';

	-- Validaciones de parámetros de entrada
	IF p_codigo_partida IS NULL OR p_codigo_partida = '' THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'El código de partida no es válido';
		LEAVE bloque_principal;
	END IF;

	SELECT COUNT(*), id_partida INTO v_partida_existe, v_id_partida
	   FROM partidas 
	   WHERE codigo_partida = p_codigo_partida 
	   AND id_modalidad = 1;

	-- Validaciones de parámetros de entrada
	IF v_id_partida IS NULL OR v_id_partida <= 0 THEN
       SET p_codigo_retorno = -1;
       SET p_mensaje_retorno = 'El ID de partida no es válido';
		LEAVE bloque_principal;
	END IF;

	IF v_partida_existe = 0 THEN
		SET p_codigo_retorno = 2;
		SET p_mensaje_retorno = 'No existen datos para la partida especificada';
	END IF;
		-- Consulta principal        
        Select iri.id_intento, iri.id_requisito, req.descripcion, req.retroalimentacion, iri.cantidad_movimientos, iri.movimientos_toAmbiguo, 
		iri.movimientos_toNoAmbiguo, iri.es_correcto
		from intento_requisitos_incorrectos iri
		INNER JOIN requisitos req ON iri.id_requisito = req.id_requisito
        INNER JOIN intentos i ON iri.id_intento = i.id_intento
		where iri.id_intento = p_id_intento and i.id_jugador = p_id_jugador; -- Aquí va el parámetro del id_partida

	   -- Si llegamos aquí, todo se ejecutó correctamente
       
		SELECT tiempo_intento, precision_aciertos, precision_errores INTO p_tiempo, p_pre_aciertos, p_pre_errores
		from intentos  WHERE -- id_partida = v_id_partida and -- Aquí va el parámetro del id_partida
		id_intento = p_id_intento;
       
	   SET p_codigo_retorno = 1;
	   SET p_mensaje_retorno = 'Resultados de consulta generadas exitosamente';
   END bloque_principal; 
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_stats_details_for_player_student //
CREATE PROCEDURE sp_stats_details_for_player_student(
    -- Parámetros de entrada
    IN p_codigo_partida VARCHAR(10),
    IN p_id_jugador INT,
    -- Parámetros de salida
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500),
    OUT p_intentos INT,
    OUT p_tiempo INT,
    OUT p_nombres VARCHAR(200),
    OUT p_apellidos VARCHAR(200),
    OUT p_correo VARCHAR(200)
)
BEGIN
    -- Declaración de variables locales
    DECLARE v_partida_existe INT DEFAULT 0;
    DECLARE v_id_partida INT;

    -- Manejo de errores SQL
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            @sqlstate = RETURNED_SQLSTATE,
            @errno = MYSQL_ERRNO,
            @text = MESSAGE_TEXT;
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = CONCAT('Error en la ejecución del procedimiento: ', @text, ' (', @errno, ')');
        ROLLBACK;
    END;

    bloque_principal: BEGIN

        -- Inicialización de variables de salida
        SET p_codigo_retorno = 0;
        SET p_mensaje_retorno = 'Proceso iniciado correctamente';

        -- Validaciones de parámetros de entrada
        IF p_codigo_partida IS NULL OR p_codigo_partida = '' THEN
            SET p_codigo_retorno = -1;
            SET p_mensaje_retorno = 'El código de partida no es válido';
            LEAVE bloque_principal;
        END IF;

        -- Verificar existencia de la partida
        SELECT COUNT(*), id_partida INTO v_partida_existe, v_id_partida
        FROM partidas 
        WHERE codigo_partida = p_codigo_partida 
		AND id_modalidad = 1;

        IF v_id_partida IS NULL OR v_id_partida <= 0 THEN
            SET p_codigo_retorno = -1;
            SET p_mensaje_retorno = 'El ID de partida no es válido';
            LEAVE bloque_principal;
        END IF;

        IF v_partida_existe = 0 THEN
            SET p_codigo_retorno = 2;
            SET p_mensaje_retorno = 'No existen datos para la partida especificada';
        ELSE 
            -- Consulta principal con detalles de requisitos
            SELECT 
                i.*,
                GROUP_CONCAT(
                    CONCAT(
                        r.id_requisito, '|',
                        r.descripcion, '|',
                        iri.es_correcto, '|',
                        iri.cantidad_movimientos, '|',
                        iri.movimientos_toAmbiguo, '|',
                        iri.movimientos_toNoAmbiguo
                    )
                    SEPARATOR '¬'
                ) as requisitos_detalles
            FROM intentos i
            LEFT JOIN intento_requisitos_incorrectos iri ON i.id_intento = iri.id_intento
            LEFT JOIN requisitos r ON iri.id_requisito = r.id_requisito
            WHERE i.id_partida = v_id_partida 
            AND i.id_jugador = p_id_jugador
            GROUP BY i.id_intento
            ORDER BY i.numero_intento ASC;

            -- Obtener estadísticas generales
            SELECT 
                SUM(tiempo_intento), 
                COUNT(DISTINCT numero_intento) 
            INTO 
                p_tiempo, 
                p_intentos
            FROM intentos 
            WHERE id_partida = v_id_partida 
            AND id_jugador = p_id_jugador;
            
            -- Obtener datos del jugador
            SELECT 
                nombres, 
                apellidos, 
                correo 
            INTO 
                p_nombres, 
                p_apellidos, 
                p_correo
            FROM jugadores 
            WHERE id_jugador = p_id_jugador;
            
            SET p_codigo_retorno = 1;
            SET p_mensaje_retorno = 'Resultados de consulta generados exitosamente';
        END IF;
    END bloque_principal; 
END //
DELIMITER ;

-- CONSTRUCTION GAME
DELIMITER //
DROP PROCEDURE IF EXISTS sp_stats_for_player_construction_student //
CREATE PROCEDURE sp_stats_for_player_construction_student(
    -- Parámetros de entrada
	IN p_codigo_partida VARCHAR(10),
	IN p_id_jugador INT,
   -- Parámetros de salida
	OUT p_codigo_retorno INT,
	OUT p_mensaje_retorno VARCHAR(500),
    OUT p_intentos INT,
    OUT p_tiempo INT,
	OUT p_nombres VARCHAR(200),
	OUT p_apellidos VARCHAR(200),
	OUT p_correo VARCHAR(200)
)
BEGIN
	-- Declaración de variables locales
	DECLARE v_partida_existe INT DEFAULT 0;
	DECLARE v_id_partida INT;

	-- Manejo de errores SQL
	DECLARE EXIT HANDLER FOR SQLEXCEPTION
	BEGIN
	   GET DIAGNOSTICS CONDITION 1
					@sqlstate = RETURNED_SQLSTATE,
					@errno = MYSQL_ERRNO,
					@text = MESSAGE_TEXT;
       SET p_codigo_retorno = -1;
	   SET p_mensaje_retorno = CONCAT('Error en la ejecución del procedimiento: ', @text, ' (', @errno, ')');
       ROLLBACK;
	END;

	bloque_principal: BEGIN

		-- Inicialización de variables de salida
		SET p_codigo_retorno = 0;
		SET p_mensaje_retorno = 'Proceso iniciado correctamente';

		-- Validaciones de parámetros de entrada
		IF p_codigo_partida IS NULL OR p_codigo_partida = '' THEN
			SET p_codigo_retorno = -1;
			SET p_mensaje_retorno = 'El código de partida no es válido';
			LEAVE bloque_principal;
		END IF;

		SELECT COUNT(*), id_partida INTO v_partida_existe, v_id_partida
		   FROM partidas 
		   WHERE codigo_partida = p_codigo_partida 
		   AND id_modalidad = 2;

		-- Validaciones de parámetros de entrada
		IF v_id_partida IS NULL OR v_id_partida <= 0 THEN
		   SET p_codigo_retorno = -1;
		   SET p_mensaje_retorno = 'El ID de partida no es válido';
		   LEAVE bloque_principal;
		END IF;

		IF v_partida_existe = 0 THEN
			SET p_codigo_retorno = 2;
			SET p_mensaje_retorno = 'No existen datos para la partida especificada';
			LEAVE bloque_principal;
		END IF;    
		-- Consulta principal
		Select req.requisito_completo, ic.*, 
        (SELECT SUM(dci.cantidad_movimientos)
		 FROM detalle_construccion_intento dci
		 WHERE dci.id_intento IN (
			 SELECT id_intento 
			 FROM intentos_construccion 
			 WHERE id_partida = ic.id_partida 
			   AND id_jugador = ic.id_jugador
               AND id_intento = ic.id_intento
		 )) AS cantidad_movimientos
        from intentos_construccion ic
        INNER JOIN requisitos_construccion req ON ic.id_requisito = req.id_requisito
		WHERE ic.id_partida = v_id_partida  -- Aquí va el parámetro del id_partida
		and ic.id_jugador = p_id_jugador ORDER BY numero_intento asc;
		-- Si llegamos aquí, todo se ejecutó correctamente
		   
		SELECT sum(tiempo_intento), COUNT(numero_intento) INTO p_tiempo, p_intentos
		from intentos_construccion WHERE id_partida = v_id_partida  -- Aquí va el parámetro del id_partida
		and id_jugador = p_id_jugador ORDER BY numero_intento asc;
		   
		SELECT nombres, apellidos, correo INTO p_nombres, p_apellidos, p_correo
		FROM jugadores WHERE id_jugador = p_id_jugador;
		   
	   SET p_codigo_retorno = 1;
	   SET p_mensaje_retorno = 'Resultados de consulta generadas exitosamente';
   	END bloque_principal; 
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_details_for_attempt_construction_student //
CREATE PROCEDURE sp_details_for_attempt_construction_student(
    -- Parámetros de entrada
	IN p_codigo_partida VARCHAR(10),
	IN p_id_jugador INT,
    IN p_id_intento INT,
   -- Parámetros de salida
	OUT p_codigo_retorno INT,
	OUT p_mensaje_retorno VARCHAR(500),
    OUT p_tiempo INT,
	OUT p_pre_aciertos VARCHAR(200),
	OUT p_movimientos INT,
    OUT p_correctos INT,
    OUT p_señuelos_usados INT
)
BEGIN
	-- Declaración de variables locales
	DECLARE v_partida_existe INT DEFAULT 0;
	DECLARE v_id_partida INT;

   -- Manejo de errores SQL
   DECLARE EXIT HANDLER FOR SQLEXCEPTION
   BEGIN
	   GET DIAGNOSTICS CONDITION 1
					@sqlstate = RETURNED_SQLSTATE,
					@errno = MYSQL_ERRNO,
					@text = MESSAGE_TEXT;
       SET p_codigo_retorno = -1;
	   SET p_mensaje_retorno = CONCAT('Error en la ejecución del procedimiento: ', @text, ' (', @errno, ')');
       ROLLBACK;
   END;
	
  bloque_principal: BEGIN
   -- Inicialización de variables de salida
   SET p_codigo_retorno = 0;
   SET p_mensaje_retorno = 'Proceso iniciado correctamente';

	-- Validaciones de parámetros de entrada
	IF p_codigo_partida IS NULL OR p_codigo_partida = '' THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'El código de partida no es válido';
		LEAVE bloque_principal;
	END IF;


	SELECT COUNT(*), id_partida INTO v_partida_existe, v_id_partida
	   FROM partidas 
	   WHERE codigo_partida = p_codigo_partida 
	   AND id_modalidad = 2;

	-- Validaciones de parámetros de entrada
	IF v_id_partida IS NULL OR v_id_partida <= 0 THEN
       SET p_codigo_retorno = -1;
       SET p_mensaje_retorno = 'El ID de partida no es válido';
		LEAVE bloque_principal;
	END IF;

	IF v_partida_existe = 0 THEN
		SET p_codigo_retorno = 2;
		SET p_mensaje_retorno = 'No existen datos para la partida especificada';
	END IF;
		-- Consulta principal        
        Select dci.*, frag.texto, frag.es_señuelo
		from detalle_construccion_intento dci
        INNER JOIN fragmentos_requisito frag ON dci.id_fragmento = frag.id_fragmento
        INNER JOIN intentos_construccion i ON dci.id_intento = i.id_intento
		where dci.id_intento = p_id_intento and i.id_jugador = p_id_jugador; -- Aquí va el parámetro del id_partida

	   -- Si llegamos aquí, todo se ejecutó correctamente
       
       SELECT SUM(dci.cantidad_movimientos) INTO p_movimientos
		 FROM detalle_construccion_intento dci
		 WHERE dci.id_intento IN (
			 SELECT id_intento 
			 FROM intentos_construccion 
			 WHERE id_partida = v_id_partida 
			   AND id_jugador = p_id_jugador
               AND id_intento = p_id_intento);
       
		SELECT tiempo_intento, precision_construccion, fragmentos_correctos, señuelos_usados 
        INTO p_tiempo, p_pre_aciertos, p_correctos, p_señuelos_usados
		from intentos_construccion  WHERE -- id_partida = v_id_partida and -- Aquí va el parámetro del id_partida
		id_intento = p_id_intento;
       
	   SET p_codigo_retorno = 1;
	   SET p_mensaje_retorno = 'Resultados de consulta generadas exitosamente';
   END bloque_principal; 
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_full_construction_report_student //
CREATE PROCEDURE sp_get_full_construction_report_student(
    -- Parámetros de entrada
    IN p_codigo_partida VARCHAR(10),
    IN p_id_jugador INT,
    -- Parámetros de salida
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500),
    OUT p_tiempo_total INT,
    OUT p_total_intentos INT,
    OUT p_nombres VARCHAR(200),
    OUT p_apellidos VARCHAR(200),
    OUT p_correo VARCHAR(200)
)
BEGIN
    -- Declaración de variables locales
    DECLARE v_partida_existe INT DEFAULT 0;
    DECLARE v_id_partida INT;

    -- Manejo de errores SQL
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            @sqlstate = RETURNED_SQLSTATE,
            @errno = MYSQL_ERRNO,
            @text = MESSAGE_TEXT;
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = CONCAT('Error en la ejecución del procedimiento: ', @text, ' (', @errno, ')');
        ROLLBACK;
    END;

    bloque_principal: BEGIN
        -- Inicialización y validaciones (igual que en los SPs originales)
        SET p_codigo_retorno = 0;
        SET p_mensaje_retorno = 'Proceso iniciado correctamente';

        -- Validaciones iniciales...
        
        SELECT COUNT(*), id_partida INTO v_partida_existe, v_id_partida
        FROM partidas 
        WHERE codigo_partida = p_codigo_partida 
        AND id_modalidad = 2;

        -- Validaciones de partida...

        -- Primera consulta: Información general de intentos
        SELECT 
            req.requisito_completo,
            ic.*,
			(SELECT SUM(dci.cantidad_movimientos)
			 FROM detalle_construccion_intento dci
			 WHERE dci.id_intento IN (
				 SELECT id_intento 
				 FROM intentos_construccion 
				 WHERE id_partida = ic.id_partida 
				   AND id_jugador = ic.id_jugador
				   AND id_intento = ic.id_intento
			 )) AS cantidad_movimientos,
            (
                SELECT GROUP_CONCAT(
                    CONCAT(
                        dci.id_fragmento, '|',
                        dci.posicion_usada, '|',
                        dci.tiempo_colocacion, '|',
                        dci.cantidad_movimientos, '|',
                        dci.es_correcto, '|',
                        fr.texto, '|',
                        fr.es_señuelo
                    ) 
                    SEPARATOR '¬'
                )
                FROM detalle_construccion_intento dci
                INNER JOIN fragmentos_requisito fr ON dci.id_fragmento = fr.id_fragmento
                WHERE dci.id_intento = ic.id_intento
            ) as detalles_intento
        FROM intentos_construccion ic
        INNER JOIN requisitos_construccion req ON ic.id_requisito = req.id_requisito
        WHERE ic.id_partida = v_id_partida
        AND ic.id_jugador = p_id_jugador 
        ORDER BY ic.id_requisito, ic.numero_intento;

        -- Obtener totales
        SELECT SUM(tiempo_intento), COUNT(numero_intento) 
        INTO p_tiempo_total, p_total_intentos
        FROM intentos_construccion 
        WHERE id_partida = v_id_partida
        AND id_jugador = p_id_jugador;

        -- Obtener información del jugador
        SELECT nombres, apellidos, correo 
        INTO p_nombres, p_apellidos, p_correo
        FROM jugadores 
        WHERE id_jugador = p_id_jugador;

        SET p_codigo_retorno = 1;
        SET p_mensaje_retorno = 'Reporte generado exitosamente';
    END bloque_principal;
END //
DELIMITER ;