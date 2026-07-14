<?
include("../config.inc.php");
ConnDB();

//If(isset($_REQUEST['startdate']))
//{
//    $ddb1=trim($_REQUEST['startdate']);
//}
//$ddb1=$_REQUEST['startdate'];

if ($_REQUEST[year]) {
    $term = $_REQUEST[term];
    $year = $_REQUEST[year];
    // echo "years1 : $year<br>";

}
if ($_GET['task'] == 'del') {
    $sql = "delete from grade_report_reg where COURSECODE='$_GET[COURSECODE]' and OFFICERID = '$_GET[OFFICERID]' and ACADYEAR='$_GET[ACADYEAR]' and SEMESTER='$_GET[SEMESTER]' and SECTION='$_GET[SECTION]'";
//   echo "$sql";
//   exit();
    $rs3 = mysql_query($sql);
    if ($rs3) {
        ?>
        <meta http-equiv="refresh"
              content="0; url=grade_grade_department_f.php?dpart=<?= $department_id; ?>&term=<?= $_GET[SEMESTER]; ?>&year=<?= $_GET[ACADYEAR]; ?>">
        <?php
    }


}
?>
    <link rel="stylesheet" href="css/bootstrap-3.3.2.min.css" type="text/css">
    <link rel="stylesheet" href="css/bootstrap-example.min.css" type="text/css">
    <link rel="stylesheet" href="css/prettify.min.css" type="text/css">

    <script type="text/javascript" src="js/jquery-2.1.3.min.js"></script>
    <script type="text/javascript" src="js/bootstrap-3.3.2.min.js"></script>
    <script type="text/javascript" src="js/prettify.min.js"></script>

    <script type="text/javascript" src="js/jquery-2.1.3.min.js"></script>
    <script type="text/javascript" language="javascript" src="js/jquery.dataTables.js"></script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:ital,wght@0,200;1,100&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/bootstrap-multiselect.css" type="text/css"/>

    <script type="text/javascript" src="js/bootstrap-multiselect.js"></script>


    <meta http-equiv="Content-Type" content="text/html; charset=windows-874"/>
    <style type="text/css">
        /*@import "demo_table.css";*/
        /*@import "demo_page.css";*/

        .style3 {
            font-family: sans-serif, "MS Sans Serif";
            font-size: 15px;
        }
    </style>


    <style>
        body, h1, h2, h3, h4, h5, h6 {
            font-family: 'Sarabun', sans-serif;
        }
    </style>
<!--    <script type="text/javascript">-->
<!--        $(document).ready(function () {-->
<!--//        $('#example-getting-started').multiselect();-->
<!--            $('#user1').multiselect({-->
<!--                enableFiltering: true-->
<!--            });-->
<!--        });-->
<!--    </script>-->
<!--    <script type="text/javascript">-->
<!--        $(document).ready(function () {-->
<!--//        $('#example-getting-started').multiselect();-->
<!--            $('#indicatorid').multiselect({-->
<!--                enableFiltering: true-->
<!--            });-->
<!--            $('#user2').multiselect({-->
<!--                enableFiltering: true-->
<!--            });-->
<!---->
<!--            $('#department').multiselect({-->
<!--                enableFiltering: true-->
<!--            });-->
<!--        });-->
<!--    </script>-->
<!---->
<!--    <script language="javascript" type="text/javascript">-->
<!--        ccom = 0;-->
<!---->
<!--        function formdisplay() {-->
<!--            cmntblck = document.getElementById('formhide');-->
<!--            if (ccom == 0) {-->
<!--                cmntblck.style.display = "inline";-->
<!--                ccom = 1;-->
<!--            } else {-->
<!--                cmntblck.style.display = "none";-->
<!--                ccom = 0;-->
<!--            }-->
<!--        }-->
<!--    </script>-->
    <p align="center">&nbsp;</p>
    <p align="center"><strong>ตรวจสอบสถานะการส่งผลการสอบไล่</strong></p>

    <form name="form1" method="post" action="">

        <!--  --><?php //echo "DDD : ".$datebos1;?>
