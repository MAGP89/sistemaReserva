<?php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'sistemareserva';

try {
    $conn = new PDO("mysql:host=$host;dbname=$database", $user, $password);
    // Establece el modo de error PDO a excepción
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    //echo "Connected successfully";
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}

// Crea una clase para manejar las solicitudes del servidor web
class RequestHandler {

    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    // Maneja la solicitud GET 
	public function do_GET_one() {
		// Define la consulta SELECT que se usará para obtener los datos del usuario
		$sql = 'SELECT * FROM usuarios';
	
		// Valida los parámetros que se envían en la solicitud
		$filters = [];
		if (!empty($_GET)) {
			$validFields = ['id' => true, 'usuario' => true, 'nivel' => true];
			foreach ($_GET as $field => $value) {
				if (array_key_exists($field, $validFields)) {
					$filters[$field] = $value;
				} else {
					// Si el parámetro no es válido, se devuelve un error
					http_response_code(400);
					header('Content-Type: application/json');
					$response = ['error' => 'Invalid field in query string'];
					echo json_encode($response);
					return;
				}
			}
		}
		if (!empty($filters)) {
			$where = ' WHERE ';
			$conditions = [];
			foreach ($filters as $field => $value) {
				$conditions[] = "$field = :$field";
			}
			$where .= implode(' AND ', $conditions);
			$sql .= $where;
		}
	
		// Ejecuta la consulta a la base de datos MySQL
		$stmt = $this->conn->prepare($sql);
		foreach ($filters as $field => $value) {
			$stmt->bindValue(":$field", $value);
		}
		$stmt->execute();
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
		// Devuelve los resultados de la consulta como una respuesta JSON
		http_response_code(200);
		header('Content-Type: application/json');
		$response = $results;
		echo json_encode($response);
	}

	public function do_GET_all() {
		// Ejecuta una consulta SELECT que obtiene todos los usuarios de la base de datos
		$stmt = $this->conn->prepare('SELECT * FROM usuarios');
		$stmt->execute();
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

		// Establece el código de estado y el tipo de contenido de la respuesta
		http_response_code(200);
		header('Content-Type: application/json');

		// Devuelve la respuesta
		echo json_encode($results);
	}

	public function do_GET_version() {
		// Define la respuesta que se devolverá
		$response = ['version' => 'API Sistema Reserva'];

		// Establece el código de estado y el tipo de contenido de la respuesta
		http_response_code(200);
		header('Content-Type: application/json');

		// Devuelve la respuesta
		echo json_encode($response);
	}

	// Maneja la solicitud POST
	public function do_POST($data) {
		// Agrega un nuevo usuario a la base de datos
		$stmt = $this->conn->prepare("INSERT INTO Usuarios (nombre, usuario, clave, nivel) VALUES (:nombre, :usuario, :clave, :nivel)");
		$stmt->bindParam(':nombre', $data['nombre']);
		$stmt->bindParam(':usuario', $data['usuario']);
		$stmt->bindParam(':clave', $data['clave']); // Se ha actualizado el nombre del campo a "clave"
		$stmt->bindParam(':nivel', $data['nivel']);
		// Ejecuta la consulta y comprueba si ha sido correcta
		if ($stmt->execute()) {
			// Obtiene el ID del usuario agregado
			$id = $this->conn->lastInsertId();
			// Crea la respuesta en formato JSON
			$response = [
				'mensaje' => 'Usuario agregado con éxito.',
				'status' => '200',
				'id' => $id,
				'nombre' => $data['nombre'],
				'usuario' => $data['usuario'],
				'clave' => $data['clave'],
				'nivel' => $data['nivel'],
			];
		} else {
			// Crea la respuesta en formato JSON
			$response = [
				'mensaje' => 'Error al agregar el usuario.',
				'status' => '500',
			];
		}
	
		// Establece el código de respuesta HTTP y devuelve la respuesta en formato JSON
		http_response_code($response['status']);
		header('Content-Type: application/json');
		echo json_encode($response);
	}

