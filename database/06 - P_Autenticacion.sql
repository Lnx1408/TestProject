DELIMITER //
DROP PROCEDURE IF EXISTS sp_login_usuario //
CREATE PROCEDURE sp_login_usuario(
    IN pUsuarioCorreo VARCHAR(100),  -- Nombre de usuario o correo
    IN pPassword VARCHAR(256),        -- Contraseña
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500),
    OUT p_id_usuario INT,
    OUT p_tipo_usuario VARCHAR(50),
    OUT p_nombres VARCHAR(50),
    OUT p_apellidos VARCHAR(50),
    OUT p_correo VARCHAR(50),
    OUT p_max_sesiones INT,
    OUT p_estado VARCHAR(20)
)
BEGIN
    -- Manejo de errores SQL
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'Error en la ejecución del procedimiento';
        SET p_id_usuario = 0;
        ROLLBACK;
    END;
    
    SET p_codigo_retorno = 0;
    SET p_mensaje_retorno = 'Proceso de login iniciado';
    SET p_id_usuario = 0;
    SET p_max_sesiones = 0;
    SET p_estado = '';
    
    -- Verificar credenciales y obtener datos del usuario
    -- Nota: La verificación real del password se hará en PHP con password_verify()
    SELECT 
        j.id_jugador, 
        t.codigo, 
        j.nombres, 
        j.apellidos, 
        j.correo, 
        j.max_sesiones,
        j.estado
    INTO 
        p_id_usuario, 
        p_tipo_usuario, 
        p_nombres, 
        p_apellidos, 
        p_correo,
        p_max_sesiones,
        p_estado
    FROM jugadores j
    JOIN tipo_usuario t ON j.id_tipo = t.id_tipo
    WHERE (j.usuario = pUsuarioCorreo OR j.correo = pUsuarioCorreo)
      AND j.password = pPassword
    LIMIT 1;
    
    -- Verificar si el usuario existe
    IF p_id_usuario > 0 THEN
        -- Verificamos el estado
        IF p_estado = 'activo' THEN
            -- Usuario activo
            SET p_codigo_retorno = 1;
            SET p_mensaje_retorno = 'Usuario encontrado';
        ELSE
            -- Usuario inactivo o bloqueado
            SET p_codigo_retorno = 3;
            SET p_mensaje_retorno = CONCAT('Cuenta en estado: ', p_estado);
            SET p_id_usuario = 0;
        END IF;
    ELSE
        -- Usuario no encontrado
        SET p_codigo_retorno = 2;
        SET p_mensaje_retorno = 'Usuario o contraseña incorrectos';
        SET p_id_usuario = 0;
    END IF;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_crear_sesion //
CREATE PROCEDURE sp_crear_sesion(
    IN p_id_sesion VARCHAR(36),
    IN p_id_jugador INT,
    IN p_fecha_expiracion TIMESTAMP,
    IN p_ip_direccion VARCHAR(45),
    IN p_info_dispositivo TEXT,
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500)
)
BEGIN
    DECLARE v_max_sesiones INT DEFAULT 0;
    DECLARE v_sesiones_activas INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'Error en la ejecución del procedimiento';
    END;

    main_block: BEGIN
        SET p_codigo_retorno = 0;
        SET p_mensaje_retorno = 'Inicializando creación de sesión';

        -- Verificar existencia del jugador
        IF NOT EXISTS (SELECT 1 FROM jugadores WHERE id_jugador = p_id_jugador) THEN
            SET p_codigo_retorno = -2;
            SET p_mensaje_retorno = 'El jugador no existe';
            LEAVE main_block;
        END IF;

        START TRANSACTION;

        -- Obtener máximo de sesiones permitidas
        SELECT max_sesiones INTO v_max_sesiones 
        FROM jugadores 
        WHERE id_jugador = p_id_jugador;

        IF v_max_sesiones > 0 THEN
            SELECT COUNT(*) INTO v_sesiones_activas 
            FROM sesiones 
            WHERE id_jugador = p_id_jugador AND activa = TRUE;

            IF v_sesiones_activas >= v_max_sesiones THEN
                -- Desactivar la sesión más antigua
                UPDATE sesiones 
                SET activa = FALSE 
                WHERE id_sesion = (
                    SELECT id_sesion FROM (
                        SELECT id_sesion 
                        FROM sesiones 
                        WHERE id_jugador = p_id_jugador AND activa = TRUE 
                        ORDER BY fecha_creacion ASC 
                        LIMIT 1
                    ) AS temp_table
                );
            END IF;
        END IF;

        -- Crear nueva sesión
        INSERT INTO sesiones (
            id_sesion, id_jugador, fecha_expiracion, ip_direccion, info_dispositivo, activa
        ) VALUES (
            p_id_sesion, p_id_jugador, p_fecha_expiracion, p_ip_direccion, p_info_dispositivo, TRUE
        );

        COMMIT;

        SET p_codigo_retorno = 1;
        SET p_mensaje_retorno = 'Sesión creada correctamente';
    END main_block;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_actualizar_token_recuperacion //
