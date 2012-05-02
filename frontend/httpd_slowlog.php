<?php
/*made by brooke :) */

// $CNF['date_format']="Y-m-d H:i:s";
 $CNF['date_format']="Y-m-d 00:00:00";
 $CNF['table_name']="my_slowlog";

 $CNF['date_start']=null;
 $CNF['date_end']=null;
 $CNF['unique_hide_small']=null;
 $CNF['unique_hide_num']=null;
 
 $CNF['sql_skip_rows']=0;
 $CNF['sql_limit']=50;
 $CNF['sql_orderby']='id';
 $CNF['sql_order_type']='ASC';


 $LINKS=array(
    'total_rows'=>'?page=1',
    'unique_rows'=>'?page=2',
    'max_query_time'=>'?page=3',
    'big_time_rows'=>'?page=4'
 );

 $STARTTIME=time();
 $RESULT = array();

//onCreate---
session_start();
//---

/*
echo "GET : ";
print_r($_GET);
echo "<br> SESSION: ";
print_r($_SESSION);
*/
//set CNF
 if(array_key_exists("skip",$_GET)) $CNF['sql_skip_rows']=$_GET['skip']; 
 if(array_key_exists("sql_orderby",$_GET)) $CNF['sql_orderby']=$_GET['sql_orderby']; 
 if(array_key_exists("sql_order_type",$_GET)) $CNF['sql_order_type']=$_GET['sql_order_type']; 


if(!$_SESSION['unique_hide_small']) $_SESSION['unique_hide_small']="on";
if($_GET['submit_date']) 
{
  $_SESSION['submit_date']=true;
  if(!$_GET['unique_hide_small']){ $_SESSION['unique_hide_small'] = "off";}
  else $_SESSION['unique_hide_small'] = "on";
  echo "OK";
}
/*
echo "<br> SESSION: ";
print_r($_SESSION);
*/

$CNF['unique_hide_small']=$_SESSION['unique_hide_small'];


 function set_global_variables()
 {
  global $_GET;
  global $CNF;
  global $_SESSION;

  $y=time()-(24*60*60);
  
  $list['date_start']=array(date($CNF['date_format'],$y),'/^201[0-9]{1}-[01]{1}[0-9]{1}-[0123]{1}[0-9]{1}\ [0-9]{2}:[0-9]{2}:[0-9]{2}$/');
  $list['date_end']=array(date($CNF['date_format']),'/^201[0-9]{1}-[01]{1}[0-9]{1}-[0123]{1}[0-9]{1}\ [0-9]{2}:[0-9]{2}:[0-9]{2}$/');
  $list['unique_hide_num']=array(10,'/^[0-9]{1,4}$/');


  $where=array($_SESSION,$_GET);

  foreach($list as $key=>$val)
  {
   foreach($where as $gl)
   {
      if(array_key_exists($key,$gl))
      {
       if(array_key_exists("1",$val))
       {
         if(preg_match($val[1],$gl[$key]))
         {
          $CNF[$key]=$gl[$key];
         }
         else die ("[ERR] variable '".$key."' not '".$val[1]."' ");
       }
       else $CNF[$key]=$gl[$key];
      }
      elseif(!$CNF[$key]) $CNF[$key]=$val[0];
   }
   $_SESSION[$key]=$CNF[$key];
  }

 }



 function make_db_query($query,&$num=0)
 {
  $mysqli = new mysqli('localhost', 'slowloguser', '8JCuU2qNrb', 'my_slowlog') or die("[ERR] Unable connect to dababase : ".mysqli_connect_error());

  if(($query)&&("string" == gettype($query))&&(strlen($query)>20))
  {
   if($res = $mysqli->query($query))
   {
    $num=$mysqli->affected_rows;
    if($num > 0)
    {
     return $res;
    }
    else return 0;   
   }
   else return 0;
  }
  else return 0;
  $mysqli->close();
 }

/* Global statistic ---*/
function get_total_rows($query)
{ 
 global $CNF;
 if($result=make_db_query($query))
 {
    $row = $result->fetch_assoc();
    $result->close();
    return $row['result'];
 }
 return false;
}

function get_query_array($query)
{
 if($result=make_db_query($query))
 {
  return $result->fetch_assoc();
 }
 return false;
}

/*--- Global statistic */ 

/* print Functions ---*/