<!--        <input type="input" id="ddb1" name="ddb1" value=" --><?php //= $ddb1; ?><!--">-->
        <div align="center">
            <strong>เลือกสาขาวิชา :</strong> &nbsp;
            <?
            $dpartid = $_REQUEST["dpart"];
            //echo "<br>dpartid ------> $dpartid<br>";
            $sql = "SELECT * from tbldepartment WHERE department_id in (5,6,7,8,9,10,11,12,25,36)";
            $result = mysql_query($sql);

            ?>
            <select name="dpart" id="dpart">
                <option value="null" selected='selected'>ทั้งหมด</option>
                <?
                while ($fetcharr = mysql_fetch_array($result)) {
                    $id = $fetcharr[department_id];
                    $department_name = $fetcharr[department_name];
                    if ($dpartid == $id) {
                        echo "<option value=\"$id\" selected='selected'>$department_name</option>\n";
                    } else {
                        echo "<option value=\"$id\">$department_name</option>\n";
                    }

                }

                ?>
            </select>

            ภาคการศึกษา&nbsp;&nbsp;
            <select name="term">
                <option value="1" <? if ($term == 1) { ?> selected <? } ?>>ภาคต้น</option>
                <option value="2" <? if ($term == 2) { ?> selected <? } ?>>ภาคปลาย</option>
                <option value="3" <? if ($term == 3) { ?> selected <? } ?>>ภาคฤดูร้อน</option>

            </select>


            &nbsp;&nbsp;&nbsp;ปีการศึกษา
            <!--      --><?php //echo "year : $year<br>";?>
            <select name="year">
                <? for ($i = 2565; $i <= 2580; $i++) { ?>
                    <option value="<?= $i ?>"<? if ($year == $i) { ?> selected <? } ?>>
                        <?= $i ?>
                    </option>
                <? } ?>
            </select>
            <input name="show" type="submit" value="แสดงข้อมูล">
        </div>
    </form>

    <p align="center"><br>
    </p>

    <div align="center">
        <?


        $ap3 = "<font color='red'>ส่งกลับแก้ไขจากส่วนกลาง</font>";
        $depart_id = $_REQUEST[dpart];
        // echo "depart_id : $depart_id<br>";
        //  if($_POST[show]){

       // echo $sql_1;


        ?>
        <table width="850" border="1" cellpadding="0" cellspacing="0">
            <tr style="background-color: #ADD8E6">
                <td align="center"><b>สถานะการส่งผลสอบ</b></td>
                <td align="center"><h5><img width="45" src="images/s1.png"><br>ยังไม่ส่ง</h5></td>
                <td align="center"><h5><img width="45" src="images/s2.png"><br>ส่งรายงานผลสอบแล้ว</h5></td>
                <td align="center"><h5><img width="45" src="images/s3.png"><br>ผ่านที่ประชุมสาขาฯ</h5></td>
                <td align="center"><h5><img width="45" src="images/s4.png"><br>ผ่านที่ประชุมกรรมการคณะฯ</h5></td>
            </tr>
        </table>
        <br>
        <!--        <a href="grade_department_add.php?department_id=--><?php //= $dpart; ?><!--&tt=-->
        <?php //= $term; ?><!--&yy=--><?php //= $year; ?><!--"-->
        <!--           class="add_topic" onClick="formdisplay();"><img src="images/addnew2.jpg" width="120" border="0"></a>-->
        <table width="929" border="1" cellpadding="0" cellspacing="0">
            <tr>
                <!--      <td width="250" bgcolor="#66CCCC"><div align="center"><strong>ผู้สอน</strong></div></td>-->
                <td width="5%" bgcolor="#66CCCC">
                    <div align="center"><strong>ลำดับ</strong></div>
                </td>
                <td width="55%" bgcolor="#66CCCC">
                    <div align="center"><strong>รายวิชา</strong></div>
                </td>
                <td width="8%" bgcolor="#66CCCC">
                    <div align="center"><strong>Sec.</strong></div>
                </td>
                <td width="8%" bgcolor="#66CCCC">
                    <div align="center"><img width="45" src="images/s1.png"></div>
                </td>
                <td width="8%" bgcolor="#66CCCC">
                    <div align="center"><img width="45" src="images/s2.png"></div>
                </td>
                <td width="8%" bgcolor="#66CCCC">
                    <div align="center"><img width="45" src="images/s3.png"></div>
                </td>
                <td width="8%" bgcolor="#66CCCC">
                    <div align="center"><img width="45" src="images/s4.png"></div>
                </td>
            </tr>
            <?
            $colors = array('white', '#F0FFFF');
            $colorIndex = 0;
            // สลับสีแถว


            $i = 1;
            $color = "#F0FFFF";