CREATE PROCEDURE sp_actualizar_token_recuperacion(
    IN p_correo VARCHAR(255),
    IN p_reset_token VARCHAR(255),
    IN p_reset_token_expires TIMESTAMP,
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500),
    OUT p_id_jugador INT
)
BEGIN
    DECLARE v_id_jugador INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'Error en la ejecución del procedimiento';
        SET p_id_jugador = 0;
    END;

    START TRANSACTION;

    -- Verificar si el correo existe y está activo
    SELECT id_jugador INTO v_id_jugador
    FROM jugadores 
    WHERE correo = p_correo AND estado = 'activo'
    LIMIT 1;

    IF v_id_jugador > 0 THEN
        -- Actualizar token de recuperación
        UPDATE jugadores 
        SET 
            reset_token = p_reset_token,
            reset_token_expires = p_reset_token_expires
        WHERE id_jugador = v_id_jugador;

        SET p_id_jugador = v_id_jugador;
        SET p_codigo_retorno = 1;
        SET p_mensaje_retorno = 'Token de recuperación actualizado';
    ELSE
        SET p_id_jugador = 0;
        SET p_codigo_retorno = 2;
        SET p_mensaje_retorno = 'Correo no encontrado o cuenta inactiva';
    END IF;

    COMMIT;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_cambiar_password //
CREATE PROCEDURE sp_cambiar_password(
    IN p_id_jugador INT,
    IN p_password_hash VARCHAR(255),
    IN p_reset_token VARCHAR(255),
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500)
)
BEGIN
    DECLARE v_token_valido INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'Error en la ejecución del procedimiento';
    END;

    START TRANSACTION;

    -- Verificar si el token es válido
    IF p_reset_token IS NOT NULL THEN
        SELECT COUNT(*) INTO v_token_valido
        FROM jugadores
        WHERE id_jugador = p_id_jugador 
          AND reset_token = p_reset_token
          AND reset_token_expires > NOW();
    ELSE
        SET v_token_valido = 1; -- Cambio manual
    END IF;

    IF v_token_valido > 0 THEN
        -- Actualizar contraseña y limpiar token
        UPDATE jugadores 
        SET 
            password = p_password_hash,
            ultimo_cambio_password = NOW(),
            reset_token = NULL,
            reset_token_expires = NULL
        WHERE id_jugador = p_id_jugador;

        -- Cerrar sesiones activas
        UPDATE sesiones 
        SET activa = FALSE 
        WHERE id_jugador = p_id_jugador AND activa = TRUE;

        SET p_codigo_retorno = 1;
        SET p_mensaje_retorno = 'Contraseña actualizada correctamente';
    ELSE
        SET p_codigo_retorno = 2;
        SET p_mensaje_retorno = 'Token inválido o expirado';
    END IF;

    COMMIT;
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS sp_cambiar_password_usuario //
CREATE PROCEDURE sp_cambiar_password_usuario(
    IN p_id_jugador INT,
    IN p_password_actual VARCHAR(255),
    IN p_password_nuevo VARCHAR(255),
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500)
)
BEGIN
    DECLARE v_password_actual VARCHAR(255);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'Error en la ejecución del procedimiento';
    END;

    SET p_codigo_retorno = 0;
    SET p_mensaje_retorno = 'Inicializando cambio de contraseña';

    -- Obtener la contraseña actual
    SELECT password INTO v_password_actual
    FROM jugadores 
    WHERE id_jugador = p_id_jugador;

    IF v_password_actual IS NULL THEN
        SET p_codigo_retorno = 2;
        SET p_mensaje_retorno = 'Usuario no encontrado';
    ELSE
        -- Verificar si la contraseña actual coincide
        IF v_password_actual = p_password_actual THEN
            START TRANSACTION;

            -- Actualizar la contraseña
            UPDATE jugadores 
            SET 
                password = p_password_nuevo,
                ultimo_cambio_password = NOW()
            WHERE id_jugador = p_id_jugador;

            COMMIT;

            SET p_codigo_retorno = 1;
            SET p_mensaje_retorno = 'Contraseña actualizada correctamente';
        ELSE
            SET p_codigo_retorno = 3;
            SET p_mensaje_retorno = 'La contraseña actual es incorrecta';
        END IF;
    END IF;
