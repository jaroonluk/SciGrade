<?php
include('mpdf/mpdf.php');

include("../config.inc.php");
ConnDB();



mysql_query("set NAMES  UTF8 ");

$grade_id = $_GET[grade_id];
$sql = "select * from grade_report   where grade_id =$grade_id   ";
$result = mysql_query($sql);
$m1 = mysql_fetch_array($result);


$sql = "select * from grade_std   where grade_id =$grade_id order by grade_std_id asc ";
$result1 = mysql_query($sql);
$count_row = mysql_num_rows($result1);
$count_row = $count_row + 1;
$grade_std1 = mysql_fetch_array($result1);
$f_id = $grade_std1[grade_std_id];
?>
    <html>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>แบบรายงานผลการสอบไล่ </title>

    <body>

    <?php


    ?>
    <p align="center">แบบรายงานผลการสอบไล่ </p>
    <p align="center">ประจำภาค ( <?php if ($m1[term] == 1) { ?>/<?php } else { ?>&nbsp;<?php } ?> )
        ต้น&nbsp; (<?php if ($m1[term] == 2) { ?>/<?php } else { ?>&nbsp;<?php } ?> )
        ปลาย&nbsp; (<?php if ($m1[term] == 3) { ?>/<?php } else { ?>&nbsp;<?php } ?>) ภาคการศึกษาพิเศษ
        ปีการศึกษา <?= $m1[year] ?></p>
    <p align="center">รหัส/ชื่อวิชา (ภาษาอังกฤษ) <?= $m1[subject_code] ?> <?= $m1[subject] ?> </p>

    <?php
    //Get  ช่วงคะแนน  grade_report
    $sql = "select * from grade_report   where  grade_id  =$grade_id ";
    //echo $sql ;
    $result2 = mysql_query($sql);
    $gg = mysql_fetch_array($result2);
    $a1 = $gg[score_a];
    $bb1 = $gg[score_bb];
    $b1 = $gg[score_b];
    $cc1 = $gg[score_cc];
    $c1 = $gg[score_c];
    $ddd = $gg[score_dd];
    $d1 = $gg[score_d];
    $f1 = $gg[score_f];
    $ss = 0;
    $thai_month_arr = array(
        "0" => "",
        "1" => "มกราคม",
        "2" => "กุมภาพันธ์",
        "3" => "มีนาคม",
        "4" => "เมษายน",
        "5" => "พฤษภาคม",
        "6" => "มิถุนายน",
        "7" => "กรกฎาคม",
        "8" => "สิงหาคม",
        "9" => "กันยายน",
        "10" => "ตุลาคม",
        "11" => "พฤศจิกายน",
        "12" => "ธันวาคม"
    );
    ?>
    <table width="100%" border="1" cellspacing="0" cellpadding="0" class="style3">
        <tr class="thead2">
            <td width="100" rowspan="2"><p>ลำดับที่</p></td>
            <td width="307" rowspan="2"><p>ชื่อวิชา</p>
                <p>(อาจารย์ผู้สอน)</p></td>
            <td width="134" rowspan="2"><p>กลุ่ม</p>
                <p>(คณะ)</p></td>
            <td width="102">เกรด</td>
            <td width="82">A</td>
            <td width="82">B+</td>
            <td width="82">B</td>
            <td width="82">C+</td>
            <td width="82">C</td>
            <td width="82">D+</td>
            <td width="82">D</td>
            <td width="82">F</td>
            <td width="82">I</td>
            <td width="82">S</td>
            <td width="82">U
            </th>
            <td width="82">W</td>
            <!--<td width="90">xลาออก</td> -->
            <td width="84">รวม</td>
            <td width="84">ค่าเฉลี่ย</td>
            <td width="84">SD</td>
        </tr>
        <tr class="thead2">
            <td>ช่วงคะแนน</td>
            <td><?= $a1 ?></td>
            <td><?= $bb1 ?></td>
            <td><?= $b1 ?></td>
            <td><?= $cc1 ?></td>
            <td><?= $c1 ?></td>
            <td><?= $ddd ?></td>
            <td><?= $d1 ?></td>
            <td><?= $f1 ?></td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <!--<td>x</td> -->
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
        <tr class="thead3">
            <td rowspan=<?= $count_row + 1 ?>>1</td>
            <?php
            if ($grade_std1[type_course] == "1") {
                //$tyf="ภาคปกติ";
                $tyf = "";
            } elseif ($grade_std1[type_course] == "2") {
                $tyf = "(โครงการพิเศษ)";
            } elseif ($grade_std1[type_course] == "3") {
                $tyf = "(ปริญญาตรี ก้าวหน้า)";
            } elseif ($grade_std1[type_course] == "4") {
                $tyf = "(นานาชาติ)";
            } elseif ($grade_std1[type_course] == "5") {
                $tyf = "(นานาชาติ โครงการพิเศษ)";
            }
            ?>
            <td rowspan=<?= $count_row + 1 ?>> <?= $m1[subject_code] ?> <?= strtoupper($m1[subject]) ?><br/>
                <?= $m1[teacher] ?></td>
            <td><?= $grade_std1[sec] ?> <?= strtoupper($grade_std1[fac]) . ": $tyf"; ?></td>
            <td>  <?
                $total_std1[0] = $grade_std1[total_std];
                ?>
                <?= $grade_std1[total_std] ?></td>
            <td><?
                $a[0] = $grade_std1[num_a];
                ?>
                <?= $grade_std1[num_a] ?></td>
            <td><?
                $bb[0] = $grade_std1[num_bb];
                ?>
                <?= $grade_std1[num_bb] ?></td>
            <td>
                <?
                $b[0] = $grade_std1[num_b];
                ?>
                <?= $grade_std1[num_b] ?></td>
            <td>
                <?
                $cc[0] = $grade_std1[num_cc];
                ?>
                <?= $grade_std1[num_cc] ?></td>
            <td>
                <?
                $c[0] = $grade_std1[num_c];
                ?>
                <?= $grade_std1[num_c] ?></td>
            <td>
                <?
                $ddxx[0] = $grade_std1[num_dd];
                ?>
                <?= $grade_std1[num_dd] ?>    </td>
            <td>
                <?
                $d[0] = $grade_std1[num_d];
                ?>
                <?= $grade_std1[num_d] ?>    </td>
            <td>
                <?
                $f[0] = $grade_std1[num_f];
                ?>
                <?= $grade_std1[num_f] ?></td>
            <td>
                <?
                $ii[0] = $grade_std1[num_i];
                ?>
                <?= $grade_std1[num_i] ?></td>
            <td>
                <?
                $ss = $grade_std1[num_s];
                ?>
                <?= $grade_std1[num_s] ?>    </td>
            <td>
                <?
                $v[0] = $grade_std1[num_v];
                ?>
                <?= $grade_std1[num_v] ?>    </td>
            <td>
                <?
                $w[0] = $grade_std1[num_w];
                ?>
                <?= $grade_std1[num_w] ?>    </td>
            <!--
    <td>  
	x<? //
            $out[0] = $grade_std1[num_out];
            ?>
  <? //=$grade_std1[num_out]?></td>
  -->

            <td><?
                $total_std[0] = $grade_std1[total_std];
                ?>
                <?= $grade_std1[total_std] ?></td>
            <td rowspan=<?= $count_row + 1 ?>><?php if ($gg[mean] == 0) {
                    echo "-";
                } else {
                    echo number_format($gg[mean], 2, '.', ',');
                } ?></td>
            <td rowspan=<?= $count_row + 1 ?>><?php if ($gg[sd] == 0) {
                    echo "-";
                } else {
                    echo number_format($gg[sd], 2, '.', ',');
                } ?></td>
        </tr>
        <?php
        $sql = "select * from grade_std  where grade_id =$grade_id and grade_std_id !=$f_id order by grade_std_id  asc ";


        $result1 = mysql_query($sql);
        $i = 1;
        while ($grade_std2 = mysql_fetch_array($result1)) {
            ?>
            <tr class="thead3">
                <?php
                if ($grade_std2[type_course] == "1") {
                    //$tyf="ภาคปกติ";
                    $tyf = "";
                } elseif ($grade_std2[type_course] == "2") {
                    $tyf = "(โครงการพิเศษ)";
                } elseif ($grade_std2[type_course] == "3") {
                    $tyf = "(ปริญญาตรี ก้าวหน้า)";
                } elseif ($grade_std2[type_course] == "4") {
                    $tyf = "(นานาชาติ)";
                } elseif ($grade_std2[type_course] == "5") {
                    $tyf = "(นานาชาติ โครงการพิเศษ)";
                }
                ?>
                <td><?= $grade_std2[sec] ?> <?= strtoupper($grade_std2[fac]) . ": $tyf"; ?></td>
                <td><?
                    $total_std1[$i] = $grade_std2[total_std];
                    ?>
                    <?= $grade_std2[total_std] ?></td>
                <td>
                    <?
                    $a[$i] = $grade_std2[num_a];
                    ?>
                    <?= $grade_std2[num_a] ?>
                </td>
                <td><?
                    $bb[$i] = $grade_std2[num_bb];
                    ?>
                    <?= $grade_std2[num_bb] ?></td>
                <td><?
                    $b[$i] = $grade_std2[num_b];
                    ?>
                    <?= $grade_std2[num_b] ?></td>
                <td><?
                    $cc[$i] = $grade_std2[num_cc];
                    ?>
                    <?= $grade_std2[num_cc] ?></td>
                <td>
                    <?
                    $c[$i] = $grade_std2[num_c];
                    ?>
                    <?= $grade_std2[num_c] ?></td>
                <td>
                    <?
                    $ddxx[0] = $ddxx[0] + $grade_std2[num_dd];
                    ?>
                    <?= $grade_std2[num_dd] ?></td>
                <td>
                    <?
                    $d[$i] = $grade_std2[num_d];
                    ?>
                    <?= $grade_std2[num_d] ?></td>
                <td>
                    <?
                    $f[$i] = $grade_std2[num_f];
                    ?>
                    <?= $grade_std2[num_f] ?>
                </td>
                <td>
                    <?
                    $ii[$i] = $grade_std2[num_i];
                    ?>
                    <?= $grade_std2[num_i] ?>
                </td>
                <td>
                    <?
                    $ss = $ss + $grade_std2[num_s];
                    ?>
                    <?= $grade_std2[num_s] ?>
                </td>
                <td>
                    <?
                    $v[$i] = $grade_std2[num_v];
                    ?>
                    <?= $grade_std2[num_v] ?></td>
                <td>
                    <?
                    $w[$i] = $grade_std2[num_w];
                    ?>
                    <?= $grade_std2[num_w] ?>
                </td>
                <td> <?
                    $out[$i] = $grade_std2[num_out];
                    ?>
                    <?= $grade_std2[total_std] ?></td>
                <!-- <td>  x<? //
                $total_std[$i] = $grade_std2[total_std];
                ?>
  <? //=$grade_std2[total_std]
                ?></td>
  -->

            </tr>
            <? $i++;
        } ?>
        <tr class="thead3">
            <td><strong>รวม</strong></td>
            <td><?= array_sum($total_std1) ?></td>
            <td><?= array_sum($a) ?></td>
            <td><?= array_sum($bb) ?></td>
            <td><?= array_sum($b) ?></td>
            <td><?= array_sum($cc) ?></td>
            <td><?= array_sum($c) ?></td>
            <td><?= $ddxx[0] ?></td>
            <td><?= array_sum($d) ?></td>
            <td><?= array_sum($f) ?></td>
            <td><?= array_sum($ii) ?></td>
            <td><?php echo $ss; ?></td>
            <td><?= array_sum($v) ?></td>
            <td><?= array_sum($w) ?></td>
            <!-- <td >x<? //=array_sum($out)?></td> -->
            <td><?= array_sum($total_std) ?></td>

        </tr>

        <!--    *********************** % ********************************-->
        <tr class="thead3">
            <td><strong>%</strong></td>
            <!--        <td >--><? //=array_sum($total_std1)?><!--</td>-->
            <td>-</td>
            <td><?= number_format(((array_sum($a) * 100) / array_sum($total_std)), 2, '.', ',') ?></td>
            <td><?= number_format(((array_sum($bb) * 100) / array_sum($total_std)), 2, '.', ',') ?></td>
            <td><?= number_format(((array_sum($b) * 100) / array_sum($total_std)), 2, '.', ',') ?></td>
            <td><?= number_format(((array_sum($cc) * 100) / array_sum($total_std)), 2, '.', ',') ?></td>
            <td><?= number_format(((array_sum($c) * 100) / array_sum($total_std)), 2, '.', ',') ?></td>
            <td>
                <!--                --><?php //if ($_SERVER['HTTP_X_REAL_IP'] == '10.177.164.9') {
                //                    echo "-->".((array_sum($c) * 100) / array_sum($total_std));
                //                } ?>

                <?= number_format(((array_sum($ddxx) * 100) / array_sum($total_std)), 2, '.', ',') ?></td>
            <td><?= number_format(((array_sum($d) * 100) / array_sum($total_std)), 2, '.', ',') ?></td>
            <td><?= number_format(((array_sum($f) * 100) / array_sum($total_std)), 2, '.', ',') ?></td>
            <td><?= number_format(((array_sum($ii) * 100) / array_sum($total_std)), 2, '.', ',') ?></td>
            <td><?= number_format((($ss * 100) / array_sum($total_std)), 2, '.', ',') ?></td>
            <td><?= number_format(((array_sum($v) * 100) / array_sum($total_std)), 2, '.', ',') ?></td>
            <td><?= number_format(((array_sum($w) * 100) / array_sum($total_std)), 2, '.', ',') ?></td>
            <!-- <td >x<? //=array_sum($out)?></td> -->
            <td><?= 100.00 ?></td>

        </tr>
        <!--    *********************** end  % ********************************-->
        <tr class="thead4">
            <td>&nbsp;</td>
            <td colspan="19">หมายเหตุ : <?= $m1[reason] ?></td>
            <!--      นักศึกษาได้เกรด I เนื่องจาก-->
        </tr>
    </table>


    <br>

    <table width="100" height="40" border="1" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center" style=" margin:auto">
                <barcode code="<?php echo $grade_id; ?>" type="C39" height="1"/>
                <br>
                <?php echo $grade_id; ?>
            </td>
        </tr>
    </table>


    <br/>
    <br/>
    &nbsp;
    <table width="1259" height="89" border="0">
        <tr>
            <td width="516"><p style="font-size:20pt;">ลงชื่อ…………………………………..</p>
                <p style="font-size:20pt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(<?= getName($m1[username]) ?>)</p>
                <p style="font-size:20pt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;อาจารย์ประจำวิชา</p>
            </td>

            <td width="400"><p style="font-size:18pt;">
                    <!--            ลงชื่อ……………………………………….……...……..</p>-->
                    <!--      <p style="font-size:20pt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(-->
                    <!--          ………………………………………..……..-->
                    <!--        )</p>-->
                    <!--    <p style="font-size:20pt;">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;หัวหน้าสาขาวิชา</p>    -->
            </td>
        </tr>
    </table>

    <p><!--<img alt="testing" src="barcode.php?text=1234" height="30px" />-->


    </p>
    <p>


        <br>


    </p>


    <pagefooter name="myFooter" content-right="พิมพ์เมื่อ {DATE j/m/Y H:i:s}"/>
    <setpagefooter name="myFooter" value="on"/>
    </body>
    </html>
<?php
$html = ob_get_contents();
ob_end_clean();
$mpdf = new mPDF('th_sarabun', 'A4-L', '0', '30', '10', '10', '20');
$stylesheet = file_get_contents('mpdf/css/style.css');
$mpdf->autoScriptToLang = true;
$mpdf->autoLangToFont = true;

$mpdf->WriteHTML($stylesheet, 1); //เรียกใช้ css
$mpdf->WriteHTML($html);
$namepdf = $m1[subject_code] . "-report-" . $m1[year] . ".pdf";
//$mpdf->Output('report.pdf','I');
$mpdf->Output($namepdf, 'I');
?>