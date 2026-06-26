<?php
include("../config.inc.php");
if ($login != true) {
    include("../relog.php");
} else {
    ConnDB();
    $user_name=$username;
    ?>
    <?php session_start(); ?>

    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    <script type='text/javascript' src='autoNumeric.js'></script>

    <script src="https://kit.fontawesome.com/ff6d88ef1e.js" crossorigin="anonymous"></script>

    <style type="text/css">
        <!--

        .require {
            height: 20px;
            color: #FF0000;
            padding-left: 5px;
            padding-right: 5px;
            font-size: 12px;
            line-height: 15px;
            width: 100px;
            float: none;
        }


        -->
    </style>

    <script language="JavaScript">
        function chkNum(ele) {
            <!--   var num = parseFloat(ele.value); -->
            <!--    ele.value = num.toFixed(0);-->
            var num = parseFloat(ele.value);
            num2 = num.toFixed(0);
            if (num2 != num) num2 = num.toFixed(2);
            ele.value = num2;

        }


    </script>

    <script language="JavaScript">

        <!--
        function namosw_goto_byselect(sel, targetstr) {
            var index = sel.selectedIndex;
            if (sel.options[index].value != '') {
                if (targetstr == 'blank') {
                    window.open(sel.options[index].value, 'win1');
                } else {
                    var frameobj;
                    if (targetstr == '') targetstr = 'self';
                    if ((frameobj = eval(targetstr)) != null)
                        frameobj.location = sel.options[index].value;
                }
            }
        }

        // -->
    </script>

    <script type="text/javascript">

        function chk_form() {
            $(".aa + span.require").remove();
            $(".aa").each(function () {
                $(this).each(function () {
                    if ($(this).val() == "") {
                        $(this).after("<span class=require>This field is required</span>");
                    }
                });
            });
            if ($(".aa").next().is(".require") == false) {
                return true;
            } else {
                return false;
            }
        }
    </script>

    <!--    <link rel="stylesheet" type="text/css" href="../despatch/script/jquery.autocomplete.css" />-->
    <!--    <script type="text/javascript" src="../despatch/script/jquery.js"></script>-->
    <!--    <script type='text/javascript' src='../despatch/script/jquery.autocomplete.js'></script>-->
    <script type="text/javascript">
        $().ready(function () {

            $("#subjcode1").autocomplete({
                source: "get_course_json.php",
                minLength: 2,
                select: function (event, ui) {
                    $("#txt2").val(ui.item.id);
                }
            });

            $("#std_i1").autocomplete({
                source: "get_course_json2.php",
                minLength: 2,
                select: function (event, ui) {
                    // $("#txt2").val(ui.item.id);
                }
            });

        });
    </script>

    <style type="text/css">


        /* The hint to Hide and Show */
        .hint {
            display: none;
            position: absolute;
            right: 150px;
            width: 200px;
            margin-top: -4px;
            border: 1px solid #c93;
            padding: 10px 12px;
            /* to fix IE6, I can't just declare a background-color,
            I must do a bg image, too!  So I'm duplicating the pointer.gif
            image, and positioning it so that it doesn't show up
            within the box */
            background: #ffc url(../images/pointer.gif) no-repeat -10px 5px;
        }

        /* The pointer image is hadded by using another span */
        .hint .hint-pointer {
            position: absolute;
            left: -10px;
            top: 5px;
            width: 10px;
            height: 19px;
            background: url(../images/pointer.gif) left top no-repeat;
        }

        .style1 {
            color: #FF0000
        }

        .style2 {
            font-size: 16px
        }
    </style>

    <script type="text/javascript">
        function addLoadEvent(func) {
            var oldonload = window.onload;
            if (typeof window.onload != 'function') {
                window.onload = func;
            } else {
                window.onload = function () {
                    oldonload();
                    func();
                }
            }
        }


        function prepareInputsForHints() {
            var inputs = document.getElementsByTagName("input");
            for (var i = 0; i < inputs.length; i++) {
                // test to see if the hint span exists first
                if (inputs[i].parentNode.getElementsByTagName("span")[0]) {
                    // the span exists!  on focus, show the hint
                    inputs[i].onfocus = function () {
                        this.parentNode.getElementsByTagName("span")[0].style.display = "inline";
                    }
                    // when the cursor moves away from the field, hide the hint
                    inputs[i].onblur = function () {
                        this.parentNode.getElementsByTagName("span")[0].style.display = "none";
                    }
                }
            }
            // repeat the same tests as above for selects
            var selects = document.getElementsByTagName("select");
            for (var k = 0; k < selects.length; k++) {
                if (selects[k].parentNode.getElementsByTagName("span")[0]) {
                    selects[k].onfocus = function () {
                        this.parentNode.getElementsByTagName("span")[0].style.display = "inline";
                    }
                    selects[k].onblur = function () {
                        this.parentNode.getElementsByTagName("span")[0].style.display = "none";
                    }
                }
            }
        }

        addLoadEvent(prepareInputsForHints);
    </script>


    <SCRIPT LANGUAGE="JavaScript">
        <!--

        function formatDecimal(input) {
            if (input == null || isNaN(input)) {
                return "";
            }

            input += '';
            var x = input.split('.');
            var wholeNumber = x[0];
            var fraction = input - Math.floor(input);
            var _fraction = Math.round(fraction * 100).toString();
            var rgx = /(\d+)(\d{3})/;
            while (rgx.test(wholeNumber)) {
                wholeNumber = wholeNumber.replace(rgx, '$1' + ',' + '$2');
            }
            if (_fraction.length == 1) {
                _fraction = "0" + _fraction;
            }
            return wholeNumber + '.' + _fraction;
        }

        function format_amount(source) {
            var amountTextboxName = source.id;
            var amountTextbox = document.getElementById(amountTextboxName);

            var val = formatDecimal(amountTextbox.value);

            amountTextbox.value = val;
        }


        function compare() {

            var a1 = parseInt(document.myform.num_a1.value);
            var a2 = parseInt(document.myform.num_a2.value);
            var bb1 = parseInt(document.myform.num_bb1.value);
            var bb2 = parseInt(document.myform.num_bb2.value);
            var b1 = parseInt(document.myform.num_b1.value);
            var b2 = parseInt(document.myform.num_b2.value);
            var cc1 = parseInt(document.myform.num_cc1.value);
            var cc2 = parseInt(document.myform.num_cc2.value);
            var c1 = parseInt(document.myform.num_c1.value);
            var c2 = parseInt(document.myform.num_c2.value);
            var dd1 = parseInt(document.myform.num_dd1.value);
            var dd2 = parseInt(document.myform.num_dd2.value);
            var d1 = parseInt(document.myform.num_d1.value);
            var d2 = parseInt(document.myform.num_d2.value);
            var f1 = parseInt(document.myform.num_f1.value);
            var f2 = parseInt(document.myform.num_f2.value);


            if (a1 != '' && a2 != '') { //
                if (a1 < a2) {
                    alert("ค่าคะแนนช่องที่ 1 ต้องมากกว่าช่องที่ 2 ครับ");
                    document.myform.num_a1.focus();
                    return false;
                }
            }

            if (bb1 != '' && bb2 != '') { //
                if (bb1 < bb2) {
                    alert("ค่าคะแนนช่องที่ 1 ต้องมากกว่าช่องที่ 2 ครับ");
                    document.myform.num_bb1.focus();
                    return false;
                }
            }

            if (b1 != '' && b2 != '') { //
                if (b1 < b2) {
                    alert("ค่าคะแนนช่องที่ 1 ต้องมากกว่าช่องที่ 2 ครับ");
                    document.myform.num_b1.focus();
                    return false;
                }
            }


            if (cc1 != '' && cc2 != '') { //
                if (cc1 < cc2) {
                    alert("ค่าคะแนนช่องที่ 1 ต้องมากกว่าช่องที่ 2 ครับ");
                    document.myform.num_cc1.focus();
                    return false;
                }
            }

            if (c1 != '' && c2 != '') { //
                if (c1 < c2) {
                    alert("ค่าคะแนนช่องที่ 1 ต้องมากกว่าช่องที่ 2 ครับ");
                    document.myform.num_c1.focus();
                    return false;
                }
            }

            if (dd1 != '' && dd2 != '') { //
                if (dd1 < dd2) {
                    alert("ค่าคะแนนช่องที่ 1 ต้องมากกว่าช่องที่ 2 ครับ");
                    document.myform.num_dd1.focus();
                    return false;
                }
            }

            if (d1 != '' && d2 != '') { //
                if (d1 < d2) {
                    alert("ค่าคะแนนช่องที่ 1 ต้องมากกว่าช่องที่ 2 ครับ");
                    document.myform.num_d1.focus();
                    return false;
                }
            }

            if (f1 != '' && f2 != '') { //
                if (f1 < f2) {
                    alert("ค่าคะแนนช่องที่ 1 ต้องมากกว่าช่องที่ 2 ครับ");
                    document.myform.num_f1.focus();
                    return false;
                }
            }


            if (!isNaN(f1) && !isNaN(f2)) { //
                if (isNaN(d1) || isNaN(d2) || isNaN(dd1) || isNaN(dd2) || isNaN(c1) || isNaN(c2) || isNaN(cc1) || isNaN(cc2) || isNaN(b1) || isNaN(b2) || isNaN(bb1) || isNaN(bb2) || isNaN(a1) || isNaN(a2)) { //
                    alert("กรุณากรอกคะแนนให้ครบจนถึงช่องเกรด F ครับ ");
                    //    document.myform.num_f1.focus();
                    return false;
                }
            }


            if (!isNaN(d1) && !isNaN(d2)) { //
                if (isNaN(dd1) || isNaN(dd2) || isNaN(c1) || isNaN(c2) || isNaN(cc1) || isNaN(cc2) || isNaN(b1) || isNaN(b2) || isNaN(bb1) || isNaN(bb2) || isNaN(a1) || isNaN(a2)) { //
                    alert("กรุณากรอกคะแนนให้ครบจนถึงช่องเกรด D ครับ ");
                    //    document.myform.num_f1.focus();
                    return false;
                }
            }

            if (!isNaN(dd1) && !isNaN(dd2)) { //
                if (isNaN(c1) || isNaN(c2) || isNaN(cc1) || isNaN(cc2) || isNaN(b1) || isNaN(b2) || isNaN(bb1) || isNaN(bb2) || isNaN(a1) || isNaN(a2)) { //
                    alert("กรุณากรอกคะแนนให้ครบจนถึงช่องเกรด D+ ครับ ");
                    //    document.myform.num_f1.focus();
                    return false;
                }
            }

            if (!isNaN(c1) && !isNaN(c2)) { //
                if (isNaN(cc1) || isNaN(cc2) || isNaN(b1) || isNaN(b2) || isNaN(bb1) || isNaN(bb2) || isNaN(a1) || isNaN(a2)) { //
                    alert("กรุณากรอกคะแนนให้ครบจนถึงช่องเกรด C ครับ ");
                    //    document.myform.num_f1.focus();
                    return false;
                }
            }

            if (!isNaN(cc1) && !isNaN(cc2)) { //
                if (isNaN(b1) || isNaN(b2) || isNaN(bb1) || isNaN(bb2) || isNaN(a1) || isNaN(a2)) { //
                    alert("กรุณากรอกคะแนนให้ครบจนถึงช่องเกรด C+ ครับ ");
                    //    document.myform.num_f1.focus();
                    return false;
                }
            }

            if (!isNaN(b1) && !isNaN(b2)) { //
                if (isNaN(bb1) || isNaN(bb2) || isNaN(a1) || isNaN(a2)) { //
                    alert("กรุณากรอกคะแนนให้ครบจนถึงช่องเกรด B ครับ ");
                    //    document.myform.num_f1.focus();
                    return false;
                }
            }

            if (!isNaN(bb1) && !isNaN(bb2)) { //
                if (isNaN(a1) || isNaN(a2)) { //
                    alert("กรุณากรอกคะแนนให้ครบจนถึงช่องเกรด B+ ครับ ");
                    //    document.myform.num_f1.focus();
                    return false;
                }
            }

        }


        //-->
    </SCRIPT>
    <meta http-equiv="Content-Type" content="text/html; charset=windows-874"/>
    <?
    ///  include("../config.inc.php");
    // ConnDB();


    ?>
    <?

    if ($_POST['ti1'] != '' && $_POST['year'] != '' && $_POST['subjcode1'] != '' && $_GET['selecttype'] != '0' && $_GET['selecttype'] != '') { //or $_POST['selecttype'] != "0"   subjcode1   selecttype

        $xd = $desc_y . "-" . $desc_m . "-" . $desc_d;
        //$d =$desc_y."-".$desc_m."-".$desc_d;
        $num_a = $num_a1 . "-" . $num_a2;
        $num_bb = $num_bb1 . "-" . $num_bb2;
        $num_b = $num_b1 . "-" . $num_b2;
        $num_cc = $num_cc1 . "-" . $num_cc2;
        $num_c = $num_c1 . "-" . $num_c2;
        $num_dd = $num_dd1 . "-" . $num_dd2;
        $num_d = $num_d1 . "-" . $num_d2;
        $num_f = $num_f1 . "-" . $num_f2;
        $year = $_POST['year'];
        $teacher = $_POST['teacher'];
        $subjtype = $_POST['subjtype'];

        //check บันทึกว่ามีรายวิชาหรือไม่
        $sql = "select * from grade_report where  term =$ti1 and  year=$year and subject_code='$subjcode1' and username='$user_name' ";
        //   echo $sql;
        $result = mysql_query($sql) or die(mysql_error());
        $num_rows = mysql_num_rows($result);

        /*   if($num_rows>=1){

           echo  " <script>alert('ไม่สามารถบันทึกได้ เนื่องจากท่านได้กรอกรายวิชา  $subjcode1 ในภาคเรียนที่ $ti1 ปีการศึกษา $year เรียบร้อยแล้ว');history.back();</script><br> " ;
     exit();

           }else{*/
        //  echo "xxxxx : ".$_POST["year"];

        $selecttype = $_GET["selecttype"];
        $num01 = $_POST["num01"];
        //  $subjcode1= trim($subjcode1);
        $subjcode1 = str_replace(" ", "", $subjcode1, $var);
        //$subjcode1=trim($subjcode1);
        $subjcode1 = preg_replace('/[[:space:]]+/', '', trim($subjcode1));
        //  echo "subjcode1 : $subjcode1<br>";
        $subjcode2 = checksubject($subjcode1,$std_i1);
        // echo "subjcode2 : $subjcode2<br>";
        $num05 = $_POST["num05"];
        if ($num05 == 1) {
            $std_i = "ตัดเกรดร่วมกับ :" . $_POST["std_i1"];
            // Insert ตาราง grad_report2  เพื่อเชควิชาที่ตัดเกรดร่วม
            checksubjectID(trim($subjcode1), trim($_POST["std_i1"]),$subject,$user_name);
            // exit();
        } elseif ($num05 == 2) {
            $std_i = "ติด I เนื่องจาก :" . $_POST["std_i2"];
        } elseif ($num05 == 3) {
            $std_i = $_POST["std_i3"];
        }

        if ($user_name != "") {
            $SQL = "insert into grade_report 
(created,term,year,subject_code,subject_code2,subject,username,score_a,score_bb,score_b,
score_cc,score_c,score_dd,score_d,score_f,mean,sd,reasonid,reason,teacher,type_course,degree,programid,selecttype,totalnumstdevz,totalevaluationscore,statuseva,intflag
) 
 values ('$xd',$ti1,$year,'$subjcode1','$subjcode2','$subject','$user_name','$num_a','$num_bb','$num_b',
'$num_cc','$num_c','$num_dd','$num_d','$num_f','$mean','$sd_std','$num05','$std_i','$teacher','$subjtype','$edulv','$programid','$selecttype','$totalnumstdevz','$totalevaluationscore','$num01','$intflag'
)";
        } else {
            echo "ไม่สามารถบันทึกข้อมูลได้ เนื่องจากเปิดหน้าจอการใช้งานนานเกินกำหนดเวลา กรุณาดำเนินการใหม่ค่ะ !!";
            echo "<meta http-equiv='refresh' content='2; url=grate.php'>";
        }


//echo $SQL ;
        $result = mysql_query($SQL) or die(mysql_error());
        $grade_id = mysql_insert_id();

        $_SESSION["term_g"] = $ti1;
        $_SESSION["year_g"] = $year;


        if ($result) {
            ?>
            <BR><BR>
            <CENTER>
                <table width="478" height="161" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td height="35" bgcolor="#E2E2E2">&nbsp;</td>
                    </tr>
                    <tr>
                        <td height="85" align="center" bgcolor="#FFFFFF"><br/>
                            <br/>
                            <img src="../images/loading02.gif" width="120" height="26"/><br/>
                            <br/>
                            <span class="style3">กรุณารอซักครู่...ระบบกำลังบันทึกข้อมูล</span></td>
                    </tr>
                    <tr>
                        <td height="35" bgcolor="#E2E2E2">&nbsp;</td>
                    </tr>
                </table>
            </CENTER>


            <?

            //----------------------- ประเมิน ----------------------------
            if (!isset($_SESSION['hasRun'])) {
                $string = $username . "scita";
                //echo $string;
                $encodedString = base64_encode($string);
                $currentYear = date('Y');
                $url = "https://ita.sc.kku.ac.th/surveyitsb/$currentYear/17/$encodedString";
                // ใช้ Header redirect (ดีที่สุด)
                header("Location: $url");
                exit;
            }


            //----------------------------------------------------------

            echo "<meta http-equiv='refresh' content='2; url=grate_std_list.php?grade_id=$grade_id&num01=$num01'>";
        } else {
            echo "ไม่สามารถบันทึกข้อมูลได้";
            echo "<meta http-equiv='refresh' content='2; url=grate.php'>";
        }
    } else {
        ?>
        <div align="center"><br>

            <strong>เพิ่มแบบรายงานผลการสอบไล่</strong>
<!--            --><?php //=$username;?><!--/ user_name : --><?php //=$user_name;?>
            <br>
        </div>
        <SCRIPT language="JavaScript" src="functions.js">
            function check_form() {
                var year = document.form.year.value;
                //  var lastname = document.form.lastname.value;
                if (year.length < 1) {
                    alert("โปรดกรอกปีการศึกษา");
                    document.myform.year.focus();
                    return false;
                } else {
                    document.form.Submit.disabled = true;
                    return true;
                }
            }
        </SCRIPT>
        <form id="myform" name="myform" method="post" action="" onSubmit="return compare()"
              enctype="multipart/form-data" onsubmit="return check_form();">
            <div align="center">
                <table width="806" border="0">
                    <tr>
                        <td>&nbsp;</td>
                        <td class="style1">
                            <div align="right" class="style2">กรุณาเลือก หรือ กรอกที่มี * ให้ครบ</div>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#FFD7EB"><strong>ประเภทรายวิชา</strong>&nbsp;<span class="style1">*</span></td>
                        <td>

                            <select size="1" name="selecttype" id="selecttype"
                                    onChange="namosw_goto_byselect(this, 'self')"> >
                                <?php if ($selecttype == "0" || $selecttype == "") { ?>
                                    <option value="?selecttype=0" selected="selected"><---เลือก---></option>
                                    <option value="?selecttype=1">รายวิชาบริการ</option>
                                    <option value="?selecttype=2">รายวิชาในหลักสูตร</option>
                                <?php } elseif ($selecttype == "1") { ?>
                                    <option value="?selecttype=0"><---เลือก---></option>
                                    <option value="?selecttype=1" selected="selected">รายวิชาบริการ</option>
                                    <option value="?selecttype=2">รายวิชาในหลักสูตร</option>
                                <?php } elseif ($selecttype == "2") { ?>
                                    <option value="?selecttype=0"><---เลือก---></option>
                                    <option value="?selecttype=1">รายวิชาบริการ</option>
                                    <option value="?selecttype=2" selected="selected">รายวิชาในหลักสูตร</option>
                                <?php } ?>
                            </select>
                            <!--
                        <a href="../despatch/script/jquery.autocomplete.css">x</a>
                        -->
                        </td>
                    </tr>


                    <!--                    <tr>-->
                    <!--                        <td bgcolor="#FFD7EB"><strong>ระดับการศึกษา</strong>&nbsp;<span class="style1">*</span></td>-->
                    <!--                        <td>-->
                    <?php
                    ////$sel_edulv[$subj[edulv]] = " selected";
                    //?>
                    <!--                            <select size="1" name="edulv">-->
                    <!--                                <option value="1">ปริญญาตรี</option>-->
                    <!--                                <option value="2">ปริญญาโท</option>-->
                    <!--                                <option value="3">ปริญญาเอก</option>-->
                    <!--                            </select>-->
                    <!--                        </td>-->
                    <!--                    </tr>-->


                    <tr>
                        <td width="236" bgcolor="#FFD7EB"><strong>ภาคการศึกษา</strong></td>
                        <td width="560"><input name="ti1" type="radio" checked="checked"
                                               value="1" <? if ($_SESSION["term_g"] == 1) {
                                echo "checked";
                            } ?> />ภาคต้น
                            &nbsp;&nbsp;<input name="ti1" type="radio" value="2" <? if ($_SESSION["term_g"] == 2) {
                                echo "checked";
                            } ?> />ภาคปลาย
                            &nbsp;&nbsp;<input name="ti1" type="radio" value="3" <? if ($_SESSION["term_g"] == 3) {
                                echo "checked";
                            } ?> />ภาคการศึกษาพิเศษ
                            <!--                    <tr>-->
                            <!--                        <td width="236" bgcolor="#FFD7EB"><strong>ปีการศึกษา<span class="style1">*</span></strong></td>-->
                            <!--                        <td width="560"><input type="text" name="year" value="--><?php //= $_SESSION["year_g"] ?><!--" class="aa"/>-->
                            <!--                            <span class="hint">กรอกปีการศึกษา<span class="hint-pointer">&nbsp;</span></span></td>-->
                            <!--                    </tr>-->

                    <tr>
                        <td width="236" bgcolor="#FFD7EB"><strong>ปีการศึกษา<span class="style1">*</span></strong></td>
                        <td width="560">
                            <!--                            <input type="text" name="year" value="--><?php //= $_SESSION["year_g"] ?><!--" class="aa"/>-->
                            <?php
                            $currentMonth = date("m");
                            $currentYear = date("Y")+543;
                            if($currentMonth <=9)
                            {
                                $currentYear=$currentYear-1;
                            }
                            //echo "currentYear : $currentYear";
                            ?>
                            <select size="1" name="year">
                                <?php
                                for($i=2565;$i<=2570;$i++)
                                {
                                    if($i==$currentYear){
                                        ?>
                                        <option value="<?=$i;?>"selected><?=$i;?></option>
                                    <?}else{
                                        ?>
                                        <option value="<?=$i;?>"><?=$i;?></option>
                                        <?
                                    }
                                }
                                ?>

                            </select>

                        </td>
                    </tr>


                    <?php
                    $selecttype = $_GET[selecttype];

                    if ($selecttype == 2) {
                        ?>
                        <tr>
                            <td width="236" bgcolor="#FFD7EB"><strong>ระดับการศึกษา</strong></td>
                            <td><select size="1" name="edulv">
                                    <option value="3" <?= $sel_edulv[1]; ?>>ปริญญาตรี</option>
                                    <option value="5" <?= $sel_edulv[2]; ?>>ปริญญาโท</option>
                                    <option value="7" <?= $sel_edulv[3]; ?>>ปริญญาเอก</option>
                                </select></td>
                        </tr>
                        <?php
                    } else {

                        ?>
                        <tr>
                            <td width="236" bgcolor="#FFD7EB"><strong>ระดับการศึกษา</strong></td>
                            <td><select size="1" name="edulv">
                                    <option value="3" <?= $sel_edulv[1]; ?>>ปริญญาตรี</option>
                                </select></td>
                        </tr>

                        <?php
                    }
                    ?>

                    <?php
                    if ($selecttype == 2) {
                        ?>
                        <tr>
                            <td width="236" bgcolor="#FFD7EB"><strong>หลักสูตร</strong></td>
                            <td><select name="programid">
                                    <?
                                    if ($ddid == 15 || $ddid == 17) {
                                        $sql = "select * from  tblprogram_qa ORDER BY typestudy ASC";
                                    } else {
                                        $sql = "select * from  tblprogram_qa   where  department_id = '$ddid' or  (programid = 46 or programid = 47)  ORDER BY typestudy ASC ";
                                    }

                                    $result_group = mysql_query($sql);
                                    while ($rs_group = mysql_fetch_array($result_group)) {
                                        if ($rs_user[programid] == $rs_group[programid]) {
                                            ?>
                                            <option value="<?= $rs_group[programid] ?>" selected="selected">
                                                <?= $rs_group[programname] ?>
                                            </option>
                                        <? } else {
                                            ?>
                                            <option value="<?= $rs_group[programid] ?>">
                                                <?= $rs_group[programname] ?>
                                            </option>
                                            <?
                                        }
                                    } ?>
                                </select></td>
                        </tr>
                        <?php
                    }
                    ?>
                    <tr>
                        <td bgcolor="#FFD7EB"><strong>รหัสวิชา<span class="style1">*</span></strong></td>
                        <td>
                            <!-- <input type="text"  id="subjcode1" name="subjcode1"    onkeyup="txt1_keyup();if(this.value*1!=this.value) this.value='' ;"  onchange="txt1_keyup()" class="aa"   />
                                    <span class="hint">กรอกรหัสวิชาเฉพาะตัวเลข<span class="hint-pointer">&nbsp;</span></span>
                                    -->
                            <input name="subjcode1" type="text" class="aa" id="subjcode1" maxlength="10"/>
                            <span class="hint">กรอกรหัสวิชา<span class="hint-pointer">&nbsp;</span></span>

                            <div id="divresult" style="display:none"></div>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#FFD7EB"><strong>ชื่อวิชา</strong></td>
                        <td><input name="subject" type="text" id="txt2" size="80"/></td>
                    </tr>
                    <!--
                    <tr>
                    <td bgcolor="#FFD7EB"><strong>ประเภทรายวิชา</strong></td>
                      <td><input name="subjtype" id="subjtype" type="radio" value="1" checked="checked" />
                ภาคปกติ 
                  <input name="subjtype" id="subjtype" type="radio" value="2" />
                โครงการพิเศษ </td>
                    </tr>
                    -->
                    <tr>
                        <td bgcolor="#FFD7EB"><strong>อาจารย์ผู้สอน</strong></td>
                        <td><input name="teacher" type="text" size="80"
                                   value="<?php echo getName2($username); ?>"/><br/><span style="color: red">* กรณีมีผู้สอนหลายคน ให้ใส่คอมม่า (comma) คั่นชื่อของผู้สอนแต่ละคน</span>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#FFD7EB"><strong>วันที่บันทึก</strong></td>
                        <td><select name="desc_d">
                                <? for ($i = 1; $i <= 31; $i++) {
                                    ?>
                                    <option value="<? echo $i; ?>"<? if ($i == $mday) echo " selected"; ?>>
                                        &nbsp;<? echo $i; ?>&nbsp;
                                    </option>
                                <? } ?>
                            </select>
                            <select name="desc_m">
                                <? for ($i = 1; $i <= 12; $i++) {
                                    ?>
                                    <option value="<? echo $i; ?>"<? if ($i == $mm) echo " selected"; ?>>
                                        &nbsp;<? echo $sm[$i]; ?>&nbsp;
                                    </option>
                                <? } ?>
                            </select>
                            <select name="desc_y">
                                <? for ($i = 2555; $i <= 2575; $i++) {
                                    ?>
                                    <option value="<? echo $i - 543; ?>"<? if ($i == $year) echo " selected"; ?>>
                                        &nbsp;<? echo $i; ?>&nbsp;
                                    </option>
                                <? } ?>
                            </select></td>
                    </tr>
                </table>

                <p>
                <table width="600" border="0" cellpadding="0" cellspacing="0">
                    <tr>
                        <td width="270" bgcolor="#FFD7EB"><strong>ค่าเฉลี่ยคะแนน</strong></td>
                        <td width="354"><label>
                                <input type="text" name="mean" id="mean"
                                       onKeyUp="if(this.value*1!=this.value) this.value='' ;"
                                       onChange="format_amount(this);">
                                <span class="hint">กรอกเป็นเลขทศนิยม 2 ตำแหน่ง และ ถ้ามีคนเรียนน้อยกว่า 5  คน ไม่ต้องกรอกช่องนี้<span
                                            class="hint-pointer">&nbsp;</span></span>
                            </label></td>
                    </tr>
                    <tr>
                        <td bgcolor="#FFD7EB"><strong>ค่าส่วนเบี่ยงเบนมาตรฐานคะแนน</strong></td>
                        <td><input type="text" name="sd_std" id="sd_std"
                                   onKeyUp="if(this.value*1!=this.value) this.value='' ;"
                                   onChange="format_amount(this);">
                            <span class="hint">กรอกเป็นเลขทศนิยม 2 ตำแหน่ง และ ถ้ามีคนเรียนน้อยกว่า 5  คน ไม่ต้องกรอกช่องนี้<span
                                        class="hint-pointer">&nbsp;</span></span></td>
                    </tr>
                    <tr>
                        <td bgcolor="#FFD7EB" align="right"><strong>* หมายเหตุ :</strong></td>
                        <td>
                            <input name="num05" type="radio" value="1" /> ตัดเกรดร่วมกับ
                            <!--                            <input type="text" name="std_i1" id="std_i1" placeholder="รหัสวิชา,...">-->
                            <!--                            <br>-->
                            <input name="std_i1" type="text" class="aa" id="std_i1"  maxlength="8"/>

                            <span class="hint"><font color="red"> กรอกรหัสวิชาเท่านั้น!!</font><span class="hint-pointer">&nbsp;</span></span>
                        </td>
                    </tr>
                    <tr>
                        <td bgcolor="#FFD7EB"><strong></strong></td>
                        <td><input name="num05" type="radio" value="2"/> ติด I เนื่องจาก &nbsp;<input type="text"
                                                                                                      name="std_i2"
                                                                                                      id="std_i2">


                        </td>
                    </tr>

                    <tr>
                        <td bgcolor="#FFD7EB"><strong></strong></td>
                        <td><input name="num05" type="radio" value="3" /> อื่นๆ &nbsp;<input type="text" name="std_i3"
                                                                                             id="std_i3">


                        </td>
                    </tr>

                    <!--                    <tr>-->
                    <!--                        <td bgcolor="#FFD7EB"><strong></strong></td>-->
                    <!--                        <td></td>-->
                    <!--                    </tr>-->


                    <script>
                        function hiddenn(pvar) {
                            if (pvar == 0) {
                                document.getElementById("txt1").style.display = 'none';
                            } else {
                                document.getElementById("txt1").style.display = '';
                            }

                        }
                    </script>
                    <tr>
                        <td bgcolor="#FFD7EB" colspan="2">

                            <div align="left">
                                <input type="radio" name="num01" value="1" onclick="hiddenn('0')"/>
                                ต้องการกรอกคะแนนประเมินแยกตาม Section <br>
                                <input name="num01" type="radio" onclick="hiddenn('1')" value="2" checked="checked"/>
                                ต้องการกรอกคะแนนประเมินรวม
                            </div>
                        </td>

                    </tr>


                </table>

                <div name="txt1" id="txt1">
                    <strong>จำนวนนักศึกษาที่เข้าประเมิน </strong> <input type="text" name="totalnumstdevz"
                                                                         id="totalnumstdevz" maxLength="5"
                                                                         onKeyUp="if(this.value*1!=this.value) this.value='' ;"/>
                    <br>
                    <strong>ผลการประเมินรายวิชาโดยนักศึกษา </strong> <label>
                        <!--<input type="text" name="totalevaluationscore" id="totalevaluationscore" min="1" max="5" maxLength="5" onKeyUp="if(this.value*1!=this.value) this.value='' ;" /> -->
                        <input type="number" name="totalevaluationscore" id="totalevaluationscore" min="1" max="5"
                               maxLength="5" step="0.01" onKeyUp="if(this.value*1!=this.value) this.value='' ;"/>

                        <span class="hint">ดูผลประเมินจาก https://reg.kku.ac.th <span class="hint-pointer">
&nbsp;</span><img src="https://e.sc.kku.ac.th/sci-eoffice/teacher/images2/teacher2.png" width="408"
                  height="250"/></span>
                    </label>
                </div>

                </p>

                <?php if ($selecttype != 0) { ?>
                    <table width="384" border="0">
                        <tr>
                            <td colspan="2" bgcolor="#FFCCCC"><strong>ช่วงคะแนนการประเมินผลการเรียน</strong></td>
                        </tr>
                        <tr>
                            <td colspan="2" bgcolor="#FFCCCC">
                                <div align="center">
                                    <input type="radio" id="intflag1" name="intflag" checked="checked" value="0">
                                    มีทศนิยม
                                    <input type="radio" id="intflag2" name="intflag" value="1">
                                    เป็นจำนวนเต็ม
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" bgcolor="#FFCCCC">
                                <div align="center"><strong>คะแนนของเกรดต่าง ๆ</strong></div>
                            </td>
                        </tr>
                        <tr>
                            <td width="103" bgcolor="#FFCCCC">
                                <div align="center"><strong>เกรด</strong></div>
                            </td>
                            <td width="271" bgcolor="#FFCCCC">
                                <div align="center"><strong>ช่วงคะแนน</strong></div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div align="center"><strong>A</strong></div>
                            </td>
                            <td>
                                <div align="center">

                                    <input name="num_a1" type="text" id="num_a1" size="5" value="100"
                                           readonly="readonly" style="background-color:#CCCCCC; "
                                    >
                                    -&nbsp;
                                    <input name="num_a2" type="text" id="num_a2" size="5"
                                    >
                                    <i class="fas fa-backspace" id="del_a"></i>
                                    <span class="hint"><!--คะแนนขอบเขตบนของเกรด เช่น
    A ช่วงคะแนน  100-90 ให้กรอก 100 ที่ช่องด้านซ้าย และ 90 ในช่องด้านขวา-->กรุณากรอกเฉพาะขอบเขตล่างของช่วงคณะแนน เป็นเลขจำนวนเต็ม เท่านั้น!!  <span
                                                class="hint-pointer">&nbsp;</span></span></div>
                            </td>
                        </tr>
                        <tr>
                            <td bgcolor="#FFECF5">
                                <div align="center"><strong>B+</strong></div>
                            </td>
                            <td bgcolor="#FFECF5">
                                <div align="center">

                                    <input name="num_bb1" type="text" id="num_bb1" size="5" readonly="readonly"
                                           style="background-color:#CCCCCC; "
                                    >
                                    -&nbsp;
                                    <input name="num_bb2" type="text" id="num_bb2" size="5"
                                    >
                                    <i class="fas fa-backspace" id="del_bb"></i>

                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div align="center"><strong>B</strong></div>
                            </td>
                            <td>
                                <div align="center">

                                    <input name="num_b1" type="text" id="num_b1" size="5" readonly="readonly"
                                           style="background-color:#CCCCCC; "
                                    >
                                    -&nbsp;
                                    <input name="num_b2" type="text" id="num_b2" size="5"
                                    >
                                    <i class="fas fa-backspace" id="del_b"></i>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td bgcolor="#FFECF5">
                                <div align="center"><strong>C+</strong></div>
                            </td>
                            <td bgcolor="#FFECF5">
                                <div align="center">

                                    <input name="num_cc1" type="text" id="num_cc1" size="5" readonly="readonly"
                                           style="background-color:#CCCCCC; "
                                    >
                                    -&nbsp;
                                    <input name="num_cc2" type="text" id="num_cc2" size="5"
                                    >
                                    <i class="fa fa-backspace" id="del_cc"></i>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div align="center"><strong>C</strong></div>
                            </td>
                            <td>
                                <div align="center">

                                    <input name="num_c1" type="text" id="num_c1" size="5" readonly="readonly"
                                           style="background-color:#CCCCCC; "
                                    >
                                    -&nbsp;
                                    <input name="num_c2" type="text" id="num_c2" size="5"
                                    >
                                    <i class="fa fa-backspace" id="del_c"></i>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td bgcolor="#FFECF5">
                                <div align="center"><strong>D+</strong></div>
                            </td>
                            <td bgcolor="#FFECF5">
                                <div align="center">

                                    <input name="num_dd1" type="text" id="num_dd1" size="5" readonly="readonly"
                                           style="background-color:#CCCCCC; "
                                    >
                                    -&nbsp;
                                    <input name="num_dd2" type="text" id="num_dd2" size="5"
                                    >
                                    <i class="fa fa-backspace" id="del_dd"></i>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div align="center"><strong>D</strong></div>
                            </td>
                            <td>
                                <div align="center">

                                    <input name="num_d1" type="text" id="num_d1" size="5" readonly="readonly"
                                           style="background-color:#CCCCCC; "
                                    >
                                    -&nbsp;
                                    <input name="num_d2" type="text" id="num_d2" size="5"
                                    >
                                    <i class="fa fa-backspace" id="del_d"></i>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td bgcolor="#FFECF5">
                                <div align="center"><strong>F</strong></div>
                            </td>
                            <td bgcolor="#FFECF5">
                                <div align="center">

                                    <input name="num_f1" type="text" id="num_f1" size="5" readonly="readonly"
                                           style="background-color:#CCCCCC; "
                                    >
                                    -&nbsp;
                                    <input name="num_f2" type="text" id="num_f2" size="5"
                                    >
                                    <i class="fa fa-backspace" id="del_f"></i>
                                </div>
                            </td>
                        </tr>
                    </table>

                    <p>
                        <label>
                            <input type="submit" name="save" id="save" value="บันทึกข้อมูล"/>
                        </label>
                        <label>
                            &nbsp;&nbsp;
                            <input type="reset" name="cancel" id="cancel" value="ล้างข้อมูล"/>
                        </label>
                    </p>

                <?php } ?>
            </div>

        </form>

    <? }
} ?>
<?php
function checksubject($subject_code,$subject2)
{
    $subject_code = strtoupper(trim($subject_code));
    $sql = " SELECT * from grad_report2  WHERE subject_code = '$subject_code'  ";
    // echo $sql;
    $result = mysql_query($sql) or die(mysql_error());
    if ($result) {
        $rs = mysql_fetch_array($result);
        $num_rows = mysql_num_rows($result);
        if ($num_rows >= 1) {
            $subject_code2 = trim($rs[subject_code2]);
        } else {
            if ($subject2 != "") {
            // ถ้าไม่มีข้อมูลในตาราง grad_report2 ให้เชครหัสวิชาเทียบ
            $sql2 = " SELECT * from grad_report2  WHERE subject_code = '$subject2'  ";
            $result2 = mysql_query($sql2) or die(mysql_error());
            $num_rows2 = mysql_num_rows($result2);
            if ($num_rows2 != 0) {
                $rs2 = mysql_fetch_array($result2);
                $subject_code2 = trim($rs2[subject_code2]);
            } else {
                $subject_code2 = trim($subject_code);
                //$subject_code2 = trim($subject2);
            }
        }else{
                $subject_code2 = trim($subject_code);
        }
        }
    }
    return $subject_code2;

}