END //
DELIMITER ;

-- Procedimiento para actualizar correo electrónico
DELIMITER //
DROP PROCEDURE IF EXISTS sp_actualizar_correo_usuario //
CREATE PROCEDURE sp_actualizar_correo_usuario(
    IN p_id_jugador INT,
    IN p_correo_nuevo VARCHAR(255),
    IN p_password VARCHAR(255),
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500)
)
BEGIN
    DECLARE v_password_actual VARCHAR(255);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'Error en la ejecución del procedimiento';
        ROLLBACK;
    END;

    START TRANSACTION;

    -- Verificar si el correo ya está en uso por otro usuario
    IF EXISTS (
        SELECT 1 
        FROM jugadores 
        WHERE correo = p_correo_nuevo AND id_jugador != p_id_jugador
    ) THEN
        SET p_codigo_retorno = 4;
        SET p_mensaje_retorno = 'El correo ya está en uso por otro usuario';
        ROLLBACK;
        LEAVE_proc: BEGIN LEAVE LEAVE_proc; END;
    END IF;

    -- Verificar si el usuario existe y obtener su contraseña actual
    SELECT password INTO v_password_actual
    FROM jugadores 
    WHERE id_jugador = p_id_jugador;

    IF v_password_actual IS NULL THEN
        SET p_codigo_retorno = 2;
        SET p_mensaje_retorno = 'Usuario no encontrado';
        ROLLBACK;
    ELSE
        -- Verificar que la contraseña sea correcta
        IF v_password_actual = p_password THEN
            -- Actualizar correo electrónico
            UPDATE jugadores 
            SET 
                correo = p_correo_nuevo,
                fecha_modificacion = NOW()
            WHERE id_jugador = p_id_jugador;

            COMMIT;

            SET p_codigo_retorno = 1;
            SET p_mensaje_retorno = 'Correo electrónico actualizado correctamente';
        ELSE
            SET p_codigo_retorno = 3;
            SET p_mensaje_retorno = 'La contraseña es incorrecta';
            ROLLBACK;
        END IF;
    END IF;
END //
DELIMITER ;


-- Procedimiento para obtener sesiones activas de un usuario
DELIMITER //
DROP PROCEDURE IF EXISTS sp_obtener_sesiones_activas //
CREATE PROCEDURE sp_obtener_sesiones_activas(
    IN p_id_jugador INT,
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'Error en la ejecución del procedimiento';
        ROLLBACK;
    END;
    
    SET p_codigo_retorno = 1;
    SET p_mensaje_retorno = 'Sesiones obtenidas correctamente';
    
    -- Obtener sesiones activas
    SELECT 
        id_sesion, 
        fecha_creacion, 
        ultima_actividad, 
        ip_direccion, 
        info_dispositivo
    FROM sesiones 
    WHERE id_jugador = p_id_jugador AND activa = TRUE 
    ORDER BY ultima_actividad DESC;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_obtener_historial_actividad //
CREATE PROCEDURE sp_obtener_historial_actividad(
    IN p_id_jugador INT,
    IN p_periodo INT,
    IN p_offset INT,
    IN p_limit INT,
    OUT p_codigo INT,
    OUT p_mensaje VARCHAR(255),
    OUT p_total_registros INT
)
BEGIN
    DECLARE fecha_desde DATETIME;
    
    -- Establecer un valor por defecto para el período si es inválido
    IF p_periodo <= 0 THEN 
        SET p_periodo = 90; -- Por defecto 90 días
    END IF;
    
    -- Calcular la fecha desde la cual obtener el historial
    SET fecha_desde = DATE_SUB(NOW(), INTERVAL p_periodo DAY);
    
    -- Obtener el total de registros para paginación
    SELECT COUNT(*) INTO p_total_registros 
    FROM sesiones 
    WHERE id_jugador = p_id_jugador 
    AND fecha_creacion >= fecha_desde;
    
    -- Obtener las sesiones del período especificado
    SELECT 
        id_sesion,
        fecha_creacion,
        fecha_expiracion,
        ultima_actividad,
        ip_direccion,
        info_dispositivo,
        activa
    FROM sesiones 
    WHERE id_jugador = p_id_jugador 
    AND fecha_creacion >= fecha_desde
    ORDER BY fecha_creacion DESC
    LIMIT p_offset, p_limit;
    
    -- Establecer código y mensaje de éxito
    SET p_codigo = 1;
    SET p_mensaje = 'Historial de actividad obtenido correctamente';
    
END //
DELIMITER ;

-- Procedimiento para cerrar una sesión específica
DELIMITER //
DROP PROCEDURE IF EXISTS sp_cerrar_sesion //
CREATE PROCEDURE sp_cerrar_sesion(
    IN p_id_jugador INT,
    IN p_id_sesion VARCHAR(36),
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500)
)
BEGIN
    DECLARE v_filas_afectadas INT;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'Error en la ejecución del procedimiento';
        ROLLBACK;
    END;
    
    -- Cerrar la sesión específica
    UPDATE sesiones 
    SET activa = FALSE 
    WHERE id_jugador = p_id_jugador AND id_sesion = p_id_sesion;
    
    -- Verificar si se actualizó alguna fila
    SET v_filas_afectadas = ROW_COUNT();
    
    IF v_filas_afectadas > 0 THEN
        SET p_codigo_retorno = 1;
        SET p_mensaje_retorno = 'Sesión cerrada correctamente';
    ELSE
        SET p_codigo_retorno = 2;
        SET p_mensaje_retorno = 'No se encontró la sesión o ya estaba cerrada';
    END IF;
