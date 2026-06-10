<?php
declare(strict_types=1);

$route = $_GET['r'] ?? $_GET['route'] ?? 'inicio';

$routes = [
	'inicio' => 'vistas/index.php',
	'index' => 'vistas/index.php',
	'home' => 'vistas/index.php',

	'mensajes' => 'vistas/panel_inspector.php',
	'panel-inspector' => 'vistas/panel_inspector.php',
	'panel_inspector' => 'vistas/panel_inspector.php',

	'novedades' => 'vistas/panel_admin.php',
	'panel-admin' => 'vistas/panel_admin.php',
	'panel_admin' => 'vistas/panel_admin.php',
	'crear_examen' => 'vistas/crear_examen.php',
	'crear-examen' => 'vistas/crear_examen.php',

	'servicios' => 'vistas/inscripcion_examen.php',
	'inscripcion-examen' => 'vistas/inscripcion_examen.php',
	'inscripcion_examen' => 'vistas/inscripcion_examen.php',

	'actividad-reciente' => 'vistas/actividad_reciente.php',
	'actividad_reciente' => 'vistas/actividad_reciente.php',

	'contactos' => 'vistas/subida_documentacion.php',
	'subida-documentacion' => 'vistas/subida_documentacion.php',
	'subida_documentacion' => 'vistas/subida_documentacion.php',

	'carnet-emitido' => 'vistas/carnet_emitido.php',
	'carnet_emitido' => 'vistas/carnet_emitido.php',

	'usuario-aprobado' => 'vistas/usuario_aprobado.php',
	'usuario_aprobado' => 'vistas/usuario_aprobado.php',

	'usuario-rechazado' => 'vistas/usuario_rechazado.php',
	'usuario_rechazado' => 'vistas/usuario_rechazado.php',
];

$target = $routes[$route] ?? $routes['inicio'];

