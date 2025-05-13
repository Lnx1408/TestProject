use reqscapetest_db;

INSERT INTO email_config(`name`, `host`, `port`, `secure`, `from_email`, `from_name`, `username`, `password`, `is_default`) VALUES
('email_config_default', 'smtp.gmail.com', 587, 'tls', 'ingenieria.softwarem4@gmail.com', 'ReqScape', 'ingenieria.softwarem4@gmail.com', 'vpmy rsha posu buty', 1); 

INSERT INTO modalidades (nombre, codigo,descripcion) VALUES
('Clasificación','MOD-CLASS','Clasificar requisitos entre ambiguos y no ambiguos'),
('Construcción', 'MOD-BUILD', 'Construir requisitos ordenando fragmentos correctamente');

INSERT INTO tipo_usuario (nombre_tipo, codigo) VALUES
('Administrador', 'A'),
('Docente', 'D'),
('Estudiante', 'E');

-- Insertar un jugador de prueba
INSERT INTO jugadores (nombres,id_tipo, apellidos, usuario, correo, password) 
VALUES
('YERMIN YAIR',1, 'LINO SANCHEZ', 'ylino', 'yls.2000@outlook.es', '5994471abb01112afcc18159f6cc74b4f511b99806da59b3caf5a9c173cacfc5'),
('CRISTOPHER DUSTIN',2, 'SILVA FAJARDO', 'csilva', 'csilva@test.com', '5994471abb01112afcc18159f6cc74b4f511b99806da59b3caf5a9c173cacfc5'),
('Estudiante',3, 'Prueba', 'est1', 'est1@test.com', '5994471abb01112afcc18159f6cc74b4f511b99806da59b3caf5a9c173cacfc5');

-- Insertar una partida de prueba
INSERT INTO partidas (id_modalidad, codigo_partida, id_usuario_creacion, estado, tiempo_limite) VALUES
(1, 'TEST123', '1', 'activa', 600), -- 10 minutos de tiempo límite
(2, 'BUILD123', '1', 'activa', 1800);

-- Registrar participación del jugador en la partida
INSERT INTO partidas_jugadores (id_partida, id_jugador, estado) VALUES
(1, 1, 'en_progreso');

-- INSERT INTO partidas_jugadores (id_partida, id_jugador, estado) VALUES
-- (2, 1, 'en_progreso');

