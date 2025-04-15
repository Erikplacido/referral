<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["error" => "Usuário não autenticado"]);
    exit;
}

$user_id = $_SESSION['user_id'];

$query = "SELECT name, club_category FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
$conn->close();

if (!$user) {
    echo json_encode(["error" => "Usuário não encontrado"]);
    exit;
}

// Definir a imagem com base na club_category
$icons = [
    "Blue Topaz" => "https://bluefacilityservices.com.au/wp-content/uploads/2024/10/topaz_icon-1-150x150.png",
    "Blue Tanzanite" => "https://bluefacilityservices.com.au/wp-content/uploads/2024/10/tanzanite_icon-1-150x150.png",
    "Blue Sapphire" => "https://bluefacilityservices.com.au/wp-content/uploads/2024/10/sapphire_icon-1-150x150.png"
];

// Define o ícone ou um padrão caso a categoria não exista
$image_url = $icons[$user['club_category']] ?? "https://bluefacilityservices.com.au/wp-content/uploads/2024/10/default_icon.png";

echo json_encode([
    "user" => [
        "name" => $user['name'],
        "club_category" => $user['club_category'],
        "icon" => $image_url
    ]
]);
?>