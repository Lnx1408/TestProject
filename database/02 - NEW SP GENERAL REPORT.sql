DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_general_info_construction_report //
CREATE PROCEDURE sp_get_general_info_construction_report(
    IN p_codigo_partida VARCHAR(10),
	IN p_id_creador INT,
    OUT p_codigo INT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    -- Declaración de variables
    DECLARE v_id_partida INT;
    DECLARE v_id_modalidad INT;
    DECLARE v_total_requisitos INT;
    DECLARE v_total_jugadores INT;
    
    -- Control de errores con handler
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_codigo = -1;
        SET p_mensaje = 'Error al obtener la información general de la partida';
    END;

    bloque_principal: BEGIN
    
		-- Verificar que la partida existe y es de tipo construcción
		SELECT p.id_partida, p.id_modalidad 
		INTO v_id_partida, v_id_modalidad
		FROM partidas p 
		WHERE p.codigo_partida = p_codigo_partida and p.id_usuario_creacion = p_id_creador
		LIMIT 1;

		-- Validar que la partida existe y es de tipo construcción
		IF v_id_partida IS NULL THEN
			SET p_codigo = -1;
			SET p_mensaje = 'La partida no existe';
			-- Retornar un conjunto vacío
			SELECT NULL AS creator_name, 
				   NULL AS creation_date,
				   NULL AS game_code,
				   NULL AS total_requirements,
				   NULL AS total_players;
			LEAVE bloque_principal;
		ELSEIF v_id_modalidad != 2 THEN
			SET p_codigo = -1;
			SET p_mensaje = 'La partida no es de tipo construcción';
			-- Retornar un conjunto vacío
			SELECT NULL AS creator_name, 
				   NULL AS creation_date,
				   NULL AS game_code,
				   NULL AS total_requirements,
				   NULL AS total_players;
			LEAVE bloque_principal;
		ELSE
			-- Obtener total de requisitos
			SELECT COUNT(*) 
			INTO v_total_requisitos
			FROM requisitos_construccion_partida
			WHERE id_partida = v_id_partida;

			-- Obtener total de jugadores
			SELECT COUNT(DISTINCT id_jugador)
			INTO v_total_jugadores
			FROM partidas_jugadores
			WHERE id_partida = v_id_partida;

			-- Retornar la información general
			SELECT 
				CONCAT(j.nombres, ' ', j.apellidos) AS creator_name,
				DATE_FORMAT(p.fecha_creacion, '%Y/%m/%d') AS creation_date,
				p.codigo_partida AS game_code,
				v_total_requisitos AS total_requirements,
				v_total_jugadores AS total_players
			FROM partidas p
			INNER JOIN jugadores j ON p.id_usuario_creacion = j.id_jugador
			WHERE p.id_partida = v_id_partida;

			SET p_codigo = 1;
			SET p_mensaje = 'Información recuperada con éxito';
			
		END IF;
	END bloque_principal;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_summary_stats_construction_report //
CREATE PROCEDURE sp_get_summary_stats_construction_report(
    IN p_codigo_partida VARCHAR(10),
    IN p_id_creador INT,
    OUT p_codigo INT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    -- Declaración de variables
    DECLARE v_id_partida INT;
    DECLARE v_total_requisitos INT;
    DECLARE v_total_jugadores INT;
    DECLARE v_jugadores_completados INT;
    DECLARE v_jugadores_en_progreso INT;
    DECLARE v_jugadores_sin_iniciar INT;
    
    -- Control de errores con handler
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_codigo = -1;
        SET p_mensaje = 'Error al obtener las estadísticas de la partida';
        ROLLBACK;
    END;

    -- Iniciar transacción
    START TRANSACTION;
    
    -- Obtener ID de la partida
    SELECT id_partida 
    INTO v_id_partida
    FROM partidas 
    WHERE codigo_partida = p_codigo_partida and id_usuario_creacion = p_id_creador
    AND id_modalidad = 2
    LIMIT 1;

    -- Validar que la partida existe
    IF v_id_partida IS NULL THEN
        SET p_codigo = -1;
        SET p_mensaje = 'La partida no existe o no es de tipo construcción';
        ROLLBACK;
        -- Retornar conjunto vacío
        SELECT 
            NULL AS total_players,
            NULL AS average_accuracy,
            NULL AS average_time,
            NULL AS completion_rate,
            NULL AS completed_count,
            NULL AS completed_percentage,
            NULL AS in_progress_count,
            NULL AS in_progress_percentage,
            NULL AS not_started_count,
            NULL AS not_started_percentage;
    ELSE
        -- Obtener total de requisitos por partida
        SELECT COUNT(*)
        INTO v_total_requisitos
        FROM requisitos_construccion_partida
        WHERE id_partida = v_id_partida;

        -- Obtener total de jugadores
        SELECT COUNT(DISTINCT id_jugador)
        INTO v_total_jugadores
        FROM partidas_jugadores
        WHERE id_partida = v_id_partida;

        -- Calcular jugadores que han completado todos los requisitos
        SELECT COUNT(DISTINCT pj.id_jugador)
        INTO v_jugadores_completados
        FROM partidas_jugadores pj
        WHERE pj.id_partida = v_id_partida
        AND pj.estado = 'completado';

        -- Calcular jugadores en progreso (tienen al menos un intento)
        SELECT COUNT(DISTINCT pj.id_jugador)
        INTO v_jugadores_en_progreso
        FROM partidas_jugadores pj
        INNER JOIN intentos_construccion ic ON pj.id_partida = ic.id_partida 
            AND pj.id_jugador = ic.id_jugador
        WHERE pj.id_partida = v_id_partida
        AND pj.estado = 'en_progreso';

        -- Calcular jugadores sin iniciar (sin intentos)
        SET v_jugadores_sin_iniciar = v_total_jugadores - (v_jugadores_completados + v_jugadores_en_progreso);

        -- Retornar estadísticas completas
       WITH EstadisticasJugador AS (
            SELECT 
				ic.id_partida,
                ic.id_jugador,
                AVG(ic.precision_construccion) AS precision_promedio_jugador,
                SUM(ic.tiempo_intento) AS tiempo_promedio_jugador
            FROM intentos_construccion ic
			LEFT JOIN partidas_jugadores pj ON ic.id_jugador = pj.id_jugador AND pj.id_partida = ic.id_partida
            WHERE ic.id_partida = v_id_partida
			AND pj.estado = 'completado'
            GROUP BY ic.id_jugador
        ),
        EstadisticasGenerales AS (
            SELECT 
                AVG(precision_promedio_jugador) AS precision_promedio,
                AVG(tiempo_promedio_jugador) AS tiempo_promedio
            FROM EstadisticasJugador
        )
        SELECT 
            v_total_jugadores AS total_players,
            ROUND(eg.precision_promedio, 2) AS average_accuracy,
            CONCAT(
                FLOOR(eg.tiempo_promedio/60), 'min ',
                MOD(FLOOR(eg.tiempo_promedio), 60), 's'
            ) AS average_time,
            ROUND((v_jugadores_completados * 100.0 / v_total_jugadores), 2) AS completion_rate,
            -- Estadísticas de progreso
            v_jugadores_completados AS completed_count,
            ROUND((v_jugadores_completados * 100.0 / v_total_jugadores), 2) AS completed_percentage,
            v_jugadores_en_progreso AS in_progress_count,
            ROUND((v_jugadores_en_progreso * 100.0 / v_total_jugadores), 2) AS in_progress_percentage,
            v_jugadores_sin_iniciar AS not_started_count,
            ROUND((v_jugadores_sin_iniciar * 100.0 / v_total_jugadores), 2) AS not_started_percentage
        FROM EstadisticasGenerales eg;

        SET p_codigo = 1;
        SET p_mensaje = 'Estadísticas recuperadas con éxito';
        COMMIT;
    END IF;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_time_analysis_construction_report //
CREATE PROCEDURE sp_get_time_analysis_construction_report(
    IN p_codigo_partida VARCHAR(10),
    IN p_id_creador INT,
    
    OUT po_average_time VARCHAR(100),
    OUT po_total_time_invested VARCHAR(100),
    OUT po_best_time VARCHAR(100), 
    OUT po_best_time_player_name VARCHAR(100),  
	OUT po_best_time_player_lastn VARCHAR(100),
    OUT p_codigo INT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    -- Declaración de variables
    DECLARE v_id_partida INT;
    
    -- Control de errores con handler
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            @sqlstate = RETURNED_SQLSTATE,
            @errno = MYSQL_ERRNO,
            @text = MESSAGE_TEXT;
            
		DROP TEMPORARY TABLE IF EXISTS temp_tiempos_por_requisito;
		DROP TEMPORARY TABLE IF EXISTS temp_tiempos_por_jugador;
        SET p_codigo = -1;
        -- SET p_mensaje = 'Error al obtener el análisis de tiempo de la partida';
		SET p_mensaje = CONCAT('Error en la ejecución del procedimiento: ', @text, ' (', @errno, ')');
    END;

    -- Iniciar transacción
    bloque_principal: BEGIN
    
		-- Obtener ID de la partida
		SELECT id_partida 
		INTO v_id_partida
		FROM partidas 
		WHERE codigo_partida = p_codigo_partida and id_usuario_creacion = p_id_creador
		AND id_modalidad = 2
		LIMIT 1;

		-- Validar que la partida existe
		IF v_id_partida IS NULL THEN
			SET p_codigo = -1;
			SET p_mensaje = 'La partida no existe o no es de tipo construcción';
			LEAVE bloque_principal;
		ELSE
			-- TABLA AUXILIAR QUE ME DA LOS TIEMPOS TOTALES DE LOS REQUISITOS EJ: RE1 INTENTOS 3 T FINAL = (SUMA INTENTOS)
			DROP TEMPORARY TABLE IF EXISTS temp_tiempos_por_requisito;
			CREATE TEMPORARY TABLE temp_tiempos_por_requisito AS
			SELECT 
				ic.id_partida,
				ic.id_jugador,
				ic.id_requisito,
				SUM(ic.tiempo_intento) as tiempo_total_requisito
			FROM intentos_construccion ic
			WHERE ic.id_partida = v_id_partida -- DESDE AQUI YA FILTRO QUE LOS DATOS SEAN PARA UN ID PARTIDA ESPECIFICO
			GROUP BY ic.id_partida, ic.id_jugador, ic.id_requisito;
			
			-- TIEMPOS TOTALES POR JUGADOR SOLO QUIENES FINALIZARON
			DROP TEMPORARY TABLE IF EXISTS temp_tiempos_por_jugador;
			CREATE TEMPORARY TABLE temp_tiempos_por_jugador AS
			SELECT 
				ttr.id_jugador,
				SUM(ttr.tiempo_total_requisito) as tiempo_total_por_jugador,
				pj.estado
			FROM temp_tiempos_por_requisito ttr
			LEFT JOIN partidas_jugadores pj ON ttr.id_jugador = pj.id_jugador AND pj.id_partida = ttr.id_partida
			WHERE pj.estado = 'completado'
			GROUP BY ttr.id_jugador, pj.estado;
			
			SELECT 
				-- Tiempo promedio por requisito
				CONCAT(
					FLOOR(AVG(tiempos.tiempo_total_requisito)/60), 'min ',
					MOD(FLOOR(AVG(tiempos.tiempo_total_requisito)), 60), 's'
				) as average_time,
				-- Tiempo total invertido
				CONCAT(
					FLOOR(SUM(tiempos.tiempo_total_requisito)/3600), 'h ',
					FLOOR((SUM(tiempos.tiempo_total_requisito) % 3600)/60), 'm'
				) as total_time_invested
                INTO po_average_time, po_total_time_invested
			FROM temp_tiempos_por_requisito tiempos;

			SELECT 
				CONCAT(
					FLOOR(MIN(ttj.tiempo_total_por_jugador)/60), 'min ',
					MOD(FLOOR(MIN(ttj.tiempo_total_por_jugador)), 60), 's'
				) as best_time,
				j.nombres as best_time_player_name, j.apellidos as best_time_player_lastn
                INTO po_best_time, po_best_time_player_name, po_best_time_player_lastn
			FROM temp_tiempos_por_jugador ttj
			INNER JOIN jugadores j ON ttj.id_jugador = j.id_jugador
			WHERE ttj.tiempo_total_por_jugador = (
				SELECT MIN(tiempo_total_por_jugador)
				FROM temp_tiempos_por_jugador
			);
			
			-- Obtener distribución de tiempo
			SELECT 
				CASE 
					WHEN tiempo_total_por_jugador < 900 THEN '0-15 min'
					WHEN tiempo_total_por_jugador < 1200 THEN '15-20 min'
					ELSE '20+ min'
				END as rango_tiempo,
				COUNT(DISTINCT id_jugador) as cantidad_jugadores
			FROM temp_tiempos_por_jugador
			GROUP BY 
				CASE 
					WHEN tiempo_total_por_jugador < 900 THEN '0-15 min'
					WHEN tiempo_total_por_jugador < 1200 THEN '15-20 min'
					ELSE '20+ min'
				END;
			SET p_codigo = 1;
			SET p_mensaje = 'Análisis de tiempo recuperado con éxito';
		END IF;
	END bloque_principal;
    DROP TEMPORARY TABLE IF EXISTS temp_tiempos_por_requisito;
	DROP TEMPORARY TABLE IF EXISTS temp_tiempos_por_jugador;
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_time_analysis_requirement_construction_report //
CREATE PROCEDURE sp_get_time_analysis_requirement_construction_report(
    IN p_codigo_partida VARCHAR(10),
    IN p_id_creador INT,
    
    OUT p_codigo INT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    -- Declaración de variables
    DECLARE v_id_partida INT;
    
    -- Control de errores con handler
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            @sqlstate = RETURNED_SQLSTATE,
            @errno = MYSQL_ERRNO,
            @text = MESSAGE_TEXT;
            
		DROP TEMPORARY TABLE IF EXISTS temp_tiempos_por_requisito;
		DROP TEMPORARY TABLE IF EXISTS temp_tiempos_por_jugador;
        SET p_codigo = -1;
        -- SET p_mensaje = 'Error al obtener el análisis de tiempo de la partida';
		SET p_mensaje = CONCAT('Error en la ejecución del procedimiento: ', @text, ' (', @errno, ')');
    END;

    -- Iniciar transacción
    bloque_principal: BEGIN
    
		-- Obtener ID de la partida
		SELECT id_partida 
		INTO v_id_partida
		FROM partidas 
		WHERE codigo_partida = p_codigo_partida and id_usuario_creacion = p_id_creador
		AND id_modalidad = 2
		LIMIT 1;

		-- Validar que la partida existe
		IF v_id_partida IS NULL THEN
			SET p_codigo = -1;
			SET p_mensaje = 'La partida no existe o no es de tipo construcción';
			LEAVE bloque_principal;
		ELSE
			-- TABLA AUXILIAR QUE ME DA LOS TIEMPOS TOTALES DE LOS REQUISITOS EJ: RE1 INTENTOS 3 T FINAL = (SUMA INTENTOS)
			DROP TEMPORARY TABLE IF EXISTS temp_tiempos_por_requisito;
			CREATE TEMPORARY TABLE temp_tiempos_por_requisito AS
			SELECT 
				ic.id_partida,
				ic.id_jugador,
				ic.id_requisito,
				SUM(ic.tiempo_intento) as tiempo_total_requisito
			FROM intentos_construccion ic
			WHERE ic.id_partida = v_id_partida -- DESDE AQUI YA FILTRO QUE LOS DATOS SEAN PARA UN ID PARTIDA ESPECIFICO
			GROUP BY ic.id_partida, ic.id_jugador, ic.id_requisito;
			
			-- TIEMPOS TOTALES POR JUGADOR SOLO QUIENES FINALIZARON
			DROP TEMPORARY TABLE IF EXISTS temp_tiempos_por_jugador;
			CREATE TEMPORARY TABLE temp_tiempos_por_jugador AS
			SELECT 
				ttr.id_jugador,
				SUM(ttr.tiempo_total_requisito) as tiempo_total_por_jugador,
				pj.estado
			FROM temp_tiempos_por_requisito ttr
			LEFT JOIN partidas_jugadores pj ON ttr.id_jugador = pj.id_jugador AND pj.id_partida = ttr.id_partida
			WHERE pj.estado = 'completado'
			GROUP BY ttr.id_jugador, pj.estado;
			
			
			-- Obtener análisis por requisito
			SELECT 
			rc.requisito_completo AS description,
			COALESCE(
				(SELECT 
					CONCAT(
						FLOOR(AVG(ttr.tiempo_total_requisito) / 60), 'min ',
						MOD(FLOOR(AVG(ttr.tiempo_total_requisito)), 60), 's'
					)
				 FROM temp_tiempos_por_requisito ttr
				 WHERE ttr.id_requisito = rc.id_requisito
				 GROUP BY ttr.id_requisito
				),
				'0' -- Si no hay datos en la tabla `temp_tiempos_por_requisito`, mostrar N/A
			) AS average_time,
			ROUND(
				(COUNT(DISTINCT CASE WHEN pj.estado = 'completado' THEN ic.id_jugador END) * 100.0) /
				COUNT(DISTINCT ic.id_jugador),
				2
			) AS completion_rate,
			ROUND(COUNT(ic.id_intento) * 1.0 / COUNT(DISTINCT ic.id_jugador), 1) AS average_attempts
		FROM requisitos_construccion rc
		INNER JOIN requisitos_construccion_partida rcp ON rc.id_requisito = rcp.id_requisito
		LEFT JOIN intentos_construccion ic ON rc.id_requisito = ic.id_requisito
		LEFT JOIN partidas_jugadores pj ON ic.id_partida = pj.id_partida AND ic.id_jugador = pj.id_jugador
		WHERE rcp.id_partida = v_id_partida
		GROUP BY rc.id_requisito, rc.requisito_completo;


			SET p_codigo = 1;
			SET p_mensaje = 'Análisis de tiempo recuperado con éxito';
		END IF;
	END bloque_principal;
    DROP TEMPORARY TABLE IF EXISTS temp_tiempos_por_requisito;
	DROP TEMPORARY TABLE IF EXISTS temp_tiempos_por_jugador;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_difficulty_analysis_construction_report //
CREATE PROCEDURE sp_get_difficulty_analysis_construction_report(
    IN p_codigo_partida VARCHAR(10),
    IN p_id_creador INT,
     
    OUT po_min_attempts VARCHAR(100), 
	OUT po_max_attempts VARCHAR(100), 
    OUT po_min_attempts_player_name VARCHAR(100), 
    OUT po_min_attempts_player_lastn VARCHAR(100), 
	OUT po_max_attempts_player_name VARCHAR(100), 
    OUT po_max_attempts_player_lastn VARCHAR(100), 
    OUT p_codigo INT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    -- Declaración de variables
    DECLARE v_id_partida INT;
    
    -- Control de errores con handler
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            @sqlstate = RETURNED_SQLSTATE,
            @errno = MYSQL_ERRNO,
            @text = MESSAGE_TEXT;
        SET p_codigo = -1;
		DROP TEMPORARY TABLE IF EXISTS temp_intentos_requisito;
		DROP TEMPORARY TABLE IF EXISTS temp_stats_requisito;
        SET p_mensaje = CONCAT('Error en la ejecución del procedimiento: ', @text, ' (', @errno, ')');
    END;

    -- Iniciar transacción
    bloque_principal: BEGIN
    
		-- Obtener ID de la partida
		SELECT id_partida 
		INTO v_id_partida
		FROM partidas 
		WHERE codigo_partida = p_codigo_partida and id_usuario_creacion = p_id_creador
		AND id_modalidad = 2
		LIMIT 1;

		-- Validar que la partida existe
		IF v_id_partida IS NULL THEN
			SET p_codigo = -1;
			SET p_mensaje = 'La partida no existe o no es de tipo construcción';
			LEAVE bloque_principal;
		ELSE
			-- Crear tabla temporal con los intentos por requisito y jugador
			DROP TEMPORARY TABLE IF EXISTS temp_intentos_requisito;
			CREATE TEMPORARY TABLE temp_intentos_requisito AS
			SELECT 
				ic.id_partida,
				ic.id_requisito,
				ic.id_jugador,
				COUNT(ic.id_intento) as total_intentos,
				AVG(ic.precision_construccion) as precision_promedio,
				SUM(ic.señuelos_usados) as total_señuelos
			FROM intentos_construccion ic
			WHERE ic.id_partida = v_id_partida
			GROUP BY ic.id_requisito, ic.id_jugador;

			-- Crear tabla temporal con estadísticas por requisito
			DROP TEMPORARY TABLE IF EXISTS temp_stats_requisito;
			CREATE TEMPORARY TABLE temp_stats_requisito AS
			SELECT 
				rc.id_requisito,
				rc.requisito_completo,
				AVG(tir.total_intentos) as promedio_intentos,
				AVG(tir.precision_promedio) as precision_promedio,
				AVG(tir.total_señuelos) as promedio_señuelos,
				COUNT(DISTINCT tir.id_jugador) as total_jugadores
			FROM requisitos_construccion rc
			INNER JOIN requisitos_construccion_partida rcp ON rc.id_requisito = rcp.id_requisito
			LEFT JOIN temp_intentos_requisito tir ON rc.id_requisito = tir.id_requisito
			WHERE rcp.id_partida = v_id_partida
			GROUP BY rc.id_requisito, rc.requisito_completo;

			-- Obtener los 3 requisitos más desafiantes
			SELECT 
				ROW_NUMBER() OVER (ORDER BY promedio_intentos DESC) as rankg,
				requisito_completo as description,
				ROUND(promedio_intentos, 1) as average_attempts
			FROM temp_stats_requisito
			ORDER BY promedio_intentos DESC
			LIMIT 3;

			-- Obtener estadísticas de intentos (mejor y peor caso)
			WITH EstadisticasJugador AS (
				SELECT 
					j.id_jugador,
					j.nombres, j.apellidos,
					SUM(tir.total_intentos) as total_intentos_jugador
				FROM jugadores j
				INNER JOIN temp_intentos_requisito tir ON j.id_jugador = tir.id_jugador
				LEFT JOIN partidas_jugadores pj ON tir.id_jugador = pj.id_jugador AND pj.id_partida = tir.id_partida
				WHERE pj.estado = 'completado'
				GROUP BY j.id_jugador
			)
			SELECT 
				MIN(total_intentos_jugador) as min_attempts,
				MAX(total_intentos_jugador) as max_attempts,
				(SELECT nombres 
				 FROM EstadisticasJugador 
				 WHERE total_intentos_jugador = MIN(ej.total_intentos_jugador) 
				 LIMIT 1) as min_attempts_player_name,
				(SELECT apellidos 
				 FROM EstadisticasJugador 
				 WHERE total_intentos_jugador = MIN(ej.total_intentos_jugador) 
				 LIMIT 1) as min_attempts_player_lastn,
				(SELECT nombres 
				 FROM EstadisticasJugador 
				 WHERE total_intentos_jugador = MAX(ej.total_intentos_jugador) 
				 LIMIT 1) as max_attempts_player_name, 
				(SELECT apellidos 
				 FROM EstadisticasJugador 
				 WHERE total_intentos_jugador = MAX(ej.total_intentos_jugador) 
				 LIMIT 1) as max_attempts_player_lastn
                 INTO 
					po_min_attempts, 
					po_max_attempts, 
					po_min_attempts_player_name, 
					po_min_attempts_player_lastn, 
					po_max_attempts_player_name, 
					po_max_attempts_player_lastn
			FROM EstadisticasJugador ej;

			SET p_codigo = 1;
			SET p_mensaje = 'Análisis de dificultad recuperado con éxito';
			
			COMMIT;
		END IF;
	END bloque_principal;
    DROP TEMPORARY TABLE IF EXISTS temp_intentos_requisito;
	DROP TEMPORARY TABLE IF EXISTS temp_stats_requisito;
END //
DELIMITER ;


/** JUEGO DE CLASIFICACION*/
DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_general_info_classification_report //
CREATE PROCEDURE sp_get_general_info_classification_report(
   IN p_codigo_partida VARCHAR(10),
   IN p_id_creador INT,
   OUT p_codigo INT,
   OUT p_mensaje VARCHAR(255)
)
BEGIN
   -- Declaración de variables
   DECLARE v_id_partida INT;
   DECLARE v_id_modalidad INT;
   DECLARE v_total_requisitos INT;
   DECLARE v_total_jugadores INT;
   
   -- Control de errores
   DECLARE EXIT HANDLER FOR SQLEXCEPTION
   BEGIN
       SET p_codigo = -1;
       SET p_mensaje = 'Error al obtener la información general de la partida';
   END;

   bloque_principal: BEGIN
       -- Verificar partida y modalidad
       SELECT p.id_partida, p.id_modalidad 
       INTO v_id_partida, v_id_modalidad
       FROM partidas p 
       WHERE p.codigo_partida = p_codigo_partida 
       AND p.id_usuario_creacion = p_id_creador
       LIMIT 1;

       IF v_id_partida IS NULL THEN
           SET p_codigo = -1;
           SET p_mensaje = 'La partida no existe';
           SELECT NULL AS creator_name, 
                  NULL AS creation_date,
                  NULL AS game_code,
                  NULL AS total_requirements;
           LEAVE bloque_principal;
       ELSEIF v_id_modalidad != 1 THEN
           SET p_codigo = -1;
           SET p_mensaje = 'La partida no es de tipo clasificación';
           SELECT NULL AS creator_name, 
                  NULL AS creation_date,
                  NULL AS game_code,
                  NULL AS total_requirements;
           LEAVE bloque_principal;
       ELSE
           -- Total requisitos
           SELECT COUNT(*) 
           INTO v_total_requisitos
           FROM requisitos_clasificacion_partida
           WHERE id_partida = v_id_partida;

           -- Total jugadores
           SELECT COUNT(DISTINCT id_jugador)
           INTO v_total_jugadores
           FROM partidas_jugadores
           WHERE id_partida = v_id_partida;

           -- Retornar información
           SELECT 
               CONCAT(j.nombres, ' ', j.apellidos) AS creator_name,
               DATE_FORMAT(p.fecha_creacion, '%Y/%m/%d') AS creation_date,
               p.codigo_partida AS game_code,
               v_total_requisitos AS total_requirements,
               v_total_jugadores AS total_players
           FROM partidas p
           INNER JOIN jugadores j ON p.id_usuario_creacion = j.id_jugador
           WHERE p.id_partida = v_id_partida;

           SET p_codigo = 1;
           SET p_mensaje = 'Información recuperada con éxito';
       END IF;
   END bloque_principal;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_summary_stats_classification_report //
CREATE PROCEDURE sp_get_summary_stats_classification_report(
	IN p_codigo_partida VARCHAR(10),
	IN p_id_creador INT,
   
	OUT p_one_attempt INT,
	OUT p_one_attempt_percentage DECIMAL(5,2),
	OUT p_two_three_attempts INT,
	OUT p_two_three_percentage DECIMAL(5,2),
	OUT p_more_attempts INT,
	OUT p_more_percentage DECIMAL(5,2),
	OUT p_codigo INT,
	OUT p_mensaje VARCHAR(255)
)
BEGIN
   DECLARE v_id_partida INT;
   DECLARE v_total_requisitos INT;
   DECLARE v_total_jugadores INT;
   DECLARE v_jugadores_completados INT;
   DECLARE v_jugadores_en_progreso INT;
   DECLARE v_jugadores_sin_iniciar INT;
   
   DECLARE v_one_attempt INT;
   DECLARE v_two_three_attempts INT;
   DECLARE v_more_attempts INT;

   
   DECLARE EXIT HANDLER FOR SQLEXCEPTION
   BEGIN
       SET p_codigo = -1;
       SET p_mensaje = 'Error al obtener las estadísticas de la partida';
       ROLLBACK;
   END;

   START TRANSACTION;
   
   SELECT id_partida 
   INTO v_id_partida
   FROM partidas 
   WHERE codigo_partida = p_codigo_partida 
   AND id_usuario_creacion = p_id_creador
   AND id_modalidad = 1
   LIMIT 1;

   IF v_id_partida IS NULL THEN
       SET p_codigo = -1;
       SET p_mensaje = 'La partida no existe o no es de tipo clasificación';
       ROLLBACK;
       SELECT NULL AS total_players,
              NULL AS first_attempt_accuracy,
              NULL AS average_time,
              NULL AS completed_count,
              NULL AS completed_percentage,
              NULL AS in_progress_count,
              NULL AS in_progress_percentage,
              NULL AS not_started_count,
              NULL AS not_started_percentage;
   ELSE
       SELECT COUNT(*) INTO v_total_requisitos
       FROM requisitos_clasificacion_partida
       WHERE id_partida = v_id_partida;

       SELECT COUNT(DISTINCT id_jugador) INTO v_total_jugadores
       FROM partidas_jugadores
       WHERE id_partida = v_id_partida;

       SELECT COUNT(DISTINCT pj.id_jugador) INTO v_jugadores_completados
       FROM partidas_jugadores pj
       WHERE pj.id_partida = v_id_partida
       AND pj.estado = 'completado';

       SELECT COUNT(DISTINCT pj.id_jugador) INTO v_jugadores_en_progreso
       FROM partidas_jugadores pj
       INNER JOIN intentos i ON pj.id_partida = i.id_partida 
           AND pj.id_jugador = i.id_jugador
       WHERE pj.id_partida = v_id_partida
       AND pj.estado = 'en_progreso';

       SET v_jugadores_sin_iniciar = v_total_jugadores - (v_jugadores_completados + v_jugadores_en_progreso);

		   SELECT 
			   COUNT(*) INTO v_one_attempt
		   FROM partidas_jugadores pj
		   WHERE pj.id_partida = v_id_partida
		   AND pj.estado = 'completado'
		   AND (
			   SELECT COUNT(DISTINCT numero_intento)
			   FROM intentos i
			   WHERE i.id_partida = pj.id_partida 
			   AND i.id_jugador = pj.id_jugador
		   ) = 1;

		   SELECT 
			   COUNT(*) INTO v_two_three_attempts
		   FROM partidas_jugadores pj
		   WHERE pj.id_partida = v_id_partida
		   AND pj.estado = 'completado'
		   AND (
			   SELECT COUNT(DISTINCT numero_intento)
			   FROM intentos i
			   WHERE i.id_partida = pj.id_partida 
			   AND i.id_jugador = pj.id_jugador
		   ) BETWEEN 2 AND 3;

		   SELECT 
			   COUNT(*) INTO v_more_attempts
		   FROM partidas_jugadores pj
		   WHERE pj.id_partida = v_id_partida
		   AND pj.estado = 'completado'
		   AND (
			   SELECT COUNT(DISTINCT numero_intento)
			   FROM intentos i
			   WHERE i.id_partida = pj.id_partida 
			   AND i.id_jugador = pj.id_jugador
		   ) > 3;

		   -- Asignar valores a los parámetros de salida
		   SET p_one_attempt = v_one_attempt;
		   SET p_one_attempt_percentage = ROUND((v_one_attempt * 100.0 / v_jugadores_completados), 2);
		   SET p_two_three_attempts = v_two_three_attempts;
		   SET p_two_three_percentage = ROUND((v_two_three_attempts * 100.0 / v_jugadores_completados), 2);
		   SET p_more_attempts = v_more_attempts;
		   SET p_more_percentage = ROUND((v_more_attempts * 100.0 / v_jugadores_completados), 2);

       WITH PrimerIntentoStats AS (
           SELECT 
               i.id_jugador,
               i.numero_intento,
               i.precision_general,
               i.tiempo_intento
           FROM intentos i
           WHERE i.id_partida = v_id_partida
           AND i.numero_intento = 1
       ), TiemposJugadores AS (
		   SELECT 
			   pj.id_jugador,
			   SUM(i.tiempo_intento) as tiempo_total,
			   i.fecha_intento
		   FROM partidas_jugadores pj
		   INNER JOIN intentos i ON pj.id_partida = i.id_partida 
			   AND pj.id_jugador = i.id_jugador
		   WHERE pj.id_partida = v_id_partida
		   AND pj.estado = 'completado'
		   GROUP BY pj.id_jugador
		)
       SELECT 
           v_total_jugadores AS total_players,
           ROUND(AVG(pis.precision_general), 2) AS first_attempt_accuracy,
            CONCAT(
			   FLOOR(AVG(tj.tiempo_total)/60), 'min ',
			   MOD(FLOOR(AVG(tj.tiempo_total)), 60), 's'
		   ) AS average_time,
           v_jugadores_completados AS completed_count,
           ROUND((v_jugadores_completados * 100.0 / v_total_jugadores), 2) AS completed_percentage,
           v_jugadores_en_progreso AS in_progress_count,
           ROUND((v_jugadores_en_progreso * 100.0 / v_total_jugadores), 2) AS in_progress_percentage,
           v_jugadores_sin_iniciar AS not_started_count,
           ROUND((v_jugadores_sin_iniciar * 100.0 / v_total_jugadores), 2) AS not_started_percentage
       FROM PrimerIntentoStats pis
       LEFT JOIN TiemposJugadores tj ON pis.id_jugador = tj.id_jugador;

       SET p_codigo = 1;
       SET p_mensaje = 'Estadísticas recuperadas con éxito';
       COMMIT;
   END IF;
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_time_analysis_classification_report //
CREATE PROCEDURE sp_get_time_analysis_classification_report(
   IN p_codigo_partida VARCHAR(10),
   IN p_id_creador INT,
   OUT po_average_time VARCHAR(100),
   OUT po_best_time VARCHAR(100),
   OUT po_best_time_player_name VARCHAR(100),
   OUT po_best_time_player_lastn VARCHAR(100),
   OUT po_worst_time VARCHAR(100),
   OUT po_worst_time_player_name VARCHAR(100),
   OUT po_worst_time_player_lastn VARCHAR(100),
   OUT p_codigo INT,
   OUT p_mensaje VARCHAR(255)
)
BEGIN
   DECLARE v_id_partida INT;
   
   DECLARE EXIT HANDLER FOR SQLEXCEPTION
   BEGIN
	   GET DIAGNOSTICS CONDITION 1
				@sqlstate = RETURNED_SQLSTATE,
				@errno = MYSQL_ERRNO,
				@text = MESSAGE_TEXT;
		DROP TEMPORARY TABLE IF EXISTS temp_tiempos_jugador_classification;
		SET p_codigo = -1;
		SET p_mensaje = CONCAT('Error al obtener el análisis de tiempo: ', @text, ' (', @errno, ')');
   END;

   bloque_principal: BEGIN
       SELECT id_partida INTO v_id_partida
       FROM partidas 
       WHERE codigo_partida = p_codigo_partida 
       AND id_usuario_creacion = p_id_creador
       AND id_modalidad = 1;

       IF v_id_partida IS NULL THEN
           SET p_codigo = -1;
           SET p_mensaje = 'La partida no existe o no es de tipo clasificación';
           LEAVE bloque_principal;
       END IF;

		DROP TEMPORARY TABLE IF EXISTS temp_tiempos_jugador_classification;
		CREATE TEMPORARY TABLE temp_tiempos_jugador_classification AS 
           SELECT 
               pj.id_jugador,
               j.nombres,
               j.apellidos,
               SUM(i.tiempo_intento) as tiempo_total
           FROM partidas_jugadores pj
           INNER JOIN intentos i ON pj.id_partida = i.id_partida AND pj.id_jugador = i.id_jugador
           INNER JOIN jugadores j ON pj.id_jugador = j.id_jugador
           WHERE pj.id_partida = v_id_partida
           AND pj.estado = 'completado'
           GROUP BY pj.id_jugador, j.nombres, j.apellidos; 
              
       SELECT 
           CONCAT(
               FLOOR(AVG(tiempo_total)/60), 'min ',
               MOD(FLOOR(AVG(tiempo_total)), 60), 's'
           ),
           CONCAT(
               FLOOR(MIN(tiempo_total)/60), 'min ',
               MOD(FLOOR(MIN(tiempo_total)), 60), 's'
           ),
           (SELECT nombres FROM temp_tiempos_jugador_classification WHERE tiempo_total = MIN(tj.tiempo_total)),
           (SELECT apellidos FROM temp_tiempos_jugador_classification WHERE tiempo_total = MIN(tj.tiempo_total)),
           CONCAT(
               FLOOR(MAX(tiempo_total)/60), 'min ',
               MOD(FLOOR(MAX(tiempo_total)), 60), 's'
           ),
           (SELECT nombres FROM temp_tiempos_jugador_classification WHERE tiempo_total = MAX(tj.tiempo_total)),
           (SELECT apellidos FROM temp_tiempos_jugador_classification WHERE tiempo_total = MAX(tj.tiempo_total))
       INTO 
           po_average_time,
           po_best_time,
           po_best_time_player_name,
           po_best_time_player_lastn,
           po_worst_time,
           po_worst_time_player_name,
           po_worst_time_player_lastn
       FROM temp_tiempos_jugador_classification tj;

       -- Distribución de tiempo
       SELECT 
           CASE 
               WHEN tiempo_total < 360 THEN '0-6 min'
               WHEN tiempo_total < 600 THEN '6-10 min'
               ELSE '+10 min'
           END as rango_tiempo,
           COUNT(id_jugador) as cantidad_jugadores
       FROM temp_tiempos_jugador_classification
       GROUP BY 
           CASE 
               WHEN tiempo_total < 480 THEN '0-8 min'
               WHEN tiempo_total < 600 THEN '8-10 min'
               ELSE '+10 min'
           END;

       SET p_codigo = 1;
       SET p_mensaje = 'Análisis de tiempo recuperado con éxito';
   END bloque_principal;
	DROP TEMPORARY TABLE IF EXISTS temp_tiempos_jugador_classification;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_requirements_analysis_classification_report //
CREATE PROCEDURE sp_get_requirements_analysis_classification_report(
   IN p_codigo_partida VARCHAR(10),
   IN p_id_creador INT,
   OUT po_ambiguous_count INT,
   OUT po_non_ambiguous_count INT,
   OUT p_codigo INT,
   OUT p_mensaje VARCHAR(255)
)
BEGIN
   DECLARE v_id_partida INT;
   
   DECLARE EXIT HANDLER FOR SQLEXCEPTION
   BEGIN
       DROP TEMPORARY TABLE IF EXISTS temp_stats_requisito;
       SET p_codigo = -1;
       SET p_mensaje = 'Error al obtener el análisis de requisitos';
   END;

   bloque_principal: BEGIN
       SELECT id_partida INTO v_id_partida
       FROM partidas 
       WHERE codigo_partida = p_codigo_partida 
       AND id_usuario_creacion = p_id_creador
       AND id_modalidad = 1;

       IF v_id_partida IS NULL THEN
           SET p_codigo = -1;
           SET p_mensaje = 'La partida no existe o no es de tipo clasificación';
           SET po_ambiguous_count = NULL;
           SET po_non_ambiguous_count = NULL;
           SELECT NULL as descripcion,
                  NULL as es_ambiguo,
                  NULL as avg_time,
                  NULL as avg_moves,
                  NULL as success_rate;
           LEAVE bloque_principal;
       END IF;

       SELECT 
           SUM(CASE WHEN r.es_ambiguo = 1 THEN 1 ELSE 0 END),
           SUM(CASE WHEN r.es_ambiguo = 0 THEN 1 ELSE 0 END)
       INTO po_ambiguous_count, po_non_ambiguous_count
       FROM requisitos r
       INNER JOIN requisitos_clasificacion_partida rcp ON r.id_requisito = rcp.id_requisito
       WHERE rcp.id_partida = v_id_partida;

       CREATE TEMPORARY TABLE temp_stats_requisito AS
       WITH IntentosRequisito AS (
           SELECT 
               iri.id_requisito,
               i.id_jugador,
               SUM(iri.cantidad_movimientos) as total_movimientos,
               SUM(i.tiempo_intento) as tiempo_total,
               CASE WHEN SUM(CASE WHEN iri.es_correcto = 1 THEN 1 ELSE 0 END) > 0 THEN 1 ELSE 0 END as clasifico_correctamente
           FROM intentos i
           INNER JOIN intento_requisitos_incorrectos iri ON i.id_intento = iri.id_intento
           WHERE i.id_partida = v_id_partida
           GROUP BY iri.id_requisito, i.id_jugador
       )
       SELECT 
           r.id_requisito,
           r.descripcion,
           r.es_ambiguo,
           COUNT(DISTINCT ir.id_jugador) as total_jugadores,
           AVG(ir.total_movimientos) as promedio_movimientos,
           AVG(ir.tiempo_total) as promedio_tiempo,
           SUM(ir.clasifico_correctamente) * 100.0 / COUNT(DISTINCT ir.id_jugador) as tasa_exito
       FROM requisitos r
       INNER JOIN requisitos_clasificacion_partida rcp ON r.id_requisito = rcp.id_requisito
       LEFT JOIN IntentosRequisito ir ON r.id_requisito = ir.id_requisito
       WHERE rcp.id_partida = v_id_partida
       GROUP BY r.id_requisito;

       SELECT 
           descripcion,
           es_ambiguo,
           CONCAT(
               FLOOR(promedio_tiempo/60), 'min ',
               MOD(FLOOR(promedio_tiempo), 60), 's'
           ) as avg_time,
           ROUND(promedio_movimientos, 1) as avg_moves,
           ROUND(tasa_exito, 1) as success_rate
       FROM temp_stats_requisito
       ORDER BY tasa_exito ASC;

       SET p_codigo = 1;
       SET p_mensaje = 'Análisis de requisitos recuperado con éxito';

       DROP TEMPORARY TABLE IF EXISTS temp_stats_requisito;
   END bloque_principal;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_challenging_requirements_classification_report //
CREATE PROCEDURE sp_get_challenging_requirements_classification_report(
   IN p_codigo_partida VARCHAR(10),
   IN p_id_creador INT,
   OUT p_codigo INT,
   OUT p_mensaje VARCHAR(255)
)
BEGIN
   DECLARE v_id_partida INT;
   
   DECLARE EXIT HANDLER FOR SQLEXCEPTION
   BEGIN
       SET p_codigo = -1;
       SET p_mensaje = 'Error al obtener los requisitos desafiantes';
   END;
   bloque_principal: BEGIN
       SELECT id_partida INTO v_id_partida
       FROM partidas 
       WHERE codigo_partida = p_codigo_partida 
       AND id_usuario_creacion = p_id_creador
       AND id_modalidad = 1;
       IF v_id_partida IS NULL THEN
           SET p_codigo = -1;
           SET p_mensaje = 'La partida no existe o no es de tipo clasificación';
           SELECT NULL as description, NULL as is_ambiguous, NULL as error_rate, NULL as players_with_errors, NULL as total_players;
           LEAVE bloque_principal;
       END IF;
       WITH RequisitoEstadisticas AS (
           SELECT 
               r.id_requisito,
               r.descripcion,
               r.es_ambiguo,
               COUNT(DISTINCT i.id_jugador) as total_jugadores,
               COUNT(DISTINCT CASE WHEN iri.es_correcto = 0 THEN i.id_jugador END) as jugadores_error
           FROM requisitos r
           INNER JOIN requisitos_clasificacion_partida rcp ON r.id_requisito = rcp.id_requisito
           INNER JOIN intento_requisitos_incorrectos iri ON r.id_requisito = iri.id_requisito
           INNER JOIN intentos i ON iri.id_intento = i.id_intento
           WHERE rcp.id_partida = v_id_partida
           GROUP BY r.id_requisito
       )
       SELECT 
           descripcion as description,
           es_ambiguo as is_ambiguous,
           ROUND((jugadores_error * 100.0 / total_jugadores), 1) as error_rate,
           jugadores_error as players_with_errors,
           total_jugadores as total_players
       FROM RequisitoEstadisticas
       ORDER BY jugadores_error DESC
       LIMIT 3;
       SET p_codigo = 1;
       SET p_mensaje = 'Requisitos desafiantes recuperados con éxito';
   END bloque_principal;
END //
DELIMITER ;
