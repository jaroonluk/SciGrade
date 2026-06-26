<?
include("../config.inc.php");
if ($login != true){
	include("../relog.php");
}else{
	ConnDB();
?>
<html>
<head>
<title>การจัดการระบบ e-office</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-874">
<style type="text/css">
small { font-family: Microsoft Sans Serif, MS Sans Serif, sans-serif; font-size: 10pt; } 
input,textarea { font-family: Microsoft Sans Serif, MS Sans Serif, sans-serif; font-size: 10pt; } 
b { font-family: Microsoft Sans Serif, MS Sans Serif, sans-serif; font-size: 10pt; } 
big { font-family: Microsoft Sans Serif, MS Sans Serif, sans-serif; font-size: 11pt; } 
FONT,td { font-family: Microsoft Sans Serif, MS Sans Serif, sans-serif; font-size: 10pt; } 
BODY { font-size: 11pt; font-family: Microsoft Sans Serif, MS Sans Serif, sans-serif; } 
body {  margin: 0px  0px; padding: 0px  0px}
.style8 {font-weight: bold; font-family: "Times New Roman", Times, serif; font-size: 12px; }
</style>
</head>
<body>
<br>
<?
   //Menu งานบริการ
$sql1="select * from  tblprivileges  where  system_id = '11' and username='$username' ";

 //echo $sql1;
$result_admin1= mysql_query($sql1);
 $rs_admin_service =mysql_fetch_array($result_admin1);


//ภาควิชา
 
?>
      <?
     // echo $username;
      if( $rs_admin_service[level] == '1'  || $username =='113615'  || $username=='114529'){ ?>
<TABLE WIDTH=80% align="center" BORDER=0 CELLPADDING=0 CELLSPACING=0>
  <TR>
    <TD><IMG SRC="../images/blocks/blocks01_01.gif" WIDTH=12 HEIGHT=37 ALT=""></TD>
    <TD   background="../images/blocks/blocks01_02.gif" WIDTH=100% HEIGHT=37 ALT=""  align="middle"><span class="style8">เมนูหลักสำหรับเจ้าหน้าที่สาขาวิชา/หัวหน้าสาขาวิชา</span></TD>
    <TD><IMG SRC="../images/blocks/blocks01_03.gif" WIDTH=14 HEIGHT=37 ALT=""></TD>
  </TR>
  <TR>
    <TD  background="../images/blocks/blocks01_04.gif" WIDTH=12 HEIGHT=100% ALT=""></TD>
    <TD  background="../images/blocks/blocks01_05.gif" WIDTH=100% HEIGHT=100% ALT="">
    <table width="100%"  border="0" align="center" cellpadding="5" cellspacing=0 bordercolorlight="#FFFFFF" bordercolordark="#FFFFFF">      
      <tr align="center">
        <td width="34%"><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="grade_department.php"><img src="../images/icon/03.png" alt="" width="100" height="100" border="0"></a><a href="../add-user.php"></a><br>
            <a href="grade_department.php">อนุมัติเกรดรายวิชา</a></font></td>
          <td width="34%"><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="grade_report1.php"><img src="../images/icon/08.png" alt="" width="100" height="100" border="0"></a><a href="../add-user.php"></a><br>
                  <a href="grade_report1.php">แบบรายงานผลการสอบไล่ รวม</a></font></td>
          <td width="33%"><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="fee_research1.php"><img src="images/app_preferences.gif" alt="" width="50" height="50" border="0"></a><a href="../add-user.php"></a><br>
                  <a href="fee_research1.php">รายงานข้อมูลชำระเงินค่าธรรมเนียมวิจัย</a></font></td>
        </tr>
      <tr align="center">
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>
      <tr align="center">
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        </tr>
        <tr align="center">
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        </tr>
        <tr align="center">
         <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
    </table></TD>
    <TD  background="../images/blocks/blocks01_06.gif" WIDTH=14 HEIGHT=100% ALT=""></TD>
  </TR>
  <TR>
    <TD><IMG SRC="../images/blocks/blocks01_07.gif" WIDTH=12 HEIGHT=20 ALT=""></TD>
    <TD  background="../images/blocks/blocks01_08.gif" WIDTH=100% HEIGHT=20 ALT=""></TD>
    <TD><IMG SRC="../images/blocks/blocks01_09.gif" WIDTH=14 HEIGHT=20 ALT=""></TD>
  </TR>
</TABLE>
   <?  } ?>
<br>
      <?       if( $rs_admin_service[level] == '0' ){ ?>
<br>
<TABLE WIDTH=80% align="center" BORDER=0 CELLPADDING=0 CELLSPACING=0>
  <TR>
    <TD><IMG SRC="../images/blocks/blocks01_01.gif" WIDTH=12 HEIGHT=37 ALT=""></TD>
    <TD   background="../images/blocks/blocks01_02.gif" WIDTH=100% HEIGHT=37 ALT=""  align="middle"><span class="style8">เมนูหลักสำหรับงานบริการ คณะวิทยาศาสตร์</span></TD>
    <TD><IMG SRC="../images/blocks/blocks01_03.gif" WIDTH=14 HEIGHT=37 ALT=""></TD>
  </TR>
  <TR>
    <TD  background="../images/blocks/blocks01_04.gif" WIDTH=12 HEIGHT=100% ALT=""></TD>
    <TD  background="../images/blocks/blocks01_05.gif" WIDTH=100% HEIGHT=100% ALT="">
    <table width="100%"  border="0" align="center" cellpadding="5" cellspacing=0 bordercolorlight="#FFFFFF" bordercolordark="#FFFFFF">      
      <tr align="center">
        <td width="34%"><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="grade_report.php"><img src="../images/icon/08.png" alt="" width="50" height="50" border="0"></a><a href="../add-user.php"></a><br>
            <a href="grade_report.php">แบบรายงานผลการสอบไล่ แต่ละสาขาวิชา</a></font></td>
        <td width="33%"><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="grade_manage.php"><img src="../images/icon/03.png" width="50" height="50"  border="0"></a><br>
              <a href="grade_manage.php">อนุมัติเกรดรายวิชาแต่ละสาขาวิชา</a></font></td>
        <td width="33%"><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="major_depprt.php"><img src="images/app_preferences.gif" alt="" width="50" height="50" border="0"></a><a href="../add-user.php"></a><br>
            <a href="major_depprt.php">กำหนดสาขา ในภาควิชาต่างๆ</a></font></td>
        </tr>
      <tr align="center">
        <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="grade_round.php"><img src="images/large-icons.gif" alt="" width="50" height="50"  border="0"></a><br>
            <a href="grade_round.php">กำหนด ภาคการศึกษา ปีการศึกษาสำหรับรายงานเกรด</a></font></td>
        <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="master_depart.php"><img src="images/large-icons.gif" alt="" width="50" height="50"  border="0"></a><br>
            <a href="master_depart.php">กำหนดอาจารย์ หัวหน้าสาขาวิชา</a></font></td>
        <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="fee_research_load.php"><img src="../images/icon/08.png" alt="" width="50" height="50"  border="0"></a><a href="../add-user.php"></a><br>
            <a href="fee_research_load.php">ดึงข้อมูล นักศึกษาจากสำนักทะเีบียน</a></font></td>
      </tr>
      <tr align="center">
        <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="fee_research.php"><img src="images/app_preferences.gif" alt="" width="50" height="50" border="0"></a><a href="../add-user.php"></a><br>
            <a href="fee_research.php">จัดการข้อมูลชำระเงินค่าธรรมเนียมวิจัย</a></font></td>
        <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="add_couse_dpart.php"><img src="../images/icon/08.png" alt="" width="82" height="82" border="0"></a><br>
            <a href="add_couse_dpart.php">เพิ่มข้อมูลหลักสูตร ในภาควิชา</a></font></td>
        <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="grade_sum.php"><img src="images/app_preferences.gif" alt="" width="50" height="50" border="0"></a> <br>
            <a href="grade_sum.php">แบบรายงานเกรดเฉลี่ยแต่ละรายวิชา</a></font></td>
        </tr>

        <tr align="center">
            <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="grade_language.php">
                        <img src="images/app_preferences.gif" alt="" width="50" height="50" border="0"></a>
<!--                    <a href="../add-user.php"></a>-->
                    <br>
                    <a href="grade_language.php">แบบรายงานรายวิชาที่เปิดสอน<br>แยกตามภาษาที่ใช้ในการสอน</a></font></td>
            <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="../teacher66/dump_grade_report2.php">
                        <img src="images/app_preferences.gif" alt="" width="50" height="50" border="0"></a>
                    <!--                    <a href="../add-user.php"></a>-->
                    <br>
                    <a href="../teacher66/dump_grade_report2.php">Dump วิชาคู่ (รายงานผลการสอบไล่ ADMIN!!)</a></font></td>
            <td></td>
        </tr>


        <tr align="center">
        <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="report_fee1.php"><img src="../images/icon/08.png" alt="" width="50" height="50" border="0"></a><br>
            <a href="report_fee1.php">รายงานค่าธรรมเนียมวิจัย</a></font></td>
            
        <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="sub_grade.php"><img src="images/large-icons.gif" alt="" width="50" height="50"  border="0"></a><br>
            <a href="sub_grade.php">ยิง Barcode  รับเข้าใบส่งเกรด</a></font></td>
        <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="sub_send_grade.php"><img src="images/large-icons.gif" alt="" width="50" height="50"  border="0"></a><br>
            <a href="sub_send_grade.php">ยิง Barcode  ส่งออกใบส่งเกรด</a></font></td>
        </tr>
        <tr align="center">
         <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="im_exam.php"><img src="images/app_preferences.gif" alt="" width="50" height="50" border="0"></a><br>
             <a href="im_exam.php">นำเข้าข้อมูล สำหรับรัน Barcode  ติดซองข้อสอบ</a></font></td>
             
          <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="print_exam.php"><img src="../images/icon/08.png" alt="" width="82" height="82" border="0"></a><br>
          <a href="print_exam.php">พิมพ์ Barcode  ติดซองข้อสอบ</a></font></td>
          <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="sub_exam_in.php"><img src="images/large-icons.gif" alt="" width="50" height="50" border="0"></a> <br>
              <a href="sub_exam_in.php">ตรวจเช็ค การรับข้อสอบ</a></font></td>
        </tr>
           <tr align="center">
         <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="sub_exam_out.php"><img src="../images/icon/08.png" alt="" width="50" height="50" border="0"></a> <br>
             <a href="sub_exam_out.php">ตรวจเช็ค การส่งข้อสอบ</a></font></td>
          <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="report_grade_nsend.php"><img src="../images/icon/08.png" alt="" width="82" height="82" border="0"></a><br>
            <a href="report_grade_nsend.php">รายงานจำนวนรายวิชาที่ส่งเกรดเข้าประชุมกรรมการคณะแบบที่ 1</a></font></td>
          <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="report_grade_nsend1.php"><img src="../images/icon/08.png" alt="" width="82" height="82" border="0"></a><br>
              <a href="report_grade_nsend1.php">รายงานจำนวนรายวิชาที่ส่งเกรดเข้าประชุมกรรมการคณะแบบที่ 2</a></font></td>
        </tr>
        <tr align="center">
         <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="std_load.php"><img src="images/large-icons.gif" alt="" width="50" height="50" border="0"></a> <br>
             <a href="std_load.php">ส่งออกข้อมูลนักศึกษาสำหรับทุน พสวท.</a></font></td>
         <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="../teacher/grate.php"><img src="images/large-icons.gif" alt="" width="50" height="50" border="0"></a></font>
         <!--
         <font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="https://sc3.kku.ac.th/sci_class/web/manage.php"><img src="images/large-icons.gif" alt="" width="50" height="50" border="0"></a> <br>
          <a href="https://sc3.kku.ac.th/sci_class/web/manage.php">จัดการข้อมูลห้องเรียน</a></font>
          -->
          <br>
          <a href="../teacher/grate.php">กรอก รายงานผลการสอบ (แทน อาจารย์)</a></td>
              <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="https://e.sc.kku.ac.th/reg/index_pdcouse.php"><img src="../images/icon/08.png" alt="" width="50" height="50"  border="0"></a><a href="../add-user.php"></a><br>
            <a href="https://e.sc.kku.ac.th/reg/index_pdcouse.php">ดึงข้อมูลรายวิชาจากสำนักทะเบียน<br>(แสดงข้อมูลรายวิชา รายงานผลสอบไล่)</a></font></td>
        <td>&nbsp;</td>
        </tr>

        <tr align="center">

            <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="grade_dump_f.php">
                        <img src="images/large-icons.gif" alt="" width="50" height="50" border="0"></a> <br>
                    <a href="grade_dump_f.php">1.update ข้อมูลบุคลากร REG.</a></font></td>

            <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="grade_dump_f2.php">
                        <img src="images/large-icons.gif" alt="" width="50" height="50" border="0"></a> <br>
                    <a href="grade_dump_f2.php">2.Download ข้อมูลรายวิชา</a></font></td>

            <td><font size="2" face="Microsoft Sans Serif, MS Sans Serif, sans-serif"><a href="grade_department_for_reg.php">
                        <img src="images/large-icons.gif" alt="" width="50" height="50" border="0"></a> <br>
                    <a href="grade_department_for_reg.php">3.จัดการข้อมูลรายวิชา REG.</a>
                <br>
                    <br>
                    <a href="grade_grade_department_f1.php">4.จัดการข้อมูลรายวิชา REG.</a>
                    <br>
                    <a href="grade_grade_department_f2.php">5.ตรวจสอบสถานะการส่งผลสอบ.</a>
                </font>

            </td>

            <td>&nbsp;</td>
        </tr>


    </table></TD>
    <TD  background="../images/blocks/blocks01_06.gif" WIDTH=14 HEIGHT=100% ALT=""></TD>
  </TR>
  <TR>
    <TD><IMG SRC="../images/blocks/blocks01_07.gif" WIDTH=12 HEIGHT=20 ALT=""></TD>
    <TD  background="../images/blocks/blocks01_08.gif" WIDTH=100% HEIGHT=20 ALT=""></TD>
    <TD><IMG SRC="../images/blocks/blocks01_09.gif" WIDTH=14 HEIGHT=20 ALT=""></TD>
  </TR>
</TABLE>
  <? }?>
<p>
  <?}?>
</p>
<p>&nbsp; </p>
</body>
</html>
