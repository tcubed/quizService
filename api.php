<?php
date_default_timezone_set("America/Chicago");
//===============================================================================
//===============================================================================
//                             LOCATION/LOGGING
//===============================================================================
//===============================================================================
function getUserLoc_old($ip){
    echo "file_get_contents";
    $url='http://api.ipstack.com/'.$ip.'?access_key=8a33354d0397b348fde8c3bc27a4297c';
    echo $url."<br />";
    $json = file_get_contents($url);
    echo $json;
}
function getUserLoc($ip){
    echo "curl";
    // set IP address and API access key
    //$ip = '134.201.250.155';
    $access_key = '8a33354d0397b348fde8c3bc27a4297c';

    // Initialize CURL:
    $url='http://api.ipstack.com/'.$ip.'?access_key='.$access_key;
    echo $url.'<br />';
    $ch = curl_init($url);
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Store the data:
    $json = curl_exec($ch);
    curl_close($ch);

    // Decode JSON response:
    $api_result = json_decode($json, true);

    // Output the "capital" object inside "location"
    echo "json <br />";
    echo $json;
    echo "api result<br />";
    echo $api_result['location']['capital'];
}

function logVisitors($msg=""){
    $fnlog='logs.txt';
    $now=new DateTime();
    //$today=$now->format('Y-m-d H:i:s');
    $today=$now->format('Y-m-d H:i');
    $ip = $_SERVER["REMOTE_ADDR"];
    if(array_key_exists("id",$_COOKIE)==False){
        $id="?";
        $msg=$msg." Visitors should have an id...";
    }
    else{
        $id=$_COOKIE["id"];
    }
    

    $logmsg=$today.','.$id.','.$ip;
    if($msg!=""){
        $logmsg=$logmsg.','.$msg;
    }

    // check for 
    if(file_exists($fnlog)){
        $arr=explode(PHP_EOL,file_get_contents($fnlog));
        foreach($arr as $L){
            if($L==$logmsg){return;}
        }
    }
    file_put_contents($fnlog, $logmsg.PHP_EOL , FILE_APPEND | LOCK_EX);
}
function tailFile($filepath, $lines = 1) {
    return trim(implode("<br >", array_slice(file($filepath), -$lines)));
}
//===============================================================================
//===============================================================================
//                                QUESTIONS
//===============================================================================
//===============================================================================
function qByIndex($idx){
    global $db;
    return $db[$idx];
}
function contentByIntervals($bcvarr){
    // create a content array by a collective set of book_chapter_verse intervals
    // $bcvarr=[[bcv_start,bcv_end],[bcv2_start,bcv2_end]]
    global $db;
    //cho '<br />contentByIntervals<br />';
    $content=array();
    foreach($bcvarr as $bcvrng){
        //echo 'bcvrng <br />';
        //var_dump($bcvrng);
        $bcvstart=explode("_",$bcvrng[0]);
        $bcvend=explode("_",$bcvrng[1]);
        if($bcvstart[0]!=$bcvend[0]){
            echo 'interval must be in same book!';
            die;
        }

        foreach($db as $q){
            // if not in right book, skip
            if($q['BOOK']!=$bcvstart[0]){continue;}
            //
            $ch=intval($q['CH']);
            if($ch<(int)$bcvstart[1]){continue;}
            if($ch>(int)$bcvend[1]){continue;}

            // numerical value of question <chapter>.%03d<verse>
            if($q['VE']==""){$ve=0;}
            else{$ve=floatval($q['VE']);}
            $qv=$ch+(floatval($q['VS'])+$ve)/1000;
            // 
            $bcv1=(int)$bcvstart[1]+floatval($bcvstart[2])/1000;
            $bcv2=(int)$bcvend[1]+floatval($bcvend[2])/1000;
            if(($qv>=$bcv1)&($qv<=$bcv2)){
                //echo $q['BCV'].'<br />';
                array_push($content,$q);
            }
        }
    }
    //echo 'content: '.count($content).' elements. <br />';
    return $content;
}
function filterContent($cont,$qtype=''){
    if($qtype==''){return $cont;}
    $arr=array();
    

    // question types
    switch($qtype){
        case 'CR':
            foreach($cont as $q){
                if(in_array($q['TYPE'],array('CR','CVR','CVRMA','CRMA'))){
                    array_push($arr,$q);
                }
            }
            break;
        case 'Q':
            foreach($cont as $q){
                if(in_array($q['TYPE'],array('Q','Q2V'))){
                    array_push($arr,$q);
                }
            }
            break;
        case 'MA':
            foreach($cont as $q){
                if(in_array($q['TYPE'],array('MA','MACR','CRMA'))){
                    array_push($arr,$q);
                }
            }
            break;
        case 'FT':
            foreach($cont as $q){
                if(in_array($q['TYPE'],array('FT','FTN','F2V','FTV'))){
                    array_push($arr,$q);
                }
            }
            break;
        case 'INT':
            foreach($cont as $q){
                if($q['TYPE']=='INT'){
                    array_push($arr,$q);
                }
            }
            break;
        case 'SIT':
            foreach($cont as $q){
                if($q['TYPE']=='SIT'){
                    array_push($arr,$q);
                }
            }
            break;
        /*=======================*/
        case 'MR':
            foreach($cont as $q){
                if($q['TYPE']=='MR'){
                    array_push($arr,$q);
                }
            }
            break;
        case 'FTL':
            foreach($cont as $q){
                if($q['TYPE']=='FTL'){
                    array_push($arr,$q);
                }
            }
            break;
        /*=======================*/
        default:
            return $cont;
    }
    return $arr;
}

