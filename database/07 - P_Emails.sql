DELIMITER //

DROP PROCEDURE IF EXISTS sp_get_email_config //
CREATE PROCEDURE sp_get_email_config(
    IN p_config_name VARCHAR(100)
)
BEGIN
    DECLARE v_count INT;
    
    -- Verificar si existe la configuración específica
    SELECT COUNT(*) INTO v_count FROM email_config 
    WHERE `name` = p_config_name AND is_active = 1;
    
    IF v_count > 0 THEN
        -- Retornar la configuración específica
        SELECT 
            id, `name`, `host`, `port`, secure, from_email, from_name, 
            username, `password`, is_default
        FROM email_config 
        WHERE `name` = p_config_name AND is_active = 1;
    ELSE
        -- Retornar la configuración por defecto
        SELECT 
            id, `name`, `host`, `port`, secure, from_email, from_name, 
            username, `password`, is_default
        FROM email_config 
        WHERE is_default = 1 AND is_active = 1
        LIMIT 1;
    END IF;
END //

-- Procedimiento para encolar un correo
DROP PROCEDURE IF EXISTS sp_enqueue_email //
CREATE PROCEDURE sp_enqueue_email(
    IN p_to_email VARCHAR(255),
    IN p_subject VARCHAR(255),
    IN p_body TEXT,
    IN p_options TEXT,
    IN p_template VARCHAR(100),
    IN p_template_data TEXT,
    IN p_priority TINYINT,
    OUT p_result TINYINT,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE v_next_attempt DATETIME;
    SET v_next_attempt = NOW();
    
    -- Intentar insertar
    BEGIN
        DECLARE EXIT HANDLER FOR SQLEXCEPTION
        BEGIN
			GET DIAGNOSTICS CONDITION 1
					@sqlstate = RETURNED_SQLSTATE,
					@errno = MYSQL_ERRNO,
					@text = MESSAGE_TEXT;
            SET p_result = 0;
            SET p_message = CONCAT('Error al encolar el correo', @text, ' (', @errno, ')');
            ROLLBACK;
        END;
        
        START TRANSACTION;
        
        INSERT INTO email_queue (
            to_email, subject, body, options, 
            template, template_data, priority, next_attempt_at
        ) VALUES (
            p_to_email, p_subject, p_body, p_options, 
            p_template, p_template_data, p_priority, v_next_attempt
        );
        
        SET p_result = 1;
        SET p_message = 'Correo encolado exitosamente';
        
        COMMIT;
    END;
END //

-- Procedimiento para obtener correos pendientes
DROP PROCEDURE IF EXISTS sp_get_pending_emails //
CREATE PROCEDURE sp_get_pending_emails(
    IN p_limit INT
)
BEGIN
    SELECT * FROM email_queue 
    WHERE status IN ('pending', 'failed') 
    AND next_attempt_at <= NOW() 
    AND attempts < max_attempts 
    ORDER BY priority ASC, next_attempt_at ASC 
    LIMIT p_limit;
END //

-- Procedimiento para actualizar estado
DROP PROCEDURE IF EXISTS sp_update_email_status //
CREATE PROCEDURE sp_update_email_status(
    IN p_id INT,
    IN p_status VARCHAR(20),
    IN p_error TEXT,
    OUT p_result TINYINT,
    OUT p_message VARCHAR(255)
)
BEGIN
    -- Variable para sent_at
    DECLARE v_sent_at DATETIME;
    
    -- Inicializar variables de salida
    SET p_result = 0;
    SET p_message = 'No se pudo actualizar el estado del correo';
    
    -- Si es 'sent', actualizar sent_at
    IF p_status = 'sent' THEN
        SET v_sent_at = NOW();
        
        UPDATE email_queue 
        SET status = p_status, error = p_error, sent_at = v_sent_at 
        WHERE id = p_id;
    ELSE
        UPDATE email_queue 
        SET status = p_status, error = p_error 
        WHERE id = p_id;
    END IF;
    
    -- Verificar si se actualizó correctamente
    IF ROW_COUNT() > 0 THEN
        SET p_result = 1;
        SET p_message = 'Estado actualizado correctamente';
    END IF;
END //

-- Procedimiento para reprogramar un correo
DROP PROCEDURE IF EXISTS sp_requeue_email //
CREATE PROCEDURE sp_requeue_email(
    IN p_id INT,
    IN p_attempts INT,
    IN p_next_attempt DATETIME,
    OUT p_result TINYINT,
    OUT p_message VARCHAR(255)
)
BEGIN
    -- Inicializar variables de salida
    SET p_result = 0;
    SET p_message = 'No se pudo reprogramar el correo';
    
    -- Actualizar
    UPDATE email_queue 
    SET status = 'pending', attempts = p_attempts, next_attempt_at = p_next_attempt 
    WHERE id = p_id;
    
    -- Verificar si se actualizó
    IF ROW_COUNT() > 0 THEN
        SET p_result = 1;
        SET p_message = 'Correo reprogramado correctamente';
    END IF;
END //

DELIMITER ;