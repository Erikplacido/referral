<?php
require_once 'conexao.php';

$query = "
    SELECT 
        u.name, 
        SUM(p.payment_value) AS total_received
    FROM payments p
    JOIN users u ON p.user_id = u.id
    GROUP BY p.user_id
    ORDER BY total_received DESC
    LIMIT 3
";

$result = $conn->query($query);

if (!$result) {
    echo json_encode(["error" => "Erro na consulta: " . $conn->error]);
    exit;
}

$ranking = [];
while ($row = $result->fetch_assoc()) {
    $ranking[] = $row;
}

$conn->close();

echo json_encode($ranking);
?>