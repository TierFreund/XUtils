<?
define( "MO_NOTRIM", 1 );
define( "MO_ALLOWHTML", 2 );

function dump($var,$ShowFunc=TRUE,$step=0, $inAryObj=0,$name='', &$quellcode){
	$Out='';
	$fmt="(%s) => %s\n";
	$fmtc="%s%s";
	$typ=gettype($var);
	switch($typ){
		case 'boolean': 
			$quellcode.=sprintf($fmtc,$var?'true':'false',$inAryObj?'':";\n");
			$Out.=sprintf($fmt,'bool',$var?'true':'false');
			break; 
		case 'integer': 
			$quellcode.=sprintf($fmtc,$var,$inAryObj?'':";\n");
			$Out.=sprintf($fmt,'int',$var);
			break; 
		case 'double' : 
			$quellcode.=sprintf($fmtc,$var,$inAryObj?'':";\n");
			$Out.=sprintf($fmt,'float',$var); 
			break;
		case 'string' : 
			$quellcode.=sprintf($fmtc,"'$var'",$inAryObj?'':";\n");
			$Out.=sprintf($fmt,'string',"'".htmlspecialchars($var)."'");
			break; 
		case 'array'  :  
			if($inAryObj!=0)$Out.=str_repeat(' ',$step);;
			$Out.="=> Array [\n";
			if($inAryObj>0){
				$quellcode.=$name?"$name=array(":"array(";
			}else if($inAryObj!=0){
				$quellcode.='array(';
			}else 
				$quellcode.='array(';
//			$quellcode.=($inAryObj<0)?'array(':($inAryObj==0)?'array(':$name?"$name=array(":"array(";
			$step+=3; $cc=0;
			$leer=str_repeat(' ',$step);
			foreach($var as $k=>$v){
				if(!$k)$k=$cc++; // if(!$k)continue;
				$Out.="$leer<b>$k</b> ";
				$quellcode.="\n$leer".(is_string($k)?"'$k'":$k)."=>";
				$Out.=trim(dump($v, $ShowFunc, $step,-1,$k, $quellcode))."\n";
				$quellcode.=",";
			}
			$step-=3;$leer=str_repeat(' ',$step);
			$Out.="$leer] <i>End Array <b>$name</b></i>\n";
			$quellcode=substr($quellcode,0,strlen($quellcode)-1)."\n$leer)";
			if($inAryObj==0)$quellcode='$'.($name?$name:'myArray')."=$quellcode;";
			break;
		case 'object' :
			$quellcode.='$'.($name?$name:'myObj')."=new stdClass();\n";
			$leer=str_repeat(' ',$step);
			if($inAryObj!=0)$Out.=$leer;
			$Out.="=> Object '".get_class($var)."' (\n";
			$step+=3;$cc=0; $leer=str_repeat(' ',$step);
			foreach(get_object_vars($var) as $k=>$v){
				if(!$k)$k=$cc++; // if(!$k)continue;
				$quellcode.="{$name}->$k=";
				$Out.="$leer<b>".htmlspecialchars($k)."</b> ";
//				$Out.=htmlspecialchars(trim(dump($v, $ShowFunc,$step,1,$k,$quellcode))."\n");
				$Out.=trim(dump($v, $ShowFunc,$step,1,$k,$quellcode))."\n";
			}
			if($ShowFunc){
				$leer=str_repeat(' ',$step);
				foreach(get_class_methods($var) as $k=>$v){
					if (substr($v,0,2)=='__') continue; // System Funktion
					$Out.=$leer."Funktion : <b>$v()</b>\n";
				}
			}
			$step-=3;
			$Out.=str_repeat(' ',$step). ") <i>End Object : $name</i>\n";
			break;
		case 'resource':; break; 
		case 'NULL'	  : 
			$quellcode.=sprintf($fmtc,'null',$inAryObj?'':";\n");
			$Out.=sprintf($fmt,'','null'); 
			break; 
		case 'unknown type"': 
			$Out.=sprintf($fmt,'??','null'); 
			break; 
 	}
	return $Out;
}
function dumpvar($var,$VarName='',$ShowFunc=TRUE,$mode='dump'){
	$display=$VarName?" => <b>$VarName</b>":'';
	$quellcode='';
	$code=dump($var,$ShowFunc,0,0,'', $quellcode);
	echo "<em>//--=> DUMP".($mode=='dump'?'VAR':'CODE')." $display <=---------------------------</em><br>";
	if($mode=='dump')echo '<code>'.nl2br(str_replace(' ','&nbsp;', $code)).'</code>'; 
	if($mode=='code')echo '<code>&lt;?<br>'.nl2br(str_replace(array(' ','myArray'),array('&nbsp;',$VarName?$VarName:'myArray'),$quellcode)).'<br>?&gt;</code>'; 
}
function dumpcode($var, $VarName=''){
	dumpvar($var,$VarName,false,'code');
}	
	
