
<?php
//创建连接
$mysqli=new mysqli("localhost","root","","volunteer");
//检查连接是否被创建
if (mysqli_connect_errno()) {
printf("Connect failed: %s\n", mysqli_connect_error());
exit();
}
/*
* 创建一个准备查询语句:
* ?是个通配符,可以用在任何有文字的数据
* 相当于一个模板，也就是预备sql语句
*/
if ($stmt = $mysqli->prepare("insert into `vol_msg`(mid,content) values(?,?)")){
/*第一个参数是绑定类型，"s"是指一个字符串,也可以是"i"，指的是int。也可以是"db"，
* d代表双精度以及浮点类型，而b代表blob类型,第二个参数是变量
*/
$stmt->bind_param("is",$id,$content);
//给变量赋值
$id = "";
$content = "这是插入的内容";
//执行准备语句
$stmt->execute();
//显示插入的语句
echo "Row inserted".$stmt->affected_rows;
//下面还可以继续添加多条语句，不需要prepare预编译了
//关闭数据库的链接
$mysqli->close();
}
?> 


<?php
//创建连接
$mysqli=new mysqli("localhost","root","","volunteer");
//设置mysqli编码
mysqli_query($mysqli,"SET NAMES utf8");
//检查连接是否被创建
if (mysqli_connect_errno()) {
printf("Connect failed: %s\n", mysqli_connect_error());
exit();
}
//创建准备语句
if ($stmt = $mysqli->prepare("select mid,content from `vol_msg`")){
//执行查询
$stmt->execute();
//为准备语句绑定实际变量
$stmt->bind_result($id,$content);
//显示绑定结果的变量
while($stmt->fetch()){
echo "第".$id."条： ".$content."<br />";
}
//关闭数据库的链接
$mysqli->close();
}
?>