$routePayloads = [
	'inicio' => [
		'page_title' => 'App Ciudadana - Inicio',
		'welcome_text' => 'Bienvenido de nuevo,',
		'user_name' => 'Juan Perez',
		'tramite_label' => 'Estado del Trámite',
		'tramite_title' => 'Carnet de Manipulador',
		'tramite_status' => 'PENDIENTE',
		'tramite_deadline' => 'Próximo vencimiento: 15/12/2025',
		'tramite_progress' => 'Paso 2 de 3: Evaluación Técnica',
		'documents' => [
			[
				'label' => 'Subir DNI',
				'icon' => 'badge',
				'route' => 'subida_documentacion',
				'state' => 1,
			],
			[
				'label' => 'Foto Carnet',
				'icon' => 'add_a_photo',
				'route' => 'subida_documentacion',
				'state' => 0,
			],
		],
		'exams' => [
			[
				'month' => 'OCT',
				'day' => '24',
				'title' => 'CRESTA',
				'time' => '09:00 AM',
				'place' => 'Aula 3',
				'available' => 1,
				'route' => 'inscripcion_examen',
			],
			[
				'month' => 'NOV',
				'day' => '08',
				'title' => 'Polideportivo Municipal',
				'time' => '02:00 PM',
				'place' => 'Salón B',
				'available' => 0,
				'route' => 'inscripcion_examen',
			],
		],
		'download_label' => 'Descargar Carnet',
		'download_route' => 'carnet_emitido',
		'download_enabled' => 1,
	],
	'inscripcion_examen' => [
		'page_title' => 'Inscripción a examen - App Ciudadana',
		'status_label' => 'Estado actual del trámite',
		'status_title' => 'Documentación Aprobada',
		'requirements_title' => 'Requisitos cumplidos',
		'requirements' => [
			[
				'label' => 'DNI cargado',
				'icon' => 'check',
				'state' => 1,
			],
			[
				'label' => 'Foto cargada',
				'icon' => 'check',
				'state' => 1,
			],
		],
		'exams_title' => 'Próximas fechas de examen',
		'exams' => [
			[
				'month' => 'OCT',
				'day' => '24',
				'title' => 'CRESTA',
				'capacity' => 1,
				'capacity_label' => 'CUPOS DISPONIBLES',
				'time' => '09:00 AM',
				'room' => 'Aula 4 - Planta Alta',
				'route' => 'inscripcion_examen',
			],
			[
				'month' => 'OCT',
				'day' => '28',
				'title' => 'Polideportivo Norte',
				'capacity' => 1,
				'capacity_label' => 'CUPOS DISPONIBLES',
				'time' => '14:30 PM',
				'room' => 'Salón de Usos Múltiples',
				'route' => 'inscripcion_examen',
			],
			[
				'month' => 'NOV',
				'day' => '02',
				'title' => 'Delegación Municipal',
				'capacity' => 0,
				'capacity_label' => 'SIN CUPOS',
				'time' => '10:00 AM',
				'room' => 'Aula 1 - Planta Baja',
				'route' => 'inscripcion_examen',
			],
		],
		'cta_text' => 'Inscribirse',
		'footer_note' => 'Recuerde presentarse 15 minutos antes con su DNI físico.',
		'footer_note_enabled' => 1,
	],
	'panel_admin' => [
		'page_title' => 'Panel Administrativo - App Ciudadana',
		'stats' => [
			[
				'label' => 'TOTAL INSCRIPTOS',
				'value' => '1,240',
				'icon' => 'groups',
				'style' => 'primary',
			],
			[
				'label' => 'APROBADOS',
				'value' => '850',
				'icon' => 'check_circle',
				'style' => 'success',
			],
			[
				'label' => 'RECHAZADOS',
				'value' => '120',
				'icon' => 'cancel',
				'style' => 'danger',
			],
			[
				'label' => 'CARNETS EMITIDOS',
				'value' => '730',
				'icon' => 'badge',
				'style' => 'secondary',
			],
		],
		'activities' => [
			[
				'nombre' => 'Juan Perez',
				'dni' => '35.849.201',
				'estado' => 'PENDIENTE',
				'estado_class' => 'pendiente',
			],
			[
				'nombre' => 'Maria Garcia',
				'dni' => '27.482.910',
				'estado' => 'PAGADO',
				'estado_class' => 'pagado',
			],
			[
				'nombre' => 'Carlos Rodriguez',
				'dni' => '31.902.115',
				'estado' => 'RECHAZADO',
				'estado_class' => 'rechazado',
			],
		],
	],
	'carnet-emitido' => [
		'numero' => '20-35849201-8',
		'titular' => 'Juan Perez',
		'fecha_emision' => '15/10/2023',
		'fecha_vencimiento' => '15/10/2025',
	],
	'carnet_emitido' => [
		'numero' => '20-35849201-8',
		'titular' => 'Juan Perez',
		'fecha_emision' => '15/10/2023',
		'fecha_vencimiento' => '15/10/2025',
	],
];

if (in_array($route, ['inicio', 'index', 'home'], true)) {
	$target .= '?data=' . rawurlencode(json_encode($routePayloads['inicio'], JSON_UNESCAPED_UNICODE));
	} elseif (in_array($route, ['inscripcion-examen', 'inscripcion_examen'], true)) {
	$target .= '?data=' . rawurlencode(json_encode($routePayloads['inscripcion_examen'], JSON_UNESCAPED_UNICODE));
	} elseif (in_array($route, ['novedades', 'panel-admin', 'panel_admin'], true)) {
	$target .= '?data=' . rawurlencode(json_encode($routePayloads['panel_admin'], JSON_UNESCAPED_UNICODE));
	} elseif (in_array($route, ['actividad-reciente', 'actividad_reciente'], true)) {
	$target .= '?data=' . rawurlencode(json_encode($routePayloads['panel_admin'], JSON_UNESCAPED_UNICODE));
	} elseif (isset($routePayloads[$route])) {
	$target .= '?' . http_build_query($routePayloads[$route]);
}

header('Location: ' . $target, true, 302);
exit;
