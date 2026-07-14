<?
include("../config.inc.php");
ConnDB();

//   if($_POST[year]){
//   $term=$_POST[term];
//     $year=$_POST[year];
//
//   }else{
//       $vPdRound		= checkPdRound1();
//  $tmppdRound=explode("/",$vPdRound);
//$term=$tmppdRound[0];
//$year=$tmppdRound[1];
// }
?>
<script src="../researchs/datetimepicker_css.js"></script>
<p align="center">&nbsp;</p>
<p align="center"><strong>Download รายวิชา REG</strong></p>
<form name="form1" method="post" action="#" >


    <div align="center">
        <p>
            ภาคการศึกษา
            <select name="term" id="term">
                <option value="1">ภาคต้น</option>
                <option value="2">ภาคปลาย</option>
                <option value="3">ภาคฤดูร้อน</option>
            </select>

            ปีการศึกษา
            <select name="years" id="years">
                <option value="null">เลือก</option>
                <?php
                $datey=date("Y")+543;
                for($i=2566;$i<=$datey;$i++){?>
                    <option value="<?=$i;?>"><?=$i;?></option>
                <?php } ?>
            </select>
            <input name="show" type="submit" value=" ตกลง ">

<?php
//echo "<br>years : ".$_POST[years]."<br>";
if($_POST[years] != "null") {
    include("../config.reg21.php");
    $sqlof = " SELECT
	course.COURSECODE, 
	course.COURSENAMEENG, 
	class.SECTION, 
	class.ACADYEAR, 
	class.SEMESTER, 
	class.ENROLLSEAT, 
	course.PERIOD1, 
	course.CREDITTOTAL, 
	course.PERIOD2, 
	course.PERIOD3, 
	class.LEVELID, 
	officer.FACULTYID, 
	officer.OFFICERNAME, 
	officer.OFFICERSURNAME, 
	officer.KKUMAIL, 
	officer.OFFICEREMAIL, 
	officer.OFFICERID
FROM
	class
	INNER JOIN
	classinstructor
	ON 
		class.CLASSID = classinstructor.CLASSID
	INNER JOIN
	course
	ON 
		class.COURSEID = course.COURSEID
	INNER JOIN
	officer
	ON 
		classinstructor.OFFICERID = officer.OFFICERID
WHERE
	course.COURSENAMEENG NOT LIKE '%SEMINAR%' AND
	course.COURSENAMEENG NOT LIKE '%THESIS%' AND
	course.COURSENAMEENG NOT LIKE '%INDEPENDENT STUDY%' AND
	course.COURSENAMEENG NOT LIKE '%DISSERTATION%' AND
	officer.FACULTYID = '2' AND
	class.ACADYEAR = '$years' AND
	class.SEMESTER = '$term' ";
   // echo "<br><br>sql : $sqlof<br>";
    $result2 = mysql_query($sqlof);
    while ($rs2 = mysql_fetch_array($result2)) {
        $COURSECODE = $rs2[COURSECODE];
        $COURSENAMEENG = $rs2[COURSENAMEENG];
        $SECTION = $rs2[SECTION];
        $ACADYEAR = $rs2[ACADYEAR];
        $SEMESTER = $rs2[SEMESTER];
        $ENROLLSEAT = $rs2[ENROLLSEAT];
        $PERIOD1 = $rs2[PERIOD1];
        $CREDITTOTAL = $rs2[CREDITTOTAL];
        $PERIOD2 = $rs2[PERIOD2];
        $PERIOD3 = $rs2[PERIOD3];
        $LEVELID = $rs2[LEVELID];
        $FACULTYID = $rs2[FACULTYID];
        $OFFICERNAME = $rs2[OFFICERNAME];
        $OFFICERSURNAME = $rs2[OFFICERSURNAME];
        $KKUMAIL = $rs2[KKUMAIL];
        $OFFICEREMAIL = $rs2[OFFICEREMAIL];
        $OFFICERID = $rs2[OFFICERID];
       // echo $OFFICERID."<br>";

        if($OFFICERID!="")
        {
          //  echo "YYYYYYYYYYYYYYYYYYYYYYYYY<br>";
          //  echo "$COURSECODE,$COURSENAMEENG,$SECTION,$ACADYEAR,$SEMESTER,$LEVELID,$OFFICERNAME,$OFFICERSURNAME,$KKUMAIL,$OFFICERID<br>";
            $aa=addtblreportgrad($COURSECODE,$COURSENAMEENG,$SECTION,$ACADYEAR,$SEMESTER,$LEVELID,$OFFICERNAME,$OFFICERSURNAME,$KKUMAIL,$OFFICERID);
            if($aa==1){ echo "เพิ่ม $COURSECODE : $COURSENAMEENG เรียบร้อย...<br>";}
           // exit();
           // echo "aa : $aa<br>";
        }
        //else{
         //   echo "xxxxxxxxxxxxxxxxxxxxx<br>";
       // }

    }
    ?>
<!--    <meta http-equiv="refresh" content="1; url=index.php">-->
            <?php
}
?>


            <?php
            function addtblreportgrad($COURSECODE,$COURSENAMEENG,$SECTION,$ACADYEAR,$SEMESTER,$LEVELID,$OFFICERNAME,$OFFICERSURNAME,$KKUMAIL,$OFFICERID)
            {
                global $conn;
//            $sql_check = "SELECT * FROM addtblreport_grad_reg WHERE COURSECODE = '$COURSECODE'  and ACADYEAR = '$ACADYEAR'  and SEMESTER = '$SEMESTER'  and SECTION = '$SECTION'and OFFICERID = '$OFFICERID'";
                $sql_check = "SELECT * FROM grade_report_reg WHERE COURSECODE = '$COURSECODE'  and ACADYEAR = '$ACADYEAR'  and SEMESTER = '$SEMESTER'  and SECTION = '$SECTION'and OFFICERID = '$OFFICERID'";
            $result_check = mysql_query($sql_check,$conn);
         //   echo  $result_check;
//echo "<br>num_rows : ".mysql_num_rows($result_check)."<br>";
//echo "<br>sql_check : $sql_check<br>";
            if (mysql_num_rows($result_check) > 0) {
                $sql_update = "UPDATE grade_report_reg SET COURSECODE = '$COURSECODE', COURSENAMEENG = '$COURSENAMEENG', LEVELID = '$LEVELID',OFFICERNAME='$OFFICERNAME',
                            OFFICERSURNAME='$OFFICERSURNAME',KKUMAIL='$KKUMAIL' WHERE COURSECODE = '$COURSECODE' and  OFFICERID='$OFFICERID' and ACADYEAR='$ACADYEAR' and SEMESTER = '$SEMESTER' and SECTION='$SECTION'";
               // echo "sql_update : $sql_update<br>";
                                $result_update = mysql_query($sql_update,$conn);
                if (!$result_update)
                {
                    $a=0;
                }else{
                    $a=1;
                }

            }else{
                $sqlof = " INSERT INTO grade_report_reg (COURSECODE,COURSENAMEENG,SECTION,ACADYEAR,SEMESTER,LEVELID,FACULTYID,OFFICERNAME,OFFICERSURNAME,KKUMAIL,OFFICERID)
VALUES ('$COURSECODE','$COURSENAMEENG','$SECTION','$ACADYEAR','$SEMESTER','$LEVELID','2','$OFFICERNAME','$OFFICERSURNAME','$KKUMAIL','$OFFICERID') ";
//echo "sqlof : $sqlof<br>";
                $result2 = mysql_query($sqlof,$conn);
                if($result2)
                {
                    $a=1;
                }else{
                    $a=0;
                }
            }
return $a;
//----------------------------------------------
            }
            ?>

            &nbsp;    </p>

        <p>

        </p>
    </div>
</form>