-- Insertar requisitos de prueba
INSERT INTO requisitos (id_usuario_creador, descripcion, es_ambiguo, retroalimentacion, es_funcional) VALUES
(1, 'El sistema debe permitir que los usuarios recuperen su contraseña ingresando su correo electrónico y respondiendo una pregunta de seguridad predefinida.', 0, 'Se especifica exactamente cómo se debe recuperar la contraseña y qué interacción se espera del usuario.', 1),
(1, 'El sistema debe generar un archivo PDF con el resumen de las transacciones del mes y enviarlo al correo del usuario registrado el primer día de cada mes.', 0, 'Se detalla el formato del archivo, el contenido esperado y el momento en que se debe enviar.', 1),
(1, 'El sistema debe registrar cada inicio de sesión exitoso en un registro de auditoría, incluyendo la fecha, hora y dirección IP del usuario.', 0, 'Se especifica la acción a realizar, los datos que deben registrarse y el propósito del requisito.', 1),
(1, 'El sistema debe permitir a los usuarios subir archivos de hasta 10 MB en formatos PDF, DOCX o JPG.', 0, 'Se establece los límites de tamaño y los formatos permitidos.', 1),
(1, 'Los administradores deben poder modificar el estado de una orden (pendiente, procesando, completada, cancelada) desde el panel de control.', 0, 'Se define las opciones disponibles y el alcance de la acción administrativa.', 1),
(1, 'El sistema debe notificar al usuario sobre la caducidad de su suscripción con 7 días de anticipación mediante una alerta en la interfaz y un correo electrónico.', 0, 'Se especifica cuándo y cómo se envían las notificaciones, así como el canal utilizado.', 1),
(1, 'El sistema debe procesar las solicitudes de los usuarios con un tiempo de respuesta menor a 3 segundos en condiciones normales de operación.', 0, 'Se define un límite medible y específico para evaluar el rendimiento.', 0),
(1, 'La base de datos debe poder almacenar hasta 10 millones de registros de usuarios sin degradación significativa del rendimiento.', 0, 'Se establece un volumen de datos específico y el criterio para medir el rendimiento.', 0),
(1, 'Todas las comunicaciones entre el cliente y el servidor deben estar protegidas mediante el protocolo HTTPS.', 0, 'Se especifica el estándar de seguridad requerido para las comunicaciones.', 0),
(1, 'El sistema debe realizar copias de seguridad completas de la base de datos cada 24 horas y almacenarlas en un servidor externo.', 0, 'Se define la frecuencia, el alcance y el lugar de almacenamiento de las copias de seguridad.', 0),
(1, 'El diseño de la interfaz debe ajustarse automáticamente a pantallas de tamaño entre 4 y 12 pulgadas sin pérdida de funcionalidad.', 0, 'Se define el rango de tamaños y el criterio de éxito (sin pérdida de funcionalidad).', 0),
(1, 'El sistema debe ser capaz de soportar hasta 500 solicitudes concurrentes sin que el tiempo de respuesta promedio supere los 2 segundos', 0, 'Se especifica un límite concreto de solicitudes concurrentes y un tiempo máximo aceptable para las respuestas, lo que permite su evaluación precisa.', 0),
(1, 'El sistema debe permitir a los usuarios completar el registro en un tiempo razonable según las mejores prácticas de experiencia de usuario.', 1, 'No define qué se considera "tiempo razonable" ni qué prácticas específicas se están tomando como referencia.', 1),
(1, 'El sistema debe enviar notificaciones automáticas a los usuarios en función de su actividad reciente y sus preferencias configuradas.', 1, 'No especifica qué se entiende por "actividad reciente" ni qué tipos de notificaciones o preferencias están incluidas.', 1),
(1, 'El sistema debe permitir búsquedas inteligentes y relevantes que ofrezcan resultados acordes con las necesidades del usuario.', 1, 'No explica qué significa "inteligentes", "relevantes" o "necesidades del usuario", ni cómo se miden.', 1),
(1, 'El sistema debe proteger los datos del usuario conforme a los estándares aplicables en la industria tecnológica.', 1, 'No especifica cuáles son los estándares aplicables ni qué nivel de protección se espera.', 1),
(1, 'El sistema debe tener un diseño visual optimizado para garantizar una experiencia agradable en cualquier dispositivo.', 1, 'No explica qué significa "optimizado" o "agradable", ni cómo se evalúa en diferentes dispositivos.', 0),
(1, 'El sistema debe garantizar tiempos de respuesta acordes con los estándares esperados en aplicaciones modernas.', 1, 'No especifica cuáles son esos "estándares esperados" ni el rango de tiempo considerado aceptable.', 0),
(1, 'El sistema debe ser accesible en múltiples plataformas para brindar soporte a una amplia variedad de usuarios.', 1, 'No detalla cuáles son esas "múltiples plataformas" ni define qué abarca una "amplia variedad de usuarios".', 0),
(1, 'El sistema debe contar con medidas avanzadas de seguridad para prevenir accesos no autorizados.', 1, 'No explica qué son "medidas avanzadas" ni cómo se define o verifica el éxito en la prevención de accesos no autorizados.', 0);

INSERT INTO requisitos_clasificacion_partida (id_requisito, id_partida) VALUES
-- Requisitos utilizados en la clasificacion
(1, 1),
(2, 1),
(3, 1),
(18, 1),
(19, 1),
(20, 1);

