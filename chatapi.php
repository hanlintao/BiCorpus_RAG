<?php
session_start();
include "shared/lock.php";
if($user_type > 2)
{
    header("Location: login.php");
    exit();
}

function ChatGPT($prompt, $slug, $apiKey) {
    $ch = curl_init();
    $url = "http://localhost:3001/api/v1/openai/chat/completions";
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $postData = json_encode([
        "messages" => [
            [
                "role" => "system",
                "content" => "You are a helpful assistant"
            ],
            [
                "role" => "user",
                "content" => $prompt
            ]
        ],
        "model" => $slug,  // 使用 slug 作为 model 的值
        "stream" => true,
        "temperature" => 0.7
    ]);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    
    $headers = [
        "Content-Type: application/json",
        "Authorization: Bearer " . $apiKey,
        "accept: */*"
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    // 设置写入回调函数
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($curl, $data) {
        echo $data;
        ob_flush();
        flush();
        return strlen($data);
    });

    $result = curl_exec($ch);
    
    if (curl_error($ch)) {
        echo "cURL Error: " . curl_error($ch);
    }
    
    curl_close($ch);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prompt']) && isset($_POST['slug']) && isset($_POST['apiKey'])) {
    $prompt = $_POST['prompt'];
    $slug = $_POST['slug'];
    $apiKey = $_POST['apiKey'];
    
    // 设置适当的头部以允许流式输出
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    
    ChatGPT($prompt, $slug, $apiKey);
} else {
    echo json_encode(["error" => "No prompt, slug, or apiKey provided."]);
}
?>