	public function agregarHoraReserva($data) {
		// Agrega una nueva hora de reserva a la base de datos
		$stmt = $this->conn->prepare("INSERT INTO horasreservadas (id_usuario, fecha, hora_inicio, hora_fin, estado) VALUES (:id_usuario, :fecha, :hora_inicio, :hora_fin, :estado)");
		$stmt->bindParam(':id_usuario', $data['id_usuario']);
		$stmt->bindParam(':fecha', $data['fecha']);
		$stmt->bindParam(':hora_inicio', $data['hora_inicio']);
		$stmt->bindParam(':hora_fin', $data['hora_fin']);
		$stmt->bindParam(':estado', $data['estado']);
		// Ejecuta la consulta y comprueba si ha sido correcta
		if ($stmt->execute()) {
			// Obtiene el ID de la hora de reserva agregada
			$id = $this->conn->lastInsertId();
			// Crea la respuesta en formato JSON
			$response = [
				'mensaje' => 'Hora de reserva agregada con éxito.',
				'status' => '200',
				'id' => $id,
				'id_usuario' => $data['id_usuario'],
				'fecha' => $data['fecha'],
				'hora_inicio' => $data['hora_inicio'],
				'hora_fin' => $data['hora_fin'],
				'estado' => $data['estado'],
			];
		} else {
			// Crea la respuesta en formato JSON
			$response = [
				'mensaje' => 'Error al agregar la hora de reserva.',
				'status' => '500',
			];
		}
	
		// Establece el código de respuesta HTTP y devuelve la respuesta en formato JSON
		http_response_code($response['status']);
		header('Content-Type: application/json');
		echo json_encode($response);
	}
	
    // Maneja la solicitud PUT
	public function do_PUT($data) {
		// Actualiza la información de un usuario en la base de datos
		$stmt = $this->conn->prepare("UPDATE usuarios SET nombre = :nombre, usuario = :usuario, clave = :clave, nivel = :nivel WHERE id = :id");
		$stmt->bindParam(':nombre', $data['nombre']);
		$stmt->bindParam(':usuario', $data['usuario']);
		$stmt->bindParam(':clave', $data['clave']);
		$stmt->bindParam(':nivel', $data['nivel']);
		$stmt->bindParam(':id', $data['id']);
	
		// Ejecuta la consulta y comprueba si ha sido correcta
		if ($stmt->execute()) {
			// Obtiene la información del usuario actualizado
			$stmt = $this->conn->prepare("SELECT id, nombre, usuario, clave, nivel FROM usuarios WHERE id = :id");
			$stmt->bindParam(':id', $data['id']);
			$stmt->execute();
			$user = $stmt->fetch();
	
			// Crea la respuesta en formato JSON
			$response = [
				'mensaje' => 'Usuario actualizado con éxito.',
				'status' => '200',
				'id' => $user['id'],
				'nombre' => $user['nombre'],
				'usuario' => $user['usuario'],
				'clave' => $user['clave'],
				'nivel' => $user['nivel'],
			];
		} else {
			// Crea la respuesta en formato JSON
			$response = [
				'mensaje' => 'Error al actualizar el usuario.',
				'status' => '500',
			];
		}
		// Establece el código de respuesta HTTP y devuelve la respuesta en formato JSON
		http_response_code(intval($response['status']));
		header('Content-Type: application/json');
		echo json_encode($response);
	}