-- Insertar requisitos para construir
INSERT INTO requisitos_construccion (id_usuario_creador, requisito_completo, nivel_dificultad) VALUES
(1, 'Los administradores deben poder generar reportes semanales de ventas en formato CSV o PDF.', 1),
(1, 'El sistema debe registrar automáticamente la fecha y hora de cada transacción realizada por los usuarios.', 1),
(1, 'Los usuarios deben poder cambiar el idioma de la interfaz entre inglés, español y francés desde la configuración.', 1),
(1, 'El sistema debe mostrar un mensaje de error si el usuario intenta ingresar una contraseña que no cumpla con los requisitos de seguridad.', 1),
(1, 'El sistema debe permitir la eliminación de cuentas por parte de los usuarios, previa confirmación mediante un código enviado a su correo.', 1),
(1, 'El sistema debe ser capaz de procesar hasta 10,000 transacciones simultáneas sin errores de rendimiento.', 1),
(1, 'La interfaz debe cargar completamente en menos de 2 segundos en conexiones de internet de al menos 10 Mbps.', 1),
(1, 'El sistema debe almacenar los datos de usuario en una base de datos cifrada utilizando AES-256.', 1),
(1, 'El diseño debe ser completamente adaptable a dispositivos móviles con pantallas de entre 4 y 10 pulgadas.', 1),
(1, 'Las notificaciones enviadas por el sistema deben ser entregadas en un tiempo máximo de 5 segundos después del evento que las genera.', 1),
(1, 'El sistema debe ser compatible con navegadores modernos como Chrome, Firefox, Safari y Edge, en sus últimas dos versiones.', 1),
(1, 'Las pruebas de carga del sistema deben demostrar que soporta al menos 50,000 usuarios activos por hora sin degradación del rendimiento.', 1),
(1, 'El sistema debe realizar un respaldo completo de la base de datos cada 24 horas y almacenar los archivos de respaldo durante 30 días.', 1),
(1, 'La documentación técnica del sistema debe estar disponible en inglés y español y cumplir con el estándar IEEE 1063.', 1),
(1, 'El sistema debe estar disponible el 99.99% del tiempo, con un tiempo de inactividad programado no mayor a 1 hora por mes.', 1),
(1, 'El sistema debe permitir a los usuarios crear una cuenta ingresando su nombre, correo electrónico y contraseña.', 1),
(1, 'El sistema debe permitir a los administradores asignar roles específicos a los usuarios registrados.', 1),
(1, 'Los usuarios deben poder filtrar los resultados de búsqueda por categoría, rango de precios y disponibilidad.', 1),
(1, 'El sistema debe enviar un correo electrónico de confirmación después de que el usuario realice un pedido exitoso.', 1),
(1, 'El sistema debe permitir a los usuarios restablecer su contraseña a través de un enlace enviado a su correo electrónico.', 1);

-- Insertar fragmentos para construir
INSERT INTO fragmentos_requisito (id_requisito, texto, posicion_correcta, es_señuelo) VALUES
-- Fragmentos correctos para el Requisito 1
(1, 'Los administradores', 1, FALSE),
(1, 'deben poder generar', 2, FALSE),
(1, 'reportes semanales', 3, FALSE),
(1, 'de ventas', 4, FALSE),
(1, 'en formato CSV o PDF', 5, FALSE),
-- Señuelos para el Requisito 1
(1, 'informes resumidos', NULL, TRUE),
(1, 'datos relacionados', NULL, TRUE),
(1, 'en cualquier formato', NULL, TRUE),

-- Fragmentos correctos para el Requisito 2
(2, 'El sistema', 1, FALSE),
(2, 'debe registrar', 2, FALSE),
(2, 'automáticamente', 3, FALSE),
(2, 'la fecha y hora', 4, FALSE),
(2, 'de cada transacción', 5, FALSE),
(2, 'realizada por los usuarios', 6, FALSE),
-- Señuelos para el Requisito 2
(2, 'los eventos principales', NULL, TRUE),
(2, 'información general', NULL, TRUE),
(2, 'a discreción del usuario', NULL, TRUE),

-- Fragmentos correctos para el Requisito 3
(3, 'Los usuarios', 1, FALSE),
(3, 'deben poder cambiar', 2, FALSE),
(3, 'el idioma de la interfaz', 3, FALSE),
(3, 'entre inglés, español y francés', 4, FALSE),
(3, 'desde la configuración', 5, FALSE),
-- Señuelos para el Requisito 3
(3, 'a cualquier idioma disponible', NULL, TRUE),
(3, 'sin restricciones específicas', NULL, TRUE),
(3, 'de manera automática', NULL, TRUE),

-- Fragmentos correctos para el Requisito 4
(4, 'El sistema', 1, FALSE),
(4, 'debe mostrar un mensaje', 2, FALSE),
(4, 'de error', 3, FALSE),
(4, 'si el usuario intenta ingresar', 4, FALSE),
(4, 'una contraseña que no cumpla', 5, FALSE),
(4, 'con los requisitos de seguridad', 6, FALSE),
-- Señuelos para el Requisito 4
(4, 'una contraseña débil', NULL, TRUE),
(4, 'un valor no válido', NULL, TRUE),
(4, 'sin detalles específicos', NULL, TRUE),

-- Fragmentos correctos para el Requisito 5
(5, 'El sistema', 1, FALSE),
(5, 'debe permitir la eliminación', 2, FALSE),
(5, 'de cuentas', 3, FALSE),
(5, 'por parte de los usuarios', 4, FALSE),
(5, 'previa confirmación', 5, FALSE),
(5, 'mediante un código enviado a su correo', 6, FALSE),
-- Señuelos para el Requisito 5
(5, 'de forma opcional', NULL, TRUE),
(5, 'directamente sin confirmación', NULL, TRUE),
(5, 'solo bajo ciertas condiciones', NULL, TRUE),

