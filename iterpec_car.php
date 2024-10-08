<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de Aluguel de Carros</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            width: 100%;
            background-color: #fff;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h2 {
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            color: #007bff;
        }
        form {
            margin-bottom: 30px;
        }
        label {
            font-weight: bold;
            margin-top: 10px;
            display: block;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            margin: 5px 0 15px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            background-color: #007bff;
            color: white;
            cursor: pointer;
            border: none;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <form method="POST" action="process-carro.php?action=rentACarSearch">
            <h2>Pesquisar Aluguel de Carros</h2>
            <label for="pickup_date">Data de Retirada:</label>
            <input type="date" id="pickup_date" name="pickup_date" required>

            <label for="pickup_hour">Hora de Retirada:</label>
            <input type="number" id="pickup_hour" name="pickup_hour" min="0" max="23" required>

            <label for="pickup_minute">Minutos de Retirada:</label>
            <input type="number" id="pickup_minute" name="pickup_minute" min="0" max="59" required>

            <label for="pickup_location">Código do Local de Retirada:</label>
            <input type="text" id="pickup_location" name="pickup_location" required>

            <label for="pickup_location_type">Tipo de Local de Retirada:</label>
            <input type="text" id="pickup_location_type" name="pickup_location_type" required>

            <label for="dropoff_date">Data de Devolução:</label>
            <input type="date" id="dropoff_date" name="dropoff_date" required>

            <label for="dropoff_hour">Hora de Devolução:</label>
            <input type="number" id="dropoff_hour" name="dropoff_hour" min="0" max="23" required>

            <label for="dropoff_minute">Minutos de Devolução:</label>
            <input type="number" id="dropoff_minute" name="dropoff_minute" min="0" max="59" required>

            <label for="dropoff_location">Código do Local de Devolução (opcional):</label>
            <input type="text" id="dropoff_location" name="dropoff_location">

            <label for="dropoff_location_type">Tipo de Local de Devolução (opcional):</label>
            <input type="text" id="dropoff_location_type" name="dropoff_location_type">

            <label for="sipp_code">Código SIPP (opcional):</label>
            <input type="text" id="sipp_code" name="sipp_code">

            <button type="submit">Pesquisar Carros</button>
        </form>
    </div>
</body>
</html>

<?php
// Função genérica para realizar requisições à API Iterpec com JSON usando wp_remote_post
function apiRequest($url, $data) {
    $response = wp_remote_post($url, array(
        'method'    => 'POST',
        'headers'   => array(
            'Content-Type' => 'application/json',
        ),
        'body'      => wp_json_encode($data),
        'timeout'   => 60,
    ));

    if (is_wp_error($response)) {
        return 'Erro ao realizar a requisição: ' . $response->get_error_message();
    }

    return json_decode(wp_remote_retrieve_body($response), true);
}

// Função para pesquisa de aluguel de carros
function rentACarSearch($data) {
    $url = 'https://ws-iterpec.cangooroo.net/API/REST/RentACar.svc/Search';
    return apiRequest($url, $data);
}

// Função para obter condições de reserva de aluguel de carros
function getRentACarBookingConditions($data) {
    $url = 'https://ws-iterpec.cangooroo.net/API/REST/RentACar.svc/getBookingConditions';
    return apiRequest($url, $data);
}

// Função para confirmar reserva de aluguel de carros
function doCarBooking($data) {
    $url = 'https://ws-iterpec.cangooroo.net/API/REST/RentACar.svc/DoBooking';
    return apiRequest($url, $data);
}

// Função para salvar os resultados da pesquisa de carros no banco de dados do WordPress
function saveCarSearchResults($results) {
    foreach ($results['Vehicles'] as $vehicle) {
        // Inserir um novo post personalizado para o veículo
        $post_id = wp_insert_post(array(
            'post_title'   => 'Aluguel de Carro: ' . $vehicle['VehicleModel'],
            'post_content' => 'Informações sobre o carro: ' . $vehicle['VehicleDescription'],
            'post_type'    => 'rent_a_car',
            'post_status'  => 'publish',
        ));

        // Atualizar meta informações do veículo
        update_post_meta($post_id, 'price', $vehicle['Price']);
        update_post_meta($post_id, 'currency', $vehicle['Currency']);
        update_post_meta($post_id, 'pickup_date', $_POST['pickup_date']);
        update_post_meta($post_id, 'dropoff_date', $_POST['dropoff_date']);
        update_post_meta($post_id, 'pickup_location', $_POST['pickup_location']);
        update_post_meta($post_id, 'dropoff_location', $_POST['dropoff_location']);
    }
}

// Lógica para processar as requisições de acordo com a ação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $credential = array(
        "Username" => "cdo.api",
        "Password" => "WSRl5gfYgZYn"
    );

    if (isset($_GET['action']) && $_GET['action'] == 'rentACarSearch') {
        // Dados da pesquisa de aluguel de carros
        $searchData = array(
            "Credential" => $credential,
            "Pickup" => array(
                "Date" => $_POST['pickup_date'],
                "Hour" => $_POST['pickup_hour'],
                "Minutes" => $_POST['pickup_minute'],
                "LocationCode" => $_POST['pickup_location'],
                "LocationType" => $_POST['pickup_location_type']
            ),
            "Dropoff" => array(
                "Date" => $_POST['dropoff_date'],
                "Hour" => $_POST['dropoff_hour'],
                "Minutes" => $_POST['dropoff_minute'],
                "LocationCode" => isset($_POST['dropoff_location']) ? $_POST['dropoff_location'] : null,
                "LocationType" => isset($_POST['dropoff_location_type']) ? $_POST['dropoff_location_type'] : null
            ),
            "SippCode" => isset($_POST['sipp_code']) ? $_POST['sipp_code'] : null
        );

        $result = rentACarSearch($searchData);

        if ($result && isset($result['Vehicles'])) {
            saveCarSearchResults($result);
        }

        echo "<h2>Resultado da Pesquisa de Aluguel de Carros:</h2>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";

    } elseif (isset($_GET['action']) && $_GET['action'] == 'getBookingConditions') {
        // Dados para obter condições de reserva de carro
        $data = array(
            "Credential" => $credential,
            "CarId" => $_POST['car_id'],
            "Token" => $_POST['token']
        );

        $result = getRentACarBookingConditions($data);

        echo "<h2>Condições de Reserva (Aluguel de Carro):</h2>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";

    } elseif (isset($_GET['action']) && $_GET['action'] == 'doBooking') {
        // Dados para confirmar a reserva de carro
        $data = array(
            "Credential" => $credential,
            "CarId" => $_POST['car_id'],
            "Token" => $_POST['token'],
            "PickupDate" => $_POST['pickup_date'],
            "DropoffDate" => $_POST['dropoff_date']
        );

        $result = doCarBooking($data);

        echo "<h2>Confirmação de Reserva (Aluguel de Carro):</h2>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    }
}
?>
