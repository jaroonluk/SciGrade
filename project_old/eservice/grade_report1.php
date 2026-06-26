<?
include("../config.inc.php");
ConnDB();

if ($_POST[year]) {
    $term = $_POST[term];
    $year = $_POST[year];

} else {
    $vPdRound = checkPdRound1();
    $tmppdRound = explode("/", $vPdRound);
    $term = $tmppdRound[0];
    $year = $tmppdRound[1];
}
?>
<script src="../researchs/datetimepicker_css.js"></script>
<p align="center">&nbsp;</p>
<p align="center"><strong>แบบรายงานผลการสอบไล่สำหรับเจ้าหน้าที่ </strong></p>
<!-- --><?php //if ($_SERVER['HTTP_X_REAL_IP'] == '10.177.164.23') {
// ?>
<!---->
<!--<form name="form1" method="post" action="gt_report_68.php" target="_blank">-->
<!--    --><?//
// }else{?>

<form name="form1" method="post" action="gt_report_68.php" target="_blank">
<?// } ?>

    <div align="center">
        <p><strong>เลือกสาขาวิชา :</strong> &nbsp;
            <?

            //Get department
            $sql = "select  * from tbluser where username='$username'";
            $result = mysql_query($sql);
            $row = mysql_fetch_array($result);

            $dpartid = $row[department_id];
            //dpart
            // $sql ="select * from tbldepartment where department_id = $dpartid";

//            if ($dpartid == 25) {
//                //dpart
//                $sql = "select * from tbldepartment where department_id = '25' or  department_id = '36'";
//            } else {
//                if ($row['userid'] == '116412') {
//                    $sql = "select * from tbldepartment where department_id = '25' or  department_id = '22' or  department_id = '36'";
//                } else {
//                    $sql = "select * from tbldepartment where department_id = $dpartid";
//                }
//            }

            if($dpartid == 25) {
                //dpart
                $sql = "select * from tbldepartment where department_id = '25' or  department_id = '36'  or  department_id = '31'  or  department_id = '35'   ";
            }elseif($dpartid == 17) {
                if($row['userid']== '113615') {  //ฐิติมา
                    $sql = "select * from tbldepartment where department_id  in (5,6,7,8,9,10,11,12,25,31,32,36,35)";
                }else{

                    $sql = "select * from tbldepartment where department_id  in (17,36,34)";
                }
            }else{
                if($row['userid']== '116412') { //มยุรี
                    $sql = "select * from tbldepartment where department_id = '25' or  department_id = '22' or  department_id = '36' or  department_id = '31'  or  department_id = '35'";
                    //   $sql = "select * from tbldepartment where department_id  in (5,6,7,8,9,10,11,12,25,31,32,36)";
                }elseif($row['userid']== '113615'){
                    //  $sql = "select * from tbldepartment where department_id = '25' or  department_id = '22' or  department_id = '36'";
                    $sql = "select * from tbldepartment where department_id  in (5,6,7,8,9,10,11,12,25,31,32,36,35)";
                }else {
                    $sql = "select * from tbldepartment where department_id = $dpartid";
                }
            }

            //echo $sql;
            $result = mysql_query($sql);
            ?>
            <select name="dpart" id="dpart">

                <?
                while ($fetcharr = mysql_fetch_array($result)) {
                    $id = $fetcharr[department_id];
                    $department_name = $fetcharr[department_name];

                    echo "<option value=\"$id\">$department_name</option>\n";

                }

                ?>
            </select>
            &nbsp;&nbsp;ระดับการศึกษา :
            <select size="1" name="edulv">
                <option value="3" <?= $sel_edulv[1]; ?>>ปริญญาตรี</option>
                <option value="5" <?= $sel_edulv[2]; ?>>บัณฑิตศึกษา</option>
                <option value="0" <?= $sel_edulv[2]; ?>>รวมทั้งหมด</option>
<!--                <option value="7" --><?php //= $sel_edulv[3]; ?>
<!--                    ปริญญาเอก</option>-->
            </select>

            <br>
            จากวันที่

            <input type="text" name="startdate" id="startdate" value="<? echo $_REQUEST['startdate']; ?>"
                   onclick="javascript:NewCssCal('startdate')" style="cursor:pointer"/>
            <img src="../researchs/images2/cal.gif" onclick="javascript:NewCssCal('startdate')" style="cursor:pointer"/>
            &nbsp;
            ถึงวันที่
            <input type="text" name="enddate" id="enddate" value="<? echo $_REQUEST['enddate']; ?>"
                   onclick="javascript:NewCssCal('enddate')" style="cursor:pointer"/>
            <img src="../researchs/images2/cal.gif" onclick="javascript:NewCssCal('enddate')" style="cursor:pointer"/>

            &nbsp; </p>
        <p><strong>รูปแบบ </strong>
            <label>
                <input type="radio" name="ch" id="radio" value="0"/>
                ยังไม่ผ่านการรับรองผลสอบ (ที่ประชุมสาขาวิชา) </label>&nbsp;&nbsp;
            <label>
                <input type="radio" name="ch" id="radio" value="1" checked="checked"/>
                ผ่านการรับรองผลสอบ (ที่ประชุมสาขาวิชา) </label>
            &nbsp;&nbsp;<br>
            <font color="red"> ระบบจะสามารถรายงานได้ เฉพาะวิชาที่ผ่านที่ประชุมสาขาวิชา เท่านั้น !!</font>
            <!--      <label>-->
            <!--      <input type="radio" name="ch" id="radio" value="3" />-->
            <!--      ทั้งหมด </label>-->

        </p>
        <p>
            <input name="show" type="submit" value="พิมพ์รายงาน">
        </p>
    </div>
</form>