<?php
require_once("../config.inc.php");
if ($login != true){
    include("../relog.php");
}else {
echo "Hello Dump grad_report2 <br>";
//exit();

ConnDB();
//$sqlx=" select * from tblapprove_pd WHERE username = '114650 ' and evalter = '108038' and term = '1' and years = '2564' ORDER BY date_user desc LIMIT 1  ";
$sqlx = " SELECT * FROM grad_report2_f ";
$rsMWL = mysql_query($sqlx) or die("<br> Can't Open tblapprove_pd =" . mysql_error() . " <br> LINE AT=" . __LINE__);
$n = mysql_num_rows($rsMWL);
//echo "N : $n<br>";
while ($wl = mysql_fetch_array($rsMWL)) {
//                                $markname="$mark.$ma";
    $subject_code2 = $wl[subject_code2];
    $subject_code = $wl[subject_code];
    $subject = $wl[subject];
   // echo "$subject_code2 : $subject_code : $subject <br>";
    // $ma++;

    //----------------------------- >
//    $sqly = " SELECT * FROM grad_report2 where subject_code2  = '$subject_code2 '  and subject_code = '$subject_code ' ";
//    $rsy1 = mysql_query($sqly) or die("<br> Can't Open tblapprove_pd =" . mysql_error() . " <br> LINE AT=" . __LINE__);
//    $n = mysql_num_rows($rsy1);
//   // echo "-------> N : $n<br>";
//    if($n ==0){  // insert เฉพาะวิชาที่ไม่ซ้ำเท่านั้น
//        $sql = " insert into grad_report2 (subject_code2,subject_code,subject) values ('$subject_code2','$subject_code','$subject')";
//
//        $result = mysql_query($sql);
//        if($result)
//        {
//            echo "Complete -----> $sql<br>";
//        }
//    }
//    ConnDB();
   // $subject_code = mysql_real_escape_string($subject_code);
    //$subject_code2 = mysql_real_escape_string($subject_code2);

    $sqly1 = " SELECT * FROM grad_report2 where  subject_code = '$subject_code' "; //subject_code2  = '$subject_code2 '  and
   // echo "$sqly1 <br>";
    $rsy1 = mysql_query($sqly1) or die("<br> Can't Open tblapprove_pd =" . mysql_error() . " <br> LINE AT=" . __LINE__);
    $n1 = mysql_num_rows($rsy1);
    // echo "-------> N : $n<br>";
    if($n1 ==0)
    {  // check insert เฉพาะวิชาที่ไม่ซ้ำเท่านั้น
        $sqly2 = " SELECT * FROM grad_report2 where  subject_code2 = '$subject_code' "; //subject_code2  = '$subject_code2 '  and
        $rsy2 = mysql_query($sqly2) or die("<br> Can't Open tblapprove_pd =" . mysql_error() . " <br> LINE AT=" . __LINE__);
        $n2 = mysql_num_rows($rsy2);
        if($n2==0){
            $sqly3 = " SELECT * FROM grad_report2 where  subject_code = '$subject_code2' "; //subject_code2  = '$subject_code2 '  and
            echo "$sqly3 <br>";
            $rsy3 = mysql_query($sqly3) or die("<br> Can't Open tblapprove_pd =" . mysql_error() . " <br> LINE AT=" . __LINE__);
            $n3 = mysql_num_rows($rsy3);
            if($n3==0){
                $sqly4 = " SELECT * FROM grad_report2 where  subject_code2 = '$subject_code2'"; //subject_code2  = '$subject_code2 '  and
                $rsy4 = mysql_query($sqly4) or die("<br> Can't Open tblapprove_pd =" . mysql_error() . " <br> LINE AT=" . __LINE__);
                $n4 = mysql_num_rows($rsy4);
                if($n4==0){
                    $sql = " insert into grad_report2 (subject_code2,subject_code,subject) values ('$subject_code2','$subject_code','$subject')";
                    $result = mysql_query($sql);
                    if($result)
                    {
                        echo "Complete -----> $sql<br>";
                    }else {
                        echo "Insert failed: " . mysql_error() . "";
                    }
                }else{
                    $wl4 = mysql_fetch_assoc($rsy4);
                    $subject_code2_4 = $wl4[subject_code2];
                    $subject_code_4 = $wl4[subject_code];
                    $subject_4 = $wl4[subject];
                    $sql = " insert into grad_report2 (subject_code2,subject_code,subject) values ('$subject_code2_4','$subject_code','$subject_4')";
                    $result = mysql_query($sql);
                    if($result)
                    {
                        echo "Complete -----> $sql<br>";
                    }else {
                        echo "Insert failed: " . mysql_error() . "";
                    }
                }
            }else{
                $wl3 = mysql_fetch_assoc($rsy3);
                $subject_code2_3 = $wl3[subject_code2];
                $subject_code_3 = $wl3[subject_code];
                $subject_3 = $wl3[subject];
                $sql = " insert into grad_report2 (subject_code2,subject_code,subject) values ('$subject_code2_3','$subject_code','$subject_3')";
                $result = mysql_query($sql);
                if($result)
                {
                    echo "Complete -----> $sql<br>";
                }else {
                    echo "Insert failed: " . mysql_error() . "";
                }
            }
        }else{
           // $wl2 = mysql_fetch_array($rsy2);
            $wl2 = mysql_fetch_assoc($rsy2);
            $subject_code2_2 = $wl2[subject_code2];
            $subject_code_2 = $wl2[subject_code];
            $subject_2 = $wl2[subject];
            $sql = " insert into grad_report2 (subject_code2,subject_code,subject) values ('$subject_code2_2','$subject_code','$subject_2')";
            $result = mysql_query($sql);
            if($result)
            {
                echo "Complete -----> $sql<br>";
            }else {
                echo "Insert failed: " . mysql_error() . "";
            }
        }


    }

    //-------------------------------->
}

}

?>