-- Fragmentos correctos para el Requisito 6
(6, 'El sistema', 1, FALSE),
(6, 'debe ser capaz de procesar', 2, FALSE),
(6, 'hasta 10,000 transacciones simultáneas', 3, FALSE),
(6, 'sin errores de rendimiento', 4, FALSE),
-- Señuelos para el Requisito 6
(6, 'hasta un número razonable', NULL, TRUE),
(6, 'un número aproximado de transacciones', NULL, TRUE),
(6, 'con posibles interrupciones', NULL, TRUE),

-- Fragmentos correctos para el Requisito 7
(7, 'La interfaz', 1, FALSE),
(7, 'debe cargar completamente', 2, FALSE),
(7, 'en menos de 2 segundos', 3, FALSE),
(7, 'en conexiones de al menos 10 Mbps', 4, FALSE),
-- Señuelos para el Requisito 7
(7, 'en la mayoría de los casos', NULL, TRUE),
(7, 'sin un tiempo exacto garantizado', NULL, TRUE),
(7, 'dependiendo de las condiciones', NULL, TRUE),

-- Fragmentos correctos para el Requisito 8
(8, 'El sistema', 1, FALSE),
(8, 'debe almacenar los datos de usuario', 2, FALSE),
(8, 'en una base de datos cifrada', 3, FALSE),
(8, 'utilizando AES-256', 4, FALSE),
-- Señuelos para el Requisito 8
(8, 'en un formato genérico', NULL, TRUE),
(8, 'con un método de cifrado estándar', NULL, TRUE),
(8, 'en servidores externos no garantizados', NULL, TRUE),

-- Fragmentos correctos para el Requisito 9
(9, 'El diseño', 1, FALSE),
(9, 'debe ser completamente adaptable', 2, FALSE),
(9, 'a dispositivos móviles', 3, FALSE),
(9, 'con pantallas de entre 4 y 10 pulgadas', 4, FALSE),
-- Señuelos para el Requisito 9
(9, 'a cualquier tipo de dispositivo', NULL, TRUE),
(9, 'sin considerar el tamaño de pantalla', NULL, TRUE),
(9, 'con un diseño parcialmente ajustable', NULL, TRUE),

-- Fragmentos correctos para el Requisito 10
(10, 'Las notificaciones enviadas por el sistema', 1, FALSE),
(10, 'deben ser entregadas', 2, FALSE),
(10, 'en un tiempo máximo de 5 segundos', 3, FALSE),
(10, 'después del evento que las genera', 4, FALSE),
-- Señuelos para el Requisito 10
(10, 'tan rápido como sea posible', NULL, TRUE),
(10, 'sin un tiempo definido', NULL, TRUE),
(10, 'dependiendo del tipo de notificación', NULL, TRUE),

-- Fragmentos correctos para el Requisito 11
(11, 'El sistema', 1, FALSE),
(11, 'debe ser compatible', 2, FALSE),
(11, 'con navegadores modernos', 3, FALSE),
(11, 'como Chrome, Firefox, Safari y Edge', 4, FALSE),
(11, 'en sus últimas dos versiones', 5, FALSE),
-- Señuelos para el Requisito 11
(11, 'con los navegadores más utilizados.', NULL, TRUE),
(11, 'sin restricciones de versiones', NULL, TRUE),
(11, 'en la mayoría de los navegadores recientes', NULL, TRUE),

-- Fragmentos correctos para el Requisito 12
(12, 'Las pruebas de carga del sistema', 1, FALSE),
(12, 'deben demostrar que soporta', 2, FALSE),
(12, 'al menos 50,000 usuarios activos por hora', 3, FALSE),
(12, 'sin degradación del rendimiento', 4, FALSE),
-- Señuelos para el Requisito 12
(12, 'una cantidad promedio de usuarios', NULL, TRUE),
(12, 'con un nivel aceptable de rendimiento', NULL, TRUE),
(12, 'sin un número exacto de usuarios', NULL, TRUE),

-- Fragmentos correctos para el Requisito 13
(13, 'El sistema', 1, FALSE),
(13, 'debe realizar un respaldo completo', 2, FALSE),
(13, 'de la base de datos', 3, FALSE),
(13, 'cada 24 horas', 4, FALSE),
(13, 'y almacenar los archivos de respaldo', 5, FALSE),
(13, 'durante 30 días',6, FALSE),
-- Señuelos para el Requisito 13
(13, 'de manera periódica', NULL, TRUE),
(13, 'con una retención ajustable', NULL, TRUE),
(13, 'un tiempo considerable', NULL, TRUE),