function print_global_result()
{ 
  global $RESULT;
  global $LINKS;
  global $CNF;
  
  $result="<form method='GET' name='date_form'><table class='header_wide' cellpadding='3' cellspacing='1' border=0>";
  //   <strong>".$CNF['date_start']." - ".$CNF['date_end']."</strong>
  if ($CNF['unique_hide_small']=='on') $unique_hide_small="CHECKED";
  $result.="
  <tr valign=top>
   <td width=150>date_start</td>
   <td width=150 align='left'>".$CNF['date_start']."</td>
   <td align='left' rowspan='".(count($RESULT) + 3)."'>
    <table border=0 cellpadding='3' cellspacing='1'>
     <tr valign=top>
      <td valign=top>
       <nobr>
       <input type='checkbox' $unique_hide_small name='unique_hide_small' >
       Hide unique querys if less <input type='text' class='biginput' size='2' maxlenght='2' name='unique_hide_num' value='".$CNF['unique_hide_num']."'>  times
       </nobr>
       </td>
     </tr>
     <tr valign='top' ><td><input type=text class='biginput' name='date_start' maxlenght='19'  value='".$CNF['date_start']."'></td></tr>
     <tr valign= top' ><td><input type=text class='biginput' name='date_end' maxlenght='19' value='".$CNF['date_end']."'></td></tr>

     <tr><td><input type='hidden' name='PHPSESSID' value='".session_id()."' /><br></td></tr>
     <tr><td><input type='submit' name='submit_date' class='button' value='apply'></td></tr>
    </table>
   </td>
  </tr>";
  $result.="
  <tr valign=top>
   <td>date_end</td>
   <td>".$CNF['date_end']."</td>

  </tr>";
  foreach($RESULT as $key=>$value)
  {
   $a=null; $a1=null;
   if(array_key_exists($key,$LINKS))
   {
    $a="<a href='".$LINKS[$key]."'>";
    $a1="</a>";
   }
   $result.="<tr valign=top class='even_row'><td>$a$key$a1</td><td>$a$value$a1</td></tr>";
  }
  $result.="<tr><td colspan='2'>&nbsp;</td></tr></table></form>";
  $result.="<br><br>";
  return $result;
}

function print_query_result($query,$enable_links=false)
{
 global $CNF;
 global $_GET;
 $html="<table class='tableinfo' cellpadding='3' cellspacing='1' width='100%'>";
 if($result=make_db_query($query))
 {
  $fields=$result->fetch_fields();
  $headers="<tr class='header'>";
  //--
  if(($_GET['hostname'])and($_GET['request']))
  {
   $link_part="&hostname=".$_GET['hostname']."&request=".$_GET['request'];
  }
  else $link_part=null;
  //--
  foreach($fields as $value)
  {
   $links="&nbsp;&nbsp;[ 
   <a href='?page=".$_GET['page']."&sql_orderby=".$value->name."&sql_order_type=ASC".$link_part."'>A</a>
   <a href='?page=".$_GET['page']."&sql_orderby=".$value->name."&sql_order_type=DESC".$link_part."'>D</a>
   ]";
   $headers.="<td>".$value->name." $links</td>";
  }
  $headers.="</tr>";
  $html.=$headers;
  while($row = $result->fetch_assoc())
  {
   $html.="<tr class='even_row'>";
   $keys=array_keys($row);
   //--
   if($enable_links) $link="?page=5&hostname=".$row['hostname']."&request=".$row['request'];
   //--
   while($k=array_shift($keys))
   {
   //--
    if(($enable_links)and($k=='request')) $row[$k]="<a href=\"$link\">".$row[$k]."</a>";
   //--
    $html.="<td>".$row[$k]."</td>";
   }
   $html.="</tr>";
  }
 }
 else $html.="<tr><td>Query return 0 rows</td></tr>";
 $html.="</table>";
 return $html;
}

function print_navi()
{
 global $CNF;
 global $_GET;

  if(($_GET['hostname'])and($_GET['request']))
  {
   $link_part="&hostname=".$_GET['hostname']."&request=".$_GET['request'];
  }
  else $link_part=null;
 return "
 <center>
  <b>
  <a  class='history' href='?page=".$_GET['page']."&skip=".($CNF['sql_skip_rows'] - $CNF['sql_limit'])."&sql_orderby=".$CNF['sql_orderby']."&sql_order_type=".$CNF['sql_order_type'].$link_part."'>&larr; back</a>
  &nbsp;&nbsp;
  <a class=history href='?page=".$_GET['page']."&skip=".($CNF['sql_skip_rows'] + $CNF['sql_limit'])."&sql_orderby=".$CNF['sql_orderby']."&sql_order_type=".$CNF['sql_order_type'].$link_part."'>forward &rarr</a>
  </b>
 </center>
 ";
}

