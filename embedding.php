<?php

session_start();

include "shared/lock.php";

if(!isset($_POST['content']) || !isset($_POST['file_id']) || !isset($_POST['row_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
    exit;
}

$content = $_POST['content'];
$file_id = intval($_POST['file_id']);
$row_id = intval($_POST['row_id']);

$url = "http://localhost:11434/api/embeddings";
$data = [
    "model" => "nomic-embed-text",
    "prompt" => $content
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\n",
        'method'  => 'POST',
        'content' => json_encode($data),
    ],
];

$context  = stream_context_create($options);
$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to get embedding']);
    exit;
}

$response = json_decode($result, true);

if (isset($response['embedding'])) {
    $embedding = mysqli_real_escape_string($conn, json_encode($response['embedding']));
    $update_sql = "UPDATE `tmdata` SET `embedding` = '{$embedding}' WHERE `file_id` = {$file_id} AND `id` = {$row_id}";
    if (mysqli_query($conn, $update_sql)) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to store embedding']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid response from embedding engine']);
}
?>