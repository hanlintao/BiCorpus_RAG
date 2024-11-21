<?php
session_start();
include "shared/lock.php";

if($user_type > 2) {
    header("Location: login.php");
}

include "shared/head.php";
include "shared/navbar.php";
?>

<div class="container">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card mt-4">
                <div class="card-header">
                    <h4>文本上传</h4>
                </div>
                <div class="card-body">
                    <form id="textUploadForm">
                        <div class="form-group">
                            <label for="content">请输入或粘贴文本内容：</label>
                            <textarea class="form-control" id="content" name="content" rows="10" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary mt-3">处理文本</button>
                    </form>
                </div>
            </div>
            
            <div id="progressArea" class="mt-4" style="display:none;">
                <div class="progress">
                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                </div>
                <div id="statusMessage" class="mt-2"></div>
            </div>
            
            <div id="resultArea" class="mt-4">
            </div>
        </div>
    </div>
</div>


<script>
$(document).ready(function() {
    $('#textUploadForm').on('submit', function(e) {
        e.preventDefault();
        
        const content = $('#content').val();
        if (!content) {
            alert('请输入文本内容');
            return;
        }
        
        $('#progressArea').show();
        $('#resultArea').empty();
        $('#statusMessage').html('<div class="alert alert-info">正在处理文本...</div>');
        
        $.ajax({
            url: 'process_text.php',
            method: 'POST',
            data: {
                content: content
            },
            success: function(response) {
                console.log('Raw response:', response); // 添加原始响应日志
                
                try {
                    const result = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (result.status === 'success') {
                        $('#statusMessage').html(`
                            <div class="alert alert-success">
                                <h5>处理完成！</h5>
                                <p>成功处理片段数：${result.chunks_count}</p>
                                <p>总片段数：${result.total_chunks}</p>
                            </div>
                        `);
                    } else {
                        $('#statusMessage').html(`
                            <div class="alert alert-danger">
                                <h5>处理失败</h5>
                                <p>错误信息：${result.message}</p>
                                <p>详细信息：${result.details || '无'}</p>
                            </div>
                        `);
                    }
                } catch (e) {
                    $('#statusMessage').html(`
                        <div class="alert alert-danger">
                            <h5>解析响应时出错</h5>
                            <p>错误类型：${e.name}</p>
                            <p>错误信息：${e.message}</p>
                            <p>原始响应：${response}</p>
                        </div>
                    `);
                    console.error('Parse error:', e);
                }
            },
            error: function(xhr, status, error) {
                $('#statusMessage').html(`
                    <div class="alert alert-danger">
                        <h5>服务器错误</h5>
                        <p>状态码：${xhr.status}</p>
                        <p>错误类型：${status}</p>
                        <p>错误信息：${error}</p>
                        <p>响应文本：${xhr.responseText}</p>
                    </div>
                `);
                console.error('Ajax error:', {xhr, status, error});
            }
        });
    });
});
</script>