function vardump($var,$VarName='',$ShowFunc=TRUE,$mode='dump'){
	return dumpvar($var,$VarName,$ShowFunc,$mode);
}	
function Val2Var($v){
	if(is_bool($v)===true)
        $v=(bool)$v;
    else if(is_numeric($v)===true){
		if(is_double($v)===true)$v=(double)$v;
		else $v=(integer)$v;
	}
	return $v;
}
	
function parse_html_header($header){
	@list($head, $body) = explode("\r\n\r\n", $header, 2);
	$head=explode("\r\n",$head);
	$r['head']=$r['compled']=$r['type']=$r['method']='';
	list($r['method'], $r['data'],$r['type']) = explode(' ', array_shift($head), 3);
	if($r['data']&&$r['data'][0]=='/')$r['data']=substr($r['data'],1);
	$len=0; $compled=false;
	$r['compled']=$compled; 
	foreach($head as $b){
		list($k,$v) = explode(':',$b,2);
		$k=strtoupper(trim($k));
		if($k=='CONTENT-LENGTH')$len=(int)$v;
		$r['head'][$k]=trim($v);
	}
	if($len){
		$bodylen=strlen($body);
		if($len<=$bodylen){
			$r['content']=substr($body,0,$len);
			$r['nextdata']=substr($body,$len);
			$compled=$len==$bodylen?true:1;
		}
	}else{
		$compled=true;
		if($r['method']=='POST'&&!$body)$compled=false;
		if($r['method']=='GET'&&!$r['data'])$compled=false;
		if($compled)$r['content']=$body;
	}	
	if(!$compled)return $compled;
	$r['compled']=$compled; 
	return $r;
}
function parse_html_header_data($head){
	if(!$head||!is_array($head))return $head;
	if($head['method']=='NOTIFY')return $head;
	$r=&$head['data']; $pa=null;
	if($head['method']=='POST'){
		if($head['content']&&$head['content'][0]!='<')
			$pa=explode('&',$head['content']);
	}elseif($r['method']=='GET'){
		if(strpos($r,'=')!==false)
			$pa=explode('&',substr($r,1));
		elseif(preg_match('/(.*)\((.*)\)/',$r,$matches)){
            array_shift($matches);
            $r=[]; $head['method']='CALL';
			$r['name']=array_shift($matches);
            if($aa=array_shift($matches)){
                $r['arguments']=explode(',',$aa);
                foreach($r['arguments'] as $k=>$v)
					$r['arguments'][$k]=Val2Var($v);
            }else $r['arguments']=[];
		}
	}
	if(!$pa)return $head;
	$r=[];
	foreach($pa as $k=>$v){
		list($kk,$vv)=explode('=',$v,2);
		$r[$kk]=Val2Var($vv);
	}
	return $head;
}

