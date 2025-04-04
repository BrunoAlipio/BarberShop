<?php

// Iniciar a sessão e carregar a chave do Stripe
require_once('stripe-php-master/init.php'); // Certifique-se de ter o Composer instalado e a biblioteca Stripe incluída


\Stripe\Stripe::setApiKey('sk_test_51R9X0FEF5S4iRk4pVvNwmSYGHpKsti7n47YlNNchpWsO2efaif3IwyAVnuIi9wUwNU767pJs03aFUeD3QjiDCEXX00aJ8fcQjT');

// Pegar os dados enviados do frontend (token, dia, hora)
$data = json_decode(file_get_contents('php://input'), true);

$token = $data['token'];
$dia = $data['dia'];
$hora = $data['hora'];

try {
    // Criar a cobrança no Stripe
    $charge = \Stripe\Charge::create([
        'amount' => 1000,
        'currency' => 'brl',
        'source' => $token, // O token do cartão de crédito
        'description' => 'Pagamento para agendamento - ' . $dia . ' às ' . $hora
    ]);

    // Verificar se o pagamento foi bem-sucedido
    if ($charge->status == 'succeeded') {
        // Conectar ao banco de dados e inserir o agendamento
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "agendamentos";
       
        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("Conexão falhou: " . $conn->connect_error);
        }

        // Inserir o agendamento no banco de dados
        $stmt = $conn->prepare("INSERT INTO agendamentos_terceiro (dia, hora) VALUES (?, ?)");
        $stmt->bind_param("ss", $dia, $hora);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false]);
        }

        $stmt->close();
        $conn->close();
    } else {
        echo json_encode(['success' => false]);
    }
} catch (\Stripe\Exception\ApiErrorException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>