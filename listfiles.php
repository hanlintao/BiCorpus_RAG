<?php

session_start();

include "shared/lock.php";

if($user_type > 2)
{
	header("Location: index.php");
}
?>

<?php

include "shared/head.php";

?>
	
<?php

include "shared/navbar.php";

?>

<div class="container-fluid">
   <div class="row">
      <div class="col-md-12">
		<div class="col-md-4">
		</div>

		<div class="col-md-4">
			<form method="GET" action="" > 
				<div class="input-group">
                    <input type="text" class="form-control" name="query">
                    <span class="input-group-btn">
                        <button class="btn btn-primary" type="submit">检索</button>
                    </span>
				</div>	
			</form>		
		</div>

		<div class="col-md-4">
		</div>		
	  </div>
   </div>
   
   <div class="row">
		<div class="col-md-0">
		</div>
		<div class="col-md-12">

		<?php
		
		function getName($number)
		{
			//根据志愿者ID获取志愿者真实姓名
			
			global $conn;
			
			$sql_get_name = "SELECT fullname FROM users WHERE id = '{$number}'";
			
			$get_name = mysqli_query($conn,$sql_get_name);
			
			$row =  mysqli_fetch_array($get_name,MYSQLI_ASSOC);
			
			return $row["fullname"];
			
		}
		mysqli_select_db($conn,DB_DATABASE); //连接数据库
		
		//用户第一次访问时没有输入任何查询词，所以当无法获取查询词时，默认的查询词是空
		if(!isset($_GET["query"]))
		{
			$query = "";
		}
		else
		{
			$query = $_GET["query"];
			//echo $query;
		}

		//获取页码
		if(!isset($_GET["page"]))
		{
			$page = "";
		}
		else
		{
			$page = $_GET["page"];
		}
					
		// 分页代码解析参见：https://www.mitrajit.com/bootstrap-pagination-in-php-and-mysql/
		
		$limit = 10;
		$adjacents = 4;

      
		//用户第一次访问时没有点击任何页码，所以默认页码是1，从第0个记录开始检索（所以offset的值为0）；但如果能够得到页码，比如页码是5，而我们设置了每页显示10条结果（limit=10），所以第五页应该是从前40个结果开始，所以是10*(5-1)。
		if(isset($_GET['page']) && $_GET['page'] != "") {
			$page = $_GET['page'];
			$offset = $limit * ($page-1);
		} else {
			$page = 1;
			$offset = 0;
		}
		
		// 如果当前的页码是5，则当前的offset是40。但是，此时用户搜索了一个词，一旦用户开始搜索词，那么offset就要从0开始，重新计算所有数据的总数。
		
		// 有一个小知识点一定要注意：只要加了limit，那么count(*)就肯定是10
		

		// 如果是管理员则全部显示，如果是普通用户则只显示他们上传的内容
		
		if($user_type == 1)
		{
			$sql_count_data = "SELECT COUNT(*) 'total_rows' FROM `files` WHERE sourcefilename LIKE '%{$query}%' OR field LIKE '%{$query}%' OR description LIKE '%{$query}%' OR uploaduser LIKE '%{$query}%'";
		}
		
		elseif($user_type == 2)
		{
			$sql_count_data = "SELECT COUNT(*) 'total_rows' FROM `files` WHERE uploaduser = '{$user_id}' AND (sourcefilename LIKE '%{$query}%' OR field LIKE '%{$query}%' OR description LIKE '%{$query}%')";
		}
		
		
		//$sql_count_data = "SELECT COUNT(*) 'total_rows' FROM `tmdata` ";
		mysqli_query($conn,"set names utf8"); 
		$count_data = mysqli_query($conn, $sql_count_data);
		$total_data = mysqli_fetch_array($count_data,MYSQLI_ASSOC);
		$total_rows = $total_data["total_rows"];
		
		$total_pages = ceil($total_rows / $limit);			
			

		if($user_type == 1)
		{
			
			$sql = "SELECT * FROM files WHERE sourcefilename LIKE '%{$query}%' OR field LIKE '%{$query}%' OR description LIKE '%{$query}%' OR uploaduser LIKE '%{$query}%' ORDER BY status,id DESC limit $offset, $limit  ";
			
		}
		
		elseif($user_type == 2)
		{
			$sql = "SELECT * FROM files WHERE uploaduser = '{$user_id}' AND (sourcefilename LIKE '%{$query}%' OR field LIKE '%{$query}%' OR description LIKE '%{$query}%') ORDER BY id DESC limit $offset, $limit  ";
		}
		mysqli_query($conn,"set names utf8"); 
		
		$result = mysqli_query($conn,$sql);
		
		if(mysqli_num_rows($result) > 0) {
		
		echo "<table class='table table-bordered table-striped'>
				<thead>
				<td width='5%'>ID</td>
				<td width='20%'>文件名</td>
				<td width='5%'>下载</td>
				<td width='7%'>志愿者</td>".
				//<td width='10%'>时间</td>
				"<td width='10%'>领域</td>
				<td width='16%'>描述</td>
				<td width='5%'>状态</td>
				<td width='15%'>操作</td>
				<td width='17%'>审核意见</td>
			</thead>";
					
		while($row = mysqli_fetch_array($result, MYSQLI_ASSOC))
			{
				echo "<tr>
						<td>
							<a href='metadata.php?id={$row["id"]}' target = '_blank' class='btn btn-default btn-sm'>
							<span class='glyphicon glyphicon-list-alt'></span> {$row["id"]}
							</a>
						</td>
						<td>{$row["sourcefilename"]}</td>
						<td><a href='{$row["savefilepath"]}' target='_blank'>下载</a></td>
						<td>";
						
				echo getName($row["uploaduser"]); //使用getName函数获取用户的全名
				
				echo "</td>".
						//<td>{$row["uploadtime"]}</td>
						"<td><div class='edit_field editarea' id='".$row["id"]."'>".$row["field"]."</div></td>
						<td style='word-break:break-all'><div class='edit_description editarea' id='".$row["id"]."'>".$row["description"]."</div></td>
						<td>";
						
						if($row["status"] == 0)
						{
							echo '<button type="button" class="btn btn-warning btn-sm" >未发布</button>';
						}
						elseif($row["status"] == 1)
						{
							echo '<button type="button" class="btn btn-success btn-sm" >已发布</button>';
						}
						elseif($row["status"] == 3)
						{
							echo '<button type="button" class="btn btn-danger btn-sm" >待删除</button>';
						}
						
				echo		"</td>
						<td>
							<div class='btn-group'>
								<a type='button' class='btn btn-primary btn-sm' href='previewfile.php?id={$row["id"]}' target='_blank'>预览</a>";
								
							if($user_type == 1)
							{	
								if($row["status"] == 0)
								{
									echo "<a type='button' class='btn btn-info btn-sm' href='verify.php?id={$row["id"]}&status=0&query={$query}&page={$page}'>发布</a>";
									
									echo "<a type='button' class='btn btn-danger btn-sm' href='deletefile.php?id={$row["id"]}&query={$query}&page={$page}'>删除</a>";
								}
								elseif($row["status"] == 1)
								{
									echo "<a type='button' class='btn btn-warning btn-sm' href='verify.php?id={$row["id"]}&status=1&query={$query}&page={$page}'>撤回</a>";
									
									
								}
								
								$sql_check_id = "SELECT * FROM termdata WHERE segment_id = '{$row["id"]}'";
	
								mysqli_query($conn,"set names utf8"); 
		
								$id_check_result = mysqli_query($conn,$sql_check_id);
		
								//if(mysqli_num_rows($id_check_result) > 0) {
								//	echo "<a type='button' class='btn btn-primary btn-sm' href='segwords.php?id={$row["id"]}' target='_blank'>已分词</a>";
								//}
								//else{
								//	echo "<a type='button' class='btn btn-warning btn-sm' href='segwords.php?id={$row["id"]}' target='_blank'>未分词</a>";
								//}
							}
								
				echo "			</div>
						</td>";
						
				echo "<td><div class='edit_comments editarea' id='".$row["id"]."'>".$row["comments"]."</div></td>
					</tr>";
			} 
		
		echo "</table>";
		}
		
		
		if($total_pages <= (1+($adjacents * 2))) 
		{
			$start = 1;
			$end   = $total_pages;
		} 
		else 
		{
			if(($page - $adjacents) > 1) 
			{ 
				if(($page + $adjacents) < $total_pages) 
				{ 
					$start = ($page - $adjacents);            
					$end   = ($page + $adjacents);         
				} 
				else 
				{             
					$start = ($total_pages - (1+($adjacents*2)));  
					$end   = $total_pages;               
				}
			} 
			else 
			{               
				$start = 1;                                
				$end   = (1+($adjacents * 2));             
			}
		}
		
		?>		
		
	<?php if($total_pages > 0) { ?>
          <ul class="pagination pagination-sm justify-content-center">
            <!-- Link of the first page -->
            <li class='page-item <?php ($page <= 1 ? print 'disabled' : '')?>'>
              <a class='page-link' href='listfiles.php?query=<?php echo $query;?>&page=1'><<</a>
            </li>
            <!-- Link of the previous page -->
            <li class='page-item <?php ($page <= 1 ? print 'disabled' : '')?>'>
              <a class='page-link' href='listfiles.php?query=<?php echo $query;?>&page=<?php ($page>1 ? print($page-1) : print 1)?>'><</a>
            </li>
            <!-- Links of the pages with page number -->
            <?php for($i=$start; $i<=$end; $i++) { ?>
            <li class='page-item <?php ($i == $page ? print 'active' : '')?>'>
              <a class='page-link' href='listfiles.php?query=<?php echo $query;?>&page=<?php echo $i;?>'><?php echo $i;?></a>
            </li>
            <?php } ?>
            <!-- Link of the next page -->
            <li class='page-item <?php ($page >= $total_pages ? print 'disabled' : '')?>'>
              <a class='page-link' href='listfiles.php?query=<?php echo $query;?>&page=<?php ($page < $total_pages ? print($page+1) : print $total_pages)?>'>></a>
            </li>
            <!-- Link of the last page -->
            <li class='page-item <?php ($page >= $total_pages ? print 'disabled' : '')?>'>
              <a class='page-link' href='listfiles.php?query=<?php echo $query;?>&page=<?php echo $total_pages;?>'>>>                      
              </a>
            </li>
          </ul>
       <?php   } ?>
       
		</div>
		<div class="col-md-0">
		</div>
		 
	</div>
    
   </div>
		


</body>
</html>

<?php

include "activetableedit.html";

if($user_type == 1)
{
	include "activecomentsedit.html";
}


?>