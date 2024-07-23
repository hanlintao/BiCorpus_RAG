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

<div class="container">
    <div class="row">
        <!--以下是左边的-->
        <div class="col-md-4">
            <div class="form-group">
                <label for="apiKey">密钥</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="apiKey" placeholder="输入密钥" value="<?php echo htmlspecialchars($defaultApiKey); ?>">
                    <div class="input-group-append">
                        <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="button" id="fetchWorkspaces" class="btn btn-primary mt-2">获取工作区</button>
            </div>
            <div class="form-group">
                <label for="workspaceSelect">选择工作区</label>
                <select class="form-control" id="workspaceSelect" disabled>
                    <option value="">请选择工作区</option>
                </select>
            </div>
            <div class="form-group">
                <label for="similarityThreshold">相似度阈值</label>
                <input type="number" class="form-control" id="similarityThreshold" value="0.7" min="0" max="1" step="0.1">
            </div>
            <div class="form-group">
                <textarea class="form-control" rows="10" id="prompt" name="prompt" placeholder="输入你的问题"></textarea>
            </div>
            <button type="submit" name="ask" id="chatapi" class="btn btn-default" disabled>
                提问
            </button>
        </div>

        <div class="col-md-8">
            <div id="result"></div>
            <div id="aiResponse"></div>
        </div>
    </div>
</div>

</body>

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

    // After successful fetch, ensure the API key is hidden
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
    var similarityThreshold = $("#similarityThreshold").val(); // Get the threshold value

    if (workspaceSlug === "") {
        alert("请选择一个工作区");
        return;
    }

    if (prompt === "") {
        alert("请输入你的问题");
        return;
    }

    //清空当前的页面
    var responseResult = document.getElementById("result");
    var aiResponseElement = document.getElementById("aiResponse");
    responseResult.innerHTML = "";
    aiResponseElement.innerHTML = "";

    document.querySelector('#chatapi').innerHTML = "思考中...";

    // 获取用户提问的嵌入向量
    $.post('embedapi.php', { text: prompt, apiKey: apiKey }, function(embedResponse) {
        try {
            var embedObj = JSON.parse(embedResponse);
            if (embedObj.error) {
                responseResult.innerHTML = "Error: " + embedObj.error;
                return;
            }

            var embedding = embedObj.embedding;

            console.log(embedding);

            // 从数据库中检索相似内容
            $.ajax({
                url: 'retrieve.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ 
                    embedding: embedding,
                    similarityThreshold: parseFloat(similarityThreshold) // Send the threshold
                }),
                success: function(retrieveResponse) {
                    try {
                        console.log(retrieveResponse);
                        var retrieveObj = JSON.parse(retrieveResponse);

                        if (retrieveObj.error) {
                            responseResult.innerHTML = "Error: " + retrieveObj.error;
                            if (retrieveObj.invalidRows) {
                                responseResult.innerHTML += "<br>Invalid Rows: <pre>" + JSON.stringify(retrieveObj.invalidRows, null, 2) + "</pre>";
                            }
                            return;
                        }

                        if (!retrieveObj.similarContents || !Array.isArray(retrieveObj.similarContents)) {
                            responseResult.innerHTML = "Invalid retrieve response format.";
                            return;
                        }

                        var similarContents = retrieveObj.similarContents;

                        console.log(similarContents);

                        // 构建显示相似内容的表格
                        var similarContentHtml = "<table class='table table-bordered'>";
                        similarContentHtml += "<tr><th>来源内容</th><th>目标内容</th><th>相似度</th></tr>";
                        similarContents.forEach(function(content) {
                            similarContentHtml += "<tr>";
                            similarContentHtml += "<td>" + content.source_content + "</td>";
                            similarContentHtml += "<td>" + content.target_content + "</td>";
                            similarContentHtml += "<td>" + content.similarity.toFixed(4) + "</td>";
                            similarContentHtml += "</tr>";
                        });
                        similarContentHtml += "</table>";

                        responseResult.innerHTML = similarContentHtml;

                        // 将相似内容和用户提问发送给问答API
                        var combinedPrompt = "这是我的问题：【"+prompt + "】\n\n这是我匹配到的相关内容：\n\n相关内容:\n" + similarContents.map(c => c.source_content + " | " + c.target_content).join("\n") + "，请基于相关内容来回答我的问题。";
                        console.log(combinedPrompt);
                        
                        // 使用fetch API来处理流式响应
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
                            document.querySelector('#chatapi').innerHTML = "提问";
                            // 检查是否已存在复制按钮
                            if (!document.getElementById('copyButton')) {
                                // 添加一键复制功能
                                var copyButton = document.createElement('button');
                                copyButton.innerHTML = '一键复制';
                                copyButton.className = 'btn btn-primary mt-2';
                                copyButton.id = 'copyButton';
                                aiResponseElement.parentNode.insertBefore(copyButton, aiResponseElement.nextSibling);

                                new ClipboardJS('#copyButton', {
                                    text: function() {
                                        return aiResponseElement.innerText;
                                    }
                                }).on('success', function(e) {
                                    alert('复制成功!');
                                    e.clearSelection();
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            aiResponseElement.innerHTML += "Error: " + error;
                            document.querySelector('#chatapi').innerHTML = "提问";
                        });

                    } catch (e) {
                        responseResult.innerHTML = "Invalid retrieve response format.";
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error:", error);
                    responseResult.innerHTML = "Error: " + error;
                }
            });

        } catch (e) {
            responseResult.innerHTML = "Invalid embed response format.";
        }
    });
});
</script>
</html>