//            while ($m1 = mysql_fetch_array($rs_1)) {
////                $grade_id = $m1[grade_id];
//                $usernamedb = $m1[username];
//                $OFFICERID = $m1[OFFICERID];
//                $department_id = $m1[department_id];
//                $title_name = $m1[title_name];
//                $fname = $m1[fname];
//                $lname = $m1[lname];

                //----------------------------------------------------
                if($depart_id==4){  //ภาคคอม
                    $wheredepartment = "  COURSECODE like '320%' or COURSECODE like '322%' or 
	           COURSECODE like '324%'  or  COURSECODE like '342%' or  COURSECODE like '340%'    or  COURSECODE like '%SC3%' ";
                   }

                if($depart_id==5){  //ภาควิชาสถิติ
                   // $sql =" select * from grade_report where  (subject_code like '316%' or subject_code like '326%'  or subject_code like '336%'  or  subject_code like '%SC6%' ) and term ='$term' and year ='$year'  $ap  $ap1  " ;
               $wheredepartment = "   COURSECODE like '316%' or COURSECODE like '326%'  or COURSECODE like '336%'  or  COURSECODE like '%SC6%' ";
                }

                if($depart_id==6){  //ภาควิชาเคมี
                   // $sql =" select * from grade_report where  (subject_code like '312%' or subject_code like '313%'  or subject_code like '343%'  or subject_code like '332%'  or  subject_code like '%SC2%') and term ='$term' and year ='$year' $ap  $ap1  " ;
                $wheredepartment = "   COURSECODE like '312%' or COURSECODE like '313%'  or COURSECODE like '343%'  or COURSECODE like '332%'  or  COURSECODE like '%SC2%' ";
                }

                if($depart_id==7){  //ภาควิชาฟิสิกส์
                   // $sql =" select * from grade_report where  (subject_code like '315%' or subject_code like '301%'  or  subject_code like '%SC5%') and term ='$term' and year ='$year'  $ap   $ap1 " ;
                $wheredepartment = "   COURSECODE like '315%' or COURSECODE like '301%'  or  COURSECODE like '%SC5%' ";
                }

                if($depart_id==8){  //ภาควิชาชีววิทยา
                   // $sql =" select * from grade_report where  (subject_code like '311%' or subject_code like '331%'  or  subject_code like '%SC1%'  ) and term ='$term' and year ='$year'  $ap  $ap1" ;
                $wheredepartment = "  COURSECODE like '311%' or COURSECODE like '331%'  or  COURSECODE like '%SC1%'   ";
                }

                if($depart_id==9){  //ภาควิชาจุลชีววิทยา
                  //  $sql =" select * from grade_report where  (subject_code like '317%'  or  subject_code like '327%'   or  subject_code like '%SC7%'  or subject_code like '327%'  or  subject_code like 'SC7%' ) and term ='$term' and year ='$year'  $ap  $ap1 " ;
                    $wheredepartment = "  COURSECODE like '317%'  or  COURSECODE like '327%'   or  COURSECODE like '%SC7%'  or COURSECODE like '327%'  or  COURSECODE like 'SC7%' ";
                }

                if($depart_id==10){  //ภาควิชาคณิตศาสตร์
                  //  $sql =" select * from grade_report where  (subject_code like '314%' or subject_code like '321%'  or subject_code like '323%'  or  subject_code like '333%' or  subject_code like '%SC4%') and term ='$term' and year ='$year'  $ap  $ap1 " ;
                    $wheredepartment = "   COURSECODE like '314%' or COURSECODE like '321%'  or COURSECODE like '323%'  or  COURSECODE like '333%' or  COURSECODE like '%SC4%'";
                }

                if($depart_id==11){  //ภาควิชาชีวเคมี
                   // $sql =" select * from grade_report where  (subject_code like '318%'  or  subject_code like '%SC8%') and term ='$term' and year ='$year'  $ap  $ap1 " ;
                    $wheredepartment = "  COURSECODE like '318%'  or  COURSECODE like '%SC8%' ";
                }

                if($depart_id==12){  //ภาควิชาวิทยาศาสตร์สิ่งแวดล้อม
                   // $sql =" select * from grade_report where  (subject_code like '319%'  or  subject_code like '%SC9%' ) and term ='$term' and year ='$year' $ap  $ap1 " ;
                    $wheredepartment = "  COURSECODE like '319%'  or  COURSECODE like '%SC9%' ";
                }

                if($depart_id==25){   //วิทยาศาสตร์และบูรณาการ
                   // $sql =" select * from grade_report where  (subject_code like '3007%'   or subject_code like '302%'  or  subject_code like 'SC01%'  or  subject_code like 'SC02%'  or  subject_code like '3003%'  ) and term ='$term' and year ='$year'  $ap  $ap1" ;
                    $wheredepartment = "  COURSECODE like '3007%'   or COURSECODE like '302%'  or  COURSECODE like 'SC01%'  or  COURSECODE like 'SC02%'  or  COURSECODE  like '3003%'  ";
                }

                if($depart_id==36){    //คณะวิทยาศาสตร์
                   // $sql =" select * from grade_report where  (subject_code like 'SC001%'  or subject_code like 'SC002%'   or subject_code like 'SC003%' or subject_code like '3001%'  or  subject_code like '3002%'  or  subject_code like '3003%'  ) and term ='$term' and year ='$year'  $ap  $ap1" ;
                    $wheredepartment = "  COURSECODE like 'SC001%'  or COURSECODE like 'SC002%'   or COURSECODE like 'SC003%' or COURSECODE like '3001%'  or  COURSECODE like '3002%'  or  COURSECODE like '3003%' ";
                }

                //----------------------------------------------------
                ?>
                <?php