END //
DELIMITER ;

-- Procedimiento para cerrar todas las sesiones de un usuario
DELIMITER //
DROP PROCEDURE IF EXISTS sp_cerrar_todas_sesiones //
CREATE PROCEDURE sp_cerrar_todas_sesiones(
    IN p_id_jugador INT,
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500),
    OUT p_sesiones_cerradas INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'Error en la ejecución del procedimiento';
        SET p_sesiones_cerradas = 0;
        ROLLBACK;
    END;
    
    -- Cerrar todas las sesiones activas
    UPDATE sesiones 
    SET activa = FALSE 
    WHERE id_jugador = p_id_jugador AND activa = TRUE;
    
    -- Obtener el número de sesiones cerradas
    SET p_sesiones_cerradas = ROW_COUNT();
    
    SET p_codigo_retorno = 1;
    SET p_mensaje_retorno = CONCAT(p_sesiones_cerradas, ' sesiones cerradas correctamente');
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_cerrar_sesiones_excepto_actual //
CREATE PROCEDURE sp_cerrar_sesiones_excepto_actual(
    IN p_id_jugador INT,
    IN p_id_sesion_actual VARCHAR(36),
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500),
    OUT p_sesiones_cerradas INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'Error en la ejecución del procedimiento';
        SET p_sesiones_cerradas = 0;
        ROLLBACK;
    END;
    
    SET p_codigo_retorno = 0;
    SET p_mensaje_retorno = 'Inicializando cierre de sesiones';
    SET p_sesiones_cerradas = 0;
    
    -- Verificar si el usuario existe
    IF NOT EXISTS (SELECT 1 FROM jugadores WHERE id_jugador = p_id_jugador) THEN
        SET p_codigo_retorno = 2;
        SET p_mensaje_retorno = 'Usuario no encontrado';
    ELSE
        -- Cerrar todas las sesiones excepto la actual
        UPDATE sesiones 
        SET activa = FALSE 
        WHERE id_jugador = p_id_jugador 
          AND id_sesion != p_id_sesion_actual
          AND activa = TRUE;
        
        -- Obtener el número de sesiones cerradas
        SET p_sesiones_cerradas = ROW_COUNT();
        
        SET p_codigo_retorno = 1;
        SET p_mensaje_retorno = CONCAT(p_sesiones_cerradas, ' sesiones cerradas correctamente');
    END IF;
END //
DELIMITER ;

-- Procedimiento para verificar un token de recuperación
DELIMITER //
DROP PROCEDURE IF EXISTS sp_verificar_token_recuperacion //
CREATE PROCEDURE sp_verificar_token_recuperacion(
    IN p_reset_token VARCHAR(255),
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500),
    OUT p_id_jugador INT
)
BEGIN
    DECLARE v_expiracion TIMESTAMP;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'Error en la ejecución del procedimiento';
        SET p_id_jugador = 0;
        ROLLBACK;
    END;
    
    SET p_id_jugador = 0;
    
    -- Verificar token
    SELECT id_jugador, reset_token_expires INTO p_id_jugador, v_expiracion
    FROM jugadores 
    WHERE reset_token = p_reset_token
    LIMIT 1;
    
    IF p_id_jugador > 0 THEN
        -- Verificar si el token ha expirado
        IF v_expiracion > NOW() THEN
            SET p_codigo_retorno = 1;
            SET p_mensaje_retorno = 'Token válido';
        ELSE
            SET p_codigo_retorno = 2;
            SET p_mensaje_retorno = 'Token expirado';
            SET p_id_jugador = 0;
        END IF;
    ELSE
        SET p_codigo_retorno = 3;
        SET p_mensaje_retorno = 'Token no encontrado';
    END IF;
