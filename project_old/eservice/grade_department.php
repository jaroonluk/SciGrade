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
<p align="center"><strong>แบบรับรอง ผลการสอบไล่ เมื่อผ่านการพิจารณาที่ประชุมสาขาวิชา</strong></p>
<form name="form1" method="post" action="approv_grade_f.php" >
 
  
  <div align="center">
    <p><strong>ผ่านที่ประชุมสาขาวิชา  :</strong> &nbsp;
      <?
	     
		 //Get department
	  $sql			="select  * from tbluser where username='$username'";
	  $result = mysql_query($sql);
	  $row = mysql_fetch_array($result);
	  
	  $dpartid=$row[department_id];
	  //echo $dpartid;
	  if($dpartid == 25) {
          //dpart
          $sql = "select * from tbldepartment where department_id = '25' or  department_id = '36'  or  department_id = '31'   or  department_id = '35' ";
          }elseif($dpartid == 17) {
      if($row['userid']== '113615') {  //ฐิติมา
          $sql = "select * from tbldepartment where department_id  in (5,6,7,8,9,10,11,12,25,31,32,36,35)";
      }else{
          
          $sql = "select * from tbldepartment where department_id  in (17,36,34)";
      }
      }else{
          if($row['userid']== '116412'){  // มยุรี
              $sql = "select * from tbldepartment where department_id = '25' or  department_id = '22' or  department_id = '36'";
           //   $sql = "select * from tbldepartment where department_id  in (5,6,7,8,9,10,11,12,22,25,31,32,36)";
          }elseif($row['userid']== '113615'){  //ฐิติมา
            //  $sql = "select * from tbldepartment where department_id = '25' or  department_id = '22' or  department_id = '36'";
              $sql = "select * from tbldepartment where department_id  in (5,6,7,8,9,10,11,12,25,31,32,36,35)";
          }else{
            $sql = "select * from tbldepartment where department_id = $dpartid";
          }
      }
		//   echo $sql;
		  $result = mysql_query($sql);
		  ?>
	     <select name="dpart" id="dpart" >
	       
	       <?
            while( $fetcharr = mysql_fetch_array($result) )
	       { 
	        	$id = $fetcharr[department_id];
	        	$department_name = $fetcharr[department_name];
				
	        	echo "<option value=\"$id\">$department_name</option>\n";
	       
  	           }
	
			?>
           </select>
      เมื่อวันที่
      
      <input type="text" name="startdate" id="startdate" value="<? echo $_REQUEST['startdate'];?>"  onclick="javascript:NewCssCal('startdate')" style="cursor:pointer"  required />
	    <img src="../researchs/images2/cal.gif" onclick="javascript:NewCssCal('startdate')" style="cursor:pointer"/> &nbsp;

      
&nbsp;    </p>

    <p>
      <input name="show" type="submit" value=" ตกลง ">
        </p>
  </div>
</form>