function pickRandom($cont){
    $cnt=count($cont);
    $idx=random_int(0,$cnt-1);
    return $cont[$idx];
}
function print_c($cont){
    foreach($cont as $q){
        echo 'q['.$q['INDEX'].']: '.$q['TYPE'].'<br />';
    }
}

//===============================================================================
//===============================================================================
//                                DB
//===============================================================================
//===============================================================================
function validateServer(){
    // if this server is approved, this is OK!
    if(array_key_exists($_SERVER['SERVER_NAME'],$GLOBALS['CFG']["servers"])==false){
        echo json_encode(array("msg"=>"Sorry.  You don't have permissions.","status"=>"1"));
        die;
    };
}
function loadDB($db){
    return json_decode(file_get_contents($db),JSON_OBJECT_AS_ARRAY);
}
function load_data(){
    if(file_exists('data.json')==false){
        $J='{"users":[]}';
        file_put_contents("data.json",$J);
    }
    // load db
    $arr=json_decode(file_get_contents("data.json"),true);
    return $arr;
}
function save_data($arr){
    file_put_contents("data.json", json_encode($arr), LOCK_EX);
}

function registerUser(){
	$db=load_data();
	$U=$db["users"];
	
	// make sure there is the cookie has "email"
	if(array_key_exists("email",$_COOKIE)==false){
        $msg='why is there no email cookie?  $_COOKIE: '.print_r($_COOKIE,true);
        $J=array("msg"=>$msg,"status"=>1);
        echo json_encode($J);
        logVisitors($msg);
		die;
	}
	
	// confirm user isn't is database
	$found=false;
	foreach($U as $k => $v){
		if($v["email"]==$_COOKIE["email"]){
			$found=True;
			break;
		}
	}
	
	if($found){
        $msg="registration: found email (".$_COOKIE["email"]."); existing user '".$k."'.";
		//echo '{"msg":"found email","id":'.$k.'}';
        echo json_encode(array("msg"=>$msg,"id"=>$k));
        $_COOKIE["id"]=$k;
        logVisitors($msg);
		die;
	}
	else{
        // get next ID
        $uid=array_keys($db["users"]);
        if(count($uid)==0){
            $nextId=0;
        }
        else{
            $nextId=max($uid)+1;
        }
        

        // assign
        $msg="New email registration (".$_COOKIE["email"].").  Assigned user id=".$nextId.".";
		//echo '{"msg":"new email","id":'.$nextId.'}';
        echo json_encode(array("msg"=>$msg,"id"=>$nextId));
        $_COOKIE["id"]=$nextId;
		$db["users"][$nextId]=$_COOKIE;
        save_data($db);
        logVisitors($msg);
		die;
	}
}
function check_user(){
    // check that all expected cookies are populated
    //$req=array("firstName","lastName","displayName","email","district","id");
    $req=array("id");
    //$req=array("username","displayname","email","district");
    $msg="";$status=0;
    foreach($req as $k){
        if(array_key_exists($k,$_COOKIE)==false){
            $msg=$msg."No ".$k." cookie.  ";$status=1;
        }
    }
    if($status){
        echo json_encode(array("msg"=>$msg,"status"=>$status));
        logVisitors($msg);
        die;
    }
    else{
        //$nmtok=explode(" ",$_COOKIE["username"]);
        //if(count($nmtok)>1){
        //    $nm=substr($nmtok[0],0,1).' '.$nmtok[1];
       // }
        //else{
        //    $nm=$nmtok[0];
       // }
        //$nm=$_COOKIE["firstName"]." ".$_COOKIE["lastName"];
        //$msg=$msg."User:'".$nm."'.";
        $db=load_data();
        $U=$db["users"];
        $u=$U[$_COOKIE["id"]];

        //$msg=$msg."ID:".$_COOKIE["id"].".";
        $nm=$u["firstName"]." ".$u["lastName"];
        $msg=$msg.$nm."(ID:".$_COOKIE["id"].").";
        logVisitors($msg);
    }
}
function getuid(){
    if(array_key_exists("email",$_COOKIE)==false){
        $msg="no email in cookie:".print_r($_COOKIE,true);
        echo json_encode(array("msg"=>$msg,"status"=>1));
        logVisitors($msg);
        die;
    }

    $db=load_data();
    $U=$db["users"];
    foreach($U as $k => $v){
        if($v["email"]==$_COOKIE["email"]){
            echo json_encode(array("msg"=>"email:".$v["email"],"id"=>$k,"status"=>0));
            $msg="Legacy user: found email:".$v["email"]." with id=".$k;
            $_COOKIE["id"]=$k;
            logVisitors($msg);
            die;
        }
    }
    $msg="found email:".$v["email"]." but no id?!";
    echo json_encode(array("msg"=>$msg,"status"=>1));
    logVisitors($msg);
    die;
}
//===============================================================================
//===============================================================================
//                                MAIN
//===============================================================================
//===============================================================================