?>


    <script type="text/javascript">
        // $(document).ready(function () {
        //     $.extend($.fn.autoNumeric.defaults, {
        //         aSep: '.',
        //         aDec: ',',
        //     });
        // });

        jQuery(function ($) {
            if ($('input[name=intflag]:checked').val() == 0) {
                $('#num_a1').autoNumeric('init', {vMin: '0', vMax: '100.00', mDec: '2'});
                $('#num_a2').autoNumeric('init', {vMin: '0', vMax: '100.00', mDec: '2'});
                $('#num_bb1').autoNumeric('init', {vMin: '0', vMax: '100.00', mDec: '2'});
                $('#num_bb2').autoNumeric('init', {vMin: '0', vMax: '100.00', mDec: '2'});
                $('#num_b1').autoNumeric('init', {vMin: '0', vMax: '100.00', mDec: '2'});
                $('#num_b2').autoNumeric('init', {vMin: '0', vMax: '100.00', mDec: '2'});
                $('#num_cc1').autoNumeric('init', {vMin: '0', vMax: '100.00', mDec: '2'});
                $('#num_cc2').autoNumeric('init', {vMin: '0', vMax: '100.00', mDec: '2'});
                $('#num_c1').autoNumeric('init', {vMin: '0', vMax: '100.00', mDec: '2'});
                $('#num_c2').autoNumeric('init', {vMin: '0', vMax: '100.00', mDec: '2'});
                $('#num_dd1').autoNumeric('init', {vMin: '0', vMax: '100.00', mDec: '2'});
                $('#num_dd2').autoNumeric('init', {vMin: '0', vMax: '100.00', mDec: '2'});
                $('#num_d1').autoNumeric('init', {vMin: '0', vMax: '100.00', mDec: '2'});
                $('#num_d2').autoNumeric('init', {vMin: '0', vMax: '100.00', mDec: '2'});
                $('#num_f1').autoNumeric('init', {vMin: '0', vMax: '100.00', mDec: '2'});
                $('#num_f2').autoNumeric('init', {vMin: '0', vMax: '100.00', mDec: '2'});
                console.log('0');
            } else {
                console.log('1');
                $('#num_a1').autoNumeric('init', {vMin: '0', vMax: '100', mDec: '0'});
                $('#num_a2').autoNumeric('init', {vMin: '0', vMax: '100', mDec: '0'});
                $('#num_bb1').autoNumeric('init', {vMin: '0', vMax: '100', mDec: '0'});
                $('#num_bb2').autoNumeric('init', {vMin: '0', vMax: '100', mDec: '0'});
                $('#num_b1').autoNumeric('init', {vMin: '0', vMax: '100', mDec: '0'});
                $('#num_b2').autoNumeric('init', {vMin: '0', vMax: '100', mDec: '0'});
                $('#num_cc1').autoNumeric('init', {vMin: '0', vMax: '100', mDec: '0'});
                $('#num_cc2').autoNumeric('init', {vMin: '0', vMax: '100', mDec: '0'});
                $('#num_c1').autoNumeric('init', {vMin: '0', vMax: '100', mDec: '0'});
                $('#num_c2').autoNumeric('init', {vMin: '0', vMax: '100', mDec: '0'});
                $('#num_dd1').autoNumeric('init', {vMin: '0', vMax: '100', mDec: '0'});
                $('#num_dd2').autoNumeric('init', {vMin: '0', vMax: '100', mDec: '0'});
                $('#num_d1').autoNumeric('init', {vMin: '0', vMax: '100', mDec: '0'});
                $('#num_d2').autoNumeric('init', {vMin: '0', vMax: '100', mDec: '0'});
                $('#num_f1').autoNumeric('init', {vMin: '0', vMax: '100', mDec: '0'});
                $('#num_f2').autoNumeric('init', {vMin: '0', vMax: '100', mDec: '0'});
            }


        });
    </script>


    <script>

        $(document).ready(function () {
            // สำหรับลบข้อมูลในแถวนั้นๆ
            $("#del_a").click(function () {
                $("#num_a1").val('');
                $("#num_a2").val('');
            });
            $("#del_bb").click(function () {
                $("#num_bb1").val('');
                $("#num_bb2").val('');
            });
            $("#del_b").click(function () {
                $("#num_b1").val('');
                $("#num_b2").val('');
            });
            $("#del_cc").click(function () {
                $("#num_cc1").val('');
                $("#num_cc2").val('');
            });
            $("#del_c").click(function () {
                $("#num_c1").val('');
                $("#num_c2").val('');
            });
            $("#del_dd").click(function () {
                $("#num_dd1").val('');
                $("#num_dd2").val('');
            });
            $("#del_d").click(function () {
                $("#num_d1").val('');
                $("#num_d2").val('');
            });
            $("#del_f").click(function () {
                $("#num_f1").val('');
                $("#num_f2").val('');
            });
        });
    </script>

    <script>

        $(document).ready(function () {
            var intflag = 0; //ทศนิยม

            // สำหรับการแก้ไขข้อมูล
            $("#num_a2").keyup(function () {

                if ($("#num_a2").val() > 0) {
                    if ($('input[name=intflag]:checked').val() == 0) {
                        // $("#num_bb1").val(($("#num_a2").val() - 0.01).toFixed(2));
                        $("#num_a1").val('100.00');
                    } else {
                        // $("#num_bb1").val(($("#num_a2").val() - 1).toFixed(0));
                        $("#num_a1").val('100');
                    }
                }
            });

            $("#num_bb2").keyup(function () {
                    if ($("#num_bb2").val() > 0) {
                        if ($('input[name=intflag]:checked').val() == 0) {
                            // $("#num_b1").val(($("#num_bb2").val() - 0.01).toFixed(2));
                            $("#num_bb1").val(($("#num_a2").val() - 0.01).toFixed(2));
                        } else {
                            // $("#num_b1").val(($("#num_bb2").val() - 1).toFixed(0));
                            $("#num_bb1").val(($("#num_a2").val() - 1).toFixed(0));
                        }
                    } else {
                        if ($("#num_bb2").val().length > 0) {
                            if ($('input[name=intflag]:checked').val() == 0) {
                                $("#num_bb1").val(($("#num_a2").val() - 0.01).toFixed(2));
                            } else {
                                $("#num_bb1").val(($("#num_a2").val() - 1).toFixed(0));
                            }

                        } else {
                            $("#num_bb1").val('');
                        }
                    }
                }
            );

            $("#num_b2").keyup(function () {

                if ($("#num_b2").val() > 0) {
                    if ($('input[name=intflag]:checked').val() == 0) {
                        // $("#num_cc1").val(($("#num_b2").val() - 0.01).toFixed(2));
                        $("#num_b1").val(($("#num_bb2").val() - 0.01).toFixed(2));
                    } else {
                        // $("#num_cc1").val(($("#num_b2").val() - 1).toFixed(0));
                        $("#num_b1").val(($("#num_bb2").val() - 1).toFixed(0));
                    }
                } else {
                    if ($("#num_b2").val().length > 0) {
                        if ($('input[name=intflag]:checked').val() == 0) {
                            $("#num_b1").val(($("#num_bb2").val() - 0.01).toFixed(2));
                        } else {
                            $("#num_b1").val(($("#num_bb2").val() - 1).toFixed(0));
                        }
                    } else {
                        $("#num_b1").val('');
                    }

                }
            });

            $("#num_cc2").keyup(function () {

                if ($("#num_cc2").val() > 0) {
                    if ($('input[name=intflag]:checked').val() == 0) {
                        // $("#num_c1").val(($("#num_cc2").val() - 0.01).toFixed(2));
                        $("#num_cc1").val(($("#num_b2").val() - 0.01).toFixed(2));
                    } else {
                        // $("#num_c1").val(($("#num_cc2").val() - 1).toFixed(0));
                        $("#num_cc1").val(($("#num_b2").val() - 1).toFixed(0));
                    }
                } else {
                    if ($("#num_cc2").val().length > 0) {
                        if ($('input[name=intflag]:checked').val() == 0) {
                            $("#num_cc1").val(($("#num_b2").val() - 0.01).toFixed(2));
                        } else {
                            $("#num_cc1").val(($("#num_b2").val() - 1).toFixed(0));
                        }
                    } else {
                        $("#num_cc1").val('');
                    }
                }
            });

            $("#num_c2").keyup(function () {

                if ($("#num_c2").val() > 0) {
                    if ($('input[name=intflag]:checked').val() == 0) {
                        // $("#num_dd1").val(($("#num_c2").val() - 0.01).toFixed(2));
                        $("#num_c1").val(($("#num_cc2").val() - 0.01).toFixed(2));
                    } else {
                        // $("#num_dd1").val(($("#num_c2").val() - 1).toFixed(0));
                        $("#num_c1").val(($("#num_cc2").val() - 1).toFixed(0));
                    }
                } else {
                    if ($("#num_c2").val().length > 0) {
                        if ($('input[name=intflag]:checked').val() == 0) {
                            $("#num_c1").val(($("#num_cc2").val() - 0.01).toFixed(2));
                        } else {
                            $("#num_c1").val(($("#num_cc2").val() - 1).toFixed(0));
                        }
                    } else {
                        $("#num_c1").val('');
                    }
                }
            });

            $("#num_dd2").keyup(function () {

                if ($("#num_dd2").val() > 0) {
                    if ($('input[name=intflag]:checked').val() == 0) {
                        // $("#num_d1").val(($("#num_dd2").val() - 0.01).toFixed(2));
                        $("#num_dd1").val(($("#num_c2").val() - 0.01).toFixed(2));
                    } else {
                        // $("#num_d1").val(($("#num_dd2").val() - 1).toFixed(0));
                        $("#num_dd1").val(($("#num_c2").val() - 1).toFixed(0));
                    }
                } else {
                    if ($("#num_dd2").val().length > 0) {
                        if ($('input[name=intflag]:checked').val() == 0) {
                            $("#num_dd1").val(($("#num_c2").val() - 0.01).toFixed(2));
                        } else {
                            $("#num_dd1").val(($("#num_c2").val() - 1).toFixed(0));
                        }
                    } else {
                        $("#num_dd1").val('');
                    }
                }
            });

            $("#num_d2").keyup(function () {

                if ($("#num_d2").val() > 0) {
                    if ($('input[name=intflag]:checked').val() == 0) {
                        // $("#num_f1").val(($("#num_d2").val() - 0.01).toFixed(2));
                        $("#num_d1").val(($("#num_dd2").val() - 0.01).toFixed(2));
                    } else {
                        // $("#num_f1").val(($("#num_d2").val() - 1).toFixed(0));
                        $("#num_d1").val(($("#num_dd2").val() - 1).toFixed(0));
                    }
                } else {
                    if ($("#num_d2").val().length > 0) {
                        if ($('input[name=intflag]:checked').val() == 0) {
                            $("#num_d1").val(($("#num_dd2").val() - 0.01).toFixed(2));
                        } else {
                            $("#num_d1").val(($("#num_dd2").val() - 1).toFixed(0));
                        }
                    } else {
                        $("#num_d1").val('');
                    }
                }
            });

            $("#num_f2").keyup(function () {

                if ($("#num_f2").val() >= 0) {
                    if ($('input[name=intflag]:checked').val() == 0) {
                        $("#num_f1").val(($("#num_d2").val() - 0.01).toFixed(2));
                    } else {
                        $("#num_f1").val(($("#num_d2").val() - 1).toFixed(0));
                    }
                } else {
                    if ($("#num_f2").val().length > 0) {
                        if ($('input[name=intflag]:checked').val() == 0) {
                            $("#num_f1").val(($("#num_d2").val() - 0.01).toFixed(2));
                        } else {
                            $("#num_f1").val(($("#num_d2").val() - 1).toFixed(0));
                        }
                    } else {
                        $("#num_f1").val('');
                    }
                }
            });

            // ตรวจสอบว่ามีการเปลี่ยนแปลงค่าของปุ่ม radio

            $('input[name=intflag]').on('change', function () {

                // ตรวจสอบว่า radio ชื่อ intflag มีค่าเป็น 0
                if ($('input[name=intflag]:checked').val() == 0) {

                    intflag = 0; //ทศนิยม
                    // ------------------------------------------------------
                    $('#num_a1').autoNumeric('update', {vMin: '0', vMax: '100.00', mDec: '2'});
                    $('#num_a2').autoNumeric('update', {vMin: '0', vMax: '100.00', mDec: '2'});
                    $('#num_bb1').autoNumeric('update', {vMin: '0', vMax: '100.00', mDec: '2'});
                    $('#num_bb2').autoNumeric('update', {vMin: '0', vMax: '100.00', mDec: '2'});
                    $('#num_b1').autoNumeric('update', {vMin: '0', vMax: '100.00', mDec: '2'});
                    $('#num_b2').autoNumeric('update', {vMin: '0', vMax: '100.00', mDec: '2'});
                    $('#num_cc1').autoNumeric('update', {vMin: '0', vMax: '100.00', mDec: '2'});
                    $('#num_cc2').autoNumeric('update', {vMin: '0', vMax: '100.00', mDec: '2'});
                    $('#num_c1').autoNumeric('update', {vMin: '0', vMax: '100.00', mDec: '2'});
                    $('#num_c2').autoNumeric('update', {vMin: '0', vMax: '100.00', mDec: '2'});
                    $('#num_dd1').autoNumeric('update', {vMin: '0', vMax: '100.00', mDec: '2'});
                    $('#num_dd2').autoNumeric('update', {vMin: '0', vMax: '100.00', mDec: '2'});
                    $('#num_d1').autoNumeric('update', {vMin: '0', vMax: '100.00', mDec: '2'});
                    $('#num_d2').autoNumeric('update', {vMin: '0', vMax: '100.00', mDec: '2'});
                    $('#num_f1').autoNumeric('update', {vMin: '0', vMax: '100.00', mDec: '2'});
                    $('#num_f2').autoNumeric('update', {vMin: '0', vMax: '100.00', mDec: '2'});
                    // console.log('00');
                    // ------------------------------------------------------

                    /* เงื่อนไขสำหรับระบุขอบเขตล่างด้วย */
                    // if ($('#num_a2').val() > 0) {
                    //     $("#num_bb1").val(($("#num_a2").val() - 0.01).toFixed(2));
                    // }
                    // if ($('#num_bb2').val() > 0) {
                    //     $("#num_b1").val(($("#num_bb2").val() - 0.01).toFixed(2));
                    // }
                    // if ($('#num_b2').val() > 0) {
                    //     $("#num_cc1").val(($("#num_b2").val() - 0.01).toFixed(2));
                    // }
                    // if ($('#num_cc2').val() > 0) {
                    //     $("#num_c1").val(($("#num_cc2").val() - 0.01).toFixed(2));
                    // }
                    // if ($('#num_c2').val() > 0) {
                    //     $("#num_dd1").val(($("#num_c2").val() - 0.01).toFixed(2));
                    // }
                    // if ($('#num_dd2').val() > 0) {
                    //     $("#num_d1").val(($("#num_dd2").val() - 0.01).toFixed(2));
                    // }
                    // if ($('#num_d2').val() > 0) {
                    //     $("#num_f1").val(($("#num_d2").val() - 0.01).toFixed(2));
                    // }

                    if ($('#num_a2').val() > 0) {
                        // $("#num_a2").val(($("#num_a2").val() - 0.01).toFixed(2));
                    }

                    if ($('#num_bb2').val() > 0) {
                        $("#num_bb1").val(($("#num_a2").val() - 0.01).toFixed(2));
                    }
                    if ($('#num_b2').val() > 0) {
                        $("#num_b1").val(($("#num_bb2").val() - 0.01).toFixed(2));
                    }
                    if ($('#num_cc2').val() > 0) {
                        $("#num_cc1").val(($("#num_b2").val() - 0.01).toFixed(2));
                    }
                    if ($('#num_c2').val() > 0) {
                        $("#num_c1").val(($("#num_cc2").val() - 0.01).toFixed(2));
                    }
                    if ($('#num_dd2').val() > 0) {
                        $("#num_dd1").val(($("#num_c2").val() - 0.01).toFixed(2));
                    }
                    if ($('#num_d2').val() > 0) {
                        $("#num_d1").val(($("#num_dd2").val() - 0.01).toFixed(2));
                    }
                    if ($('#num_f2').val() >= 0 && $('#num_d2').val() > 0) {
                        $("#num_f1").val(($("#num_d2").val() - 0.01).toFixed(2));
                    }

                } else {
                    intflag = 1;

// ------------------------------------------------------
//                 console.log('11');
                    $('#num_a1').autoNumeric('update', {vMin: '0', vMax: '100', mDec: '0'});
                    $('#num_a2').autoNumeric('update', {vMin: '0', vMax: '100', mDec: '0'});
                    $('#num_bb1').autoNumeric('update', {vMin: '0', vMax: '100', mDec: '0'});
                    $('#num_bb2').autoNumeric('update', {vMin: '0', vMax: '100', mDec: '0'});
                    $('#num_b1').autoNumeric('update', {vMin: '0', vMax: '100', mDec: '0'});
                    $('#num_b2').autoNumeric('update', {vMin: '0', vMax: '100', mDec: '0'});
                    $('#num_cc1').autoNumeric('update', {vMin: '0', vMax: '100', mDec: '0'});
                    $('#num_cc2').autoNumeric('update', {vMin: '0', vMax: '100', mDec: '0'});
                    $('#num_c1').autoNumeric('update', {vMin: '0', vMax: '100', mDec: '0'});
                    $('#num_c2').autoNumeric('update', {vMin: '0', vMax: '100', mDec: '0'});
                    $('#num_dd1').autoNumeric('update', {vMin: '0', vMax: '100', mDec: '0'});
                    $('#num_dd2').autoNumeric('update', {vMin: '0', vMax: '100', mDec: '0'});
                    $('#num_d1').autoNumeric('update', {vMin: '0', vMax: '100', mDec: '0'});
                    $('#num_d2').autoNumeric('update', {vMin: '0', vMax: '100', mDec: '0'});
                    $('#num_f1').autoNumeric('update', {vMin: '0', vMax: '100', mDec: '0'});
                    $('#num_f2').autoNumeric('update', {vMin: '0', vMax: '100', mDec: '0'});

                    // ------------------------------------------------------


                    if ($('#num_a2').val() > 0) {
                        // $("#num_bb1").val(($("#num_a2").val() - 1).toFixed(0));
                    }
                    if ($('#num_bb2').val() > 0) {
                        $("#num_bb1").val(($("#num_a2").val() - 1).toFixed(0));
                    }
                    if ($('#num_b2').val() > 0) {
                        $("#num_b1").val(($("#num_bb2").val() - 1).toFixed(0));
                    }
                    if ($('#num_cc2').val() > 0) {
                        $("#num_cc1").val(($("#num_b2").val() - 1).toFixed(0));
                    }
                    if ($('#num_c2').val() > 0) {
                        $("#num_c1").val(($("#num_cc2").val() - 1).toFixed(0));
                    }
                    if ($('#num_dd2').val() > 0) {
                        $("#num_dd1").val(($("#num_c2").val() - 1).toFixed(0));
                    }
                    if ($('#num_d2').val() > 0) {
                        $("#num_d1").val(($("#num_dd2").val() - 1).toFixed(0));
                    }
                    if ($('#num_f2').val() >= 0 && $('#num_d2').val() > 0) {
                        $("#num_f1").val(($("#num_d2").val() - 1).toFixed(0));
                    }


                    // if ($('#num_a2').val() > 0) {
                    //     $("#num_bb1").val(($("#num_a2").val() - 1).toFixed(0));
                    // }
                    // if ($('#num_bb2').val() > 0) {
                    //     $("#num_b1").val(($("#num_bb2").val() - 1).toFixed(0));
                    // }
                    // if ($('#num_b2').val() > 0) {
                    //     $("#num_cc1").val(($("#num_b2").val() - 1).toFixed(0));
                    // }
                    // if ($('#num_cc2').val() > 0) {
                    //     $("#num_c1").val(($("#num_cc2").val() - 1).toFixed(0));
                    // }
                    // if ($('#num_c2').val() > 0) {
                    //     $("#num_dd1").val(($("#num_c2").val() - 1).toFixed(0));
                    // }
                    // if ($('#num_dd2').val() > 0) {
                    //     $("#num_d1").val(($("#num_dd2").val() - 1).toFixed(0));
                    // }
                    // if ($('#num_d2').val() > 0) {
                    //     $("#num_f1").val(($("#num_d2").val() - 1).toFixed(0));
                    //
                    // }


                    // if ($('#num_a2').val().length > 0)

                } // if ($('input[name=intflag]:checked').val() == 0) {


            });  //  $('input[name=intflag]').on('change', function ()


        });   // $(document).ready(function () {


    </script>