-- Fragmentos correctos para el Requisito 14
(14, 'La documentación técnica del sistema', 1, FALSE),
(14, 'debe estar disponible', 2, FALSE),
(14, 'en inglés y español', 3, FALSE),
(14, 'y cumplir con el estándar IEEE 1063.', 4, FALSE),
-- Señuelos para el Requisito 14
(14, 'en varios idiomas relevantes', NULL, TRUE),
(14, 'con una estructura adaptada', NULL, TRUE),
(14, 'según los requerimientos del cliente', NULL, TRUE),

-- Fragmentos correctos para el Requisito 15
(15, 'El sistema', 1, FALSE),
(15, 'debe estar disponible el 99.99% del tiempo', 2, FALSE),
(15, 'con un tiempo de inactividad programado', 3, FALSE),
(15, 'no mayor a 1 hora por mes', 4, FALSE),
-- Señuelos para el Requisito 15
(15, 'con una alta disponibilidad promedio', NULL, TRUE),
(15, 'sin especificar el tiempo máximo de inactividad', NULL, TRUE),
(15, 'con un tiempo razonanle', NULL, TRUE),

-- Fragmentos correctos para el Requisito 16
(16, 'El sistema', 1, FALSE),
(16, 'debe permitir', 2, FALSE),
(16, 'a los usuarios', 3, FALSE),
(16, 'crear una cuenta ingresando', 4, FALSE),
(16, 'su nombre, correo electrónico y', 5, FALSE),
(16, 'contraseña', 6, FALSE),
-- Señuelos para el Requisito 16
(16, 'solo su identificador único', NULL, TRUE),
(16, 'a todos', NULL, TRUE),
(16, 'con requisitos específicos', NULL, TRUE),

-- Fragmentos correctos para el Requisito 17
(17, 'El sistema', 1, FALSE),
(17, 'debe permitir a los administradores', 2, FALSE),
(17, 'asignar roles específicos', 3, FALSE),
(17, 'a los usuarios registrados', 4, FALSE),
-- Señuelos para el Requisito 17
(17, 'establecer permisos generales', NULL, TRUE),
(17, 'sin diferenciar entre roles', NULL, TRUE),
(17, 'a usuarios seleccionados automáticamente', NULL, TRUE),

-- Fragmentos correctos para el Requisito 18
(18, 'Los usuarios', 1, FALSE),
(18, 'deben poder filtrar', 2, FALSE),
(18, 'los resultados de búsqueda', 3, FALSE),
(18, 'por categoría, rango de precios', 4, FALSE),
(18, 'y disponibilidad', 5, FALSE),
-- Señuelos para el Requisito 18
(18, 'por cualquier criterio disponible', NULL, TRUE),
(18, 'basándose en sus preferencias guardadas', NULL, TRUE),
(18, 'sin criterios de filtro establecidos', NULL, TRUE),

-- Fragmentos correctos para el Requisito 19
(19, 'El sistema', 1, FALSE),
(19, 'debe enviar un correo electrónico', 2, FALSE),
(19, 'de confirmación', 3, FALSE),
(19, 'después de que el usuario realice', 4, FALSE),
(19, 'un pedido exitoso', 5, FALSE),
-- Señuelos para el Requisito 19
(19, 'una notificación sin formato definido', NULL, TRUE),
(19, 'solo en ciertos casos', NULL, TRUE),
(19, 'sin especificar un pedido exitoso', NULL, TRUE),

-- Fragmentos correctos para el Requisito 20
(20, 'El sistema', 1, FALSE),
(20, 'debe permitir a los usuarios', 2, FALSE),
(20, 'restablecer su contraseña', 3, FALSE),
(20, 'a través de un enlace enviado', 4, FALSE),
(20, 'a su correo electrónico', 5, FALSE),
-- Señuelos para el Requisito 20
(20, 'usando una pregunta de seguridad', NULL, TRUE),
(20, 'por medio de un código temporal', NULL, TRUE),
(20, 'sin requerir un enlace de confirmación', NULL, TRUE);

INSERT INTO requisitos_construccion_partida (id_requisito, id_partida) VALUES
-- Requisitos utilizados en la construccion
(9, 2),
(15, 2),
(17, 2),
(20, 2);
