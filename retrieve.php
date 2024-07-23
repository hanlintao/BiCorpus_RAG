<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include "shared/lock.php";
// 函数用于记录错误
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . "Error in retrieve.php: " . $message . "\n", 3, "error_log.txt");
}
try {
    // 获取原始POST数据
    $json = file_get_contents('php://input');
    logError("Raw POST data: " . $json);
    // 解析JSON数据
    $data = json_decode($json, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON in request body: ' . json_last_error_msg());
    }
    // 检查embedding键是否存在
    if (!isset($data['embedding']) || !is_array($data['embedding'])) {
        throw new Exception('Missing or invalid embedding in request');
    }
    $embedding = $data['embedding'];
    
    // Use the provided threshold or default to 0.7
    $similarityThreshold = isset($data['similarityThreshold']) ? floatval($data['similarityThreshold']) : 0.7;
    
    logError("Decoded embedding: " . print_r($embedding, true));
    // 验证embedding
    if (empty($embedding)) {
        throw new Exception('Empty embedding vector');
    }
    // 从数据库中检索相似内容
    $sql = "SELECT source_content, target_content, embedding FROM tmdata";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        throw new Exception('Database query failed: ' . mysqli_error($conn));
    }
    $similarContents = [];
    $invalidRows = [];
    $totalRows = 0;
    $validEmbeddings = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $totalRows++;
        $db_embedding = json_decode($row['embedding'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $invalidRows[] = [
                'embedding' => $row['embedding'],
                'error' => json_last_error_msg()
            ];
            continue;
        }
        $validEmbeddings++;
    
        // 确保两个向量的维度相同
        if (count($embedding) !== count($db_embedding)) {
            $invalidRows[] = [
                'error' => 'Dimension mismatch',
                'input_dim' => count($embedding),
                'db_dim' => count($db_embedding)
            ];
            continue;
        }
    
        $cosine_similarity = cosine_similarity($embedding, $db_embedding);
        if ($cosine_similarity > $similarityThreshold) { // Use the custom threshold
            $similarContents[] = [
                'source_content' => $row["source_content"],
                'target_content' => $row["target_content"],
                'similarity' => $cosine_similarity
            ];
        }
    }
    $response = [
        'similarContents' => $similarContents,
        'stats' => [
            'totalRows' => $totalRows,
            'validEmbeddings' => $validEmbeddings,
            'matchedContents' => count($similarContents),
            'inputEmbeddingDim' => count($embedding),
            'usedThreshold' => $similarityThreshold // Add this line to show the used threshold
        ]
    ];
    if (count($invalidRows) > 0) {
        $response['invalidRows'] = $invalidRows;
    }
    echo json_encode($response);
} catch (Exception $e) {
    logError($e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
}

function cosine_similarity($vec1, $vec2) {
    if (empty($vec1) || empty($vec2) || count($vec1) !== count($vec2)) {
        return 0; // 返回最小相似度
    }
    $dot_product = array_sum(array_map(function($a, $b) { return $a * $b; }, $vec1, $vec2));
    $magnitude1 = sqrt(array_sum(array_map(function($a) { return $a * $a; }, $vec1)));
    $magnitude2 = sqrt(array_sum(array_map(function($a) { return $a * $a; }, $vec2)));
    
    if ($magnitude1 == 0 || $magnitude2 == 0) {
        return 0; // 避免除以零
    }
    
    return $dot_product / ($magnitude1 * $magnitude2);
}
?>