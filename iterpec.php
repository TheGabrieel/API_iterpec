<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulário de API Iterpec</title>
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
        <form method="POST" action="">
            <h2>Pesquisar Hotéis</h2>
            <label for="checkin_date">Data de Check-in:</label>
            <input type="date" id="checkin_date" name="checkin_date" required>

            <label for="destination_id">ID do Destino:</label>
            <input type="text" id="destination_id" name="destination_id" required>

            <label for="num_nights">Número de Noites:</label>
            <input type="number" id="num_nights" name="num_nights" required>

            <label for="num_adults">Número de Adultos:</label>
            <input type="number" id="num_adults" name="num_adults" required>

            <button type="submit">Pesquisar Hotéis</button>
        </form>

        <form method="POST" action="?action=getBookingConditions">
            <h2>Obter Condições de Reserva</h2>
            <label for="hotel_id">ID do Hotel:</label>
            <input type="text" id="hotel_id" name="hotel_id" required>

            <label for="room_ids">IDs dos Quartos (separados por vírgula):</label>
            <input type="text" id="room_ids" name="room_ids" required>

            <label for="token">Token:</label>
            <input type="text" id="token" name="token" required>

            <button type="submit">Obter Condições</button>
        </form>

        <form method="POST" action="?action=doBooking">
            <h2>Confirmar Reserva</h2>
            <label for="booking_id">ID da Reserva:</label>
            <input type="text" id="booking_id" name="booking_id" required>

            <label for="hotel_id">ID do Hotel:</label>
            <input type="text" id="hotel_id" name="hotel_id" required>

            <label for="room_id">ID do Quarto:</label>
            <input type="text" id="room_id" name="room_id" required>

            <label for="name">Nome do Passageiro:</label>
            <input type="text" id="name" name="name" required>

            <label for="surname">Sobrenome:</label>
            <input type="text" id="surname" name="surname" required>

            <label for="email">Email do Passageiro:</label>
            <input type="email" id="email" name="email" required>

            <label for="cpf">CPF do Passageiro:</label>
            <input type="text" id="cpf" name="cpf" required>

            <label for="phone_number">Número do Telefone:</label>
            <input type="text" id="phone_number" name="phone_number" required>

            <label for="card_number">Número do Cartão de Crédito:</label>
            <input type="text" id="card_number" name="card_number" required>

            <label for="card_holder">Nome no Cartão:</label>
            <input type="text" id="card_holder" name="card_holder" required>

            <label for="month_expiration">Mês de Expiração:</label>
            <input type="text" id="month_expiration" name="month_expiration" required>

            <label for="year_expiration">Ano de Expiração:</label>
            <input type="text" id="year_expiration" name="year_expiration" required>

            <label for="security_code">Código de Segurança:</label>
            <input type="text" id="security_code" name="security_code" required>

            <button type="submit">Confirmar Reserva</button>
        </form>
    </div>
</body>
</html>


<?php
// Função genérica para realizar requisições à API Iterpec com JSON 
function apiRequest($url, $data) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json'
    ));
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

    $result = curl_exec($curl);

    if (curl_errno($curl)) {
        return 'Erro ao realizar a requisição: ' . curl_error($curl);
    }

    curl_close($curl);
    return json_decode($result, true);
}

// Função para pesquisa de hotéis e salvar como posts personalizados no WordPress
function hotelSearch($data) {
    $url = 'http://iterpec.cangooroo.net/API/REST/hotel.svc/Search';
    $response = apiRequest($url, $data);

    if (!empty($response['Hotels'])) {
        foreach ($response['Hotels'] as $hotel) {
            // Cria um novo post do tipo personalizado 'hotel'
            $post_id = wp_insert_post(array(
                'post_title'  => $hotel['Name'],
                'post_type'   => 'hotel',
                'post_status' => 'publish'
            ));

            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, 'location', $hotel['Location']);
                update_post_meta($post_id, 'price', $hotel['Price']);
                update_post_meta($post_id, 'rating', $hotel['Rating']);
                wp_set_object_terms($post_id, $hotel['Category'], 'hotel_category');
            }
        }
    }

    return $response;
}

// Função para obter condições de reserva de hotel
function getBookingConditions($data) {
    $url = 'http://iterpec.cangooroo.net/API/REST/hotel.svc/GetBookingConditions';
    $response = apiRequest($url, $data);

    return $response;
}

// Função para confirmar reserva
function doBooking($data) {
    $url = 'http://iterpec.cangooroo.net/API/REST/Hotel.svc/DoBooking';
    $response = apiRequest($url, $data);

    return $response;
}

