<?php
session_start();
include "shared/lock.php";


header('Content-Type: application/json');

// 启用错误报告
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 添加错误处理函数
function handleError($errno, $errstr, $errfile, $errline) {
    $response = [
        'status' => 'error',
        'message' => 'PHP错误',
        'details' => "[$errno] $errstr in $errfile on line $errline"
    ];
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    echo json_encode($response);
    exit;
}
set_error_handler('handleError');

function splitTextIntoChunks($text, $maxChunkSize = 200) {
    // 首先按段落分割
    $paragraphs = explode("\n\n", $text);
    $chunks = [];
    $currentChunk = '';
    
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if (empty($paragraph)) continue;
        
        // 如果当前段落加上现有chunk不超过最大长度，则合并
        if (mb_strlen($currentChunk . "\n" . $paragraph) <= $maxChunkSize) {
            $currentChunk = trim($currentChunk . "\n" . $paragraph);
        } else {
            // 如果当前段落本身就超过最大长度，需要进一步分割
            if (mb_strlen($paragraph) > $maxChunkSize) {
                // 按句子分割
                $sentences = preg_split('/(?<=[.!?。！？])\s+/', $paragraph);
                foreach ($sentences as $sentence) {
                    if (mb_strlen($sentence) > $maxChunkSize) {
                        // 如果单个句子仍然太长，按固定长度分割
                        $subChunks = mb_str_split($sentence, $maxChunkSize);
                        foreach ($subChunks as $subChunk) {
                            if (!empty($subChunk)) {
                                $chunks[] = $subChunk;
                            }
                        }
                    } else {
                        if (!empty($currentChunk) && mb_strlen($currentChunk . $sentence) > $maxChunkSize) {
                            $chunks[] = $currentChunk;
                            $currentChunk = $sentence;
                        } else {
                            $currentChunk = trim($currentChunk . " " . $sentence);
                        }
                    }
                }
            } else {
                if (!empty($currentChunk)) {
                    $chunks[] = $currentChunk;
                }
                $currentChunk = $paragraph;
            }
        }
    }
    
    // 添加最后一个chunk
    if (!empty($currentChunk)) {
        $chunks[] = $currentChunk;
    }
    
    return $chunks;
}

try {
    if ($user_type > 2) {
        throw new Exception('权限不足');
    }

    if (!isset($_POST['content']) || empty($_POST['content'])) {
        throw new Exception('未接收到文本内容');
    }

    $content = $_POST['content'];
    $chunks = splitTextIntoChunks($content);
    $successful_chunks = 0;
    $errors = [];
    $processed_chunks = [];
    
    foreach ($chunks as $index => $chunk) {
        try {
            // 获取向量嵌入
            $url = "http://localhost:11434/api/embeddings";
            $data = [
                "model" => "nomic-embed-text",
                "prompt" => $chunk
            ];
            
            $options = [
                'http' => [
                    'header'  => "Content-type: application/json\r\n",
                    'method'  => 'POST',
                    'content' => json_encode($data)
                ]
            ];
            
            $context = stream_context_create($options);
            $result = file_get_contents($url, false, $context);
            
            if ($result === FALSE) {
                throw new Exception("向量化服务请求失败");
            }
            
            $response = json_decode($result, true);
            
            if (!isset($response['embedding'])) {
                throw new Exception("未获取到向量结果");
            }
            
            $embedding = json_encode($response['embedding']);
            $safe_chunk = mysqli_real_escape_string($conn, $chunk);
            $safe_user_id = mysqli_real_escape_string($conn, $user_id);
            
            $sql = "INSERT INTO text_chunks (content, embedding, upload_user) 
                   VALUES ('$safe_chunk', '$embedding', '$safe_user_id')";
            
            if (!mysqli_query($conn, $sql)) {
                throw new Exception("数据库插入失败: " . mysqli_error($conn));
            }
            
            $successful_chunks++;
            $processed_chunks[] = [
                'index' => $index,
                'length' => mb_strlen($chunk),
                'status' => 'success'
            ];
            
        } catch (Exception $e) {
            $errors[] = [
                'chunk_index' => $index,
                'error' => $e->getMessage()
            ];
            $processed_chunks[] = [
                'index' => $index,
                'length' => mb_strlen($chunk),
                'status' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
    
    // 只输出一次JSON响应
    $response = [
        'status' => 'success',
        'chunks_count' => $successful_chunks,
        'total_chunks' => count($chunks),
        'failed_chunks' => count($errors),
        'processed_chunks' => $processed_chunks
    ];
    
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }
    
    echo json_encode($response);
    exit; // 确保脚本在这里结束
    
} catch (Exception $e) {
    $response = [
        'status' => 'error',
        'message' => $e->getMessage(),
        'details' => [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]
    ];
    echo json_encode($response);
    exit;
}