//                $sql2 = " SELECT * FROM grade_report_reg WHERE OFFICERID = '$OFFICERID' and ACADYEAR = '$year'  and  SEMESTER='$term'  $wheredepartment ";
                $sql2 = " SELECT * FROM grade_report_reg WHERE  ACADYEAR = '$year'  and  SEMESTER='$term' and ( $wheredepartment )  GROUP BY COURSECODE,SECTION  ORDER BY COURSECODE desc";
               // echo $sql2."<br>";
                $rs_2 = mysql_query($sql2);
                if (mysql_num_rows($rs_2) > 0) {
                    ?>
                    <?php

                    while ($m2 = mysql_fetch_array($rs_2)) {
                        $check="";  $check1=""; $check2=""; $check3="";
                        $grade_id = $m2[COURSECODE];
                        $ddb1=$m2[SECTION];
                        $COURSECODE = $m2[COURSECODE];
                        $arraysubjectcode[$i] = $COURSECODE;
                        $COURSENAMEENG = $m2[COURSENAMEENG];
                        $SECTION = $m2[SECTION];
                        $ACADYEAR = $m2[ACADYEAR];
                        $SEMESTER = $m2[SEMESTER];

                        if ($arraysubjectcode[$i - 1] == $arraysubjectcode[$i]) {
                            if ($colors[$i - 1] == "white") {
                                $color = "white";
                            } else {
                                $color = "#F0FFFF";
                            }
                            $colors[$i] = $color;

                        } else {
                            //$k=3;
                            if ($colors[$i - 1] == "white") {
                                $color = "#F0FFFF";
                            } else {
                                $color = "white";
                            }
                            $colors[$i] = $color;

                        }
                        $checkapp=checkstatusapprove(trim($grade_id),$SECTION,$term,$year);
                        $array = explode(":", $checkapp);
                        $checkapps=$array[0];
                        $grade_ids=$array[1];

                        $check.=($checkapps==0) ? "checked":"";
                        $check1=($checkapps==1) ? "checked":"";
                        $check2=($checkapps==2) ? "checked":"";
                        $check3=($checkapps==3) ? "checked":"";

                        if($grade_ids != "")
                        {
                        ?>
                        <tr style='background-color: <?= $color ?>;'>

                            <td width="5%" align="center">
                                <?php // echo "check : $check | check1 : $check1 | check2 : $check2 | check3 : $check3 | ";?> <br>
                                <?php echo $i; ?></td>
                            <td width="55%"><a href="../teacher66/grade_report_pdf.php?grade_id=<?=$grade_ids?>" target="_blank"><?php echo "$COURSECODE $COURSENAMEENG"; ?></a>
<!--                                --><?php // echo "<br>grade_ids : $grade_ids"; ?>
                                <div id="status<?=$grade_ids.":".$ddb1.":1";?>" style=" color:#66CC00"> </div>
                            </td>
                            <td width="8%" align="center"><?php echo $SECTION; ?>
                                <?php // echo $grade_id.":".$ddb1.":1<br>";
//                            echo "| approv = ". $checkapps?>
                            </td>
                            <label>
                            <td width="8%" align="center">

<!--                                <input type="radio" name="gender" value="male" checked> Male<br>-->
<!--                                <input type="radio" name="gender" value="female"> Female-->
<!--                                <br>-->

                                <input type="radio" name="<?=$grade_ids.":".$ddb1.":1";?>" id="<?=$grade_ids.":".$ddb1.":1";?>" onclick='doClick(this);' value="0" <?=$check?>  disabled >


<!--                                <-- ยังไม่ส่ง -->
                            </td>
                            <td width="8%" align="center">
                                <input type="radio" name="<?=$grade_ids.":".$ddb1.":1";?>" id="<?=$grade_ids.":".$ddb1.":1";?>" onclick='doClick(this);'   value="1"   <?=$check1?> >
<!--                                <input type="text" value="--><?php //=$check1?><!--">-->
<!--                                <-- ส่งแล้ว -->
                            </td>
                            <td width="8%" align="center">
                                <input type="radio" name="<?=$grade_ids.":".$ddb1.":1";?>" id="<?=$grade_ids.":".$ddb1.":1";?>" onclick='doClick(this);' value="2"   <?=$check2?>  >
<!--                                <-- ผ่านกรรมการสาขา -->
                            </td>
                            <td width="8%" align="center">
                                <input type="radio" name="<?=$grade_ids.":".$ddb1.":1";?>" id="<?=$grade_ids.":".$ddb1.":1";?>" onclick='doClick(this);' value="3"  <?=$check3?>   disabled>
<!--                                <-- ผ่านกรรมการคณะฯ -->

                            </td>
                            </label>

                        </tr>

                        <?php
                        }else{  // ถ้าไม่มี ID
                            ?>
                            <tr style='background-color: <?= $color ?>;'>

                                <td width="5%" align="center">
                                    <?php // echo "check : $check | check1 : $check1 | check2 : $check2 | check3 : $check3 | ";?> <br>
                                    <?php echo $i; ?></td>
                                <td width="55%"><strong> </strong><?php echo "$COURSECODE $COURSENAMEENG"; ?>
<!--                                    --><?php //  echo "<br>grade_ids : $grade_id <br>checkapps : $checkapps<br> check : $check"; ?>
                                    <div id="status<?=$grade_ids.":".$ddb1.":1";?>" style=" color:#66CC00"> </div>
                                </td>
                                <td width="8%" align="center"><?php echo $SECTION; ?>
                                    <?php // echo $grade_id.":".$ddb1.":1<br>";
                                    //                            echo "| approv = ". $checkapps?>
                                </td>
                                <label>
                                    <td width="8%" align="center">

                                        <input type="radio" name="<?=$grade_id.":".$ddb1.":1";?>" id="<?=$grade_id.":".$ddb1.":1";?>" onclick='doClick(this);' value="0" disabled <?=$check?>   >
<!--                                        <input type="text" value="--><?php //=$check?><!--">-->

                                    </td>
                                    <td width="8%" align="center">
                                        <input type="radio" name="<?=$grade_ids.":".$ddb1.":1";?>" id="<?=$grade_ids.":".$ddb1.":1";?>" onclick='doClick(this);'   value="1" disabled  <?=$check1?> >
                                        <!--                                <-- ส่งแล้ว -->
                                    </td>
                                    <td width="8%" align="center">
                                        <input type="radio" name="<?=$grade_ids.":".$ddb1.":1";?>" id="<?=$grade_ids.":".$ddb1.":1";?>" onclick='doClick(this);' value="2"  disabled <?=$check2?>  >
                                        <!--                                <-- ผ่านกรรมการสาขา -->
                                    </td>
                                    <td width="8%" align="center">
                                        <input type="radio" name="<?=$grade_ids.":".$ddb1.":1";?>" id="<?=$grade_ids.":".$ddb1.":1";?>" onclick='doClick(this);' value="3"  disabled  <?=$check3?>   >
                                        <!--                                <-- ผ่านกรรมการคณะฯ -->

                                    </td>
                                </label>

                            </tr>
                            <?php
                        }
                        $i++;
                    }
                    ?>
                    <?php
                }
                ?>
            <? //} ?>
        </table>
    </div>
    <p align="center">&nbsp; </p>