END //
DELIMITER ;


DELIMITER //
DROP PROCEDURE IF EXISTS sp_verificar_sesion_activa //
CREATE PROCEDURE sp_verificar_sesion_activa(
    IN p_id_sesion VARCHAR(36),
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500),
    OUT p_activa BOOLEAN
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'Error en la ejecución del procedimiento';
        SET p_activa = FALSE;
        ROLLBACK;
    END;
    
    SET p_activa = FALSE;
    
    -- Verificar si la sesión existe y está activa
    SELECT activa INTO p_activa
    FROM sesiones 
    WHERE id_sesion = p_id_sesion
    LIMIT 1;
    
    IF p_activa IS NULL THEN
        SET p_codigo_retorno = 2;
        SET p_mensaje_retorno = 'Sesión no encontrada';
        SET p_activa = FALSE;
    ELSE
        SET p_codigo_retorno = 1;
        SET p_mensaje_retorno = 'Consulta exitosa';
    END IF;
    
    -- Actualizar timestamp de última actividad
    IF p_activa = TRUE THEN
        UPDATE sesiones 
        SET ultima_actividad = CURRENT_TIMESTAMP 
        WHERE id_sesion = p_id_sesion;
    END IF;
END //
DELIMITER ;

DELIMITER //
DROP PROCEDURE IF EXISTS sp_obtener_info_perfil //
CREATE PROCEDURE sp_obtener_info_perfil(
    IN p_id_jugador INT,
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'Error en la ejecución del procedimiento';
        ROLLBACK;
    END;
    
    SET p_codigo_retorno = 0;
    SET p_mensaje_retorno = 'Inicializando obtención de perfil';
    
    -- Verificar si el usuario existe
    IF NOT EXISTS (SELECT 1 FROM jugadores WHERE id_jugador = p_id_jugador) THEN
        SET p_codigo_retorno = 2;
        SET p_mensaje_retorno = 'Usuario no encontrado';
    ELSE
        -- Obtener información detallada del perfil
        SELECT 
            j.id_jugador,
            j.usuario,
            j.nombres,
            j.apellidos,
            j.correo,
            j.fecha_registro,
            t.nombre_tipo AS tipo_usuario,
            t.codigo AS codigo_tipo
        FROM jugadores j
        JOIN tipo_usuario t ON j.id_tipo = t.id_tipo
        WHERE j.id_jugador = p_id_jugador;
        
        SET p_codigo_retorno = 1;
        SET p_mensaje_retorno = 'Información de perfil obtenida correctamente';
    END IF;
END //
DELIMITER ;

-- Procedimiento para actualizar información del perfil
DELIMITER //
DROP PROCEDURE IF EXISTS sp_actualizar_info_perfil //
CREATE PROCEDURE sp_actualizar_info_perfil(
    IN p_id_jugador INT,
    IN p_nombres VARCHAR(100),
    IN p_apellidos VARCHAR(100),
    OUT p_codigo_retorno INT,
    OUT p_mensaje_retorno VARCHAR(500)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_codigo_retorno = -1;
        SET p_mensaje_retorno = 'Error en la ejecución del procedimiento';
        ROLLBACK;
    END;
    
    SET p_codigo_retorno = 0;
    SET p_mensaje_retorno = 'Inicializando actualización de perfil';
    
    -- Verificar si el usuario existe
    IF NOT EXISTS (SELECT 1 FROM jugadores WHERE id_jugador = p_id_jugador) THEN
        SET p_codigo_retorno = 2;
        SET p_mensaje_retorno = 'Usuario no encontrado';
    ELSE
        -- Actualizar información del perfil
        UPDATE jugadores 
        SET 
            nombres = p_nombres,
            apellidos = p_apellidos,
            fecha_modificacion = NOW()
        WHERE id_jugador = p_id_jugador;
        
        SET p_codigo_retorno = 1;
        SET p_mensaje_retorno = 'Información de perfil actualizada correctamente';
    END IF;
END //
DELIMITER ;