	public function updateHoraReserva($data) {
		// Actualiza la información de una hora reservada en la base de datos
		$stmt = $this->conn->prepare("UPDATE horasreservadas SET id_usuario = :id_usuario, fecha = :fecha, hora_inicio = :hora_inicio, hora_fin = :hora_fin, estado = :estado WHERE id = :id");
		$stmt->bindParam(':id_usuario', $data['id_usuario']);
		$stmt->bindParam(':fecha', $data['fecha']);
		$stmt->bindParam(':hora_inicio', $data['hora_inicio']);
		$stmt->bindParam(':hora_fin', $data['hora_fin']);
		$stmt->bindParam(':estado', $data['estado']);
		$stmt->bindParam(':id', $data['id']);
	
		// Ejecuta la consulta y comprueba si ha sido correcta
		if ($stmt->execute()) {
			// Obtiene la información de la hora reservada actualizada
			$stmt = $this->conn->prepare("SELECT id, id_usuario, fecha, hora_inicio, hora_fin, estado FROM horasreservadas WHERE id = :id");
			$stmt->bindParam(':id', $data['id']);
			$stmt->execute();
			$horaReservada = $stmt->fetch();
	
			// Crea la respuesta en formato JSON
			$response = [
				'mensaje' => 'Hora reservada actualizada con éxito.',
				'status' => '200',
				'id' => $horaReservada['id'],
				'id_usuario' => $horaReservada['id_usuario'],
				'fecha' => $horaReservada['fecha'],
				'hora_inicio' => $horaReservada['hora_inicio'],
				'hora_fin' => $horaReservada['hora_fin'],
				'estado' => $horaReservada['estado'],
			];
		} else {
			// Crea la respuesta en formato JSON
			$response = [
				'mensaje' => 'Error al actualizar la hora reservada.',
				'status' => '500',
			];
		}
		// Establece el código de respuesta HTTP y devuelve la respuesta en formato JSON
		http_response_code(intval($response['status']));
		header('Content-Type: application/json');
		echo json_encode($response);
	}

	// Maneja la solicitud DELETE
	public function do_DELETE($data) {
		// Comprueba si se ha proporcionado un parámetro 'id' en la solicitud
		if (isset($data['id'])) {
			// Obtiene los datos del usuario a eliminar
			$stmt = $this->conn->prepare("SELECT id, nombre, usuario, clave, nivel FROM Usuarios WHERE id = :id");
			$stmt->bindParam(':id', $data['id']);
			$stmt->execute();
			$user = $stmt->fetch(PDO::FETCH_ASSOC);
	
			// Elimina el usuario de la base de datos
			$stmt = $this->conn->prepare("DELETE FROM Usuarios WHERE id = :id");
			$stmt->bindParam(':id', $data['id']);
	
			// Ejecuta la consulta y comprueba si ha sido correcta
			if ($stmt->execute()) {
				// Crea la respuesta en formato JSON
				$response = [
					'mensaje' => 'Usuario eliminado con éxito.',
					'status' => '200',
					'id' => $user['id'],
					'nombre' => $user['nombre'],
					'usuario' => $user['usuario'],
					'clave' => $user['clave'],
					'nivel' => $user['nivel'],
				];
			} else {
				// Crea la respuesta en formato JSON
				$response = [
					'mensaje' => 'Error al eliminar el usuario.',
					'status' => '500',
				];
			}
		} else {
			// Si no se ha proporcionado un parámetro 'id', devuelve un error
			$response = [
				'mensaje' => 'Falta el parámetro id en la solicitud.',
				'status' => '400',
			];
		}
	
		// Establece el código de respuesta HTTP y devuelve la respuesta en formato JSON
		http_response_code($response['status']);
		header('Content-Type: application/json');
		echo json_encode($response);
	}
	
	public function deleteHoraReservada($data) {
		// Comprueba si se ha proporcionado un parámetro 'id' en la solicitud
		if (isset($data['id'])) {
			// Obtiene los datos de la hora reservada a eliminar
			$stmt = $this->conn->prepare("SELECT id, id_usuario, fecha, hora_inicio, hora_fin, estado FROM horasreservadas WHERE id = :id");
			$stmt->bindParam(':id', $data['id']);
			$stmt->execute();
			$horaReservada = $stmt->fetch(PDO::FETCH_ASSOC);
	
			// Elimina la hora reservada de la base de datos
			$stmt = $this->conn->prepare("DELETE FROM horasreservadas WHERE id = :id");
			$stmt->bindParam(':id', $data['id']);
	
			// Ejecuta la consulta y comprueba si ha sido correcta
			if ($stmt->execute()) {
				// Crea la respuesta en formato JSON
				$response = [
					'mensaje' => 'Hora reservada eliminada con éxito.',
					'status' => '200',
					'id' => $horaReservada['id'],
					'id_usuario' => $horaReservada['id_usuario'],
					'fecha' => $horaReservada['fecha'],
					'hora_inicio' => $horaReservada['hora_inicio'],
					'hora_fin' => $horaReservada['hora_fin'],
					'estado' => $horaReservada['estado'],
				];
			} else {
				// Crea la respuesta en formato JSON
				$response = [
					'mensaje' => 'Error al eliminar la hora reservada.',
					'status' => '500',
				];
			}
		} else {
			// Si no se ha proporcionado un parámetro 'id', devuelve un error
			$response = [
				'mensaje' => 'Falta el parámetro id en la solicitud.',
				'status' => '400',
			];
		}
	
		// Establece el código de respuesta HTTP y devuelve la respuesta en formato JSON
		http_response_code($response['status']);
		header('Content-Type: application/json');
		echo json_encode($response);
	}
}