function CleanTextArray($text){
	$l=explode("\n",str_ireplace('&nbsp;',' ',strip_tags($text)));
	$ll=array();foreach($l as $v){$v=trim($v);if($v)$ll[]=$v;}
	return $ll;
}
function CreateIdentByName($name) {
	$ident = str_replace(' ', '', $name);
	$ident = str_replace(array('?','?','?','?','?','?'), array('oe', 'ae','ue','Oe', 'Ae','Ue' ), $ident);
	$ident = str_replace(array('"','\'','%','&','(',')','=','#','<','>','|','\\'), '', $ident);
	$ident = str_replace(array(',','.',':',';','!','?'), '', $ident);
	$ident = str_replace(array('+','-','/','*'), '', $ident);
	$ident = str_replace(array('?'), 'ss', $ident);
	return $ident;
}
function CombineObject(&$source, &$target) {
	$ok=FALSE;
	foreach (get_object_vars( $source ) as $k => $v) {
		if (is_array($v) or is_object($v) or $v == NULL) {
			continue;
		}
		if ($k[0] == '_') { // internal field
		  continue;
		}
		if ($v == PASSWORD_VALUE) { // Password blank nicht spaichern
		  continue;
		}
		if(isSet($target->{$k})){
	
			$target->{$k}=$source->{$k};
			$ok=TRUE;
		}
	}
	return $ok;
}
function formatText($text, $maxlen=50){
//  if(!$text || strlen($text)<$maxlen)return $text;
  $a=preg_split("/[\n]+/",$text); $l=''; $ln="\n"; //chr(13).chr(10);
  for($i=0;$i<count($a);$i++){
    $s=wordwrap($a[$i],$maxlen,"\n");
    $b=preg_split("/[\n]+/",$s);
    $l.=($l)?$ln."$b[0]":"$b[0]";
    for($ii=1;$ii<count($b);$ii++){
      $l.=$ln."$b[$ii]";
    }
  }
  return $l;
}

function Request($name, $def=null, $mask=0 ) {
    global $_REQUEST, $_POST;
    if($_REQUEST)$_R=&$_REQUEST; else $_R=&$_POST;
    if (isset( $_R[$name] )) {
      $result=$_R[$name];
      if (is_string( $result)) {
            if (!($mask&MO_NOTRIM)) {
                $result = trim( $result );
            }
            if (!($mask&MO_ALLOWHTML)) {
                $result = strip_tags( $result );
            }
            if (!get_magic_quotes_gpc()) {
                $result = addslashes( $result );
            }
      }
      return $result;
    } else {
        return $def;
    }
}

function RequestObject($include, $AllowNull=true, $OnlyIfSet=true, $prefix=''){
  if(!$include)return null;
  if(!is_array($include))$include=array($include);
  $o=new stdClass();
  foreach($include as $n){
    $v=Request($prefix.$n,'-xb!+@');
    if ($v=='-xb!+@'){
      if ($OnlyIfSet)continue;
      $v='';
    }
    if(($AllowNull)||($v))$o->$n=$v;
  }
  return $o;
}

function Redirect( $url, $msg='', $timeout=0, $asScript=false ) {
    if(!(stripos($url,'javascript:')===false)){
      $data="<script>\n".substr($url,11).";</script>";
      die("$data");
    }else
      if (trim( $msg ))$url.=(strstr( $url, '?' ))? "&msg=$msg" : "?msg=$msg";
      if (headers_sent()) {
       if($timeout)
         $data="<script>;
         function refreshurl(){
           document.location.href='$url';
         }
         setTimeout(function(){ refreshurl(); }, ".($timeout*1000).");
         </script>";
       else
         $data="<script>document.location.href='$url';</script>\n";
    } else {
       if($timeout)
          header ("Refresh: $timeout url=$url");
       else
          header( "Location: $url" );
    }
    if ($asScript)return $data;
    echo $data;
    exit();
}

function unzip($file, $path, &$files) {
  $zip = zip_open($file);
  if ($zip) {
   while ($zip_entry = zip_read($zip)) {
     if (zip_entry_filesize($zip_entry) > 0) {
       // str_replace must be used under windows to convert "/" into "\"
       $complete_path = $path.str_replace('/','',dirname(zip_entry_name($zip_entry)));
       $complete_name = $path.str_replace ('/','',zip_entry_name($zip_entry));
       if(!file_exists($complete_path)) { 
         $tmp = '';
         foreach(explode('',$complete_path) AS $k) {
           $tmp .= $k.'';
           if(!file_exists($tmp)) {
             mkdir($tmp, 0777); 
           }
         } 
       }
       if (zip_entry_open($zip, $zip_entry, "r")) {
         $files[]=$complete_name;
         $fd = fopen($complete_name, 'w');
         fwrite($fd, zip_entry_read($zip_entry, zip_entry_filesize($zip_entry)));
         fclose($fd);
         zip_entry_close($zip_entry);
       }
     }
   }
   zip_close($zip);
  }
}
?>