// inits
$CFG=json_decode(file_get_contents('cfg.json'),JSON_OBJECT_AS_ARRAY);
$msg="";

// validate this server is allowed.
validateServer();

// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
// REQUESTS THAT DON'T NEED THE DATABASE
// handle registration requests
if(array_key_exists("reg",$_GET)){
	registerUser();
}
if(array_key_exists("uid",$_GET)){
    getuid();
}

check_user();
//logVisitors();

// handle picture requests
if(array_key_exists("p",$_GET)){
    $L=glob("pics/".$_COOKIE["district"]."*.jpg");
    if(count($L)==0){
        echo json_encode(array("msg"=>"no pictures from ".$_COOKIE["district"]." yet."));
        die;
    }
    $idx=random_int(0,count($L)-1);
    echo json_encode(array("msg"=>"success!","img"=>$L[$idx]));
    //logVisitors("get image");
    die;
}

// logs
if(array_key_exists("log",$_GET)){
    header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Cache-Control: post-check=0, pre-check=0', FALSE);
    header('Pragma: no-cache');
    echo tailFile("logs.txt",$_GET["log"]);
    die;
}

// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
// QUESTIONS
if(array_key_exists("o",$_GET)){
    if($_GET["o"]=="MOVIES"){
        $cont=loadDB('movie_db.json');

        if(array_key_exists('qt',$_GET)){
            $cont=filterContent($cont,$_GET['qt']);
        }
    
        $q=pickRandom($cont);
        $j=json_encode($q);
        header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('Cache-Control: post-check=0, pre-check=0', FALSE);
        header('Pragma: no-cache');
        echo $j;
        die;
    }
    else{
        $msg="'other' database ".$_GET["o"]." not supported.";
        echo json_encode(array("msg"=>$msg,"status"=>1));
        die;
    }
}


// load questions
$db=loadDB('acts_db.json');

if(array_key_exists("i",$_GET)){
    // question by index
    $q=qByIndex((int)$_GET["i"]);
    header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Cache-Control: post-check=0, pre-check=0', FALSE);
    header('Pragma: no-cache');
    echo json_encode($q);
}
elseif(array_key_exists("rs",$_GET)&array_key_exists("re",$_GET)){
    //$bcvarr=array(array('Acts_1_1','Acts_1_5'));
    $bcvarr=array(array($_GET["rs"],$_GET["re"]));
    $cont=contentByIntervals($bcvarr);
    //$cont=filterContent($cont,$set="Local");

    // hardcode filter by Local
    $arr=array();
    foreach($cont as $q){
        if($q['SET']=="Local"){
            array_push($arr,$q);
        }
    }
    $cont=$arr;

    if(array_key_exists('qt',$_GET)){
        $cont=filterContent($cont,$_GET['qt']);
    }

    $q=pickRandom($cont);
    $j=json_encode($q);

    header('Expires: Sun, 01 Jan 2014 00:00:00 GMT');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Cache-Control: post-check=0, pre-check=0', FALSE);
    header('Pragma: no-cache');
    echo $j;
}

else{
    $cnt=count($db);
    echo $cnt.' questions <br />';
    
    // get question by index
    //var_dump(qByIndex(100));
    
    
    //echo md5($_SERVER['SERVER_NAME']);
    //echo 'validUser:'.validUser();
    //var_dump(validUser());
}

?>
