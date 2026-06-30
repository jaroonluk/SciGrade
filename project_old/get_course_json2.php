<?php
header("Content-Type: text/html; charset=tis-620");
include("../config.inc.php");
ConnDB();

$q = "";

if($_GET[term] <> ""){
    $q = $_GET[term];
}else{
    exit;
}

$q = iconv('UTF-8', 'TIS-620', $q); //แปลงจาก UTF8 เป็น TIS620
if (!$q) return;

$data = array();

$sql = "select   DISTINCT subjname ,subjcode from pdcourse  where subjcode LIKE '%$q%'  ";
//mysql_query("SET NAMES 'tis620' COLLATE 'tis620_thai_ci';");
$rsd = mysql_query($sql);

while ($rs = mysql_fetch_array($rsd)) {
    $data[] = array(
        "id"=>$rs[subjname],
        "label"=>$rs[subjcode]." : " . $rs[subjname],
        "value"=>$rs[subjcode]

    );
}
//"value"=>$rs[subjcode]

$cname = json_encode($data);
echo $cname;

?>
