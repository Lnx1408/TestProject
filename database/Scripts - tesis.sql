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
DROP PROCEDURE IF EXISTS sp_get_jugadores_por_partida //
CREATE PROCEDURE sp_get_jugadores_por_partida(
    IN codigo_partida VARCHAR(10)
)
BEGIN
	SELECT
		j.id_jugador,
		j.usuario,
		concat(j.nombres,' ', j.apellidos) as nombres,
		j.correo,
		j.isRevisor
	FROM
		reqscapetest_db.partidas AS p
	JOIN
		reqscapetest_db.partidas_jugadores AS pj ON p.id_partida = pj.id_partida
	JOIN
		reqscapetest_db.jugadores AS j ON pj.id_jugador = j.id_jugador
	WHERE
		p.codigo_partida = codigo_partida
	ORDER BY j.usuario ASC;
END //
CALL sp_get_jugadores_por_partida('TEST123');