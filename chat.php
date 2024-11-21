<?php
session_start();
include "shared/lock.php";

if($user_type > 2)
{
    header("Location: login.php");
}

// Add this function to fetch the API key
function getDefaultApiKey($userId) {
    global $conn; // Assuming $conn is your database connection variable
    $stmt = $conn->prepare("SELECT anythingllmkey FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['anythingllmkey'];
    }
    return '';
}

// Get the default API key
$defaultApiKey = getDefaultApiKey($user_id); 
?>

<?php include "shared/head.php"; ?>
<?php include "shared/navbar.php"; ?>

<div class="container-fluid">
    <!-- 顶部选项区域 -->
    
    <div class="container-fluid">
    <!-- 顶部导航栏下方的主设置区 -->
    <div class="row mt-3">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                
                <div class="d-flex gap-2">
                    <button type="button" id="fetchWorkspaces" class="btn btn-primary">
                        <i class="fas fa-sync-alt me-2"></i>刷新工作区
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- 设置区域 -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row align-items-end">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label for="apiKey" class="form-label">
                                <i class="fas fa-key me-1"></i>API密钥
                            </label>
                            <div class="input-group">
                                <input type="password" 
                                       class="form-control" 
                                       id="apiKey" 
                                       placeholder="输入API密钥" 
                                       value="<?php echo htmlspecialchars($defaultApiKey); ?>">
                                <button class="btn btn-outline-secondary" 
                                        type="button" 
                                        id="toggleApiKey">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label for="workspaceSelect" class="form-label">
                                <i class="fas fa-folder me-1"></i>工作区
                            </label>
                            <br>
                            <select class="form-select" id="workspaceSelect" disabled>
                                <option value="">请选择工作区</option>
                            </select>
                        </div>
                        
                        <div class="col-md-4 mb-3 mb-md-0">
                            <label for="similarityThreshold" class="form-label">
                                <i class="fas fa-percentage me-1"></i>相似度阈值
                            </label>
                            <input type="number" 
                                   class="form-control" 
                                   id="similarityThreshold" 
                                   value="0.7" 
                                   min="0" 
                                   max="1" 
                                   step="0.1">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


    

    <!-- 中间对话区域 -->
    <div class="row mb-4">
        <!-- 提问区 -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-question-circle me-2"></i>提问区域
                    </h5>
                </div>
                <div class="card-body">
                    <div class="form-group mb-3">
                        <textarea class="form-control" rows="10" id="prompt" name="prompt" 
                                  placeholder="请输入你的问题..." style="resize: none;"></textarea>
                    </div>
                    <button type="submit" name="ask" id="chatapi" class="btn btn-primary" disabled>
                        <i class="fas fa-paper-plane me-2"></i>提问
                    </button>
                </div>
            </div>
        </div>
        
        <!-- 回答区 -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-robot me-2"></i>AI回答
                    </h5>
                </div>
                <div class="card-body">
                    <div id="aiResponse" class="bg-light p-3 rounded" 
                         style="min-height: 300px; max-height: 500px; overflow-y: auto;">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 底部参考内容区域 -->
    <div class="col-2"></div>
        <div class="col-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-book me-2"></i>参考内容
                    </h5>
                </div>
                <div class="card-body">
                    <div id="result" class="table-responsive">
                        <!-- 表格将在这里动态插入 -->
                    </div>
                </div>
            </div>
        </div>
        <div class="col-2"></div>
</div>

<!-- Bootstrap 5 样式覆盖 -->
<style>
.card {
    border: none;
    border-radius: 0.5rem;
}

.card-header {
    border-bottom: 1px solid rgba(0,0,0,.125);
    border-radius: 0.5rem 0.5rem 0 0 !important;
}

.table {
    margin-bottom: 0;
}

.table > :not(caption) > * > * {
    padding: 0.75rem;
}

.btn-primary {
    padding: 0.5rem 1rem;
}

#aiResponse {
    
    line-height: 1.6;
}

.table-responsive {
    max-height: 500px;
    overflow-y: auto;
}

/* 自定义滚动条样式 */
::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: #555;
}

