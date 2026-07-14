<?
@ini_set('display_errors', '0');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-874"/>
    <title>ระบบดึงข้อมูลรายวิชาที่เปิดสอนภาระงาน</title>

    <script src="jquery.js"></script>


</head>

<body>
<p align="center"><strong>ดึงภาระงานจากรายวิชาที่เปิดสอนคณะวิทยาศาสตร์ ระบบ Reg</strong></p>
<p>&nbsp;</p>
<p>


</p>
<form id="form1" name="form1" method="post" action="">

    <div align="center"><strong>ปีการศึกษา</strong>&nbsp;
        <select id="lstYearID" name="lstYearID">
            <option>-เลือกปี-</option>
<!--            <option value="2556">2556</option>-->
<!--            <option value="2557">2557</option>-->
<!--            <option value="2558">2558</option>-->
<!--            <option value="2559">2559</option>-->
            <option value="2560">2560</option>
            <option value="2561">2561</option>
            <option value="2562">2562</option>
            <option value="2563">2563</option>
            <option value="2564">2564</option>
            <option value="2565">2565</option>
            <option value="2566">2566</option>
            <option value="2567">2567</option>
            <option value="2568">2568</option>
            <option value="2569">2569</option>
            <option value="2570">2570</option>
            <option value="2571">2571</option>
            <option value="2572">2572</option>
            <option value="2573">2573</option>
            <option value="2574">2574</option>
            <option value="2575">2575</option>
            <option value="2576">2576</option>
            <option value="2577">2577</option>
            <option value="2578">2578</option>
            <option value="2579">2579</option>
            <option value="2580">2580</option>
        </select>
        <input type="submit" name="button" id="button" value="Submit"/>

    </div>
</form>
<p>&nbsp;</p>


<br/>

<?
if ($_POST[button]) {

    require("conn_2.php");
    $years = $lstYearID - 543;
    $i = 1;
    $sql = "	  
				  SELECT course.COURSECODE,course.COURSENAMEENG,course.COURSEUNIT  from course	 WHERE course.CREATEDATETIME like '%$years%'  
				   and course.COURSECODE like '%SC%'  AND		course.COURSENAMEENG NOT LIKE '%SEMINAR%' AND
			course.COURSENAMEENG NOT LIKE '%THESIS%' AND
			course.COURSENAMEENG NOT LIKE '%INDEPENDENT STUDY%' AND
			course.COURSENAMEENG NOT LIKE '%DISSERTATION%'  and course.COURSENAMEENG != '' GROUP BY course.COURSECODE ";
    echo  $sql."<br>";
    $result4 = mysql_query($sql);
    while ($rs3 = mysql_fetch_array($result4)) {

        // echo $rs3['COURSECODE']."&nbsp;".$rs3['COURSENAMEENG']."&nbsp;".$rs3['ACADYEAR']."<br>";
        $COURSECODE = $rs3['COURSECODE'];
        $COURSENAMEENG = $rs3['COURSENAMEENG'];
        $COURSEUNIT = $rs3['COURSEUNIT'];


        require("myconuser.php");

        //ค้นหาว่ามีการบันทึกหรือไม่
        $sql = "select *  from pdcourse   where subjcode like '$COURSECODE'  ";
        $result = mysql_query($sql);
        //echo  $fname."&nbsp;".$lname."&nbsp; ไม่สามารถอัพเดทได้"."<br>";
        // echo $sql . "<br>";
        $m = mysql_num_rows($result);
        //  $data     =   mysql_fetch_array($result);
        //	$pdid1 = $data[pdid];
        echo $m;
        if ($m <= 0) {

            $sql = "insert into pdcourse (subjcode,subjname,courseint) values('$COURSECODE','$COURSENAMEENG','$COURSEUNIT')";
            $result = mysql_query($sql);
            echo $sql . "<br>";

        } else {

            // update
            /*
            $sql="replace into pdlist (id,pdtype,pdname,pdyear,pdterm,wkh,username) values('$pdid1','1','$subjcode $subjname','$pdyear','$pdterm','$wkh','$username1')";
            $result=mysql_query($sql);
            //echo $sql . "<br>";

            $pdid=mysql_insert_id();

           $sql="replace  into pd_teach1 (id,pdterm,pdyear,edulv,subjcode,subjname,credit,lec,lab,other,sec,stnum) values('$pdid','$pdterm','$pdyear','$edulv','$subjcode','$subjname','$credit','$lec','$lab','$other','$sec',$stnum)";
            $result=mysql_query($sql);
            */

        }

        echo $i . ") " . $COURSECODE . " : " . $COURSENAMEENG . "&nbsp;" . $lname . "&nbsp; อัพเดทเรียบร้อยแล้ว" . "<br>";
        $i++;
    }

}
?>
</body>

</html>