// Crea una instancia de la clase RequestHandler
$requestHandler = new RequestHandler($conn);

// Obtiene el método de la solicitud
$method = $_SERVER['REQUEST_METHOD'];

// Obtiene el endpoint y los parámetros de la solicitud
$parsed_url = parse_url($_SERVER['REQUEST_URI']);
$parsed_query = isset($parsed_url['query']) ? $parsed_url['query'] : null;

// Obtiene el payload de la solicitud
$data = json_decode(file_get_contents('php://input'), true);

$parsed_query = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
// Selecciona qué método debe ejecutar el RequestHandler según el método de la solicitud
switch ($method) {
	case 'GET':
        // Obtiene el query string de la solicitud
        $query_string = isset($parsed_url['query']) ? $parsed_url['query'] : null;
        
        if ((!empty($_GET) && $parsed_url['path'] === '/api/index.php/user') || (empty($_GET) && $parsed_url['path'] === '/api/index.php/users')) {
            // Si hay parámetros en la solicitud y el endpoint es '/user', o si no hay parámetros y el endpoint es '/users', se usa do_GET_one
            // Valida los campos permitidos en el query string
            $validFields = ['id', 'usuario', 'nivel'];
            $filters = [];
            parse_str($query_string, $params);
            foreach ($params as $field => $value) {
                if (in_array($field, $validFields)) {
                    $filters[$field] = $value;
                } else {
                    // Si el campo no es válido, devuelve un error
                    http_response_code(400);
                    header('Content-Type: application/json');
                    $response = ['error' => 'Invalid field in query string'];
                    echo json_encode($response);
                    return;
                }
            }
            
            // Ejecuta do_GET_one con los filtros
            $requestHandler->do_GET_one($filters);
        } elseif ($parsed_url['path'] === '/api/index.php/version') {
            // Si no hay parámetros en la solicitud y el endpoint es '/version', se usa do_GET_version
            $requestHandler->do_GET_version();
        } elseif ($parsed_url['path'] === '/api/index.php/users' || $parsed_url['path'] === '/api/index.php/user' || $parsed_url['path'] === '/api/index.php/version') {
            // Si no hay parámetros en la solicitud y el endpoint no es '/version', se usa do_GET_all
            $requestHandler->do_GET_all();
        } else {
            // Si el path de la solicitud no es '/users', '/user' o '/version', devuelve un error
            http_response_code(400);
            header('Content-Type: application/json');
            $response = ['error' => 'Invalid URL'];
            echo json_encode($response);
        }
        break;
    
	case 'POST':
		if ($parsed_url['path'] === '/api/index.php/addUser') {
		$requestHandler->do_POST($data);
		} elseif ($parsed_url['path'] === '/api/index.php/addHoraReserva') {
		$requestHandler->agregarHoraReserva($data);
		}
	break;

	case 'PUT':
		if ($parsed_url['path'] === '/api/index.php/updateUser') {
			$requestHandler->do_PUT($data);
		} elseif ($parsed_url['path'] === '/api/index.php/updateHoraReserva') {
			$requestHandler->updateHoraReserva($data);
		}
		break;

	case 'DELETE':
		if ($parsed_url['path'] === '/api/index.php/deleteUser') {
			$requestHandler->do_DELETE($data);
		} elseif ($parsed_url['path'] === '/api/index.php/deleteHoraReservada') {
			$requestHandler->deleteHoraReservada($data);
		}
		break;

    default:
        // Si el método de la solicitud no es válido, se devuelve un error
        http_response_code(405);
        header('Content-Type: application/json');
        $response = ['error' => 'Invalid method'];
        echo json_encode($response);
        break;
}