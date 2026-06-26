

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <link rel="stylesheet" href="../eplan/css/bootstrap-3.3.2.min.css" type="text/css">
    <link rel="stylesheet" href="../eplan/css/bootstrap-example.min.css" type="text/css">
    <link rel="stylesheet" href="../eplan/css/prettify.min.css" type="text/css">
    <script type="text/javascript" src="../eplan/js/jquery-2.1.3.min.js"></script>
    <script type="text/javascript" src="../eplan/js/bootstrap-3.3.2.min.js"></script>
    <script type="text/javascript" src="../eplan/s/prettify.min.js"></script>


    <link rel="stylesheet" href="../eplan/css/bootstrap-multiselect.css" type="text/css"/>
    <script type="text/javascript" src="../eplan/js/bootstrap-multiselect.js"></script>
<!--    <meta http-equiv="Content-Type" content="text/html; charset=windows-874"/>-->

<meta http-equiv="Content-Type" content="text/html; charset=windows-874" />

<title>Untitled Document</title>
<style type="text/css">
<!--
.style1 {color: #FF0000}
.style2 {font-weight: bold}
.style3 {
	color: #000000;
	font-weight: bold;
	font-size:14px;
}
-->

</style>

    <script type="text/javascript">
        $(document).ready(function () {
            $('#fac').multiselect({
                enableFiltering: true
            });
        });
    </script>

</head>

 

 <script language="javascript" type="text/javascript">
ccom=0;
function formdisplay() {
	cmntblck = document.getElementById('formhide');
	if (ccom == 0) {
		cmntblck.style.display = "inline";
		ccom=1;
	} else {
		cmntblck.style.display = "none";
		ccom=0;
	}
}
  </script>

<script>
		function txt1_keyup(){
			$("#divresult").load("checkexamquery.php?a="+document.getElementById("subjcode1").value,
				function(){
					document.getElementById("txt2").value=document.getElementById("divresult").innerHTML;
				}
			);
		}
		</script>
        
      
        
<body>

<p>
  <?

  include("../config.inc.php");
  ConnDB(); 

    $grade_id = $_GET[grade_id];
	    $num01 = $_GET[num01];
	$sql="select * from grade_report   where grade_id =$grade_id   ";
	$result=mysql_query($sql);
	$m=mysql_fetch_array($result);
	//echo $sql;
	if($_POST[B2]){
  if($m[degree]==5 or $m[degree]==7) {
      $pacs = $_POST[fac1];
  }else{
      $pacs = implode(",", $_POST[fac]);
  }
	if($pacs != "")
    {
        $total_std = $_POST[num_a]+$_POST[num_bb]+$_POST[num_b]+$_POST[num_cc]+$_POST[num_c]+$_POST[num_dd]+$_POST[num_d]+$_POST[num_f]+$_POST[num_s]+$_POST[num_i]+$_POST[num_v]+$_POST[num_w]+$_POST[num_out];

        $sql="replace into grade_std 
 (grade_std_id,grade_id,sec,fac,total_std,num_a,num_bb,num_b,num_cc,num_c,num_dd,num_d,
 num_f,num_ff,num_i,num_s,num_v,num_w,num_out,evaluationscore,type_course,numstdevz)     
 values('$grade_std_id','$grade_id','$_POST[sec]','$pacs','$total_std','$_POST[num_a]',
'$_POST[num_bb]','$_POST[num_b]','$_POST[num_cc]','$_POST[num_c]','$_POST[num_dd]','$_POST[num_d]','$_POST[num_f]','$_POST[num_ff]','$_POST[num_i]','$_POST[num_s]' ,'$_POST[num_v]','$_POST[num_w]','$_POST[num_out]','$_POST[scire]','$_POST[subjtype]','$_POST[numstdevz]')";
        mysql_query($sql);
//        echo $sql; exit();
    }else{
      //  echo "กรุณาเลือกคณะ !!";
        echo '<script type="text/javascript">alert("กรุณาเลือกคณะ !!");</script>';
    }

//Header("Location: ?grade_id=$grade_id");

    //echo $sql;
	
	 
	 	}
		$sql="select * from grade_std   where grade_std_id =$_GET[grade_std_id]  ";
	$result=mysql_query($sql);
	$kk=mysql_fetch_array($result);
?>
  <?
     if($_GET[task]=='delteaching')
    {
	   
	$sql="delete from grade_std   where grade_std_id =$_GET[id] ";

		$result=mysql_query($sql);
		//Header('Location: ?grade_id=$grade_id ');	
		
	}
	
	//check gs
	$mgs =substr($m['subject_code'], 3, 1);
	$gstxt="";
	if($kk['fac']){
	 $gstxt=$kk['fac'];
	}else{
	if($mgs==7 || $mgs==8 || $mgs==9 ){
	   $gstxt="GS";
	}else{
	  $gstxt="";
	}
	}
?>
</p>
<p align="center">  <strong>กรอกจำนวนนักศึกษา</strong></p>
<div align="center"><br>
<a href="#formadd" class="add_topic" onClick="formdisplay();"><img src="pic/addnew2.jpg" border="0" /></a></div>

<div id=formhide <?if ($grade_std_id == "") echo 'style="display: none;"';?>>
<form method="post" enctype="multipart/form-data" action="<? $PHP_SELF ?>" enctype="multipart/form-data" name="checkForm"
      id="checkForm">
  <TABLE WIDTH=66% align="center" BORDER=0 CELLPADDING=0 CELLSPACING=0>
    <TR>
      <TD><IMG SRC="../images/blocks/blocks01_01.gif" WIDTH=12 HEIGHT=37 ALT=""></TD>
      <TD   background="../images/blocks/blocks01_02.gif" WIDTH=600 HEIGHT=37 ALT=""  align="middle"><span class="style3">กรอกข้อมูล รายงานเกรดรายวิชา <?=$m[subject_code]?>&nbsp;<?=$m[subject]?><?php // echo " | edulv : $mgs | degree : ".$m[degree];//echo $PHP_SELF; edulv?></span></TD>
      <TD><IMG SRC="../images/blocks/blocks01_03.gif" WIDTH=14 HEIGHT=37 ALT=""></TD>
    </TR>
    <TR>
      <TD  background="../images/blocks/blocks01_04.gif" WIDTH=12 HEIGHT=100% ALT=""></TD>
      <TD  background="../images/blocks/blocks01_05.gif" WIDTH=600 HEIGHT=100% ALT=""><table border="0" width="100%" id="table3" cellspacing="0" cellpadding="0">
        <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"><b>กลุ่ม ::</b></span></td>
          <td width="601" bgcolor="#FFFFCC"><label>
          <select name="sec">
          <?php for($i=1;$i<=50;$i++){?>
          <option value="<? echo $i;?>" <? if($kk[sec]==$i) { echo "selected" ; }?> ><? echo $i;?></option>
          <?php } ?>
          </select>
             
          </label></td>
        </tr>
       
        <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"><b> คณะ::</b></span></td>
          <td bgcolor="#FFFFCC">

              <?php
             // echo "degree : ". $m[degree]."<br>";
              if($m[degree]==5 or $m[degree]==7)
              {
                  ?>
                  <input name="fac1" type="text" id="fac1" size="5" readonly value="GS" style="background-color: #D3D3D3"/>
              <?
              }else{
                  ?>

                  <?php
                 // $sql = "select * from grade_type  ORDER BY nameng asc";
                  $sql="SELECT * 
FROM grade_type 
ORDER BY 
    CASE 
        WHEN id IN (3) THEN 0 
        ELSE 1 
    END,
    nameng ASC";
                  $rs = mysql_query($sql);
                  $rsb = mysql_query($sql);
                  $arrayfac = explode(",", $kk[fac]);
                   ?>

                  <select id="fac" name="fac[]" multiple="multiple">
                      <?
                      $xs1=0;
                      while ($a = mysql_fetch_array($rs)) {

                              if (in_array($a['nameng'], $arrayfac)) {
                                  ?>
                                  <option selected="selected" value="<?= $a['nameng'] ?>"><?= $a['nameng'] ." : ".$a['namethai']?></option>
                                      <?php

                              }else{
                                  ?>
                                  <option value="<?= $a['nameng'] ?>"><?= $a['nameng'] ." : ".$a['namethai']?></option>
                                  <?php
                              }

                          $xs1++;

                      } ?>

                  </select>
              <?php
              }
              ?>

          </td>
        </tr>



              <tr>
          <td align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"><b>&nbsp;ประเภทรายวิชา ::</b></span></td>
          <td bgcolor="#FFFFCC">
<!--          --><?php // if($kk[type_course] != "2"){?>
<!--          <input name="subjtype" id="subjtype" type="radio" value="1" checked="checked" />-->
<!--ภาคปกติ -->
<!--  <input name="subjtype" id="subjtype" type="radio" value="2" />-->
<!--โครงการพิเศษ -->
<?php //}else{?>
<!--   <input name="subjtype" id="subjtype" type="radio" value="1"  />-->
<!--ภาคปกติ -->
<!--  <input name="subjtype" id="subjtype" type="radio" value="2"  checked="checked"/>-->
<!--โครงการพิเศษ -->
<!---->
<?php //} ?>

              <?php  if($kk[type_course] == "1"){?>
                  <input name="subjtype" id="subjtype" type="radio" value="1" checked="checked" />
                  ภาคปกติ 
                  <input name="subjtype" id="subjtype" type="radio" value="2" />
                  โครงการพิเศษ
                  <input name="subjtype" id="subjtype" type="radio" value="3" />
                  ก้าวหน้า <br>
                  <input name="subjtype" id="subjtype" type="radio" value="4" />
                  ปกติ นานาชาติ
                  <input name="subjtype" id="subjtype" type="radio" value="5" />
                  โครงการพิเศษ นานาชาติ
              <?php }elseif($kk[type_course] == "2"){?>
                  <input name="subjtype" id="subjtype" type="radio" value="1"  />
                  ภาคปกติ 
                  <input name="subjtype" id="subjtype" type="radio" value="2"  checked="checked"/>
                  โครงการพิเศษ
                  <input name="subjtype" id="subjtype" type="radio" value="3" />
                  ก้าวหน้า <br>
                  <input name="subjtype" id="subjtype" type="radio" value="4" />
                  ปกติ นานาชาติ
                  <input name="subjtype" id="subjtype" type="radio" value="5" />
                  โครงการพิเศษ นานาชาติ
              <?php }elseif($kk[type_course] == "3"){?>
                  <input name="subjtype" id="subjtype" type="radio" value="1"  />
                  ภาคปกติ 
                  <input name="subjtype" id="subjtype" type="radio" value="2"  />
                  โครงการพิเศษ
                  <input name="subjtype" id="subjtype" type="radio" value="3" checked="checked"/>
                  ก้าวหน้า <br>
                  <input name="subjtype" id="subjtype" type="radio" value="4" />
                  ปกติ นานาชาติ
                  <input name="subjtype" id="subjtype" type="radio" value="5" />
                  โครงการพิเศษ นานาชาติ
              <?php }elseif($kk[type_course] == "4"){?>
                  <input name="subjtype" id="subjtype" type="radio" value="1"  />
                  ภาคปกติ 
                  <input name="subjtype" id="subjtype" type="radio" value="2"  />
                  โครงการพิเศษ
                  <input name="subjtype" id="subjtype" type="radio" value="3" />
                  ก้าวหน้า <br>
                  <input name="subjtype" id="subjtype" type="radio" value="4" checked="checked"/>
                  ปกติ นานาชาติ
                  <input name="subjtype" id="subjtype" type="radio" value="5" />
                  โครงการพิเศษ นานาชาติ
              <?php }elseif($kk[type_course] == "5"){?>
                  <input name="subjtype" id="subjtype" type="radio" value="1"  />
                  ภาคปกติ 
                  <input name="subjtype" id="subjtype" type="radio" value="2" />
                  โครงการพิเศษ
                  <input name="subjtype" id="subjtype" type="radio" value="3" />
                  ก้าวหน้า <br>
                  <input name="subjtype" id="subjtype" type="radio" value="4" />
                  ปกติ นานาชาติ
                  <input name="subjtype" id="subjtype" type="radio" value="5" checked="checked"/>
                  โครงการพิเศษ นานาชาติ
              <?php }else{ ?>
                  <input name="subjtype" id="subjtype" type="radio" value="1" checked="checked" />
                  ภาคปกติ 
                  <input name="subjtype" id="subjtype" type="radio" value="2" />
                  โครงการพิเศษ
                  <input name="subjtype" id="subjtype" type="radio" value="3" />
                  ก้าวหน้า <br>
                  <input name="subjtype" id="subjtype" type="radio" value="4" />
                  ปกติ นานาชาติ
                  <input name="subjtype" id="subjtype" type="radio" value="5" />
                  โครงการพิเศษ นานาชาติ
              <?php } ?>
</td>
        </tr>
        <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"><b>จำนวน น.ศ. เกรด A(<?=$m[score_a]?>) ::</b></span></td>
          <td bgcolor="#FFFFCC"><input name="num_a" type="text" id="num_a" size="5"  value="<?=$kk[num_a]?>" /> คน</td>
        </tr>
        <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"><b> จำนวน น.ศ. เกรด B+(<?=$m[score_bb]?>) ::</b></span></td>
          <td bgcolor="#FFFFCC"><input name="num_bb" type="text" id="num_bb" size="5" value="<?=$kk[num_bb]?>"  /> คน</td>
        </tr>
         <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"><b>จำนวน น.ศ. เกรด B(<?=$m[score_b]?>) ::</b></span></td>
          <td bgcolor="#FFFFCC"><input name="num_b" type="text" id="num_b" size="5"  value="<?=$kk[num_b]?>"/> คน&nbsp;</td>
        </tr>
          <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"><b>จำนวน น.ศ. เกรด C+(<?=$m[score_cc]?>) ::</b></span></td>
          <td bgcolor="#FFFFCC"><input name="num_cc" type="text" id="num_cc" size="5" value="<?=$kk[num_cc]?>" /> คน</td>
        </tr>
           <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"><b>จำนวน น.ศ. เกรด C(<?=$m[score_c]?>) ::</b></span></td>
          <td bgcolor="#FFFFCC"><input name="num_c" type="text" id="num_c" size="5" value="<?=$kk[num_c]?>"  /> คน</td>
        </tr>
           <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"><b>จำนวน น.ศ. เกรด D+(<?=$m[score_dd]?>) ::</b></span></td>
          <td bgcolor="#FFFFCC"><input name="num_dd" type="text" id="num_dd" size="5"  value="<?=$kk[num_dd]?>"/> คน</td>
        </tr>
           <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"><b>จำนวน น.ศ. เกรด D(<?=$m[score_d]?>) ::</b></span></td>
          <td bgcolor="#FFFFCC"><input name="num_d" type="text" id="num_d" size="5"  value="<?=$kk[num_d]?>" /> คน</td>
        </tr>
         <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"><b>จำนวน น.ศ. เกรด F(<?=$m[score_f]?>) ::</b></span></td>
          <td bgcolor="#FFFFCC"><input name="num_f" type="text" id="num_f" size="5" value="<?=$kk[num_f]?>"  /> คน</td>
        </tr>
         <!--<tr>
          <td width="265" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"><b>จำนวน น.ศ. เกรด F(ขาดสอบ) ::</b></span></td>
          <td bgcolor="#FFFFCC"><input name="num_ff" type="text" id="num_ff" size="5" value="<?=$kk[num_ff]?>"  /> คน</td>
        </tr>-->
        <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"><b>จำนวน น.ศ. เกรด I ::</b></span></td>
          <td bgcolor="#FFFFCC"><input name="num_i" type="text" id="num_i" size="5" value="<?=$kk[num_i]?>" /> คน</td>
        </tr>
        <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"><b>จำนวน น.ศ. เกรด S ::</b></span></td>
          <td bgcolor="#FFFFCC"><input name="num_s" type="text" id="num_s" size="5" value="<?=$kk[num_s]?>"  /> คน</td>
        </tr>
        <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"><b>จำนวน น.ศ. เกรด U ::</b></span></td>
          <td bgcolor="#FFFFCC"><input name="num_v" type="text" id="num_v" size="5" value="<?=$kk[num_v]?>" /> คน</td>
        </tr>
        <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"><b>จำนวน น.ศ. เกรด W ::</b></span></td>
          <td bgcolor="#FFFFCC"><input name="num_w" type="text" id="num_w" size="5" value="<?=$kk[num_w]?>"  /> คน</td>
        </tr>
      
       <!--
        <tr>
          <td align="right" valign="top" bgcolor="#CCCCFF"class="style2"><b>จำนวน น.ศ. ลาออก ::</b></td>
          <?// if($std[stdnum]=="") {$tmpstdnum= 1;} else {$tmpstdnum= $std[stdnum];}  ?>
          <td bgcolor="#FFFFCC"><input name="num_out" type="text" id="num_out" size="5" value="<?//=$kk[num_out]?>" /> คน&nbsp;&nbsp; </td>
        </tr>
         -->
         <?php if($kk[statuseva] != '2'  and  $num01 != '2'){   //?> 
        <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2">จำนวนนักศึกษาที่เข้าประเมิน ::<?php // echo "KK :".$kk[statuseva] . "|num01  $num01"?></span></td>
          <td bgcolor="#FFFFCC"><label><input name="numstdevz"   type="text" id="numstdevz" size="5" value="<?=$kk[numstdevz]?>"  />        
         
         คน 
     
         </label></td>
        </tr>
        <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2">ผลการประเมินรายวิชาโดยนักศึกษา ::</span></td>
  <td bgcolor="#FFFFCC"><input name="scire" type="number" id="scire" size="5" min="1" max="5"  step="0.01"  value="<?=$kk[evaluationscore]?>"  />
    คะแนน <span class="style1">(ไม่เกิน 5 คะแนน โดยดูผลประเมินจาก https://reg.kku.ac.th)</span></td>
        </tr>
        <?php } ?>
        <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"></span></td>
          <td bgcolor="#FFFFCC"><input type="submit" value="บันทึก" name="B2">
              <span lang="en-us"> : </span>
            <input type="reset" value="ล้างข้อมูล" name="B3"></td>
        </tr>
        <tr>
          <td width="344" align="right" valign="top" bgcolor="#CCCCFF"><span class="style2"></span></td>
          <td bgcolor="#FFFFCC"><p align="right">
        
          </td>
        </tr>

      </table></TD>
      <TD  background="../images/blocks/blocks01_06.gif" WIDTH=14 HEIGHT=100% ALT=""></TD>
    </TR>
    <TR>
      <TD><IMG SRC="../images/blocks/blocks01_07.gif" WIDTH=12 HEIGHT=20 ALT=""></TD>
      <TD  background="../images/blocks/blocks01_08.gif" WIDTH=600 HEIGHT=20 ALT=""></TD>
      <TD><IMG SRC="../images/blocks/blocks01_09.gif" WIDTH=14 HEIGHT=20 ALT=""></TD>
    </TR>
  </TABLE>

<input type="hidden" value="<?=$grade_id;?>" name="grade_id">
<input type="hidden" value="<?=$_REQUEST['term'];?>" name="ti1">
<input type="hidden" value="<?=$_REQUEST['year'];?>" name="year">
<input type="hidden" value="<?=$kk[grade_std_id];?>" name="grade_std_id">
</form>
</div>
<div align="center"><br />
  <?
    $sql ="select * from grade_std   where grade_id =$grade_id ";
	$result1=mysql_query($sql);
	//echo " $sql<br>";


	//Get  ช่วงคะแนน  grade_report
      $sql ="select * from grade_report   where  grade_id  =$grade_id ";
	// echo $sql ;
	  $result2=mysql_query($sql);
	  $gg=mysql_fetch_array($result2);
	 $a =$gg[score_a];
	 $bb =$gg[score_bb];
	 $b =$gg[score_b];
	 $cc =$gg[score_cc];
	 $c =$gg[score_c];
	 $dd =$gg[score_dd];
	 $d =$gg[score_d];
	 $f =$gg[score_f];
  $edulv =$gg[edulv];
//  echo "edulv :  $edulv <br>";
  ?>
  <table width="1017" cellpadding="0" cellspacing="0" border="1">
      
    <tr valign="top" align="middle" bgcolor="#ffff66">
      <td width="50" height="58" bgcolor="#CCCCFF"><div align="center"><strong>ดำเนินการ</strong></div></td>
      <td width="25" bgcolor="#CCCCFF"><div align="center"><strong>กลุ่ม</strong></div></td>
      <td width="25" bgcolor="#CCCCFF"><div align="center"><strong><strong>(คณะ)</strong> </strong></div></td>
      <td width="50" bgcolor="#CCCCFF"><div align="center"><strong>      จำนวนนักศึกษา<br />
      (คน) </strong></div></td>
      <td width="50" bgcolor="#CCCCFF"><div align="center"><strong>A</strong></div></td>
      <td width="50" bgcolor="#CCCCFF"><div align="center"><strong>B+</strong></div></td>
      <td width="50" bgcolor="#CCCCFF"><div align="center"><strong>B</strong></div></td>
      <td width="50" bgcolor="#CCCCFF"><div align="center"><strong>C+</strong></div></td>
      <td width="50" bgcolor="#CCCCFF"><div align="center"><strong>C</strong></div></td>
      <td width="50" bgcolor="#CCCCFF"><div align="center"><strong>D+</strong></div></td>
      <td width="50" bgcolor="#CCCCFF"><div align="center"><strong>D</strong></div></td>
      <td width="50" bgcolor="#CCCCFF"><div align="center"><strong>F</strong></div></td>
     <!--  <td width="50" bgcolor="#CCCCFF"><div align="center"><strong>F<br />
      ขาดสอบ</strong></div></td> -->
      <td width="50" bgcolor="#CCCCFF"><div align="center"><strong>I</strong></div></td>
      <td width="50" bgcolor="#CCCCFF"><div align="center"><strong>S</strong></div></td>
      <td width="50" bgcolor="#CCCCFF"><div align="center"><strong>U</strong></div></td>
      <td width="50" bgcolor="#CCCCFF"><div align="center"><strong>W</strong></div></td>
    <!--  <td width="50" bgcolor="#CCCCFF"><div align="center"><strong>ลาออก</strong></div></td>-->
      <td width="50" bgcolor="#CCCCFF"><div align="center"><strong>ค่าเฉลี่ย</strong></div></td>
      <td width="50" align="middle" valign="top" bgcolor="#CCCCFF"><div align="center"><strong>SD</strong></div></td>
       <td width="50" align="middle" valign="top" bgcolor="#CCCCFF"><div align="center"><strong>คะแนนประเมิน(นศ.)</strong></div></td>
    </tr>
    <tr bgcolor="#ffff99">
      <td width="25" bgcolor="#FFFFCC"> </td>
       <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><strong> </strong></td>
      <td width="25" bgcolor="#FFFFCC"> </td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><strong>ช่วงคะแนน</strong></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><strong><?=$a?></strong></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><strong><?=$bb?></strong></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><strong><?=$b?></strong></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><strong><?=$cc?></strong></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><strong><?=$c?></strong></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><strong><?=$dd?></strong></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><strong><?=$d?></strong></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><strong><?=$f?></strong></td>
     <!-- <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><strong> xx</strong></td> -->
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><strong> </strong></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><strong> </strong></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><strong> </strong></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><strong> </strong></td>
     <!-- <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><strong> x</strong></td>-->
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC">&nbsp;</td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC">&nbsp;</td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC">&nbsp;</td>
    </tr>
    <?
   while($yy=mysql_fetch_array($result1)){
  ?>
    <tr bgcolor="#ffff99">
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><a href="?grade_id=<?=$yy[grade_id]?>&num01=<?=$gg[statuseva]?>&&grade_std_id=<?=$yy[grade_std_id]?>#formadd"  >แก้ไข</a> <a href="?task=delteaching&&id=<?=$yy[grade_std_id]?>&&grade_id=<?=$yy[grade_id]?>" onClick="return confirm('คุณต้องการลบข้อมูลหรือไม่ ?')">ลบ</a> </div></td>
      <td width="25" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?=$yy[sec];?></div></td>
      <?php
      if($yy[type_course] == "1")
	  {
	  //$tyf="ภาคปกติ";
	  $tyf="";
	  }elseif($yy[type_course] == "2")
	  {
		  $tyf="(โครงการพิเศษ)";  
	  }
	  ?>
      <td width="25" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?=strtoupper($yy[fac])."$tyf";?></div></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?=$yy[total_std];?></div></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?=$yy[num_a];?></div></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?=$yy[num_bb];?></div></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?=$yy[num_b];?></div></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?=$yy[num_cc];?></div></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?=$yy[num_c];?></div></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?=$yy[num_dd];?></div></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?=$yy[num_d];?></div></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?=$yy[num_f];?></div></td>
    <!--  <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?//=$yy[num_ff];?></div>
      xx</td>  -->
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?=$yy[num_i];?></div></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?=$yy[num_s];?></div></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?=$yy[num_v];?></div></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?=$yy[num_w];?></div></td>
     <!-- <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?//=$yy[num_out];?>x</div></td>-->
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"> 
      <strong><?=$gg[mean];?></strong></td>
      <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"> 
      <strong><?=$gg[sd];?></strong></td>
       <td width="50" align="middle" valign="top" bgcolor="#FFFFCC"><div align="center"><?=$yy[evaluationscore];?></div></td>
    </tr>
    <? }?>
    </table>

  <p><a href="grate.php?term=<?=$_REQUEST['ti1'];?>&year=<?=$_REQUEST['year'];?>"><strong>กลับหน้าแรก</strong></a></p>
</div>
</body>
</html>