function make_global_result()
{
 global $RESULT;
 global $CNF;

 $query="SELECT
	    count(sl.id) AS total_rows,  
	    count(DISTINCT sl.hostname,sl.request) AS unique_rows,
	    min(sl.time) AS min_query_time,
	    max(sl.time) AS max_query_time,
	    avg(sl.time) AS average_query_time
	FROM ".$CNF['table_name']. " sl
	".$CNF['sql_request_time'].";
 ";
 $RESULT=get_query_array($query);

 $query="SELECT count(id) AS result FROM ".$CNF['table_name']." ".$CNF['sql_request_time']." AND time > '".$RESULT['average_query_time']."' LIMIT 0,5;";
 $RESULT['big_time_rows']= get_total_rows($query);
 
 $RESULT['percent_big_rows']=($RESULT['big_time_rows']/$RESULT['total_rows'])*100;
 $RESULT['percent_big_rows']=round($RESULT['percent_big_rows']);
 $RESULT['percent_big_rows'].=" %";
}


//set CNF again :)


set_global_variables();
$CNF['sql_request_time']="WHERE request_time > '".$CNF['date_start']."' AND request_time < '".$CNF['date_end']."'";
$CNF['sql_order']="ORDER BY ".$CNF['sql_orderby']." ".$CNF['sql_order_type'];
//----

if($_SESSION['submit_date']) 
{
 make_global_result();
}
//make_global_result();


$BODY=print_global_result();

$BODY.=print_navi();

switch($_GET['page']) 
{
  case 1:
     $query="SELECT remote_ip,request_time,hostname,request,pid,time,cpu_usr,cpu_sys FROM ".$CNF['table_name']." ".$CNF['sql_request_time']." ".$CNF['sql_order']." LIMIT ".$CNF['sql_skip_rows'].",".$CNF['sql_limit']." ;";
     $BODY.=print_query_result($query);
  break;
  case 2;
    if($CNF['unique_hide_small']=="on")
    {
     $buf="HAVING num > '".$CNF['unique_hide_num']."' ";
    }
    else $buf=null;
    $query="SELECT hostname, request, COUNT(id) num, AVG(time) time_avg, SUM(time) time_total, AVG(cpu_usr) cpu_usr_avg, AVG(cpu_sys) cpu_sys_avg FROM ".$CNF['table_name']." ".$CNF['sql_request_time']." GROUP BY hostname, request $buf ".$CNF['sql_order']." LIMIT ".$CNF['sql_skip_rows'].",".$CNF['sql_limit'].";";
//    echo $query;
    $BODY.=print_query_result($query,true);
  break;
  case 3;
     $query="SELECT remote_ip,request_time,hostname,request,pid,time,cpu_usr,cpu_sys FROM ".$CNF['table_name']." ".$CNF['sql_request_time']." AND time='".$RESULT['max_query_time']."' LIMIT 0,5;";
     $BODY.=print_query_result($query);
  break;
  case 4;
     $query="SELECT remote_ip,request_time,hostname,request,pid,time,cpu_usr,cpu_sys FROM ".$CNF['table_name']." ".$CNF['sql_request_time']." AND time > '".$RESULT['average_query_time']."' ".$CNF['sql_order']." LIMIT ".$CNF['sql_skip_rows'].",".$CNF['sql_limit']." ;";
     $BODY.=print_query_result($query,true);
  break;
  case 5;
     $query="SELECT remote_ip,request_time,hostname,request,pid,time,cpu_usr,cpu_sys FROM ".$CNF['table_name']." ".$CNF['sql_request_time']." AND hostname LIKE '".$_GET['hostname']."' AND request LIKE '".$_GET['request']."' ".$CNF['sql_order']." LIMIT ".$CNF['sql_skip_rows'].",".$CNF['sql_limit']." ;";
     $BODY.=print_query_result($query);
  break;
} 

 settype($STARTTIME,"integer");
 $GENTIME=time() - $STARTTIME;
 echo("
 <html>
   <head>
    <title>.: my.alpari.ru -- apache slowlog</title>
    <link rel='stylesheet' type='text/css' href='../css.css' />
   </head>
   <body bgcolor=white text=black>
   generation time:".$GENTIME." s<br>
   ".$BODY."
   <br>
   </body>
 </html>
 ");
 
?>
