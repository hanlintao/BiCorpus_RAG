<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
include "shared/lock.php";

function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . "Error in retrieve.php: " . $message . "\n", 3, "error_log.txt");
}

try {
    $json = file_get_contents('php://input');
    logError("Raw POST data: " . $json);
    $data = json_decode($json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON in request body: ' . json_last_error_msg());
    }
    
    if (!isset($data['embedding']) || !is_array($data['embedding'])) {
        throw new Exception('Missing or invalid embedding in request');
    }
    
    $embedding = $data['embedding'];
    $similarityThreshold = isset($data['similarityThreshold']) ? floatval($data['similarityThreshold']) : 0.7;
    $maxResultsPerType = 5; // 每种类型最多返回5条结果
    
    logError("Decoded embedding: " . print_r($embedding, true));
    
    if (empty($embedding)) {
        throw new Exception('Empty embedding vector');
    }

    // 分别存储双语和单语内容
    $bilingualContents = [];
    $monolingualContents = [];
    $invalidRows = [];
    $totalRows = 0;
    $validEmbeddings = 0;

    // 1. 首先检索双语内容
    $sql_tmx = "SELECT source_content, target_content, embedding FROM tmdata";
    $result_tmx = mysqli_query($conn, $sql_tmx);
    if (!$result_tmx) {
        throw new Exception('TMX database query failed: ' . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($result_tmx)) {
        $totalRows++;
        $db_embedding = json_decode($row['embedding'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $invalidRows[] = [
                'embedding' => $row['embedding'],
                'error' => json_last_error_msg(),
                'source' => 'tmdata'
            ];
            continue;
        }
        $validEmbeddings++;

        if (count($embedding) !== count($db_embedding)) {
            $invalidRows[] = [
                'error' => 'Dimension mismatch',
                'input_dim' => count($embedding),
                'db_dim' => count($db_embedding),
                'source' => 'tmdata'
            ];
            continue;
        }

        $cosine_similarity = cosine_similarity($embedding, $db_embedding);
        if ($cosine_similarity > $similarityThreshold) {
            $bilingualContents[] = [
                'source_content' => $row["source_content"],
                'target_content' => $row["target_content"],
                'similarity' => $cosine_similarity,
                'type' => 'bilingual'
            ];
        }
    }

    // 2. 然后检索单语内容
    $sql_chunks = "SELECT content, embedding FROM text_chunks";
    $result_chunks = mysqli_query($conn, $sql_chunks);
    if (!$result_chunks) {
        throw new Exception('Text chunks database query failed: ' . mysqli_error($conn));
    }

    while ($row = mysqli_fetch_assoc($result_chunks)) {
        $totalRows++;
        $db_embedding = json_decode($row['embedding'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $invalidRows[] = [
                'embedding' => $row['embedding'],
                'error' => json_last_error_msg(),
                'source' => 'text_chunks'
            ];
            continue;
        }
        $validEmbeddings++;

        if (count($embedding) !== count($db_embedding)) {
            $invalidRows[] = [
                'error' => 'Dimension mismatch',
                'input_dim' => count($embedding),
                'db_dim' => count($db_embedding),
                'source' => 'text_chunks'
            ];
            continue;
        }

        $cosine_similarity = cosine_similarity($embedding, $db_embedding);
        if ($cosine_similarity > $similarityThreshold) {
            $monolingualContents[] = [
                'source_content' => $row["content"],
                'target_content' => null,
                'similarity' => $cosine_similarity,
                'type' => 'monolingual'
            ];
        }
    }

    // 分别对双语和单语内容进行排序
    usort($bilingualContents, function($a, $b) {
        return $b['similarity'] <=> $a['similarity'];
    });

    usort($monolingualContents, function($a, $b) {
        return $b['similarity'] <=> $a['similarity'];
    });

    // 分别取前5条
    $topBilingualContents = array_slice($bilingualContents, 0, $maxResultsPerType);
    $topMonolingualContents = array_slice($monolingualContents, 0, $maxResultsPerType);

    // 合并结果
    $similarContents = array_merge($topBilingualContents, $topMonolingualContents);

    // 再次按相似度排序（可选）
    usort($similarContents, function($a, $b) {
        return $b['similarity'] <=> $a['similarity'];
    });

    $response = [
        'similarContents' => $similarContents,
        'stats' => [
            'totalRows' => $totalRows,
            'validEmbeddings' => $validEmbeddings,
            'matchedBilingualContents' => count($bilingualContents),
            'matchedMonolingualContents' => count($monolingualContents),
            'returnedBilingualContents' => count($topBilingualContents),
            'returnedMonolingualContents' => count($topMonolingualContents),
            'totalReturnedContents' => count($similarContents),
            'inputEmbeddingDim' => count($embedding),
            'usedThreshold' => $similarityThreshold
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
        return 0;
    }
    $dot_product = array_sum(array_map(function($a, $b) { return $a * $b; }, $vec1, $vec2));
    $magnitude1 = sqrt(array_sum(array_map(function($a) { return $a * $a; }, $vec1)));
    $magnitude2 = sqrt(array_sum(array_map(function($a) { return $a * $a; }, $vec2)));
    
    if ($magnitude1 == 0 || $magnitude2 == 0) {
        return 0;
    }
    
    return $dot_product / ($magnitude1 * $magnitude2);
}
?>