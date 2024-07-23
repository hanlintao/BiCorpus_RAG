<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $text = $_POST['text'];

    // 构建请求体
    $data = [
        "model" => "nomic-embed-text",
        "prompt" => $text
    ];

    // 初始化cURL会话
    $ch = curl_init();

    // 设置cURL选项
    curl_setopt($ch, CURLOPT_URL, "http://localhost:11434/api/embeddings");
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);

    // 执行cURL请求
    $response = curl_exec($ch);

    // 检查cURL请求是否成功
    if (curl_errno($ch)) {
        echo json_encode(['error' => curl_error($ch)]);
    } else {
        // 返回API响应
        echo $response;
    }

    // 关闭cURL会话
    curl_close($ch);
}
?>