<!DOCTYPE html>
<html lang="en">
<head>
 <!--<a href='?agendar=true&dia=$dia&hora=$hora'>Reservar</a> -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <title>Agenda do fernando</title>
</head>
<body onload="carregado()">

<header class="w-full h-24 flex flex-row">

<div class="back">
<a href="servicos.html">
<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" fill="currentColor" class="bi bi-arrow-return-left" viewBox="0 0 16 16">
  <path fill-rule="evenodd" d="M14.5 1.5a.5.5 0 0 1 .5.5v4.8a2.5 2.5 0 0 1-2.5 2.5H2.707l3.347 3.346a.5.5 0 0 1-.708.708l-4.2-4.2a.5.5 0 0 1 0-.708l4-4a.5.5 0 1 1 .708.708L2.707 8.3H12.5A1.5 1.5 0 0 0 14 6.8V2a.5.5 0 0 1 .5-.5"/>
</svg>
</a>
</div>

    </header>

<main>
    <?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "agendamentos";

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Criar tabela se não existir
$sql = "CREATE TABLE IF NOT EXISTS agendamentos_segundo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dia VARCHAR(10) NOT NULL,
    hora VARCHAR(5) NOT NULL,
    UNIQUE (dia, hora)
)";
$conn->query($sql);

// Função para gerar horários disponíveis
function gerarHorarios() {
    $dias = ["Segunda", "Terça", "Quarta", "Quinta", "Sexta", "Sábado"];
    $horarios = [];
    $horaInicio = strtotime("08:00");
    $horaFim = strtotime("19:00");
    $intervalo = 30 * 60;
    
    foreach ($dias as $dia) {
        $horarios[$dia] = [];
        for ($hora = $horaInicio; $hora <= $horaFim; $hora += $intervalo) {
            $horarios[$dia][] = date("H:i", $hora);
        }
    }
    return $horarios;
}

// Função para buscar agendamentos do banco de dados
function obterAgendamentos($conn) {
    $result = $conn->query("SELECT dia, hora FROM agendamentos_segundo");
    $agendamentos = [];
    while ($row = $result->fetch_assoc()) {
        $agendamentos[$row["dia"]][] = $row["hora"];
    }
    return $agendamentos;
}

// Função para exibir horários disponíveis em formato de tabela
function mostrarHorariosDisponiveis($conn) {
    $horarios = gerarHorarios();
    $agendamentos = obterAgendamentos($conn);

    // Inicia a tabela
    echo "<table border='1'>";
    echo "<tr><th>Horário</th>"; // Cabeçalho de horários
    foreach ($horarios as $dia => $horas) {
        echo "<th>$dia</th>"; // Cabeçalhos dos dias
    }
    echo "</tr>";

    // Exibe os horários em linhas
    foreach ($horarios["Segunda"] as $index => $hora) {
        echo "<tr><td class='horarios'>$hora</td>"; // Coluna de horário
        foreach ($horarios as $dia => $horas) {
            $status = isset($agendamentos[$dia]) && in_array($hora, $agendamentos[$dia]) ? "Reservado" : "Disponível";
            if ($status == "Disponível") {
                echo "<td><button class='pay-button' data-dia='$dia' data-hora='$hora' onclick='mostraFormulario(event)'>Reservar</button></td>"; // Link para agendar
            } else {
                echo "<td class='reservado'>$status</td>"; // Exibe status "Reservado"
            }
        }
        echo "</tr>";
    }

    // Fecha a tabela
    echo "</table>";
}

// Função para agendar um horário
function agendar($conn, $dia, $hora) {
    $stmt = $conn->prepare("INSERT INTO agendamentos_segundo (dia, hora) VALUES (?, ?)");
    $stmt->bind_param("ss", $dia, $hora);
    if ($stmt->execute()) {
        echo "Agendamento confirmado para $dia às $hora.";
    } else {
        echo "Horário já reservado ou inválido.";
    }
    $stmt->close();
}

// Verifica se foi feito um pedido para agendar
if (isset($_GET['agendar']) && $_GET['agendar'] == 'true' && isset($_GET['dia']) && isset($_GET['hora'])) {
    $dia = $_GET['dia'];
    $hora = $_GET['hora'];
    agendar($conn, $dia, $hora);
} else {
    // Exibe os horários disponíveis
    mostrarHorariosDisponiveis($conn);
}

$conn->close();
?>

<script src="https://js.stripe.com/v3/"></script>

<div class="formulario">
<form id="payment-form" style="display:none;">
    <div id="card-element"></div>
    <div class="texto"><p>Para confirmar um horário voce deve pagar R$ 10,00 adiantado</p></div>
    <button id="submit">Pagar e Confirmar Agendamento</button>
  </form>
</div>

<script>

