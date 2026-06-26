<?php
session_start();
include("../config.inc.php");
if ($login != true) {
    include("../relog.php");
} else {
    ConnDB();


    ?>
    <!--    --><?php //session_start(); ?>
    <style type="text/css">
        <!--
        .style3 {
            font-family: Tahoma, "Microsoft Sans Serif";
            font-weight: bold;
            font-size: 14px;
        }

        .style5 {
            font-family: Tahoma, "Microsoft Sans Serif";
            font-size: 14px;
        }

        .style6 {
            color: #FF0000
        }

        .style7 {
            color: #0000FF
        }

        -->
    </style>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-874"/>
    <p>&nbsp;</p>
    <?php
    session_start();
    if (!isset($_SESSION['hasRun'])) {
        $string = $username. "scita";
        $encodedString = base64_encode($string);
        $currentYear = date('Y');
        echo "<meta http-equiv='refresh' content='0; url=  https://ita.sc.kku.ac.th/surveyits/$currentYear/17/$encodedString'>";
        $_SESSION['hasRun'] = true;
    }
    ?>


    <?php
    // include("../config.inc.php");
    // ConnDB();


    if ($_REQUEST['year']) {
        $term = $_REQUEST['term'];
        $year = $_REQUEST['year'];
        $_SESSION["term_g"] = $term;
        $_SESSION["year_g"] = $year;

    } elseif (isset($_SESSION["year_g"])) {

        $term = $_SESSION["term_g"];
        $year = $_SESSION["year_g"];
    } else {
//        $vPdRound = checkPdRound1();
//        $tmppdRound = explode("/", $vPdRound);
//        $term = $tmppdRound[0];
//
//        $mo = intval(date('m'));
//        if ($mo >= 10) {
//            $year = $tmppdRound[1];
//        } else {
//            $year = $tmppdRound[1] - 1;
////            $year = $tmppdRound[1] ;
//        }

        $sql = "select * from grade_term ";
        //echo $sql;
        $resultx = mysql_query($sql);
        while ($mx = mysql_fetch_array($resultx)) {
            $year = $mx[year];
            $term = $mx[term];
        }


        unset($_SESSION["term_g"]);
        unset($_SESSION["year_g"]);
    }
    ?>
    <p align="center"><strong>แบบรายงานผลการสอบไล่</strong></p>
    <form name="form1" method="post" action="">


        <div align="center">ภาคการศึกษา&nbsp;&nbsp;
            <?php
            $xxx = $_POST["term"];
            //   echo "Term :  $xxx";
            if ($xxx == "") {
                $mo = intval(date('m'));
                $yo = intval(date('Y')) + 543;
                $y02 = intval(date('Y')) + 543;
                $y01 = $y02 - 1;
                // echo "-----> ".$mo."YY : $yo / Y1 : $y01  /Y2 : $y02";
                if ($yo == $y01) {
                    if ($mo >= 10 and $mo <= 12) {
                        $term = 1;
                    } else {
                        $term = 2;
                    }

                } else {
                    if ($mo == 1) {
                        $term = 1;
                    } elseif ($mo >= 2 and $mo <= 6) {
                        $term = 2;
                    } elseif ($mo >= 7 and $mo <= 9) {
                        $term = 3;
                    } else {
                        $term = 1;
                    }
                }

            }
            //echo $year;
            ?>
            <select name="term">
                <option value="1" <? if ($term == 1) { ?> selected <? } ?>>ภาคต้น</option>
                <option value="2" <? if ($term == 2) { ?> selected <? } ?>>ภาคปลาย</option>
                <option value="3" <? if ($term == 3) { ?> selected <? } ?>>ภาคการศึกษาพิเศษ</option>

            </select>


            &nbsp;&nbsp;&nbsp;ปีการศึกษา
            <select name="year">
                <? for ($i = 2554; $i <= 2580; $i++) { ?>
                    <option value="<?= $i ?>"<? if ($year == $i) { ?> selected <? } ?>>
                        <?= $i ?>
                    </option>
                <? } ?>
            </select>
            &nbsp;
            <input name="show" type="submit" value="&#3649;&#3626;&#3604;&#3591;">
        </div>
    </form>
    <p><a href="grade_add_new.php"><strong>สร้างแบบรายงานผลการสอบไล่</strong></a></p>
    <table width="943" border="1" cellpadding="0" cellspacing="0">
        <tr>
            <td width="346" bgcolor="#B9CAEE">
                <div align="center" class="style3">รายวิชา</div>
            </td>
            <td width="192" bgcolor="#B9CAEE">
                <div align="center" class="style3">ภาคการศึกษา</div>
            </td>
            <td width="192" bgcolor="#B9CAEE">
                <div align="center" class="style3"><strong>จำนวน นศ</strong></div>
            </td>
            <td width="203" bgcolor="#B9CAEE">
                <div align="center" class="style3">ทำรายการ</div>
            </td>
        </tr>
        <?


        //        $user_name = $_SESSION['user_name'];
        $user_name = $username;
        $ap3 = "<font color='red'>ส่งกลับแก้ไขจากส่วนกลาง</font>";
        $sql = "select * from grade_report 
  where username ='$user_name' and year=$year and term =$term   ";
        // echo $sql;
        $result = mysql_query($sql);
        while ($m = mysql_fetch_array($result)) {
            ?>
            <tr>
                <td><span class="style5">
      <?= $m[subject_code] ?>
      &nbsp;
      <?= $m[subject] ?>
                        <?php
                        if ($m[approv] == 0 and $m[dateapprove2] != "") {
                            // if($m[dateapprove2] != "") {
                            echo "<br>" . $ap3 . " [" . changeDate($m[dateapprove2]) . "]";
                        }
                        ?>
    </span></td>
                <td>
                    <div align="center"><span class="style5">
        <? if ($m[term] == 1) {
            echo "ภาคต้น ปีการศึกษา $year";
        } else if ($m[term] == 2) {
            echo "ภาคปลาย ปีการศึกษา $year";
        } else {
            echo "ภาคฤดูร้อน ปีการศึกษา $year";
        } ?>
      </span></div>
                </td>
                <td>
                    <div align="center" class="style5">
                        <?php
                        if ($m[approv] == 0) { ?>
                            <a href="grate_std_list.php?grade_id=<?= $m[grade_id] ?>&num01=<?= $m[statuseva] ?>">กรอกจำนวน
                                นศ.</a>
                            <?
                        } else {
                            echo "-";
                        } ?>
                    </div>
                </td>

                <td>
                    <div align="center" class="style5">
                        <?
                        $sql = "select * from grade_std   where grade_id =$m[grade_id]    ";
                        $result1 = mysql_query($sql);
                        $y = mysql_num_rows($result1);
                        if ($y > 0) {
                            ?>


                        <? } ?>
                        <?php
                        if ($m[approv] == 0) { ?>
                            <a href="grade_report_pdf.php?grade_id=<?= $m[grade_id] ?>" target="_blank">พิมพ์</a>
                            &nbsp;&nbsp;&nbsp;<a
                                    href="grade_edit_new.php?id=<?= $m[grade_id] ?>">แก้ไข</a>&nbsp;&nbsp;&nbsp;
                            <a href="grate.php?task=delteaching&&id=<?= $m[grade_id] ?>"
                               onclick="return confirm('คุณต้องการลบข้อมูลหรือไม่ ?')">ลบ</a>
                            <?
                        } else if ($m[approv] == 1) {
                            echo "ผ่านที่ประชุมกรรมการสาขาวิชาฯ";
                        } else if ($m[approv] == 2) {
                            echo "ผ่านที่ประชุมกรรมการคณะฯ";
                        }
                        ?>


                    </div>
                </td>
            </tr>
            <?
        }
        ?>
    </table>
    <p>
        <?
        if ($_GET[task] == 'delteaching') {

            $sql = "delete from grade_report  where grade_id='$id'";
            $result = mysql_query($sql);
            $sql = "delete from grade_std   where grade_id='$id' ";
            $result = mysql_query($sql);
            echo "<meta http-equiv='refresh' content='0; url=grate.php'>";
        }
        ?> <span class="style6">**เมื่อสร้างแบบรายงานผลการสอบไล่ แล้วต้องกรอกจำนวน นักศึกษาก่อนจึงจะสามารถพิมพ์แบบฟอร์มได้</span><br>
        <span class="style6">**<span class="style7">วิชาที่ส่งเกรดช้า และ มี I ตัองแนบบันทึกมาพร้อมกับใบส่งเกรด</span> (<a
                    href="http://sc2.kku.ac.th/office/sc-service/index.php/th/download"
                    target="_blank">Download</a>)</span></p>
<?php } ?>

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

//การเรียกใช้งาน Function
//echo change_date("2015-05-05");
?>
