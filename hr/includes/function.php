<?php
$crdate = date("Y-m-d");
$crtime = date("H:i:s");
$crdatetime = date("Y-m-d H:i:s");
$today = date("d-m-Y");
function Datein($str)
{
	list($day, $month, $year) = explode('-', trim($str));
	return (strlen($str)>5)?($year."-".$month."-".$day):"";
}
function Dateout($str)
{
	list($tdat, $ttim) = explode(' ', trim($str));
	list($year,$month,$day) = explode('-', trim($tdat));
	return (strlen($str)>5)?($day."-".$month."-".$year):"";
}
function DateTimein($str)
{
	list($tdat, $ttim) = explode(' ', trim($str));
	list($day, $month, $year) = explode('-', trim($tdat));
	return (strlen($str)>5)?($year."-".$month."-".$day." ".$ttim):"";
}
function DateTimeout($str)
{
	list($tdat, $ttim) = explode(' ', trim($str));
	list($year,$month,$day) = explode('-', trim($tdat));
	return (strlen($str)>5)?($day."-".$month."-".$year." ".Timeout($ttim)):"";
}
function NUM($num){ 
	if($num=="0"){
	return $num;
	}else
	{
	$num=round($num,2);
	list($int,$dec) = explode('.', $num);
	if(strlen($int)>3){ 
				$lastthree = substr($int, strlen($int)-3, strlen($int)); 
				$restunits = substr($int, 0, strlen($int)-3); // extracts the last three digits 
	$restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits; // explodes the remaining digits in 2's formats, adds a zero in the beginning to maintain the 2's grouping. 
				$expunit = str_split($restunits, 2); 
				for($i=0; $i<sizeof($expunit); $i++){ 
					 $explrestunits .=((strlen($expunit[$i])%2 == 1)?"0".$expunit[$i]:$expunit[$i]).",";
					//$explrestunits .= (int)$expunit[$i].","; // creates each of the 2's group and adds a comma to the end 
				}     
				$thecash = $explrestunits.$lastthree;
				if(strlen($dec)>0)
				   $thecash .=".".$dec; 
		} else { 
			   $thecash = $int; 
		}
	}
		if($thecash[0]=='0')
		  $thecash=substr($thecash, 1, strlen($thecash));
		return " ".$thecash; // writes the final format where $currency is the currency symbol. 
}

function INR($num){ 
	if($num=="0"){
	return $num;
	}else{
		$num=round($num,2);
		list($int,$dec) = explode('.', $num);
		$dec=sprintf ('%0.2f',("0.".$dec));
		$dec=str_replace("0.","",$dec);
		if(strlen($int)>3){
			$lastthree = substr($int, strlen($int)-3, strlen($int)); 
			$restunits = substr($int, 0, strlen($int)-3); // extracts the last three digits 
			$restunits = (strlen($restunits)%2 == 1)?"0".$restunits:$restunits; // explodes the remaining digits in 2's formats, adds a zero in the beginning to maintain the 2's grouping. 
			$expunit = str_split($restunits, 2); 

			for($i=0; $i<sizeof($expunit); $i++){ 
				 $explrestunits .=((strlen($expunit[$i])%2 == 1)?"0".$expunit[$i]:$expunit[$i]).",";
				//$explrestunits .= (int)$expunit[$i].","; // creates each of the 2's group and adds a comma to the end 
			}     
			$thecash = $explrestunits.$lastthree;
		}
	}
	if($thecash[0]=='0')
	  $thecash=substr($thecash, 1, strlen($thecash));
	return " ".$thecash; // writes the final format where $currency is the currency symbol. 
}
function INR_words($no)
{   
	 $words = array('0'=> '' ,'1'=> 'One' ,'2'=> 'Two' ,'3' => 'Three','4' => 'Four','5' => 'Five','6' => 'Six','7' => 'Seven','8' => 'Eight','9' => 'Nine','10' => 'Ten','11' => 'Eleven','12' => 'Twelve','13' => 'Thirteen','14' => 'Fouteen','15' => 'Fifteen','16' => 'Sixteen','17' => 'Seventeen','18' => 'Eighteen','19' => 'Nineteen','20' => 'Twenty','30' => 'Thirty','40' => 'Fourty','50' => 'Fifty','60' => 'Sixty','70' => 'Seventy','80' => 'Eighty','90' => 'Ninty','100' => 'Hundred and','1000' => 'Thousand','100000' => 'Lakh','10000000' => 'Crore');
		if($no == 0)
			return ' ';
		else {
		$novalue='';
		$highno=$no;
		$remainno=0;
		$value=100;
		$value1=1000;       
				while($no>=100)    {
					if(($value <= $no) &&($no  < $value1))    {
					$novalue=$words["$value"];
					$highno = (int)($no/$value);
					$remainno = $no % $value;
					break;
					}
					$value= $value1;
					$value1 = $value * 100;
				}       
			  if(array_key_exists("$highno",$words))
				  return  $words["$highno"]." ".$novalue." ".INR_words($remainno);
			  else {
				 $unit=$highno%10;
				 $ten =(int)($highno/10)*10;            
				 return $words["$ten"]." ".$words["$unit"]." ".$novalue." ".INR_words($remainno);
			   }
		}
}
function birdate($birthDate)
{
	$birthDate1 = Datein($birthDate);
	$birthDate = explode("-", $birthDate1);
	$age = (date("md", date("U", mktime(0, 0, 0, $birthDate[0], $birthDate[1], $birthDate[2]))) > date("md")
	? ((date("Y") - $birthDate[2]) - 1)
	: (date("Y") - $birthDate[2]));
	return $age;
}
function Timeout($convdt)
{
	$time = explode(":",$convdt);
	$hour24 = $time[0];
	$minute = $time[1];
	$seconds = $time[2];
	$hour12 = $hour24;
	$ampm = "AM";
	if($hour24 > 12)
	{
		$hour12 = $hour24-12;
		$ampm = "PM";
	}
	if($hour24 == 12)
	{
		$ampm = "PM";
	}
	if($seconds==22 && $hour12==12)
	{
		$hour12 = 12;
		$ampm = "AM";
	}
	$time = $hour12.":".$minute." ".$ampm;
	return $time;
}
function Timein($convdt)
{
	$time = date("H:i:00", strtotime($convdt));
	return $time;
}

function valoperator($db,$feat)
{
$sel_operator=mysqli_query($db,"select * from eos_staffs where staff_id= '".$_SESSION['user']['staff_id']."'") or die(mysqli_error());
$res_operator=mysqli_fetch_array($sel_operator);
$menu_accessmenu=explode(",",$res_operator['staff_menu']);
if(!in_array($feat, $menu_accessmenu))
{
  echo'<script language=javascript>alert("Access Denied");</script>';
  echo("<script language='javascript'>window.history.back();</script>");
exit;
}
}
?>