<?php
function checksubjectID($sub1, $sub2,$subject,$user_name)
{
    $valsub = "";
    $sub1=strtoupper($sub1);
    $sub2=strtoupper($sub2);
    $subject=strtoupper($subject);

    $sqly = " SELECT * FROM grad_report2  where subject_code = '$sub1'";
    $rsy1 = mysql_query($sqly) or die("<br> Can't Open tblapprove_pd =" . mysql_error() . " <br> LINE AT=" . __LINE__);
    $n = mysql_num_rows($rsy1);
    // echo "-------> sqly : $sqly<br>";
    //echo "-------> N : $n<br>";
    if($n ==0)
    {
        $sqly2 = " SELECT * FROM grad_report2  where subject_code = '$sub2'";
        $rsy2 = mysql_query($sqly2) or die("<br> Can't Open tblapprove_pd =" . mysql_error() . " <br> LINE AT=" . __LINE__);
        $n2 = mysql_num_rows($rsy2);
        if($n2 ==0)
        {
            if($sub2 != "")
            {
                $sql = " insert into grad_report2 (subject_code2,subject_code,subject,username) values ('$sub2','$sub2','$subject','$user_name')";
                $result = mysql_query($sql);
                // echo "sql : $sql<br>";
                if($result)
                {
                    $sql2 = " insert into grad_report2 (subject_code2,subject_code,subject,username) values ('$sub2','$sub1','$subject','$user_name')";
                    $result2 = mysql_query($sql2);
                    // echo "sql2 : $sql2<br>";
                }
            }


        }else{
            //$result2 = mysql_query($sql);
            if($sub2 != ""){
                $gg = mysql_fetch_array($rsy2);
                $sql3 = " insert into grad_report2 (subject_code2,subject_code,subject,username) values ('$gg[subject_code2]','$sub1','$subject','$user_name')";
                mysql_query($sql3);
                // echo "sql3 : $sql3<br>";
            }


        }

    }

    return $valsub;
}

?>