/* 响应式调整 */
@media (max-width: 768px) {
    .container-fluid {
        padding: 1rem;
    }
    
    .card {
        margin-bottom: 1rem;
    }
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.10/clipboard.min.js"></script>
<script>
$(document).ready(function() {
    var defaultApiKey = $("#apiKey").val().trim();
    if (defaultApiKey !== "") {
        $("#fetchWorkspaces").click();
    }

    // Toggle API key visibility
    $("#toggleApiKey").click(function() {
        var apiKeyInput = $("#apiKey");
        var icon = $(this).find('i');
        if (apiKeyInput.attr("type") === "password") {
            apiKeyInput.attr("type", "text");
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            apiKeyInput.attr("type", "password");
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
});

$("#fetchWorkspaces").click(function() {
    var apiKey = $("#apiKey").val().trim();
    if (apiKey === "") {
        alert("请输入密钥");
        return;
    }

    $("#apiKey").attr("type", "password");
    $("#toggleApiKey i").removeClass('fa-eye-slash').addClass('fa-eye');

    $.ajax({
        url: 'http://localhost:3001/api/v1/workspaces',
        method: 'GET',
        headers: {
            'Authorization': 'Bearer ' + apiKey,
            'accept': 'application/json'
        },
        success: function(response) {
            var workspaces = response.workspaces;
            var workspaceSelect = $("#workspaceSelect");
            workspaceSelect.empty();
            workspaceSelect.append('<option value="">请选择工作区</option>');
            workspaces.forEach(function(workspace) {
                workspaceSelect.append('<option value="' + workspace.slug + '">' + workspace.name + '</option>');
            });
            workspaceSelect.prop('disabled', false);
            $("#chatapi").prop('disabled', false);
        },
        error: function() {
            alert("获取工作区失败，请检查密钥是否正确");
        }
    });
});

$("#chatapi").click(async function(){
    var apiKey = $("#apiKey").val().trim();
    var workspaceSlug = $("#workspaceSelect").val().trim();
    var prompt = $("textarea[name=prompt]").val().trim();
    var similarityThreshold = $("#similarityThreshold").val();

    if (workspaceSlug === "") {
        alert("请选择一个工作区");
        return;
    }

    if (prompt === "") {
        alert("请输入你的问题");
        return;
    }

    var responseResult = document.getElementById("result");
    var aiResponseElement = document.getElementById("aiResponse");
    responseResult.innerHTML = "";
    aiResponseElement.innerHTML = "";

    document.querySelector('#chatapi').innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>思考中...';

    $.post('embedapi.php', { text: prompt, apiKey: apiKey }, function(embedResponse) {
        try {
            var embedObj = JSON.parse(embedResponse);
            if (embedObj.error) {
                responseResult.innerHTML = '<div class="alert alert-danger">Error: ' + embedObj.error + '</div>';
                return;
            }

            var embedding = embedObj.embedding;

            $.ajax({
                url: 'retrieve.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ 
                    embedding: embedding,
                    similarityThreshold: parseFloat(similarityThreshold)
                }),
                success: function(retrieveResponse) {
                    try {
                        var retrieveObj = JSON.parse(retrieveResponse);

                        if (retrieveObj.error) {
                            responseResult.innerHTML = '<div class="alert alert-danger">Error: ' + retrieveObj.error + '</div>';
                            return;
                        }

                        if (!retrieveObj.similarContents || !Array.isArray(retrieveObj.similarContents)) {
                            responseResult.innerHTML = '<div class="alert alert-warning">未找到相关内容</div>';
                            return;
                        }

                        var similarContents = retrieveObj.similarContents;

                        // 构建表格
                        var similarContentHtml = `
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>内容类型</th>
                                        <th>来源内容</th>
                                        <th>目标内容</th>
                                        <th>相似度</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;

                        similarContents.forEach(function(content) {
                            similarContentHtml += `
                                <tr>
                                    <td>
                                        <span class="badge ${content.type === 'bilingual' ? 'bg-primary' : 'bg-secondary'}">
                                            ${content.type === 'bilingual' ? '双语对照' : '单语内容'}
                                        </span>
                                    </td>
                                    <td>${content.source_content}</td>
                                    <td>${content.target_content || '<span class="text-muted">无</span>'}</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: ${(content.similarity * 100).toFixed(1)}%;" 
                                                 aria-valuenow="${(content.similarity * 100).toFixed(1)}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                ${(content.similarity * 100).toFixed(1)}%
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            `;
                        });

                        similarContentHtml += `
                                </tbody>
                            </table>
                        `;

                        responseResult.innerHTML = similarContentHtml;

                        // 构建提示词
                        var bilingualContents = similarContents.filter(c => c.type === 'bilingual')
                            .map(c => "双语内容: " + c.source_content + " | " + c.target_content);

                        var monolingualContents = similarContents.filter(c => c.type === 'monolingual')
                            .map(c => "单语内容: " + c.source_content);

                        var combinedPrompt = "这是我的问题：【" + prompt + "】\n\n这是我匹配到的相关内容：\n\n" +
                            [...bilingualContents, ...monolingualContents].join("\n") +
                            "\n\n请基于以上相关内容来回答我的问题。如果相关内容中包含双语对照的内容，请优先使用这些内容来回答。如果有许多双语内容，请选择最相关的双语片段来回答，且仅输出译文，不要输出其他内容。";

                        // 使用fetch API处理流式响应
                        fetch('chatapi.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `prompt=${encodeURIComponent(combinedPrompt)}&slug=${encodeURIComponent(workspaceSlug)}&apiKey=${encodeURIComponent(apiKey)}`
                        })
                        .then(response => {
                            const reader = response.body.getReader();
                            const decoder = new TextDecoder();
                            let buffer = '';

                            function readStream() {
                                return reader.read().then(({ done, value }) => {
                                    if (done) {
                                        return;
                                    }
                                    buffer += decoder.decode(value, { stream: true });
                                    const lines = buffer.split('\n');
                                    buffer = lines.pop();

                                    for (const line of lines) {
                                        if (line.startsWith('data: ')) {
                                            try {
                                                const data = JSON.parse(line.slice(6));
                                                if (data.choices && data.choices[0].delta && data.choices[0].delta.content) {
                                                    aiResponseElement.innerHTML += data.choices[0].delta.content;
                                                }
                                            } catch (e) {
                                                console.error('Error parsing JSON:', e);
                                            }
                                        }
                                    }

                                    return readStream();
                                });
                            }

                            return readStream();
                        })
                        .then(() => {
                            document.querySelector('#chatapi').innerHTML = '<i class="fas fa-paper-plane me-2"></i>提问';
                            if (!document.getElementById('copyButton')) {
                                var copyButton = document.createElement('button');
                                copyButton.innerHTML = '<i class="fas fa-copy me-2"></i>复制回答';
                                copyButton.className = 'btn btn-outline-primary mt-3';
                                copyButton.id = 'copyButton';
                                aiResponseElement.parentNode.insertBefore(copyButton, aiResponseElement.nextSibling);

                                new ClipboardJS('#copyButton', {
                                    text: function() {
                                        return aiResponseElement.innerText;
                                    }
                                }).on('success', function(e) {
                                    copyButton.innerHTML = '<i class="fas fa-check me-2"></i>已复制';
                                    setTimeout(() => {
                                        copyButton.innerHTML = '<i class="fas fa-copy me-2"></i>复制回答';
                                    }, 2000);
                                    e.clearSelection();
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            aiResponseElement.innerHTML += '<div class="alert alert-danger mt-3">Error: ' + error + '</div>';
                            document.querySelector('#chatapi').innerHTML = '<i class="fas fa-paper-plane me-2"></i>提问';
                        });

                    } catch (e) {
                        responseResult.innerHTML = '<div class="alert alert-danger">解析响应失败</div>';
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error:", error);
                    responseResult.innerHTML = '<div class="alert alert-danger">Error: ' + error + '</div>';
                }
            });

        } catch (e) {
            responseResult.innerHTML = '<div class="alert alert-danger">解析嵌入向量响应失败</div>';
        }
    });
});
</script>
</body>
</html>