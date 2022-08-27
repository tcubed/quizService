<?php
date_default_timezone_set("America/Chicago");
function getUserRecord(){
    $db=load_data();
    foreach($db["users"] as $u){
        if($u["email"]==$_COOKIE["email"]){return $u;}
    }
}
function getUserLoc_old($ip){
    $url='http://api.ipstack.com/'.$ip.'?access_key=8a33354d0397b348fde8c3bc27a4297c';
    $json = file_get_contents($url);
    echo $json;
}
function getUserLoc($ip){
    // set IP address and API access key
    //$ip = '134.201.250.155';
    $access_key = '8a33354d0397b348fde8c3bc27a4297c';

    // Initialize CURL:
    $ch = curl_init('http://api.ipstack.com/'.$ip.'?access_key='.$access_key.'');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Store the data:
    $json = curl_exec($ch);
    curl_close($ch);

    // Decode JSON response:
    $api_result = json_decode($json, true);

    // Output the "capital" object inside "location"
    echo $json;
    echo $api_result['location']['capital'];
}

function logVisitors($msg=""){
    $fnlog='logs.txt';
    $now=new DateTime();
    //$today=$now->format('Y-m-d H:i:s');
    $today=$now->format('Y-m-d H:i');
    $ip = $_SERVER["REMOTE_ADDR"];
    $u=getUserRecord();

    $logmsg=$today.','.$u["id"].','.$ip;
    if($msg!=""){
        $logmsg=$logmsg.','.$msg;
    }

    // get all user id
    /*
    $db=load_data();
    $ui=array();
    foreach($db["users"] as $u1){
        array_push($ui,$u1["id"]);
    }
    */

    // check for 
    if(file_exists($fnlog)){
        $arr=explode(PHP_EOL,file_get_contents($fnlog));
        foreach($arr as $L){
            if($L==$logmsg){return;}
        }
    }
    file_put_contents($fnlog, $logmsg.PHP_EOL , FILE_APPEND | LOCK_EX);


    /*
    //$serverTimestamp = (new DateTime())->getTimestamp();
    //echo $serverTimestamp->format('U = Y-m-d H:i:s') . "\n";
    
    $ip = $_SERVER["REMOTE_ADDR"];
    $quizid=$_GET["id"];
    $todayVizStr=$today.','.$ip.','.$quizid.','.$msg;
    
    $fileday=sys_get_temp_dir().'/quizDay'.$today.'.csv';
    //unlink($fileday);

    //echo '<br>fileday: '.$fileday.'<br>';
    if(file_exists($fileday)){
        $arr=explode(PHP_EOL,file_get_contents($fileday));
        //echo 'todayfile<br>';
        //var_dump($arr);
        if(in_array($todayVizStr,$arr)){
            //echo "<br>found ".$todayVizStr.'<br>';
            return;
        }
        else{
            //echo '<br> could not find '.$todayVizStr.'<br>';
        }
    }
    //echo '<br>lets log!<br>';
    file_put_contents($fileday, $todayVizStr.PHP_EOL , FILE_APPEND | LOCK_EX);
    file_put_contents('visitors.txt', $todayVizStr.PHP_EOL , FILE_APPEND | LOCK_EX);
    */
}
function loadDB(){
    return json_decode(file_get_contents('acts_db.json'),JSON_OBJECT_AS_ARRAY);
}
function validServer(){
    // if this server is a valid user, this is OK!
    return array_key_exists($_SERVER['SERVER_NAME'],$GLOBALS['CFG']["servers"]);
}
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
        default:
            return $cont;
    }
    return $arr;
}
function pickRandom($cont){
    $cnt=count($cont);
    $idx=random_int(0,$cnt);
    return $cont[$idx];
}
function print_c($cont){
    foreach($cont as $q){
        echo 'q['.$q['INDEX'].']: '.$q['TYPE'].'<br />';
    }
}

// =============================================
//                    DB
function load_data(){
    if(file_exists('data.json')==false){
        $J='{"users":[]}';
        file_put_contents("data.json",$J);
    }
    // load db
    $arr=json_decode(file_get_contents("data.json"),true);
    //echo "arr<br />";
    //var_dump($arr);
    return $arr;
}
function save_data($arr){
    file_put_contents("data.json", json_encode($arr), LOCK_EX);
}
function check_user(){
    $db=load_data();
    //var_dump($db);
    $foundUser=false;
    foreach($db["users"] as $u){
        if($u["email"]==$_COOKIE["email"]){
            // valid user
            $foundUser=true;
            break;
        }
    }
    
    if($foundUser==false){
        // find last id
        $lastid=-1;
        foreach($db["users"] as $u){
            if($u["id"]>$lastid){$lastid=$u["id"];}
        }
        // create new user entry
        $u=array();
        foreach(array_keys($_COOKIE) as $key){
            $u[$key]=$_COOKIE[$key];
        }
        $u["id"]=$lastid+1;
        array_push($db["users"],$u);
        save_data($db);
    }
}




