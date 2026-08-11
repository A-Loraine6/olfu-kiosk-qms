<?php
function solve3x3($M, $b) {
  $det = function($m) { return $m[0][0]*($m[1][1]*$m[2][2]-$m[1][2]*$m[2][1])-$m[0][1]*($m[1][0]*$m[2][2]-$m[1][2]*$m[2][0])+$m[0][2]*($m[1][0]*$m[2][1]-$m[1][1]*$m[2][0]); };
  $D = $det($M);
  $Mx = array(array($b[0],$M[0][1],$M[0][2]),array($b[1],$M[1][1],$M[1][2]),array($b[2],$M[2][1],$M[2][2]));
  $My = array(array($M[0][0],$b[0],$M[0][2]),array($M[1][0],$b[1],$M[1][2]),array($M[2][0],$b[2],$M[2][2]));
  $Mz = array(array($M[0][0],$M[0][1],$b[0]),array($M[1][0],$M[1][1],$b[1]),array($M[2][0],$M[2][1],$b[2]));
  return array($det($Mx)/$D, $det($My)/$D, $det($Mz)/$D);
}

$pts = array(
  array('lat'=>14.620303859819074,'lng'=>121.15358155696558,'x'=>3300,'y'=>639),
  array('lat'=>14.62001165707186,'lng'=>121.15312420991697,'x'=>2687,'y'=>784),
  array('lat'=>14.61943626769976,'lng'=>121.15247410852331,'x'=>1735,'y'=>760),
  array('lat'=>14.61959129503177,'lng'=>121.15203968057038,'x'=>1168,'y'=>654),
  array('lat'=>14.619564463385176,'lng'=>121.15166379256078,'x'=>610,'y'=>443),
  array('lat'=>14.619263352459713,'lng'=>121.1516791978071,'x'=>608,'y'=>791),
  array('lat'=>14.61928295764446,'lng'=>121.15189987667863,'x'=>830,'y'=>879),
  array('lat'=>14.619672262909887,'lng'=>121.15284669652685,'x'=>2225,'y'=>850),
  array('lat'=>14.619918822564339,'lng'=>121.153348269658,'x'=>2980,'y'=>980),
  array('lat'=>14.620471634244192,'lng'=>121.1539061691739,'x'=>3700,'y'=>596),
);

$Sxx=$Sxy=$Sx=$Syy=$Sy=$S=$SxX=$SyX=$SX=$SxY=$SyY=$SY=0;
foreach($pts as $p) {
  $Sxx+=$p['lng']*$p['lng']; $Sxy+=$p['lng']*$p['lat']; $Sx+=$p['lng'];
  $Syy+=$p['lat']*$p['lat']; $Sy+=$p['lat']; $S+=1;
  $SxX+=$p['lng']*$p['x']; $SyX+=$p['lat']*$p['x']; $SX+=$p['x'];
  $SxY+=$p['lng']*$p['y']; $SyY+=$p['lat']*$p['y']; $SY+=$p['y'];
}
$M=array(array($Sxx,$Sxy,$Sx),array($Sxy,$Syy,$Sy),array($Sx,$Sy,$S));
$ABC=solve3x3($M,array($SxX,$SyX,$SX));
$DEF=solve3x3($M,array($SxY,$SyY,$SY));

$T=array('A'=>$ABC[0],'B'=>$ABC[1],'C'=>$ABC[2],'D'=>$DEF[0],'E'=>$DEF[1],'F'=>$DEF[2]);

$residuals=array();
foreach($pts as $p) {
  $bx=$T['A']*$p['lng']+$T['B']*$p['lat']+$T['C'];
  $by=$T['D']*$p['lng']+$T['E']*$p['lat']+$T['F'];
  $residuals[]=array('lat'=>$p['lat'],'lng'=>$p['lng'],'rx'=>$p['x']-$bx,'ry'=>$p['y']-$by);
}

function gpsToSvg($T,$res,$lat,$lng) {
  $bx=$T['A']*$lng+$T['B']*$lat+$T['C'];
  $by=$T['D']*$lng+$T['E']*$lat+$T['F'];
  $nearby=array();
  foreach($res as $r) {
    $dl=$lat-$r['lat']; $dl2=$lng-$r['lng'];
    $d2=$dl*$dl+$dl2*$dl2;
    if($d2>0) $nearby[]=array('r'=>$r,'d2'=>$d2);
  }
  usort($nearby,function($a,$b){return $a['d2']<=>$b['d2'];});
  $nearby=array_slice($nearby,0,4);
  if(empty($nearby)) return array('x'=>$bx,'y'=>$by);
  $wSum=$cx=$cy=0;
  foreach($nearby as $it) {
    if($it['d2']<1e-18) return array('x'=>$bx+$it['r']['rx'],'y'=>$by+$it['r']['ry']);
    $w=1/$it['d2']; $wSum+=$w; $cx+=$w*$it['r']['rx']; $cy+=$w*$it['r']['ry'];
  }
  return array('x'=>$bx+$cx/$wSum,'y'=>$by+$cy/$wSum);
}

$test=gpsToSvg($T,$residuals,14.61972470198139,121.15187815541802);
echo "Coords: 14.61972470198139, 121.15187815541802\n";
echo "Affine x: ".($T['A']*121.15187815541802+$T['B']*14.61972470198139+$T['C'])."\n";
echo "Affine y: ".($T['D']*121.15187815541802+$T['E']*14.61972470198139+$T['F'])."\n";
echo "Corrected SVG: (".number_format($test['x'],1).", ".number_format($test['y'],1).")\n";

$best=null;$bestD=INF;
foreach($pts as $p) {
  $d=sqrt(($test['x']-$p['x'])*($test['x']-$p['x'])+($test['y']-$p['y'])*($test['y']-$p['y']));
  if($d<$bestD){$bestD=$d;$best=$p;}
}
echo "Nearest calib pt: (".$best['x'].", ".$best['y'].") at ".number_format($bestD,1)." px\n";

echo "\nCalibration residuals:\n";
foreach($residuals as $r) echo "  (".$r['lat'].", ".$r['lng'].") rx=".number_format($r['rx'],1)." ry=".number_format($r['ry'],1)."\n";
