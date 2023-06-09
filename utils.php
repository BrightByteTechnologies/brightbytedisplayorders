<?php
if (isset($_POST['functionName'])) {
    switch ($_POST['functionName']) {
        case 'checkPin':
            echo checkPin();
            break;
        case 'checkLoginPin':
            echo checkLoginPin();
            break;
        case 'getOrders':
            echo getOrders();
            break;
        case 'markAsFinished':
            echo markAsFinished();
            break;
        default:
            # code...
            break;
    }
}

function checkPin()
{
    $config = file_get_contents('config.json');
    $data = json_decode($config, true);

    $order_pin = $data['RESTAURANT']['order_pin'];
    $pin = $_POST['pin'];

    if (isset($pin)) {
        if ($pin === $order_pin) { // Make sure to compare pin as a string
            $data = array(
                'status' => 200,
                'message' => 'OK'
            );
            return json_encode($data);
        } else {
            $data = array(
                'status' => 400,
                'message' => 'Pin incorrect'
            );
            return json_encode($data);
        }
    } else {
        $data = array(
            'status' => 404,
            'message' => 'Pin not found!'
        );
        return json_encode($data);
    }
}

function checkLoginPin()
{
    session_start();
    $pin = $_POST['loginPin'];
    $config = file_get_contents('config.json');
    $data = json_decode($config, true);

    $login_pin = $data['RESTAURANT']['login_pin'];
    if (isset($pin)) {
        if ($pin === $login_pin) { // Make sure to compare pin as a string
            $_SESSION["CORRECT_LOGIN"] = true;
            $data = array(
                'status' => 200,
                'message' => 'Pin correct'
            );
            return json_encode($data);
        } else {
            $data = array(
                'status' => 400,
                'message' => 'Pin incorrect'
            );
            $_SESSION["CORRECT_LOGIN"] = false;
            return json_encode($data);
        }
    } else {
        $data = array(
            'status' => 404,
            'message' => 'Pin not found!'
        );
        return json_encode($data);
    }
}

function getOrders()
{
    $groupedItems = generateOrders();

    if ($groupedItems === null) {
        echo '<div class="error-message">';
        echo '<h1> Sorry something went wrong</h1>';
        echo '<h4>We are currently experiencing technical difficulties. Please try again later.</h4>';
        echo '<button class="reload-button" onclick="location.reload()">Reload</button>';
        echo '</div>';
        return;
    }

    foreach ($groupedItems as $id => $itemsWithSameId) {
        echo '<div class="order">';
        echo '<div class="order-header">';
        echo '<div class="order-id">Bestell-ID: ' . $id . '</div>';
        echo '<div class="table-number">Tischnummer: ' . $itemsWithSameId[0]['tableNo'] . '</div>';
        echo '</div>';
        echo '<div class="order-body">';
        foreach ($itemsWithSameId as $item) {
            echo '<div class="item">';
            echo '<div class="item-name">' . $item['name'] . '</div>';
            echo '<div class="item-description">' . $item['description'] . '</div>';
            echo '<div class="item-price">' . number_format($item['price'], 2) . ' €</div>';
            echo '<div class="item-amount">Anzahl: ' . $item['amount'] . '</div>';
            echo '</div>';
        }
        echo '</div>';
        echo '<div class="order-bottom">';
        echo '<span class="total-label">Gesamtbetrag: </span>';
        echo '<span class="total-amount">' . number_format($itemsWithSameId[0]['total'], 2) . ' €</span>';
        echo '</div>';
        echo '</div>';
    }
}

function generateOrders()
{
    $config = file_get_contents('config.json');
    $data = json_decode($config, true);

    $restaurantId = $data['RESTAURANT']['id'];
    $API_KEY = $data['API'][6]['key'];
    $ch = curl_init("api.brightbytetechnologies.de/orders/unfinished?restaurant_id=" . $restaurantId);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('api-key: ' . $API_KEY));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get HTTP status code
    curl_close($ch);

    if ($httpCode !== 200) {
        return null; // API request failed
    }

    $items = json_decode($response, true);

    // Group items by ID
    $groupedItems = [];
    foreach ($items as $item) {
        $id = $item['orderId'];
        if (!isset($groupedItems[$id])) {
            $groupedItems[$id] = [];
        }
        $groupedItems[$id][] = $item;
    }
    ksort($groupedItems);

    return $groupedItems;
}

function markAsFinished() {
    $orderId = $_POST["orderId"];

    if (isset($orderId)) {
        $config = file_get_contents('config.json');
        $data = json_decode($config, true);
    
        $restaurantId = $data['RESTAURANT']['id'];
        $API_KEY = $data['API'][7]['key'];

        $data = array(
            'restaurant_id' => $restaurantId,
            'order_id' => $orderId
        );
    
        $jsonData = json_encode($data);
    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'api.brightbytetechnologies.de/orders/finish'); // Add 'http://' before the localhost URL
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('api-key: ' . $API_KEY, 'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Get the HTTP response code
        curl_close($ch);
        
        if ($httpCode == 200) { // Check the HTTP response code
            $data = array(
                'status' => 200,
                'message' => 'OK'
            );
            return json_encode($data);
        } else {
            $data = json_decode($response, true);
            return json_encode($data);
        }
        
    } else {
        $data = array(
          'status' => 400,
          'message' => 'Order-ID not found!'
        );
        return json_encode($data);
    }
}

?>