// =============================================
// load questions and users
$db=loadDB();
$CFG=json_decode(file_get_contents('cfg.json'),JSON_OBJECT_AS_ARRAY);

if(validServer()==false){
    echo "sorry.  you don't have permissions.";
    die;
}



//var_dump($_COOKIE);
//$data=load_data();
//var_dump($data);

/*
$unm=$_COOKIE["email"];
$unm=str_replace("@","_",$unm);
$unm=str_replace(".","",$unm);
$unm = filter_var($unm, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH);
echo "username: ".$unm."<br />";
*/
check_user();

$ip=$_SERVER["REMOTE_ADDR"];
getUserLoc($ip);

logVisitors();

/*
$foundUser=false;
foreach($db["users"] as $u){
    if($u["email"]==$_COOKIE["email"]{
        // valid user
        $foundUser=true;
        break;
    })
}

if($foundUser==false){
    // find last id
    $lastid=-1;
    foreach($db["users"] as $u){
        if($u["id"]>$lastid){$lastid=$u["id"];}
    }

    $u=array();
    foreach(array_keys($_COOKIE) as $key){
        $u[$key]=$_COOKIE[$key];
    }
    $u["id"]=$lastid+1;
    array_push($db["users"],$u);
    save_db($db);
}
*/


if(array_key_exists("i",$_GET)){
    $q=qByIndex((int)$_GET["i"]);
    echo json_encode($q);
}
elseif(array_key_exists("rs",$_GET)&array_key_exists("re",$_GET)){
    //$bcvarr=array(array('Acts_1_1','Acts_1_5'));
    $bcvarr=array(array($_GET["rs"],$_GET["re"]));
    $cont=contentByIntervals($bcvarr);
    //$cont=filterContent($cont,$set="Local");

    // set
    $arr=array();
    foreach($cont as $q){
        if($q['SET']=="Local"){
            array_push($arr,$q);
        }
    }
    //$cont= $arr->getArrayCopy();
    $cont=$arr;

    if(array_key_exists('qt',$_GET)){
        $cont=filterContent($cont,$_GET['qt']);
    }
    $q=pickRandom($cont);
    $j=json_encode($q);
    echo $j;
}
elseif(array_key_exists("p",$_GET)){
    $L=glob("pics/".$_COOKIE["district"]."*.jpg");
    $idx=random_int(0,count($L)-1);
    echo $L[$idx];
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

/*
$bcvarr=array(array('Acts_1_1','Acts_1_5'));
$cont=contentByIntervals($bcvarr);
echo 'content: '.count($cont).' elements. <br />';

$cont=filterContent($cont,'INT');
echo 'content: '.count($cont).' elements. <br />';
print_c($cont);
//var_dump($d);
echo 'random question<br />';
$q=pickRandom($cont);
var_dump($q);
/*

/*
// get quizid
if(array_key_Exists("id",$_GET)){
    $quizid=$_GET["id"];
}
else{
    $quizid=$_POST["id"];
}
$quizid = preg_replace("/[^A-Za-z0-9]/", '', $quizid);

// quizfile
$quizfile=sys_get_temp_dir().'/quiz'.$quizid.'.csv';

// stats file

//$quizpubfile=sys_get_temp_dir().'/quiz'.$quizid.'pub.csv';

// jump requests
if(array_key_exists("q",$_GET)){
    pushJump($quizfile);
}
if(array_key_exists("p",$_GET)){
    // /poll request
    if(file_exists($quizfile)){
        $arr=getJump($quizfile);
    }
    else{
        echo '{"jump":"none"}';
    }
    logVisitors('get jumps');
}
if(array_key_exists("stats",$_GET)){
    // record stats
    logStats();
    logVisitors('stats');
}

//
// quiz master
//
if(array_key_exists("r",$_GET)){
    // reset quizid file (jumps) or signal a jump
    quizmasterReset($quizfile);
}
if(array_key_exists("qmstamp",$_GET)){
    // set or poll the quizmaster timestamp
    quizMasterTimeStamp($quizid);
}

//
// scorekeeper
//
if(array_key_exists("quizData",$_POST)){
    setQuizData($quizid,$_POST["quizData"]);
}
if(array_key_exists("getScore",$_POST)){
    $quizid = preg_replace("/[^A-Za-z0-9]/", '', $_POST['getScore']);
    getQuizData($quizid);
}

*/
?>