// Criação de uma instância do Stripe e elementos do pagamento
var stripe = Stripe('pk_test_51R9X0FEF5S4iRk4pXQ4Q02Xu4I4cQkOvjC8z5OHYr2DAtzpoKzNVZrdZFphJbtSEIUae4qrpkYaPXCYibbA6FpKY00Arzl27jQ');
var elements = stripe.elements();
var card = elements.create('card');
card.mount('#card-element');

function mostraFormulario(event) {
    // Mostrar o formulário de pagamento
    document.getElementById('payment-form').style.display = 'block';

    // Salvar o horário e dia para envio posterior
    var dia = event.target.getAttribute('data-dia');
    var hora = event.target.getAttribute('data-hora');

    // Criação do token para processar o pagamento
    document.getElementById('payment-form').onsubmit = function(event) {
        event.preventDefault();

        stripe.createToken(card).then(function(result) {
            if (result.error) {
                console.log(result.error.message);
            } else {
                // Enviar o token e os dados de agendamento para o backend (PHP)
                var token = result.token.id;

                fetch('process_payment2.php', {
    method: 'POST',
    body: JSON.stringify({
        token: result.token.id,  // Token gerado pelo Stripe
        dia: dia,
        hora: hora
    }),
    
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        alert('Pagamento confirmado! Agendamento realizado.');
        document.getElementById('payment-form').style.display = 'none'; // Esconde o formulário
    } else {
        alert('Erro ao processar o pagamento: ' + data.error); // Exibe o erro
    }
})
.catch(error => {
    console.log("Erro no fetch: ", error);
    alert('Erro no servidor ou na requisição!');
});


            }
        });
    };
};
</script>


<style>

@import url('https://fonts.googleapis.com/css2?family=Yrsa:ital,wght@0,300..700;1,300..700&display=swap');
@import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&display=swap');

    body {
    background-image: linear-gradient(to right, #434343 0%, black 100%);
    }

    header{
    background-color: #511e01;
}

header .back{
    display: flex;
    align-items: center;
    justify-content: center;
    width: 10%;
    margin-left: auto;
    margin-right: 46.5%;
    color: #eae9e7;
    cursor: pointer;
    transition: all 500ms;
}

.back:hover{
    transform: scale(1.2);
}

header .amostra{
    padding: 2%;
    color: #eae9e7;
    font-size: 17px;
    margin-left: 1.5%;
    font-family: "Bebas Neue", sans-serif;
}

    table{
        margin-top: 5%;
        margin-left:auto;
        margin-right: auto;
        width:90%;
        border: 1px solid rgb(187, 187, 187);
        border-radius: 15px;
        overflow: hidden;
    }

    th{
        background-image: linear-gradient( #e6e6fa, #e6e6fa, #e6e6fa, #c8a2c8, #b57edc);
        color:rgb(0, 0, 0);
        font-family: "Bebas Neue", sans-serif;
        border: 1px solid rgb(187, 187, 187);
        text-align: center;
    }

    td{
        background-color: #ffffff;
        color:rgb(0, 0, 0);
        font-family: "Bebas Neue", sans-serif;
        text-align: center;
        border: 1px solid rgb(187, 187, 187);
        box-shadow: rgb(204, 219, 232) 3px 3px 6px 0px inset, rgba(255, 255, 255, 0.5) -3px -3px 6px 1px inset;
    }

    td button{
        color:rgb(5, 94, 45);
        font-weight: bold;
        font-size: 20px;
        font-family: "Yrsa", serif;
        transition: all 500ms;
    }

    td button:hover{
        text-decoration: underline;
    }

    .reservado{
        color:rgb(160, 1, 1);
        font-weight: bold;
        font-size: 20px;
        font-family: "Yrsa", serif;
        cursor: not-allowed;
    }

    .horarios{
        background-color: rgb(148, 147, 147);;
        font-size: 20px;
        font-family: "Bebas Neue", sans-serif;
        box-shadow: none;
        text-align: center;
    }

    .formulario{
        width: 100%;
        height: 300px;
        display: flex;
        margin-top: -50%;
        justify-content: center;
    }

    .formulario form{
        border: 2px solid black;
        background-color: #eae9e7;
        color: black;
        width: 35%;
        height: 50%;
        position: absolute;
        border-radius: 20px;
        margin-left: auto;
        color: #eae9e7;
    }

    form #card-element{
        background-color: #511e01;
        border-radius: 20px 20px 0 0;
        height: 30%;
    }

    form .texto{
        font-family: "Yrsa", serif;
        font-weight: bold;
        font-size: 22px;
        text-align: center;
        color: black;
    }

    form button{
        background-color: green;
        border: 2px solid black;
        border-radius: 20px;
        width: 30%;
        font-family: "Bebas Neue", sans-serif;
        color: #eae9e7;
        margin-top: 15%;
        margin-left: 35%;
        transition: all 500ms;
    }

    form button:hover{
        transform: scale(1.2);
        background-color:rgb(9, 97, 56);
    }
</style>
</main>

</body>
</html>