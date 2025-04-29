/***PROCEDIMIENTOS ALMACENADOS***/
DELIMITER //
DROP PROCEDURE IF EXISTS sp_registrar_usuario //
CREATE PROCEDURE sp_registrar_usuario(
    IN p_tipo_usuario VARCHAR(10),
    IN p_usuario VARCHAR(50),
    IN p_nombres VARCHAR(100),
    IN p_apellidos VARCHAR(100),
    IN p_correo VARCHAR(100),
    IN p_password VARCHAR(256),
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500)
)
BEGIN
    -- Declaración de variables locales
    DECLARE v_usuario_existente INT DEFAULT 0;
    DECLARE v_correo_existente INT DEFAULT 0;
    DECLARE v_id_tipo INT;

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

    bloque_principal: BEGIN

		-- Inicialización de variables de salida
		SET p_codigo_retorno = 0;
		SET p_mensaje_retorno = 'Usuario registrado correctamente';

		-- Validaciones de parámetros de entrada
		IF p_usuario IS NULL OR TRIM(p_usuario) = '' THEN
			SET p_codigo_retorno = -1;
			SET p_mensaje_retorno = 'El usuario es requerido';
            LEAVE bloque_principal;
		END IF;

		IF p_correo IS NULL OR TRIM(p_correo) = '' THEN
			SET p_codigo_retorno = -1;
			SET p_mensaje_retorno = 'El correo es requerido';
            LEAVE bloque_principal;
		END IF;

		IF p_password IS NULL OR TRIM(p_password) = '' THEN
			SET p_codigo_retorno = -1;
			SET p_mensaje_retorno = 'La contraseña es requerida';
            LEAVE bloque_principal;
		END IF;

		-- Obtener ID del tipo de usuario (con valor por defecto 'E' si no se encuentra)
		SELECT id_tipo INTO v_id_tipo
		FROM tipo_usuario
		WHERE codigo = IFNULL(p_tipo_usuario, 'E');

		-- Si no se encuentra el tipo de usuario, usar el tipo 'E'
		IF v_id_tipo IS NULL THEN
			SELECT id_tipo INTO v_id_tipo
			FROM tipo_usuario
			WHERE codigo = 'E';
		END IF;

		-- Verificar existencia de correo
		SELECT COUNT(*) INTO v_correo_existente
		FROM jugadores
		WHERE correo = p_correo;

		-- Verificar existencia de usuario
		SELECT COUNT(*) INTO v_usuario_existente
		FROM jugadores
		WHERE usuario = p_usuario;

		-- Lógica de negocio
		IF v_correo_existente > 0 THEN
			SET p_codigo_retorno = 2;
			SET p_mensaje_retorno = 'El correo ya está registrado';
		ELSEIF v_usuario_existente > 0 THEN
			SET p_codigo_retorno = 3;
			SET p_mensaje_retorno = 'El nombre de usuario ya está en uso';
		ELSE
			-- Insertar nuevo usuario
			INSERT INTO jugadores (nombres, apellidos, usuario, correo, password, id_tipo) 
			VALUES (p_nombres, p_apellidos, p_usuario, p_correo, p_password, v_id_tipo);
			
			SET p_codigo_retorno = 1;
			SET p_mensaje_retorno = 'Usuario registrado exitosamente';
		END IF;
	END bloque_principal; 
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_unirse_partida //
CREATE PROCEDURE sp_unirse_partida(
    IN p_codigo_partida VARCHAR(10),
    IN p_id_jugador INT,
    OUT p_resultado INT,
    OUT p_mensaje VARCHAR(255),
	OUT p_tipo VARCHAR(50)
)
BEGIN
    DECLARE v_id_partida INT;
    DECLARE v_existe_participacion INT;
    
    -- Inicializar variables de salida
    SET p_resultado = 0;
    SET p_mensaje = '';
    
    -- Obtener id_partida basado en el código
    SELECT p.id_partida, m.codigo INTO v_id_partida, p_tipo
    FROM partidas p
    JOIN modalidades m ON p.id_modalidad = m.id_modalidad
    WHERE p.codigo_partida = p_codigo_partida 
    AND p.estado = 'activa';
    
    -- Verificar si la partida existe y está activa
    IF v_id_partida IS NULL THEN
        SET p_resultado = -1;
        SET p_mensaje = 'La partida no existe o no está activa';
    ELSE
        -- Verificar si el jugador ya está en la partida
        SELECT COUNT(*) INTO v_existe_participacion
        FROM partidas_jugadores
        WHERE id_partida = v_id_partida AND id_jugador = p_id_jugador;
        
        IF v_existe_participacion > 0 THEN
            SET p_resultado = -2;
            SET p_mensaje = 'El jugador ya está registrado en esta partida';
        ELSE
            -- Registrar la participación
            INSERT INTO partidas_jugadores (id_partida, id_jugador)
            VALUES (v_id_partida, p_id_jugador);
            
            SET p_resultado = 1;
            SET p_mensaje = 'Registro exitoso';
        END IF;
    END IF;
END //
DELIMITER ; 

DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_game_requirements //
CREATE PROCEDURE sp_get_game_requirements(
    IN p_codigo_partida VARCHAR(10),
    IN p_id_jugador INT,
    OUT p_estado INT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE v_id_partida INT;
    DECLARE v_ultimo_intento INT;
    DECLARE v_esta_unido INT;
    
    -- Obtener id_partida
    SELECT id_partida INTO v_id_partida 
    FROM partidas 
    WHERE codigo_partida = p_codigo_partida 
    AND estado = 'activa';
    
    IF v_id_partida IS NULL THEN
        SET p_estado = -1;
        SET p_mensaje = 'Partida no encontrada o no activa';
    ELSE
        -- Verificar si el jugador está unido
        SELECT COUNT(*) INTO v_esta_unido
        FROM partidas_jugadores
        WHERE id_partida = v_id_partida AND id_jugador = p_id_jugador;
        
        IF v_esta_unido = 0 THEN
            CALL sp_unirse_partida(p_codigo_partida, p_id_jugador, @result, @msg, @typegame);
            IF @result != 1 THEN
                SET p_estado = -1;
                SET p_mensaje = @msg;
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = @msg;
            END IF;
        END IF;
        
        -- Buscar el último intento del jugador
        SELECT MAX(id_intento) INTO v_ultimo_intento
        FROM intentos
        WHERE id_partida = v_id_partida AND id_jugador = p_id_jugador;
        
        IF v_ultimo_intento IS NULL THEN
            -- Primer intento: devolver todos los requisitos
            SET p_estado = 1;
            SET p_mensaje = 'Nuevo juego';
            
            SELECT 
                rcp.id_requisito_partida,
                r.id_requisito, 
                r.descripcion, 
                r.retroalimentacion, 
                r.es_funcional, 
                'nuevo' as tipo_carga
            FROM requisitos r
            JOIN requisitos_clasificacion_partida rcp ON r.id_requisito = rcp.id_requisito
            WHERE rcp.id_partida = v_id_partida;
        ELSE
            -- Juego en progreso: devolver solo requisitos incorrectos del último intento
            SET p_estado = 2;
            SET p_mensaje = 'Juego en progreso';
            
            SELECT DISTINCT 
                rcp.id_requisito_partida,
                r.id_requisito, 
                r.descripcion, 
                r.retroalimentacion, 
                r.es_funcional, 
                'continuacion' as tipo_carga,
                i.numero_intento,
                i.precision_progresiva,
                iri.cantidad_movimientos
            FROM requisitos r
            JOIN requisitos_clasificacion_partida rcp ON r.id_requisito = rcp.id_requisito
            INNER JOIN intento_requisitos_incorrectos iri ON r.id_requisito = iri.id_requisito
            INNER JOIN intentos i ON iri.id_intento = i.id_intento
            WHERE iri.id_intento = v_ultimo_intento 
            AND iri.es_correcto = 0 
            AND rcp.id_partida = v_id_partida;
        END IF;
    END IF;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS registrar_intento //
CREATE PROCEDURE registrar_intento(
    IN p_id_partida INT,
    IN p_id_jugador INT,
    IN p_numero_intento INT,
    IN p_tiempo_intento INT,
    IN p_cantidad_movimientos INT,
    IN p_total_requisitos INT,
    IN p_requisitos_correctos INT,
    IN p_requisitos_incorrectos INT,
    IN p_requisitos_incorrectos_data TEXT, -- Cambiado de JSON a TEXT
    OUT po_total_requisitos INT,
    OUT po_general_precision DECIMAL(5,2),
    OUT po_aciertos_precision DECIMAL(5,2),
    OUT po_errores_precision DECIMAL(5,2),
    OUT po_progressiveAccuracy DECIMAL(5,2)
)
BEGIN
    -- Declaramos las variables antes de cualquier operación
    DECLARE total_correct INT DEFAULT 0;
    DECLARE total_requisitos_partida INT DEFAULT 0;
	
    -- DATA ADICIONAL DE SEGUMIENTO INDIVIDUAL 
	DECLARE p_id_intento INT;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION   
    
    BEGIN
		-- Limpiar tabla temporal si existe
        DROP TEMPORARY TABLE IF EXISTS temp_requisitos;
        ROLLBACK;
		SET po_total_requisitos = 0;
        SET po_general_precision = 0;
        SET po_aciertos_precision = 0;
        SET po_errores_precision = 0;
        SET po_progressiveAccuracy = 0;
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Error en el procedimiento registrar_intento. Operación revertida.';
    END;

	-- Crear tabla temporal para los datos JSON
    CREATE TEMPORARY TABLE IF NOT EXISTS temp_requisitos (
        id_requisito INT,
        movimientos_ambiguo INT,
        movimientos_no_ambiguo INT,
		es_correcto BOOLEAN
    );
    -- Iniciamos una transacción
    START TRANSACTION;
		
	-- Limpiar tabla temporal
    DELETE FROM temp_requisitos;
    
    -- Insertar datos en la tabla temporal usando SUBSTRING_INDEX
        SET @sql = CONCAT('
        INSERT INTO temp_requisitos (id_requisito, movimientos_ambiguo, movimientos_no_ambiguo, es_correcto)
        SELECT 
            SUBSTRING_INDEX(SUBSTRING_INDEX(data, ",", 1), ",", -1) as id_requisito,
            SUBSTRING_INDEX(SUBSTRING_INDEX(data, ",", 2), ",", -1) as movimientos_ambiguo,
            SUBSTRING_INDEX(SUBSTRING_INDEX(data, ",", 3), ",", -1) as movimientos_no_ambiguo,
            CASE 
                WHEN SUBSTRING_INDEX(data, ",", -1) = "true" THEN 1
                ELSE 0
            END as es_correcto
        FROM (
            SELECT SUBSTRING_INDEX(SUBSTRING_INDEX("', p_requisitos_incorrectos_data, '", "|", numbers.n), "|", -1) data
            FROM (
                SELECT 1 + units.i + tens.i * 10 n
                FROM (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) units,
                     (SELECT 0 i UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) tens
                WHERE 1 + units.i + tens.i * 10 <= 1 + LENGTH("', p_requisitos_incorrectos_data, '") - LENGTH(REPLACE("', p_requisitos_incorrectos_data, '", "|", ""))
            ) numbers
        ) split_data
        WHERE data != ""'
    );

    
    PREPARE stmt FROM @sql;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;

    
    -- Validaciones para evitar división por cero
    IF p_total_requisitos = 0 THEN
		SET po_total_requisitos = 0;
        SET po_general_precision = 0;
    ELSE
		SET po_total_requisitos = p_total_requisitos;
        SET po_general_precision = (p_requisitos_correctos / p_total_requisitos) * 100;
    END IF;
    
    IF p_cantidad_movimientos = 0 THEN
        SET po_aciertos_precision = 0;
        SET po_errores_precision = 0;
    ELSE
        SET po_aciertos_precision = (p_requisitos_correctos / p_cantidad_movimientos) * 100;
        SET po_errores_precision = (p_requisitos_incorrectos / p_cantidad_movimientos) * 100;
    END IF;

    -- Insertar el nuevo intento
    INSERT INTO intentos (
        id_partida, id_jugador, numero_intento, tiempo_intento, cantidad_movimientos,
        total_requisitos, requisitos_correctos, requisitos_incorrectos,
        precision_general, precision_aciertos, precision_errores, precision_progresiva
    )
    VALUES (
        p_id_partida, p_id_jugador, p_numero_intento, p_tiempo_intento, p_cantidad_movimientos,
        p_total_requisitos, p_requisitos_correctos, p_requisitos_incorrectos,
        po_general_precision, po_aciertos_precision, po_errores_precision, 0
    );
	
    -- Obtener el ID del intento recién insertado
    SET p_id_intento = LAST_INSERT_ID();

    -- Insertar los requisitos incorrectos
     INSERT INTO intento_requisitos_incorrectos (
        id_intento, 
        id_requisito, 
        cantidad_movimientos,
        movimientos_toAmbiguo,
        movimientos_toNoAmbiguo,
        es_correcto
    )
    SELECT 
        p_id_intento,
        CAST(id_requisito AS UNSIGNED),
        CAST(movimientos_ambiguo AS UNSIGNED) + CAST(movimientos_no_ambiguo AS UNSIGNED),
        CAST(movimientos_ambiguo AS UNSIGNED),
        CAST(movimientos_no_ambiguo AS UNSIGNED),
        es_correcto
    FROM temp_requisitos;
	
	-- Obtener el total de requisitos de la partida
    SELECT COUNT(DISTINCT rcp.id_requisito_partida) INTO total_requisitos_partida
    FROM requisitos_clasificacion_partida rcp
    WHERE rcp.id_partida = p_id_partida;


    -- Calcular el total de requisitos correctos acumulados
    SELECT IFNULL(SUM(requisitos_correctos), 0)
    INTO total_correct
    FROM intentos
    WHERE id_partida = p_id_partida 
    AND id_jugador = p_id_jugador;
    
    -- Calcular progressiveAccuracy
    IF total_requisitos_partida = 0 THEN
        SET po_progressiveAccuracy = 0;
    ELSE
        SET po_progressiveAccuracy = (total_correct / total_requisitos_partida) * 100;
    END IF;
    
    -- Actualizar precision_progresiva
    UPDATE intentos
    SET precision_progresiva = po_progressiveAccuracy
    WHERE id_partida = p_id_partida 
    AND id_jugador = p_id_jugador 
    AND numero_intento = p_numero_intento;
	
    IF(po_progressiveAccuracy = 100) THEN 
		update partidas_jugadores SET estado = 'completado'
		WHERE id_partida = p_id_partida AND id_jugador = p_id_jugador; 
    END IF;
    -- Confirmamos la transacción
    COMMIT;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_construction_game //
CREATE PROCEDURE sp_get_construction_game(
    IN p_codigo_partida VARCHAR(10),
    IN p_id_jugador INT,
    OUT p_estado INT,
    OUT p_mensaje VARCHAR(255),
    OUT p_requisitos_completados INT,
    OUT p_total_requisitos INT,
    OUT p_total_intentos INT
)
BEGIN
    DECLARE v_id_partida INT;
	DECLARE v_esta_unido INT;
    DECLARE v_id_modalidad INT;
    DECLARE v_ultimo_intento INT;
    DECLARE v_ultimo_requisito INT;
    DECLARE v_ultimo_requisito_partida INT;
    DECLARE v_requisito_completado BOOLEAN;
    DECLARE v_siguiente_requisito_partida INT;
    
    -- Obtener id_partida y modalidad
    SELECT p.id_partida, p.id_modalidad 
    INTO v_id_partida, v_id_modalidad
    FROM partidas p 
    WHERE p.codigo_partida = p_codigo_partida 
    AND p.estado = 'activa';
    
    IF v_id_partida IS NULL THEN
        SET p_estado = -1;
        SET p_mensaje = 'Partida no encontrada o no activa';
        SET p_requisitos_completados = 0;
        SET p_total_requisitos = 0;
        SET p_total_intentos = 0;
    ELSE
    
		-- Verificar si el jugador está unido
        SELECT COUNT(*) INTO v_esta_unido
        FROM partidas_jugadores
        WHERE id_partida = v_id_partida AND id_jugador = p_id_jugador;
        
        IF v_esta_unido = 0 THEN
            CALL sp_unirse_partida(p_codigo_partida, p_id_jugador, @result, @msg, @typegame);
            IF @result != 1 THEN
                SET p_estado = -1;
                SET p_mensaje = @msg;
                SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = @msg;
            END IF;
        END IF;
        -- Obtener total de requisitos de la partida
        SELECT COUNT(DISTINCT id_requisito_partida) INTO p_total_requisitos
        FROM requisitos_construccion_partida
        WHERE id_partida = v_id_partida;

        -- Obtener total de intentos
        SELECT (COUNT(*)+1) INTO p_total_intentos
        FROM intentos_construccion
        WHERE id_partida = v_id_partida 
        AND id_jugador = p_id_jugador;
        
        -- Obtener el último intento del jugador y el id_requisito_partida correspondiente
        SELECT 
            ic.id_intento, 
            ic.id_requisito,
            rcp.id_requisito_partida,
            (ic.fragmentos_correctos = (
                SELECT COUNT(*) 
                FROM fragmentos_requisito fr 
                WHERE fr.id_requisito = rcp.id_requisito 
                AND fr.es_señuelo = FALSE
            )) as requisito_completado
        INTO v_ultimo_intento, v_ultimo_requisito, v_ultimo_requisito_partida, v_requisito_completado
        FROM intentos_construccion ic
        JOIN requisitos_construccion_partida rcp ON 
            ic.id_partida = rcp.id_partida 
            AND ic.id_requisito = rcp.id_requisito
            AND rcp.id_requisito_partida = (
                SELECT rcp2.id_requisito_partida
                FROM requisitos_construccion_partida rcp2
                JOIN intentos_construccion ic2 ON 
                    ic2.id_partida = rcp2.id_partida 
                    AND ic2.id_requisito = rcp2.id_requisito
                WHERE ic2.id_partida = v_id_partida 
                AND ic2.id_jugador = p_id_jugador
                ORDER BY ic2.id_intento DESC
                LIMIT 1
            )
        WHERE ic.id_partida = v_id_partida 
        AND ic.id_jugador = p_id_jugador
        ORDER BY ic.id_intento DESC
        LIMIT 1;

        IF v_ultimo_intento IS NULL OR v_requisito_completado THEN
            -- Buscar siguiente requisito disponible usando id_requisito_partida
            SELECT id_requisito_partida, id_requisito 
            INTO v_siguiente_requisito_partida, v_ultimo_requisito
            FROM requisitos_construccion_partida rcp
            WHERE rcp.id_partida = v_id_partida
            AND rcp.id_requisito_partida > COALESCE(v_ultimo_requisito_partida, 0)
            AND NOT EXISTS (
                SELECT 1 
                FROM intentos_construccion ic
                WHERE ic.id_partida = rcp.id_partida
                AND ic.id_requisito = rcp.id_requisito
                AND ic.id_jugador = p_id_jugador
                AND ic.fragmentos_correctos = (
                    SELECT COUNT(*) 
                    FROM fragmentos_requisito fr 
                    WHERE fr.id_requisito = rcp.id_requisito 
                    AND fr.es_señuelo = FALSE
                )
                AND EXISTS (
                    SELECT 1 
                    FROM requisitos_construccion_partida rcp2 
                    WHERE rcp2.id_partida = ic.id_partida
                    AND rcp2.id_requisito = ic.id_requisito
                    AND rcp2.id_requisito_partida = rcp.id_requisito_partida
                )
            )
            ORDER BY rcp.id_requisito_partida
            LIMIT 1;
            
            IF v_siguiente_requisito_partida IS NULL THEN
                SET p_estado = 0;
                SET p_mensaje = 'Todos los requisitos han sido completados';
                
                update partidas_jugadores
				SET estado = 'completado'
                WHERE id_partida = v_id_partida 
				AND id_jugador = p_id_jugador; 
                
            ELSE
                SET p_estado = 1;
                SET p_mensaje = 'Nuevo requisito';
            END IF;
        ELSE
            SET p_estado = 2;
            SET p_mensaje = 'Requisito en progreso';
        END IF;

        -- Calcular el número del requisito actual basado en id_requisito_partida
        IF v_ultimo_intento IS NULL THEN
            -- Si no hay intentos, estamos en el primer requisito
            SET p_requisitos_completados = 1;
        ELSE
            -- Obtener la posición del requisito actual en la secuencia
            SELECT posicion INTO p_requisitos_completados
            FROM (
                SELECT id_requisito_partida, 
                       ROW_NUMBER() OVER (ORDER BY id_requisito_partida) as posicion
                FROM requisitos_construccion_partida
                WHERE id_partida = v_id_partida
            ) as req_positions
            WHERE id_requisito_partida = CASE 
                WHEN v_requisito_completado AND v_siguiente_requisito_partida IS NOT NULL THEN v_siguiente_requisito_partida
                ELSE v_ultimo_requisito_partida
            END;
        END IF;

        -- Solo devolver datos si hay requisito para mostrar
        IF p_estado IN (1, 2) THEN
            -- Devolver datos del requisito
            SELECT 
                rcp.id_requisito_partida,
                rc.id_requisito,
                rc.requisito_completo,
                IF(v_ultimo_intento IS NULL, 'nuevo', 'continuacion') as tipo_carga
            FROM requisitos_construccion rc
            JOIN requisitos_construccion_partida rcp ON rc.id_requisito = rcp.id_requisito
            WHERE rcp.id_partida = v_id_partida
            AND IF(p_estado = 1, 
                  rcp.id_requisito_partida = v_siguiente_requisito_partida,
                  rcp.id_requisito_partida = v_ultimo_requisito_partida);

            -- Devolver fragmentos
            SELECT 
                fr.id_fragmento,
                fr.texto
            FROM fragmentos_requisito fr
            WHERE fr.id_requisito = v_ultimo_requisito;
        END IF;
    END IF;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_validate_construction //
CREATE PROCEDURE sp_validate_construction(
    IN p_id_requisito_partida INT,
    IN p_id_jugador INT,
    IN p_tiempo_intento INT,
    IN p_movimientos TEXT,
    OUT p_resultado INT,
    OUT p_mensaje VARCHAR(255)
)
BEGIN
    DECLARE v_id_intento INT;
    DECLARE v_id_partida INT;
    DECLARE v_id_requisito INT;
    DECLARE v_intentos_previos INT;
    DECLARE v_fragmentos_correctos INT DEFAULT 0;
    DECLARE v_total_fragmentos INT;
    DECLARE v_precision DECIMAL(5,2);
    DECLARE v_señuelos_usados INT DEFAULT 0;
    
    DECLARE v_start INT;
    DECLARE v_end INT;
    DECLARE v_movimiento VARCHAR(100);
    DECLARE v_fragmento_id INT;
    DECLARE v_posicion INT;
    DECLARE v_cantidad_movimientos INT;
    DECLARE v_tiempo_colocacion INT;
    DECLARE v_es_señuelo BOOLEAN;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            @sqlstate = RETURNED_SQLSTATE,
            @errno = MYSQL_ERRNO,
            @text = MESSAGE_TEXT;
        SET p_resultado = -1;
        SET p_mensaje = CONCAT('Error en la validación: ', @text, ' (', @errno, ')');
        ROLLBACK;
    END;

    START TRANSACTION;
    
    -- Obtener id_partida e id_requisito desde requisitos_construccion_partida
    SELECT id_partida, id_requisito 
    INTO v_id_partida, v_id_requisito
    FROM requisitos_construccion_partida
    WHERE id_requisito_partida = p_id_requisito_partida;
    
    IF v_id_partida IS NULL THEN
        SET p_resultado = -1;
        SET p_mensaje = 'El requisito no está asociado a ninguna partida';
        ROLLBACK;
    ELSE
        SELECT COUNT(*) INTO v_intentos_previos
        FROM intentos_construccion
        WHERE id_requisito = v_id_requisito
        AND id_jugador = p_id_jugador
        AND id_partida = v_id_partida;
        
        INSERT INTO intentos_construccion (
            id_partida,
            id_jugador,
            id_requisito,
            numero_intento,
            tiempo_intento,
            fragmentos_correctos,
            fragmentos_incorrectos,
            señuelos_usados,
            precision_construccion
        ) VALUES (
            v_id_partida,
            p_id_jugador,
            v_id_requisito,
            v_intentos_previos + 1,
            p_tiempo_intento,
            0,
            0,
            0,
            0
        );
        
        SET v_id_intento = LAST_INSERT_ID();

        -- Crear tabla temporal para almacenar las posiciones finales
        CREATE TEMPORARY TABLE IF NOT EXISTS temp_posiciones_finales (
            id_fragmento INT PRIMARY KEY,
            posicion_final INT,
            cantidad_movimientos INT,
            tiempo_colocacion INT,
            es_correcto BOOLEAN DEFAULT FALSE
        );

        SET v_start = 1;
        WHILE v_start <= LENGTH(p_movimientos) DO
            SET v_end = LOCATE(';', p_movimientos, v_start);
            IF v_end = 0 THEN
                SET v_end = LENGTH(p_movimientos) + 1;
            END IF;
            
            SET v_movimiento = SUBSTRING(p_movimientos, v_start, v_end - v_start);
            
            SET v_fragmento_id = SUBSTRING_INDEX(v_movimiento, ',', 1);
            SET v_posicion = SUBSTRING_INDEX(SUBSTRING_INDEX(v_movimiento, ',', 2), ',', -1);
            SET v_cantidad_movimientos = SUBSTRING_INDEX(SUBSTRING_INDEX(v_movimiento, ',', 3), ',', -1);
            SET v_tiempo_colocacion = SUBSTRING_INDEX(v_movimiento, ',', -1);
			
             -- Verificar si la posición es correcta para el fragmento
            SELECT posicion_correcta INTO @posicion_correcta
            FROM fragmentos_requisito
            WHERE id_fragmento = v_fragmento_id;
            
            -- Insertar o actualizar en la tabla temporal
            INSERT INTO temp_posiciones_finales 
                (id_fragmento, posicion_final, cantidad_movimientos, tiempo_colocacion, es_correcto)
            VALUES 
                (v_fragmento_id, v_posicion, v_cantidad_movimientos, v_tiempo_colocacion,
                IF(@posicion_correcta = v_posicion, TRUE, FALSE))
            ON DUPLICATE KEY UPDATE 
                posicion_final = v_posicion,
                cantidad_movimientos = cantidad_movimientos + v_cantidad_movimientos,
                tiempo_colocacion = v_tiempo_colocacion,
                es_correcto = IF(@posicion_correcta = v_posicion, TRUE, FALSE);

            SET v_start = v_end + 1;
        END WHILE;

        -- Insertar en detalle_construccion_intento usando las posiciones finales
        INSERT INTO detalle_construccion_intento 
            (id_intento, id_fragmento, posicion_usada, cantidad_movimientos, tiempo_colocacion, es_correcto)
        SELECT 
            v_id_intento,
            id_fragmento,
            posicion_final,
            cantidad_movimientos,
            tiempo_colocacion,
            es_correcto
        FROM temp_posiciones_finales;

        -- Calcular señuelos usados
        SELECT COUNT(*) INTO v_señuelos_usados
        FROM temp_posiciones_finales tpf
        JOIN fragmentos_requisito fr ON tpf.id_fragmento = fr.id_fragmento
        WHERE fr.es_señuelo = TRUE;

        -- Calcular fragmentos correctos usando posiciones finales
        SELECT COUNT(*) INTO v_total_fragmentos
        FROM fragmentos_requisito
        WHERE id_requisito = v_id_requisito
        AND es_señuelo = FALSE;

        SELECT COUNT(*) INTO v_fragmentos_correctos
        FROM temp_posiciones_finales tpf
        JOIN fragmentos_requisito fr ON tpf.id_fragmento = fr.id_fragmento
        WHERE fr.id_requisito = v_id_requisito
        AND tpf.posicion_final = fr.posicion_correcta
        AND fr.es_señuelo = FALSE;

        SET v_precision = (v_fragmentos_correctos / v_total_fragmentos) * 100;

        UPDATE intentos_construccion
        SET 
            fragmentos_correctos = v_fragmentos_correctos,
            fragmentos_incorrectos = v_total_fragmentos - v_fragmentos_correctos,
            señuelos_usados = v_señuelos_usados,
            precision_construccion = v_precision
        WHERE id_intento = v_id_intento;

        -- Devolver resultado de la validación
        SELECT 
            v_id_intento as id_intento,
            v_fragmentos_correctos as fragmentos_correctos,
            v_total_fragmentos as total_fragmentos,
            v_precision as 'precision',
            v_señuelos_usados as señuelos_usados,
            fr.id_fragmento,
            fr.posicion_correcta
        FROM fragmentos_requisito fr
        WHERE fr.id_requisito = v_id_requisito
        AND fr.es_señuelo = FALSE
        ORDER BY fr.posicion_correcta;

        -- Limpiar tabla temporal
        DROP TEMPORARY TABLE IF EXISTS temp_posiciones_finales;

        SET p_resultado = 1;
        SET p_mensaje = 'Validación completada';
        
        COMMIT;
    END IF;
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS sp_estadisticas_jugadores_partida_clasificacion //
CREATE PROCEDURE sp_estadisticas_jugadores_partida_clasificacion(
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
            COUNT(*) as intentos,
            SUM(i.tiempo_intento) as tiempo_total_jugador,
            SUM(i.cantidad_movimientos) as movimientos_totales_jugador,
            SUM(i.requisitos_correctos) as requisitos_clasificados,
            t.requisitos_partida as total_requisitos_partida,
            CASE 
                WHEN SUM(i.requisitos_correctos) >= t.requisitos_partida THEN '1'
                ELSE '0'
            END as estado,
            CASE 
                WHEN SUM(i.requisitos_correctos) >= t.requisitos_partida THEN 'Finalizado'
                ELSE 'En Progreso'
            END as estado_texto,
            MAX(i.precision_progresiva) as porcentaje_avance_alt,
            MAX(i.fecha_intento) as ultimo_intento
        FROM intentos i
        CROSS JOIN total_requisitos_partida t
		INNER JOIN jugadores jug ON i.id_jugador = jug.id_jugador
        WHERE i.id_partida = v_id_partida
        GROUP BY i.id_partida, i.id_jugador, t.requisitos_partida
        ORDER BY estado DESC, porcentaje_avance_alt DESC;
    -- END;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_estadisticas_generales_partida //
CREATE PROCEDURE sp_estadisticas_generales_partida(
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

	IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'El ID de usuario no es válido';
	END IF;

	SELECT COUNT(*), id_partida INTO v_partida_existe, v_id_partida
	   FROM partidas 
	   WHERE codigo_partida = p_codigo_partida 
	   AND id_usuario_creacion = p_id_usuario AND id_modalidad = 1;

   -- Validaciones de parámetros de entrada
   IF v_id_partida IS NULL OR v_id_partida <= 0 THEN
       SET p_codigo_retorno = -1;
       SET p_mensaje_retorno = 'El ID de partida no es válido';
   END IF;

   IF v_partida_existe = 0 THEN
		SET p_codigo_retorno = 2;
		SET p_mensaje_retorno = 'No existen datos para la partida especificada';
	ELSE 
		-- Inicio de la transacción
		START TRANSACTION;

		-- Consulta principal
		WITH stats_por_jugador AS (
			SELECT 
				id_partida,
				id_jugador,
				COUNT(*) as intentos_necesarios,
				SUM(tiempo_intento) as tiempo_total_jugador,
				SUM(cantidad_movimientos) as movimientos_totales_jugador,
				-- Primer intento usando subconsultas
				(SELECT requisitos_correctos 
				 FROM intentos i2 
				 WHERE i2.id_partida = i.id_partida 
				 AND i2.id_jugador = i.id_jugador 
				 AND i2.numero_intento = 1) as aciertos_primer_intento,
				(SELECT total_requisitos 
				 FROM intentos i2 
				 WHERE i2.id_partida = i.id_partida 
				 AND i2.id_jugador = i.id_jugador 
				 AND i2.numero_intento = 1) as total_requisitos_primer_intento,
				(SELECT precision_general 
				 FROM intentos i2 
				 WHERE i2.id_partida = i.id_partida 
				 AND i2.id_jugador = i.id_jugador 
				 AND i2.numero_intento = 1) as precision_primer_intento,
				-- Intentos intermedios (sin usar window functions)
				(SELECT AVG(precision_general)
				 FROM intentos i2
				 WHERE i2.id_partida = i.id_partida 
				 AND i2.id_jugador = i.id_jugador
				 AND i2.numero_intento != 1 
				 AND i2.numero_intento != COUNT(*)) as promedio_precision_intentos_intermedios
			FROM intentos i
			WHERE id_partida = v_id_partida  -- Aquí va el parámetro del id_partida
			GROUP BY id_partida, id_jugador
		)
		SELECT 
			id_partida,
			COUNT(id_jugador) as total_jugadores,
			
			-- Estadísticas de intentos
			ROUND(AVG(intentos_necesarios), 2) as promedio_intentos_necesarios,
			MAX(intentos_necesarios) as max_intentos,
			MIN(intentos_necesarios) as min_intentos,
			
			-- Análisis del primer intento
			ROUND(AVG(aciertos_primer_intento * 100.0 / total_requisitos_primer_intento), 2) 
				as porcentaje_promedio_aciertos_primer_intento,
			ROUND(AVG(precision_primer_intento), 2) as precision_promedio_primer_intento,
			
			-- Tiempo y eficiencia
			ROUND(AVG(tiempo_total_jugador), 2) as tiempo_promedio_total_segundos,
			ROUND(AVG(movimientos_totales_jugador), 2) as promedio_movimientos_total,
			
			-- Categorización por número de intentos
			SUM(CASE WHEN intentos_necesarios = 1 THEN 1 ELSE 0 END) as completaron_primer_intento,
			SUM(CASE WHEN intentos_necesarios BETWEEN 2 AND 3 THEN 1 ELSE 0 END) as completaron_2_3_intentos,
			SUM(CASE WHEN intentos_necesarios > 3 THEN 1 ELSE 0 END) as necesitaron_mas_3_intentos,
			
			-- Promedios por intento
			ROUND(AVG(movimientos_totales_jugador / intentos_necesarios), 2) as promedio_movimientos_por_intento,
			ROUND(AVG(tiempo_total_jugador / intentos_necesarios), 2) as promedio_tiempo_por_intento_segundos,
			
			-- Porcentajes de distribución
			ROUND(SUM(CASE WHEN intentos_necesarios = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) 
				as porcentaje_completaron_primer_intento,
			ROUND(SUM(CASE WHEN intentos_necesarios BETWEEN 2 AND 3 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) 
				as porcentaje_completaron_2_3_intentos,
			ROUND(SUM(CASE WHEN intentos_necesarios > 3 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) 
				as porcentaje_necesitaron_mas_3_intentos
		FROM stats_por_jugador
		GROUP BY id_partida;
	   -- Si llegamos aquí, todo se ejecutó correctamente
	   SET p_codigo_retorno = 1;
	   SET p_mensaje_retorno = 'Estadísticas generadas exitosamente';
	   COMMIT;
       
   END IF;    
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_stats_for_player //
CREATE PROCEDURE sp_stats_for_player(
    -- Parámetros de entrada
	IN p_codigo_partida VARCHAR(10),
	IN p_id_usuario INT,
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

	IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'El ID de usuario no es válido';
	END IF;

	SELECT COUNT(*), id_partida INTO v_partida_existe, v_id_partida
	   FROM partidas 
	   WHERE codigo_partida = p_codigo_partida 
	   AND id_usuario_creacion = p_id_usuario AND id_modalidad = 1;

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
DROP PROCEDURE IF EXISTS sp_details_for_attempt //
CREATE PROCEDURE sp_details_for_attempt(
    -- Parámetros de entrada
	IN p_codigo_partida VARCHAR(10),
	IN p_id_usuario INT,
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

	IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'El ID de usuario no es válido';
		LEAVE bloque_principal;
	END IF;

	SELECT COUNT(*), id_partida INTO v_partida_existe, v_id_partida
	   FROM partidas 
	   WHERE codigo_partida = p_codigo_partida 
	   AND id_usuario_creacion = p_id_usuario AND id_modalidad = 1;

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
DROP PROCEDURE IF EXISTS sp_estadisticas_generales_partida_construccion //
CREATE PROCEDURE sp_estadisticas_generales_partida_construccion(
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

	IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'El ID de usuario no es válido';
		LEAVE bloque_principal;
	END IF;

	SELECT COUNT(*), id_partida INTO v_partida_existe, v_id_partida
	   FROM partidas 
	   WHERE codigo_partida = p_codigo_partida 
	   AND id_usuario_creacion = p_id_usuario AND id_modalidad = 2;

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
		WITH stats_por_jugador AS (
			SELECT 
				ic.id_partida,
				ic.id_jugador,
				COUNT(ic.id_intento) as intentos_necesarios,
				SUM(ic.tiempo_intento) as tiempo_total_jugador,
				-- Primer intento usando subconsultas
				 (SELECT SUM(dci.cantidad_movimientos)
					 FROM detalle_construccion_intento dci
					 WHERE dci.id_intento IN (
						 SELECT id_intento 
						 FROM intentos_construccion 
						 WHERE id_partida = ic.id_partida 
						   AND id_jugador = ic.id_jugador
					 )) AS movimientos_totales_jugador,
					 -- Calcular la precisión promedio con validación de nulos y redondeo
					ROUND(SUM(COALESCE(ic.precision_construccion, 0)) / COUNT(ic.id_intento), 2) AS precision_promedio
			FROM intentos_construccion ic
			LEFT JOIN partidas_jugadores pj ON ic.id_jugador = pj.id_jugador AND pj.id_partida = ic.id_partida
			WHERE ic.id_partida = v_id_partida  -- Aquí va el parámetro del id_partida
            AND pj.estado = 'completado'
			GROUP BY ic.id_partida, ic.id_jugador
		)
		SELECT 
			id_partida,
            (SELECT COUNT(*) 
             FROM partidas_jugadores WHERE id_partida = v_id_partida)  as total_jugadores,
			-- Estadísticas de intentos
			ROUND(AVG(intentos_necesarios), 2) as promedio_intentos_necesarios,
			MAX(intentos_necesarios) as max_intentos,
			MIN(intentos_necesarios) as min_intentos,
			-- Tiempo y eficiencia
			ROUND(AVG(tiempo_total_jugador), 2) as tiempo_promedio_total_segundos,
			ROUND(AVG(movimientos_totales_jugador), 2) as promedio_movimientos_total,
            
			-- Promedios por intento
			ROUND(AVG(movimientos_totales_jugador / intentos_necesarios), 2) as promedio_movimientos_por_intento,
			ROUND(AVG(tiempo_total_jugador / intentos_necesarios), 2) as promedio_tiempo_por_intento_segundos,
			ROUND(AVG(precision_promedio), 2) as precision_promedio
		FROM stats_por_jugador
		GROUP BY id_partida;
    
    SET p_codigo_retorno = 1;
	SET p_mensaje_retorno = 'Resultados de consulta generadas exitosamente';
    
	END bloque_principal; 
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS sp_estadisticas_jugadores_partida_construccion //
CREATE PROCEDURE sp_estadisticas_jugadores_partida_construccion(
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
	
    bloque_principal: BEGIN
    -- Inicialización de variables de salida
    SET p_codigo_retorno = 0;
    SET p_mensaje_retorno = 'Proceso ejecutado correctamente';

	-- Validaciones de parámetros de entrada
	IF p_codigo_partida IS NULL OR p_codigo_partida = '' THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'El código de partida no es válido';
		LEAVE bloque_principal;
	END IF;

	IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'El ID de usuario no es válido';
		LEAVE bloque_principal;
	END IF;

	SELECT COUNT(*), id_partida INTO v_partida_existe, v_id_partida
	   FROM partidas 
	   WHERE codigo_partida = p_codigo_partida 
	   AND id_usuario_creacion = p_id_usuario AND id_modalidad = 2;


    -- Validar que la partida existe
    IF v_partida_existe = 0 THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'No existen datos para la partida especificada';
		LEAVE bloque_principal;
    END IF;

	WITH requisitos_construidos_jugadores AS (
		SELECT 
			ic.id_partida,
			ic.id_jugador,
			COUNT(*) AS requisitos_construidos
		FROM intentos_construccion ic
		WHERE ic.precision_construccion = 100
		AND ic.id_partida = v_id_partida
		GROUP BY ic.id_partida, ic.id_jugador
	),
	total_requisitos_partida AS (
		SELECT 
			id_partida,
			COUNT(*) AS total_requisitos_partida
		FROM requisitos_construccion_partida 
		where id_partida = v_id_partida
		GROUP BY id_partida
	)
	SELECT 
		ic.id_partida,
		ic.id_jugador,
		jug.nombres,
		jug.apellidos,
		COUNT(*) AS intentos,
		SUM(ic.tiempo_intento) AS tiempo_total_jugador,
		(SELECT SUM(dci.cantidad_movimientos)
		 FROM detalle_construccion_intento dci
		 WHERE dci.id_intento IN (
			 SELECT id_intento 
			 FROM intentos_construccion 
			 WHERE id_partida = ic.id_partida 
			   AND id_jugador = ic.id_jugador
		 )) AS movimientos_totales_jugador,
		COALESCE(rcj.requisitos_construidos, 0) AS requisitos_construidos,
		COALESCE(trp.total_requisitos_partida, 0) AS total_requisitos_partida,
		CASE 
			WHEN COALESCE(rcj.requisitos_construidos, 0) >= COALESCE(trp.total_requisitos_partida, 0) THEN '1'
			ELSE '0'
		END AS estado,
		CASE 
			WHEN COALESCE(rcj.requisitos_construidos, 0) >= COALESCE(trp.total_requisitos_partida, 0) THEN 'Finalizado'
			ELSE 'En Progreso'
		END AS estado_texto,
		CASE 
			WHEN COALESCE(trp.total_requisitos_partida, 0) > 0 THEN 
				ROUND(COALESCE(rcj.requisitos_construidos, 0) * 100.0 / COALESCE(trp.total_requisitos_partida, 0), 2)
			ELSE 0
		END AS porcentaje_avance_alt,
		MAX(ic.fecha_intento) AS ultimo_intento
	FROM intentos_construccion ic
	CROSS JOIN total_requisitos_partida trp
	LEFT JOIN requisitos_construidos_jugadores rcj 
		ON ic.id_partida = rcj.id_partida AND ic.id_jugador = rcj.id_jugador
	INNER JOIN jugadores jug 
		ON ic.id_jugador = jug.id_jugador
	WHERE ic.id_partida = v_id_partida
	GROUP BY ic.id_partida, ic.id_jugador, jug.nombres, jug.apellidos, rcj.requisitos_construidos, trp.total_requisitos_partida
	ORDER BY estado DESC, porcentaje_avance_alt DESC;
    

	SET p_codigo_retorno = 1;
	SET p_mensaje_retorno = 'Resultados de consulta generadas exitosamente';
    
	END bloque_principal; 
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_stats_for_player_construction //
CREATE PROCEDURE sp_stats_for_player_construction(
    -- Parámetros de entrada
	IN p_codigo_partida VARCHAR(10),
	IN p_id_usuario INT,
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

		IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
			SET p_codigo_retorno = -1;
			SET p_mensaje_retorno = 'El ID de usuario no es válido';
			LEAVE bloque_principal;
		END IF;

		SELECT COUNT(*), id_partida INTO v_partida_existe, v_id_partida
		   FROM partidas 
		   WHERE codigo_partida = p_codigo_partida 
		   AND id_usuario_creacion = p_id_usuario AND id_modalidad = 2;

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
DROP PROCEDURE IF EXISTS sp_details_for_attempt_construction //
CREATE PROCEDURE sp_details_for_attempt_construction(
    -- Parámetros de entrada
	IN p_codigo_partida VARCHAR(10),
	IN p_id_usuario INT,
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

	IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'El ID de usuario no es válido';
		LEAVE bloque_principal;
	END IF;

	SELECT COUNT(*), id_partida INTO v_partida_existe, v_id_partida
	   FROM partidas 
	   WHERE codigo_partida = p_codigo_partida 
	   AND id_usuario_creacion = p_id_usuario AND id_modalidad = 2;

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
DROP PROCEDURE IF EXISTS sp_get_requirements_clasification_create_level //
CREATE PROCEDURE sp_get_requirements_clasification_create_level(
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
       ROLLBACK;
   END;
	
  bloque_principal: BEGIN
   -- Inicialización de variables de salida
   SET p_codigo_retorno = 0;
   SET p_mensaje_retorno = 'Proceso iniciado correctamente';

	IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'El ID de usuario no es válido';
		LEAVE bloque_principal;
	END IF;

	SELECT COUNT(*) INTO v_usuario_existe
	   FROM jugadores 
	   WHERE id_jugador = p_id_usuario;

	-- Validaciones de parámetros de entrada
	IF v_usuario_existe IS NULL OR v_usuario_existe <= 0 THEN
       SET p_codigo_retorno = -1;
       SET p_mensaje_retorno = 'El usuario no existe';
		LEAVE bloque_principal;
	END IF;

	-- Consulta principal        
       SELECT 
			r.id_requisito as id,
			r.descripcion as description,
			r.es_funcional as is_functional,
			r.es_ambiguo as is_ambiguous,
			r.retroalimentacion as feedback,
			r.id_usuario_creador as created_by
		FROM requisitos r
		where r.id_usuario_creador = p_id_usuario
		ORDER BY 1 DESC;

	SET p_codigo_retorno = 1;
	SET p_mensaje_retorno = 'Resultados de consulta generadas exitosamente';
   END bloque_principal; 
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_create_requirements_clasification //
CREATE PROCEDURE sp_create_requirements_clasification(
    -- Parámetros de entrada
	IN p_id_usuario INT,
	IN p_descripcion VARCHAR(500), 
	IN p_es_ambiguo INT, 
	IN p_retroalimentacion VARCHAR(500),
	IN p_tipo_requisito VARCHAR(50),
   -- Parámetros de salida
	OUT p_codigo_retorno INT,
	OUT p_mensaje_retorno VARCHAR(500), 
	OUT p_id_requisito INT
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
       ROLLBACK;
   END;
	
  bloque_principal: BEGIN
   -- Inicialización de variables de salida
   SET p_codigo_retorno = 0;
   SET p_mensaje_retorno = 'Proceso iniciado correctamente';

	IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
		SET p_codigo_retorno = -1;
		SET p_mensaje_retorno = 'El ID de usuario no es válido';
		LEAVE bloque_principal;
	END IF;

	SELECT COUNT(*) INTO v_usuario_existe
	   FROM jugadores 
	   WHERE id_jugador = p_id_usuario;

	-- Validaciones de parámetros de entrada
	IF v_usuario_existe IS NULL OR v_usuario_existe <= 0 THEN
       SET p_codigo_retorno = -1;
       SET p_mensaje_retorno = 'El usuario no existe';
		LEAVE bloque_principal;
	END IF;
	
	START TRANSACTION;

		INSERT INTO requisitos(
			id_usuario_creador, descripcion, es_ambiguo, retroalimentacion, es_funcional
		)
		VALUES (
			p_id_usuario, p_descripcion, p_es_ambiguo, p_retroalimentacion, p_tipo_requisito
		);
		
		-- Obtener el ID del intento recién insertado
		SET p_id_requisito = LAST_INSERT_ID();
		
        SELECT 
			r.id_requisito as id,
			r.descripcion as description,
			r.es_funcional as is_functional,
			r.es_ambiguo as is_ambiguous,
			r.retroalimentacion as feedback,
			r.id_usuario_creador as created_by
		FROM requisitos r
		where r.id_requisito = p_id_requisito
		ORDER BY 1 DESC;
        
    COMMIT;
	SET p_codigo_retorno = 1;
	SET p_mensaje_retorno = 'Requisito registrado exitosamente';
   END bloque_principal; 
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_update_requirements_clasification //
CREATE PROCEDURE sp_update_requirements_clasification(
    -- Parámetros de entrada
    IN p_id_usuario INT,
    IN p_id_requisito INT,
    IN p_descripcion VARCHAR(500), 
    IN p_es_ambiguo INT, 
    IN p_retroalimentacion VARCHAR(500),
    IN p_tipo_requisito VARCHAR(50),
    -- Parámetros de salida
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500), 
    OUT p_requisito_id INT
)
BEGIN
    -- Declaración de variables locales
    DECLARE v_requisito_existe INT DEFAULT 0;
    DECLARE v_es_creador INT DEFAULT 0;
    
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
        SET p_requisito_id = NULL;
        
        -- Validación de parámetros
        IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
            SET p_codigo_retorno = -1;
            SET p_mensaje_retorno = 'El ID de usuario no es válido';
            LEAVE bloque_principal;
        END IF;
        
        IF p_id_requisito IS NULL OR p_id_requisito <= 0 THEN
            SET p_codigo_retorno = -1;
            SET p_mensaje_retorno = 'El ID del requisito no es válido';
            LEAVE bloque_principal;
        END IF;
        
        -- Verificar que el requisito exista
        SELECT COUNT(*) INTO v_requisito_existe
        FROM requisitos 
        WHERE id_requisito = p_id_requisito;
        
        IF v_requisito_existe = 0 THEN
            SET p_codigo_retorno = -1;
            SET p_mensaje_retorno = 'El requisito no existe';
            LEAVE bloque_principal;
        END IF;
        
        -- Verificar que el usuario sea el creador del requisito
        SELECT COUNT(*) INTO v_es_creador
        FROM requisitos 
        WHERE id_requisito = p_id_requisito AND id_usuario_creador = p_id_usuario;
        
        IF v_es_creador = 0 THEN
            SET p_codigo_retorno = -1;
            SET p_mensaje_retorno = 'No tienes permisos para editar este requisito';
            LEAVE bloque_principal;
        END IF;
        
        START TRANSACTION;
            -- Actualizar el requisito
            UPDATE requisitos
            SET 
                descripcion = p_descripcion,
                es_ambiguo = p_es_ambiguo,
                retroalimentacion = p_retroalimentacion,
                es_funcional = p_tipo_requisito
                -- ,fecha_actualizacion = NOW()
            WHERE id_requisito = p_id_requisito;
            
            SET p_requisito_id = p_id_requisito;
            
            -- Retornar los datos actualizados
            SELECT 
                r.id_requisito as id,
                r.descripcion as description,
                r.es_funcional as is_functional,
                r.es_ambiguo as is_ambiguous,
                r.retroalimentacion as feedback,
                r.id_usuario_creador as created_by
            FROM requisitos r
            WHERE r.id_requisito = p_requisito_id;
            
        COMMIT;
        
        SET p_codigo_retorno = 1;
        SET p_mensaje_retorno = 'Requisito actualizado exitosamente';
    END bloque_principal; 
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_import_requirements_clasification //
CREATE PROCEDURE sp_import_requirements_clasification(
    IN p_id_usuario INT,
    IN p_requisitos_string TEXT,
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500),
    OUT p_total_importados INT
)
BEGIN
    DECLARE v_usuario_existe INT DEFAULT 0;
    DECLARE v_error INT DEFAULT 0;
    DECLARE v_contador INT DEFAULT 0;
    DECLARE v_pos_inicio INT DEFAULT 1;
    DECLARE v_longitud INT DEFAULT 0;
    DECLARE v_requisito TEXT;
    DECLARE v_fin INT DEFAULT 0;
	DECLARE v_codigo_ref VARCHAR(36);
    
    DECLARE v_descripcion VARCHAR(500);
    DECLARE v_es_ambiguo TINYINT;
    DECLARE v_es_funcional TINYINT;
    DECLARE v_retroalimentacion VARCHAR(500);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
		GET DIAGNOSTICS CONDITION 1
				@sqlstate = RETURNED_SQLSTATE,
				@errno = MYSQL_ERRNO,
				@text = MESSAGE_TEXT;
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = CONCAT('Error durante la ejecución.', @text, ' (', @errno, ')');
        SET p_total_importados = 0;
        DROP TEMPORARY TABLE IF EXISTS temp_requisitos;
        ROLLBACK;
    END;

    SET v_codigo_ref = UUID();

    DROP TEMPORARY TABLE IF EXISTS temp_requisitos;
    CREATE TEMPORARY TABLE temp_requisitos (
        descripcion VARCHAR(500),
        es_ambiguo TINYINT,
        es_funcional VARCHAR(50),
        retroalimentacion VARCHAR(500)
    );

    bloque_principal: BEGIN
        SET p_codigo_retorno = 0;
        SET p_mensaje_retorno = 'Proceso iniciado correctamente';
        SET p_total_importados = 0;

        IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
            SET p_codigo_retorno = -1;
            SET p_mensaje_retorno = 'El ID de usuario no es válido';
            LEAVE bloque_principal;
        END IF;

        SELECT COUNT(*) INTO v_usuario_existe
        FROM jugadores 
        WHERE id_jugador = p_id_usuario;

        IF v_usuario_existe IS NULL OR v_usuario_existe <= 0 THEN
            SET p_codigo_retorno = -1;
            SET p_mensaje_retorno = 'El usuario no existe';
            LEAVE bloque_principal;
        END IF;

        IF p_requisitos_string IS NULL OR LENGTH(TRIM(p_requisitos_string)) = 0 THEN
            SET p_codigo_retorno = -1;
            SET p_mensaje_retorno = 'No hay requisitos para importar';
            LEAVE bloque_principal;
        END IF;

        START TRANSACTION;

        loop_requisitos: LOOP
            SET v_longitud = LOCATE('¬', p_requisitos_string, v_pos_inicio);

            IF v_longitud = 0 THEN
                SET v_fin = 1;
                SET v_requisito = SUBSTRING(p_requisitos_string, v_pos_inicio);
            ELSE
                SET v_requisito = SUBSTRING(p_requisitos_string, v_pos_inicio, v_longitud - v_pos_inicio);
                SET v_pos_inicio = v_longitud + 1;
            END IF;

            IF TRIM(v_requisito) = '' THEN
                LEAVE loop_requisitos;
            END IF;

            -- Extraer campos del requisito
            SET v_descripcion = SUBSTRING_INDEX(v_requisito, '|', 1);
			SET v_es_ambiguo = SUBSTRING_INDEX(SUBSTRING_INDEX(v_requisito, '|', 2), '|', -1);
            SET v_es_funcional = CASE 
                WHEN SUBSTRING_INDEX(SUBSTRING_INDEX(v_requisito, '|', 3), '|', -1) = '1' THEN '1'
                ELSE '0'
            END;
            SET v_retroalimentacion = SUBSTRING_INDEX(v_requisito, '|', -1);

            -- Insertar en tabla temporal
            INSERT INTO temp_requisitos (
                descripcion,
                es_ambiguo,
                es_funcional,
                retroalimentacion
            ) VALUES (
                v_descripcion,
                v_es_ambiguo,
                v_es_funcional,
                v_retroalimentacion
            );

            SET v_contador = v_contador + 1;

            IF v_fin = 1 THEN
                LEAVE loop_requisitos;
            END IF;
        END LOOP;

        INSERT INTO requisitos (
            id_usuario_creador,
            descripcion,
            es_ambiguo,
            es_funcional,
            retroalimentacion,
            codigo_lote_referencia
        )
        SELECT 
            p_id_usuario,
            descripcion,
            es_ambiguo,
            es_funcional,
            retroalimentacion,
            v_codigo_ref
        FROM temp_requisitos;

		-- Retornar los IDs de los requisitos insertados usando el código de referencia
        SELECT id_requisito AS id 
        FROM requisitos 
        WHERE codigo_lote_referencia = v_codigo_ref
        ORDER BY id_requisito;

        SET p_total_importados = v_contador;

        DROP TEMPORARY TABLE IF EXISTS temp_requisitos;

        COMMIT;

        SET p_codigo_retorno = 1;
        SET p_mensaje_retorno = CONCAT('Se importaron ', p_total_importados, ' requisitos exitosamente');
    END bloque_principal;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_crear_partida_clasificacion //
CREATE PROCEDURE sp_crear_partida_clasificacion(
    -- Parámetros de entrada
    IN p_id_usuario INT,
    IN p_requisitos TEXT, -- Lista de IDs separados por coma
    -- Parámetros de salida
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500),
    OUT p_codigo_partida VARCHAR(10)
)
BEGIN
    -- Declaración de variables locales
    DECLARE v_id_partida INT;
    DECLARE v_codigo_existe INT;
    DECLARE v_codigo_valido BOOLEAN DEFAULT FALSE;
    DECLARE v_start INT DEFAULT 1;
    DECLARE v_id_requisito VARCHAR(10);
    DECLARE v_end INT;
    
    -- Declarar handler para errores SQL
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            @sqlstate = RETURNED_SQLSTATE,
            @errno = MYSQL_ERRNO,
            @text = MESSAGE_TEXT;
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = CONCAT('Error en la ejecución del procedimiento: ', @text);
        SET p_codigo_partida = NULL;
        ROLLBACK;
    END;

    -- Inicializar variables de salida
    SET p_codigo_retorno = 0;
    SET p_mensaje_retorno = 'Proceso iniciado';
    SET p_codigo_partida = NULL;

    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'ID de usuario inválido';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ID de usuario inválido';
    END IF;

    IF p_requisitos IS NULL OR TRIM(p_requisitos) = '' THEN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'Lista de requisitos vacía';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Lista de requisitos vacía';
    END IF;

    -- Iniciar transacción
    START TRANSACTION;

    -- Generar código único para la partida
    codigo_loop: LOOP
        -- Generar código aleatorio alfanumérico de 6 caracteres
        SET p_codigo_partida = UPPER(
            CONCAT(
                CHAR(FLOOR(65 + (RAND() * 26))), -- Letra
                CHAR(FLOOR(65 + (RAND() * 26))), -- Letra
                FLOOR(RAND() * 10), -- Número
                FLOOR(RAND() * 10), -- Número
                CHAR(FLOOR(65 + (RAND() * 26))), -- Letra
                FLOOR(RAND() * 10) -- Número
            )
        );

        -- Verificar si el código ya existe
        SELECT COUNT(*) INTO v_codigo_existe
        FROM partidas
        WHERE codigo_partida = p_codigo_partida;

        IF v_codigo_existe = 0 THEN
            SET v_codigo_valido = TRUE;
            LEAVE codigo_loop;
        END IF;
    END LOOP;

    -- Insertar en la tabla partidas
    INSERT INTO partidas (
        id_modalidad,
        id_usuario_creacion,
        codigo_partida,
        estado,
        tiempo_limite
    ) VALUES (
        1, -- Modalidad clasificación
        p_id_usuario,
        p_codigo_partida,
        'activa',
        600 -- 10 minutos por defecto
    );

    -- Obtener el ID de la partida creada
    SET v_id_partida = LAST_INSERT_ID();

    -- Insertar los requisitos en requisitos_clasificacion_partida
    -- Procesar la lista de requisitos
    requisitos_loop: LOOP
        -- Encontrar la siguiente coma
        SET v_end = LOCATE(',', p_requisitos, v_start);
        
        IF v_end = 0 THEN
            -- Último elemento
            SET v_id_requisito = SUBSTRING(p_requisitos FROM v_start);
            
            IF LENGTH(TRIM(v_id_requisito)) > 0 THEN
                INSERT INTO requisitos_clasificacion_partida (
                    id_requisito,
                    id_partida
                ) VALUES (
                    v_id_requisito,
                    v_id_partida
                );
            END IF;
            
            LEAVE requisitos_loop;
        END IF;

        -- Extraer el ID del requisito
        SET v_id_requisito = SUBSTRING(p_requisitos FROM v_start FOR v_end - v_start);
        
        IF LENGTH(TRIM(v_id_requisito)) > 0 THEN
            INSERT INTO requisitos_clasificacion_partida (
                id_requisito,
                id_partida
            ) VALUES (
                v_id_requisito,
                v_id_partida
            );
        END IF;

        SET v_start = v_end + 1;
    END LOOP;

    -- Confirmar transacción
    COMMIT;

    -- Establecer valores de salida
    SET p_codigo_retorno = 1;
    SET p_mensaje_retorno = 'Partida creada exitosamente';

END //

DELIMITER ;


DELIMITER //

DROP PROCEDURE IF EXISTS sp_get_requirements_construction_create_level //
CREATE PROCEDURE sp_get_requirements_construction_create_level(
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
        ROLLBACK;
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

    -- Obtener requisitos y sus fragmentos
    SELECT 
        rc.id_requisito as id,
        rc.requisito_completo,
        rc.nivel_dificultad,
        GROUP_CONCAT(
            CONCAT(
                fr.id_fragmento, '|',
                fr.texto, '|',
                COALESCE(fr.posicion_correcta, 'NULL'), '|',
                IF(fr.es_señuelo, 'true', 'false')
            )
            SEPARATOR '¬'
        ) as fragmentos
    FROM requisitos_construccion rc
    LEFT JOIN fragmentos_requisito fr ON rc.id_requisito = fr.id_requisito
    WHERE rc.id_usuario_creador = p_id_usuario
    GROUP BY rc.id_requisito
    ORDER BY rc.id_requisito DESC;

    -- Si llegamos aquí, todo se ejecutó correctamente
    SET p_codigo_retorno = 1;
    SET p_mensaje_retorno = 'Requisitos obtenidos exitosamente';
END //

DELIMITER ;


DELIMITER //

DROP PROCEDURE IF EXISTS sp_create_requirements_construction //
CREATE PROCEDURE sp_create_requirements_construction(
    -- Parámetros de entrada
    IN p_id_usuario INT,
    IN p_requisito_completo TEXT,
    IN p_fragmentos TEXT, -- Formato: "texto|posicion|es_señuelo¬texto|posicion|es_señuelo"
    -- Parámetros de salida
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500),
    OUT p_id_requisito INT
)
BEGIN
    -- Declaración de variables locales
    DECLARE v_usuario_existe INT DEFAULT 0;
    DECLARE v_start INT DEFAULT 1;
    DECLARE v_end INT;
    DECLARE v_fragmento VARCHAR(500);
    DECLARE v_texto VARCHAR(100);
    DECLARE v_posicion VARCHAR(10);
    DECLARE v_es_señuelo VARCHAR(5);
    
    -- Manejo de errores SQL
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            @sqlstate = RETURNED_SQLSTATE,
            @errno = MYSQL_ERRNO,
            @text = MESSAGE_TEXT;
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = CONCAT('Error en la ejecución del procedimiento: ', @text, ' (', @errno, ')');
        SET p_id_requisito = 0;
        ROLLBACK;
    END;

    START TRANSACTION;
    
    -- Validación de usuario
    SELECT COUNT(*) INTO v_usuario_existe
    FROM jugadores 
    WHERE id_jugador = p_id_usuario;

    IF v_usuario_existe = 0 THEN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'El usuario no existe';
        SET p_id_requisito = 0;
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Usuario no existe';
    END IF;

    -- Insertar el requisito principal
    INSERT INTO requisitos_construccion (
        id_usuario_creador,
        requisito_completo,
        nivel_dificultad
    ) VALUES (
        p_id_usuario,
        p_requisito_completo,
        1  -- Nivel de dificultad por defecto
    );

    -- Obtener el ID del requisito insertado
    SET p_id_requisito = LAST_INSERT_ID();

    -- Procesar los fragmentos
    fragmentos_loop: LOOP
        -- Encontrar el siguiente separador de fragmentos (¬)
        SET v_end = LOCATE('¬', p_fragmentos, v_start);
        
        IF v_end = 0 THEN
            -- Último fragmento
            SET v_fragmento = SUBSTRING(p_fragmentos FROM v_start);
            IF LENGTH(TRIM(v_fragmento)) = 0 THEN
                LEAVE fragmentos_loop;
            END IF;
        ELSE
            SET v_fragmento = SUBSTRING(p_fragmentos FROM v_start FOR v_end - v_start);
        END IF;

        -- Extraer componentes del fragmento
        SET v_texto = SUBSTRING_INDEX(v_fragmento, '|', 1);
        SET v_posicion = SUBSTRING_INDEX(SUBSTRING_INDEX(v_fragmento, '|', 2), '|', -1);
        SET v_es_señuelo = SUBSTRING_INDEX(v_fragmento, '|', -1);

        -- Insertar el fragmento
        INSERT INTO fragmentos_requisito (
            id_requisito,
            texto,
            posicion_correcta,
            es_señuelo
        ) VALUES (
            p_id_requisito,
            v_texto,
            CASE 
                WHEN v_posicion = 'NULL' THEN NULL
                ELSE CAST(v_posicion AS SIGNED)
            END,
            v_es_señuelo = 'true'
        );

        IF v_end = 0 THEN
            LEAVE fragmentos_loop;
        END IF;
        SET v_start = v_end + 1;
    END LOOP;

    -- Devolver los datos del requisito creado
    SELECT 
        rc.id_requisito as id,
        rc.requisito_completo,
        rc.nivel_dificultad,
        (SELECT 
            GROUP_CONCAT(
                CONCAT(
                    fr.id_fragmento, '|',
                    fr.texto, '|',
                    COALESCE(fr.posicion_correcta, 'NULL'), '|',
                    IF(fr.es_señuelo, 'true', 'false')
                )
                SEPARATOR '¬'
            )
            FROM fragmentos_requisito fr 
            WHERE fr.id_requisito = rc.id_requisito
        ) as fragmentos
    FROM requisitos_construccion rc
    WHERE rc.id_requisito = p_id_requisito;

    COMMIT;
    
    SET p_codigo_retorno = 1;
    SET p_mensaje_retorno = 'Requisito creado exitosamente';

END //

DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_import_requirements_construction //
CREATE PROCEDURE sp_import_requirements_construction(
    IN p_id_creador INT,
    IN p_requisitos_str TEXT,
	OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500),
    OUT p_total_importados INT
)
BEGIN
    DECLARE v_usuario_existe INT DEFAULT 0;
	DECLARE v_current_req TEXT;
    DECLARE v_req_text TEXT;
    DECLARE v_fragments_str TEXT;
    DECLARE v_last_req_id INT;
    DECLARE v_position INT;
    DECLARE v_fragment_text TEXT;
    DECLARE v_is_decoy BOOLEAN;
    DECLARE v_delimiter_index INT;
    DECLARE v_error BOOLEAN DEFAULT FALSE;
    DECLARE v_fragment_str TEXT;
    DECLARE v_continue BOOLEAN DEFAULT TRUE;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
                    @sqlstate = RETURNED_SQLSTATE,
                    @errno = MYSQL_ERRNO,
                    @text = MESSAGE_TEXT;
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = CONCAT('Error en la ejecución del procedimiento: ', @text, ' (', @errno, ')');
        SET p_total_importados = 0;
        ROLLBACK;
    END;

    bloque_principal: BEGIN
        -- Inicialización de variables de salida
        SET p_codigo_retorno = 0;
        SET p_mensaje_retorno = 'Proceso iniciado correctamente';
        SET p_total_importados = 0;
        
        -- Validaciones iniciales
        IF p_id_creador IS NULL OR p_id_creador <= 0 THEN
            SET p_codigo_retorno = -1;
            SET p_mensaje_retorno = 'El ID de usuario no es válido';
            LEAVE bloque_principal;
        END IF;
        
        SELECT COUNT(*) INTO v_usuario_existe
        FROM jugadores 
        WHERE id_jugador = p_id_creador;
        
        IF v_usuario_existe IS NULL OR v_usuario_existe <= 0 THEN
            SET p_codigo_retorno = -1;
            SET p_mensaje_retorno = 'El usuario no existe';
            LEAVE bloque_principal;
        END IF;
        
        -- Validar que hay datos para importar
        IF p_requisitos_str IS NULL OR LENGTH(TRIM(p_requisitos_str)) = 0 THEN
            SET p_codigo_retorno = -1;
            SET p_mensaje_retorno = 'No hay requisitos para importar';
            LEAVE bloque_principal;
        END IF;

		START TRANSACTION;
    
		SET p_total_importados = 0;
		
		requirements_loop: WHILE v_continue AND LENGTH(p_requisitos_str) > 0 DO
			-- Extraer un requisito completo hasta el próximo §
			IF LOCATE('§', p_requisitos_str) > 0 THEN
				SET v_current_req = SUBSTRING_INDEX(p_requisitos_str, '§', 1);
				SET p_requisitos_str = SUBSTRING(p_requisitos_str, LOCATE('§', p_requisitos_str) + 1);
			ELSE
				SET v_current_req = p_requisitos_str;
				SET v_continue = FALSE;
			END IF;
			
			-- Separar el requisito completo (antes del primer |) de los fragmentos
			SET v_req_text = SUBSTRING_INDEX(v_current_req, '|', 1);
			SET v_fragments_str = SUBSTRING(v_current_req, LOCATE('|', v_current_req));
			SET v_fragments_str = SUBSTRING(v_fragments_str, 2); -- Remover el primer | 
			
			-- Insertar el requisito principal
			INSERT INTO requisitos_construccion (id_usuario_creador, requisito_completo, nivel_dificultad)
			VALUES (p_id_creador, v_req_text, 1);
			
			SET v_last_req_id = LAST_INSERT_ID();
			
			-- Procesar fragmentos
			WHILE LENGTH(v_fragments_str) > 0 DO
				IF LOCATE('¬', v_fragments_str) > 0 THEN
					SET v_fragment_str = SUBSTRING_INDEX(v_fragments_str, '¬', 1);
					SET v_fragments_str = SUBSTRING(v_fragments_str, LOCATE('¬', v_fragments_str) + 1);
				ELSE
					SET v_fragment_str = v_fragments_str;
					SET v_fragments_str = '';
				END IF;
				
				-- Extraer componentes del fragmento
				SET v_fragment_text = SUBSTRING_INDEX(v_fragment_str, '|', 1);
				SET v_position = CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(v_fragment_str, '|', 2), '|', -1) AS UNSIGNED);
				SET v_is_decoy = CAST(SUBSTRING_INDEX(v_fragment_str, '|', -1) AS UNSIGNED);
				
				-- Insertar fragmento
				INSERT INTO fragmentos_requisito (id_requisito, texto, posicion_correcta, es_señuelo)
				VALUES (v_last_req_id, v_fragment_text, 
					   IF(v_is_decoy = 1, NULL, v_position), 
					   v_is_decoy);
			END WHILE;
			
			SET p_total_importados = p_total_importados + 1;
		END WHILE;

		IF v_error THEN
			ROLLBACK;
		ELSE
			COMMIT;
			SET p_codigo_retorno = 1;
			SET p_mensaje_retorno = CONCAT('Se importaron ', p_total_importados, ' requisitos exitosamente');
		END IF;
	END bloque_principal;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_crear_partida_construction //
CREATE PROCEDURE sp_crear_partida_construction(
    -- Parámetros de entrada
    IN p_id_usuario INT,
    IN p_requisitos TEXT, -- Lista de IDs separados por coma
    -- Parámetros de salida
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500),
    OUT p_codigo_partida VARCHAR(10)
)
BEGIN
    -- Declaración de variables locales
    DECLARE v_id_partida INT;
    DECLARE v_codigo_existe INT;
    DECLARE v_codigo_valido BOOLEAN DEFAULT FALSE;
    DECLARE v_start INT DEFAULT 1;
    DECLARE v_id_requisito VARCHAR(10);
    DECLARE v_end INT;
    
    -- Declarar handler para errores SQL
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1
            @sqlstate = RETURNED_SQLSTATE,
            @errno = MYSQL_ERRNO,
            @text = MESSAGE_TEXT;
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = CONCAT('Error en la ejecución del procedimiento: ', @text);
        SET p_codigo_partida = NULL;
        ROLLBACK;
    END;

    -- Inicializar variables de salida
    SET p_codigo_retorno = 0;
    SET p_mensaje_retorno = 'Proceso iniciado';
    SET p_codigo_partida = NULL;

    -- Validar parámetros de entrada
    IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'ID de usuario inválido';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'ID de usuario inválido';
    END IF;

    IF p_requisitos IS NULL OR TRIM(p_requisitos) = '' THEN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'Lista de requisitos vacía';
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Lista de requisitos vacía';
    END IF;

    -- Iniciar transacción
    START TRANSACTION;

    -- Generar código único para la partida
    codigo_loop: LOOP
        -- Generar código aleatorio alfanumérico de 6 caracteres
        SET p_codigo_partida = UPPER(
            CONCAT(
                CHAR(FLOOR(65 + (RAND() * 26))), -- Letra
                CHAR(FLOOR(65 + (RAND() * 26))), -- Letra
                FLOOR(RAND() * 10), -- Número
                FLOOR(RAND() * 10), -- Número
                CHAR(FLOOR(65 + (RAND() * 26))), -- Letra
                FLOOR(RAND() * 10) -- Número
            )
        );

        -- Verificar si el código ya existe
        SELECT COUNT(*) INTO v_codigo_existe
        FROM partidas
        WHERE codigo_partida = p_codigo_partida;

        IF v_codigo_existe = 0 THEN
            SET v_codigo_valido = TRUE;
            LEAVE codigo_loop;
        END IF;
    END LOOP;

    -- Insertar en la tabla partidas
    INSERT INTO partidas (
        id_modalidad,
        id_usuario_creacion,
        codigo_partida,
        estado,
        tiempo_limite
    ) VALUES (
        2, -- Modalidad clasificación
        p_id_usuario,
        p_codigo_partida,
        'activa',
        600 -- 10 minutos por defecto
    );

    -- Obtener el ID de la partida creada
    SET v_id_partida = LAST_INSERT_ID();

    -- Insertar los requisitos en requisitos_clasificacion_partida
    -- Procesar la lista de requisitos
    requisitos_loop: LOOP
        -- Encontrar la siguiente coma
        SET v_end = LOCATE(',', p_requisitos, v_start);
        
        IF v_end = 0 THEN
            -- Último elemento
            SET v_id_requisito = SUBSTRING(p_requisitos FROM v_start);
            
            IF LENGTH(TRIM(v_id_requisito)) > 0 THEN
                INSERT INTO requisitos_construccion_partida (
                    id_requisito,
                    id_partida
                ) VALUES (
                    v_id_requisito,
                    v_id_partida
                );
            END IF;
            
            LEAVE requisitos_loop;
        END IF;

        -- Extraer el ID del requisito
        SET v_id_requisito = SUBSTRING(p_requisitos FROM v_start FOR v_end - v_start);
        
        IF LENGTH(TRIM(v_id_requisito)) > 0 THEN
            INSERT INTO requisitos_construccion_partida (
                id_requisito,
                id_partida
            ) VALUES (
                v_id_requisito,
                v_id_partida
            );
        END IF;

        SET v_start = v_end + 1;
    END LOOP;

    -- Confirmar transacción
    COMMIT;

    -- Establecer valores de salida
    SET p_codigo_retorno = 1;
    SET p_mensaje_retorno = 'Partida creada exitosamente';

END //

DELIMITER ;

DELIMITER //

DROP PROCEDURE IF EXISTS sp_get_partidas_por_usuario //
CREATE PROCEDURE sp_get_partidas_por_usuario(
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
    WHERE p.id_usuario_creacion = p_id_usuario
    GROUP BY p.id_partida, p.codigo_partida, p.fecha_creacion, m.codigo
    ORDER BY p.fecha_creacion DESC;

    -- Si llegamos aquí, todo se ejecutó correctamente
    SET p_codigo_retorno = 1;
    SET p_mensaje_retorno = 'Partidas obtenidas exitosamente';

END //

DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_stats_details_for_player //
CREATE PROCEDURE sp_stats_details_for_player(
    -- Parámetros de entrada
    IN p_codigo_partida VARCHAR(10),
    IN p_id_usuario INT,
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

        IF p_id_usuario IS NULL OR p_id_usuario <= 0 THEN
            SET p_codigo_retorno = -1;
            SET p_mensaje_retorno = 'El ID de usuario no es válido';
            LEAVE bloque_principal;
        END IF;

        -- Verificar existencia de la partida
        SELECT COUNT(*), id_partida INTO v_partida_existe, v_id_partida
        FROM partidas 
        WHERE codigo_partida = p_codigo_partida 
        AND id_usuario_creacion = p_id_usuario AND id_modalidad = 1;

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

DELIMITER //
DROP PROCEDURE IF EXISTS sp_get_full_construction_report //
CREATE PROCEDURE sp_get_full_construction_report(
    -- Parámetros de entrada
    IN p_codigo_partida VARCHAR(10),
    IN p_id_usuario INT,
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
        AND id_usuario_creacion = p_id_usuario AND id_modalidad = 2;

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