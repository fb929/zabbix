<?php

require_once("./ZabbixAPI.class.php");
ZabbixAPI::debugEnabled(TRUE);
ZabbixAPI::login('http://10.25.7.70/zabbix/','brooke-api','pass') or die('Unable to login: '.print_r(ZabbixAPI::getLastError(),true));

//$DEBUG=true;

//define for commsort
//$_GET['commsort']=true;

$commsort=true;
if($_GET['no_commsort'])
{
 $commsort=false;
}

if($commsort)
{
  $sorttype='DESC';
  $sortfield='lastchange';
}
else
{
  if($_GET['sortfield'])
  {
    $sortfield=$_GET['sortfield'];
  }
  else $sortfield='lastchange';
  
  if($_GET['sorttype'])
  {
    $sorttype='ASC';
  }
  else $sorttype='DESC';
}

$params=array(
	'only_true'=>1,
	'monitored'=>1, 
	'output'=>'extend',
	//'active'=>1,
	'expandData'=>'host',
	'expandDescription'=>1,
	'sortfield'=>$sortfield,
	'sortorder'=>$sorttype,
//	'search'=> array('value'=>1)
);

$tr_all = ZabbixAPI::fetch_array('trigger','get',$params) or die('error: '.print_r(ZabbixAPI::getLastError(),true));

function filter_value($key)
{
  if(0<$key['value']) return $key; 
}

$tr_all=array_filter($tr_all,"filter_value");

/*
print_r($tr_all);

return;
*/

/* commsort -- */
function get_unique_priority($arr,$key)
{
 $result=array();
 foreach($arr as $val)
 {
  array_push($result,$val[$key]);
 }
 $result=array_unique($result);
 arsort($result);
 return $result;
}



function comm_sort($arr,$sort)
{
 $result=array();
 while($n=array_shift($sort))
 {
//  echo "N: $n ";
  foreach($arr as $key => $val)
  {
   if($val['priority']== $n)
   {
    $result[$key]=$val;
   }
  }
 }
 return $result;
}


if($commsort)
{
  $buf=array();
  
  foreach($tr_all as $key  => $val )
  {
   array_push($buf,array($key,$val['priority'],$val['lastchange']));
  }
  $buf1=get_unique_priority($buf,"1");
  $tr_n=comm_sort($tr_all,$buf1);
  unset($tr_all);
  $tr_all=$tr_n;
}
/* -- commsort*/


$update=null;
if(!$DEBUG) $update="onload=\"doLoad()\"";
echo("
 <html>
  <head>
	<link rel=\"shortcut icon\" href=\"../images/general/zabbix.ico\" />
	<link rel=\"stylesheet\" type=\"text/css\" href=\"../css.css\" />
        <script language=\"JavaScript\">
         <!--
          var sURL = unescape(window.location.pathname);

          function doLoad()
          {
            setTimeout( \"refresh()\", 10*1000 );
          }

          function refresh()
          {
            window.location.href = sURL;
          }
         //-->
       </script>
  </head>
  <body $update>
   <table border='0' cellpadding='2'cellspacing='0' width='100%'>
     <tr>
       <td align='left'>
       <strong>
        <script language=\"JavaScript\">
         <!--
         document.write((new Date).toLocaleString());
        //-->
       </script>
       &nbsp;&nbsp;&nbsp;&nbsp;Total triggers: ".count($tr_all)."
       </strong>
      </td>
      <td align='right'><strong><a href='../dashboard.php'>[ back ]</a></strong></td>
     </tr>
   </table> 
   <table class=\"tableinfo\" cellpadding=\"3\" cellspacing=\"1\" width='100%'>
    <tr class=\"header\">
      <td width='20%'><strong>host&nbsp;&nbsp;[<a href='?sorttype=0&sortfield=priority&no_commsort=1'>D</a> <a href='?sorttype=1&sortfield=priority&no_commsort=1'>A</a> ]</strong></td>
      <td width='10%'><strong>dur</strong>&nbsp;&nbsp;[<a href='?sorttype=0&sortfield=0&no_commsort=1'>D</a> <a href='?sorttype=1&sortfield=0&no_commsort=1'>A</a>]</td>
      <td width='70%'><strong>description</strong></td>
    </tr>
");

 function conv_time($str)
 {
  $hour=null;$min=null;$sec=null;
  $m=bcdiv($str,60,0);
  if($s=$str-$m*60) $sec.=$s."s";
  if($h=bcdiv($m,60,0)) $hour=$h."h";
  if($m=$m-$h*60) $min=$m."m";
  return "$hour $min $sec";
 }


while($tr=array_shift($tr_all))
{
// $date=date('d.m.Y H:i:s', $tr['lastchange']);
 $date=date('d.m H:i', $tr['lastchange']);
 $dur=conv_time(time() - $tr['lastchange']);
/*
 $str=array();
 array_push($str,$tr['priority'],$tr['host'],$date,$dur,$tr['description']);
 print_r($str);
*/
 $cl=null;
 $db=null;
 if($DEBUG) $db="<br>trigger:".$tr['triggerid']."<br> template:".$tr['templateid']."<br>value: ".$tr['value'];
 switch ($tr['priority'])
 {
  case 1:
  case 2:
   $cl="class='warning'";
   $mes="<strong>W</strong>";
  break;
  case 3:
   $cl="class='average'";
   $mes="<strong>A</strong>";
  break;
  case 4:
   $cl="class='high'";
   $mes="<strong>H</strong>";
  break;
  case 5:
   $cl="class='disaster'";
   $mes="<strong>D</strong>";
  break;
 }


 $dblink = mysql_connect('localhost', 'zabbix_select', 'ZAbMonr45') or die('cannot connect to mysql');
 mysql_select_db('zabbix', $dblink) or die('cannot use database');

 $query="SELECT
distinct t1.triggerid,
h1.host,
t1.description,
g1.name,
i1.itemid
FROM
triggers as t1,
functions as f1,
items as i1,
hosts as h1,
hosts_groups as hg1,
groups as g1
WHERE
t1.value=1 and
f1.triggerid=t1.triggerid and
i1.itemid=f1.itemid and
h1.hostid=i1.hostid and
hg1.hostid=h1.hostid and
g1.groupid=hg1.groupid and
t1.status=0 and
i1.status=0 and
h1.status=0 and 
f1.triggerid = '".$tr['triggerid']."';
";

 $res=mysql_query($query,$dblink);
 
 $link=null;
 $link_close=null;
 if(mysql_num_rows($res)>0)
 {
  $res=mysql_fetch_array($res);
  $link="<a href='../history.php?action=showgraph&itemid=".$res[itemid]."'>";
  $link_close="</a>";
 }
 

// echo("<tr class=\"even_row\"><td $cl>p:".$tr['priority']."</td><td $cl>".$tr['host']."</td><td>$date</td><td>$dur</td><td>".$tr['description']."</td></tr>");
 echo("<tr class=\"even_row\"><td $cl>".$tr['host']."$db</td><td>$dur</td><td>$link".$tr['description']."$link_close</td></tr>");

}

echo("
  </table>
 </body>
</html>
");

?>