// Lógica para processar requisições de acordo com a ação específica
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $credential = array(
        "Username" => "cdo.api",
        "Password" => "WSRl5gfYgZYn"
    );

    if (!isset($_GET['action'])) {
        // Pesquisa de hotéis
        $searchData = array(
            "Credential" => $credential,
            "Criteria" => array(
                "CheckinDate" => $_POST['checkin_date'],
                "Filters" => array(
                    "CheapestRoomOnly" => true,
                    "HidePackageRate" => true
                ),
                "DestinationId" => $_POST['destination_id'],
                "NumNights" => $_POST['num_nights'],
                "MainPaxCountryCodeNationality" => "BR",
                "ReturnExtendedHotelStaticData" => false,
                "ReturnHotelStaticData" => true,
                "ReturnOnRequestRooms" => true,
                "SearchRooms" => array(
                    array(
                        "ChildAges" => array(),
                        "NumAdults" => $_POST['num_adults'],
                        "Quantity" => 1
                    )
                ),
                "SearchType" => "Hotel"
            )
        );

        $result = hotelSearch($searchData);

        echo "<h2>Resultado da Pesquisa de Hotéis:</h2>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";

    } elseif ($_GET['action'] == 'getBookingConditions') {
        // Obter condições de reserva de hotel
        $data = array(
            "Credential" => $credential,
            "HotelId" => $_POST['hotel_id'],
            "RoomIds" => $_POST['room_ids'],
            "Token" => $_POST['token']
        );

        $result = getBookingConditions($data);

        echo "<h2>Condições de Reserva (Hotel):</h2>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";

    } elseif ($_GET['action'] == 'doBooking') {
        // Confirmação de reserva
        $bookingData = array(
            "Credential" => $credential,
            "BookingId" => $_POST['booking_id'],
            "HotelId" => $_POST['hotel_id'],
            "Rooms" => array(
                array(
                    "Paxs" => array(
                        array(
                            "Address" => $_POST['address'],
                            "AddressComplement" => $_POST['address_complement'],
                            "AddressNumber" => $_POST['address_number'],
                            "Age" => $_POST['age'],
                            "City" => $_POST['city'],
                            "Cpf" => $_POST['cpf'],
                            "DocumentNumber" => $_POST['document_number'],
                            "DocumentType" => $_POST['document_type'],
                            "Email" => $_POST['email'],
                            "MainPax" => $_POST['main_pax'],
                            "Name" => $_POST['name'],
                            "Surname" => $_POST['surname'],
                            "PhoneDDD" => $_POST['phone_ddd'],
                            "PhoneDDI" => $_POST['phone_ddi'],
                            "PhoneNumber" => $_POST['phone_number'],
                            "State" => $_POST['state'],
                            "Title" => $_POST['title'],
                            "ZipCode" => $_POST['zip_code'],
                            "isChild" => $_POST['is_child']
                        )
                    ),
                    "Payment" => array(
                        "PaymentConditionId" => $_POST['payment_condition_id'],
                        "PaymentRequestCC1" => array(
                            "ContractingParty" => array(
                                "Address" => $_POST['contracting_address'],
                                "AddressComplement" => $_POST['contracting_address_complement'],
                                "AddressNumber" => $_POST['contracting_address_number'],
                                "CPF" => $_POST['contracting_cpf'],
                                "CityName" => $_POST['contracting_city_name'],
                                "CountryCode" => $_POST['contracting_country_code'],
                                "DistrictName" => $_POST['contracting_district_name'],
                                "Email" => $_POST['contracting_email'],
                                "PersonName" => $_POST['contracting_person_name'],
                                "PhoneNumber" => $_POST['contracting_phone_number'],
                                "PhoneNumberDDD" => $_POST['contracting_phone_ddd'],
                                "PhoneNumberDDI" => $_POST['contracting_phone_ddi'],
                                "ReceiveCreditCardReceipt" => $_POST['contracting_receive_cc_receipt'],
                                "StateCode" => $_POST['contracting_state_code'],
                                "ZipCode" => $_POST['contracting_zip_code']
                            ),
                            "CreditCard" => array(
                                "CardNumber" => $_POST['card_number'],
                                "CardOperator" => $_POST['card_operator'],
                                "Holder" => $_POST['card_holder'],
                                "MonthExpiration" => $_POST['month_expiration'],
                                "NumberPayments" => $_POST['number_payments'],
                                "SecurityCode" => $_POST['security_code'],
                                "YearExpiration" => $_POST['year_expiration']
                            ),
                            "IsBilled" => $_POST['is_billed']
                        ),
                        "SmartCommissionValue" => $_POST['smart_commission_value']
                    ),
                    "RoomId" => $_POST['room_id']
                )
            ),
            "Token" => $_POST['token'],
            "SetWaitingPayment" => $_POST['set_waiting_payment']
        );

        $result = doBooking($bookingData);

        echo "<h2>Confirmação de Reserva:</h2>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    }
}
?>
