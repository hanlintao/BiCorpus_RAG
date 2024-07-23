<?php
session_start();
include "shared/lock.php";
include "shared/head.php";
include "shared/navbar.php";

// 处理表单提交
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST['new_password'] ?? '';
    $new_api_key = $_POST['new_api_key'] ?? '';
    $update_fields = [];

    if (!empty($new_password)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_fields[] = "password = '" . mysqli_real_escape_string($conn, $hashed_password) . "'";
    }

    if (!empty($new_api_key)) {
        $update_fields[] = "anythingllmkey = '" . mysqli_real_escape_string($conn, $new_api_key) . "'";
    }

    if (!empty($update_fields)) {
        $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = '$user_id'";
        if (mysqli_query($conn, $sql)) {
            $update_message = "更新成功！";
        } else {
            $update_message = "更新失败：" . mysqli_error($conn);
        }
    }
}

$sql = "SELECT * FROM users WHERE id = '$user_id'";
mysqli_select_db($conn, DB_DATABASE);
mysqli_query($conn, "set names utf8");

$result = mysqli_query($conn, $sql);
$row = mysqli_fetch_array($result, MYSQLI_ASSOC);
?>

<div class="container">
   <div class="row">
      <div class="col-md-12">
        <div class="col-md-2">
        </div>
        <div class="col-md-8">
            <?php if (isset($update_message)): ?>
                <div class="alert alert-info"><?php echo $update_message; ?></div>
            <?php endif; ?>
            
            <table class='table table-bordered table-striped'>
                <thead>
                    <td width='30%'>用户名</td>
                    <td width='70%'><?php echo $row["username"];?></td>
                </thead>
                <tr>
                    <td width='30%'>成果主页</td>
                    <td width='70%'><?php echo "<a href='contribution.php?id=".$row["id"]."' target='_blank'>查看</a>" ;?></td>
                </tr>
                <tr>
                    <td width='30%'>全名</td>
                    <td width='70%'><?php echo $row["fullname"];?></td>
                </tr>
                <tr>
                    <td width='30%'>单位</td>
                    <td width='70%'><?php echo $row["university"];?></td>
                </tr>
                <tr>
                    <td width='30%'>密码</td>
                    <td width='70%'>******</td>
                </tr>
                <tr>
                    <td width='30%'>AnythingLLM API 密钥</td>
                    <td width='70%'>******</td>
                </tr>
            </table>
            
            <h3>更新信息</h3>
            <form method="post" action="">
                <div class="form-group">
                    <label for="new_password">新密码</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="new_password" name="new_password">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleVisibility('new_password')">
                                <i class="fas fa-eye" id="new_password_icon"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="new_api_key">新 AnythingLLM API 密钥</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="new_api_key" name="new_api_key">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" onclick="toggleVisibility('new_api_key')">
                                <i class="fas fa-eye" id="new_api_key_icon"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">更新</button>
            </form>
        </div>
        <div class="col-md-2">
        </div>        
      </div>
   </div>
</div>

<script>
function toggleVisibility(fieldId) {
    var field = document.getElementById(fieldId);
    var icon = document.getElementById(fieldId + '_icon');
    if (field.type === "password") {
        field.type = "text";
        icon.className = "fas fa-eye-slash";
    } else {
        field.type = "password";
        icon.className = "fas fa-eye";
    }
}
</script>

<?php include "shared/footer.php"; ?>