<script>
    //AJAX สำหรับจัดการบันทึกลงฐานข้อมูล
    function Inint_AJAX() {
        try { return new ActiveXObject("Msxml2.XMLHTTP"); } catch(e) {}
        try { return new ActiveXObject("Microsoft.XMLHTTP"); } catch(e) {}
        try { return new XMLHttpRequest(); } catch(e) {}
        alert("XMLHttpRequest not supported");
        return null;
    }

    //คำสั่งที่ทำเมื่อคลิก checkbox
    function doClick(chk) {

        var req = Inint_AJAX();
        var val=chk.value;
        //alert(chk.value);
        var id= String(chk.id);
        //alert(chk.value);

        // console.log(id);

        req.open('GET', 'save_f2.php?id='+id+'&val='+val, true);
        req.onreadystatechange = function() {
            if (req.readyState==4) {
                if (req.status==200) {
                    var data=req.responseText;
                    //แสดง error ถ้ามี
                    document.getElementById("status"+id).innerHTML=data;
                }
            }
        };
        req.setRequestHeader("Content-Type", "application/x-www-form-urlencoded; charset=utf-8"); // set Header
        req.send(null);
    }

</script>

<?php
function changeDate($date)
{
//ใช้ Function explode ในการแยกไฟล์ ออกเป็น  Array
    $get_date = explode("-", $date);
//กำหนดชื่อเดือนใส่ตัวแปร $month
    $month = array("01" => "ม.ค.", "02" => "ก.พ", "03" => "มี.ค.", "04" => "เม.ย.", "05" => "พ.ค.", "06" => "มิ.ย.", "07" => "ก.ค.", "08" => "ส.ค.", "09" => "ก.ย.", "10" => "ต.ค.", "11" => "พ.ย.", "12" => "ธ.ค.");
//month
    $get_month = $get_date["1"];

//year
    $year = $get_date["0"] + 543;

    return $get_date["2"] . " " . $month[$get_month] . " " . $year;

}

?>

<?php
function checkstatusapprove($subject_code,$sec,$term,$year)
{
    $grade_id="";

    $sql =" SELECT
	grade_std.sec, 
	grade_report.grade_id, 
	grade_report.`year`, 
	grade_report.term, 
	grade_report.subject_code, 
	grade_report.approv
FROM
	grade_report
	INNER JOIN
	grade_std
	ON 
		grade_report.grade_id = grade_std.grade_id where  subject_code like '$subject_code' and sec = '$sec' and term ='$term' and year ='$year'  " ;
    $rs_1=mysql_query($sql);
    //echo $sql;
if (mysql_num_rows($rs_1) > 0) {
    $m1 = mysql_fetch_array($rs_1);
    $apps=$m1[approv]+1;
    $grade_id=$m1[grade_id];
}else{
    $apps=0;
}
return $apps.":".$grade_id;
}
?>
