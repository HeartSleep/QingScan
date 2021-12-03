<?php
// 应用公共文件


$branch = empty(getenv("branch")) ? 'master' : getenv("branch");


$_SERVER['recordDing'] = "https://oapi.dingtalk.com/robot/send?access_token=dingdingtoken";
$_SERVER['environment'] = 'aliyun';
$_SERVER['branch'] = $branch;


function getRabbitMq()
{
    $rabitMq = $_SERVER['APMQ_CONFIG'];

    return $rabitMq;
}


function getSavePath($url, $tool = "xray", $id)
{
    $urlInfo = parse_url($url);

    $urlInfo['path'] = isset($urlInfo['path']) ? $urlInfo['path'] : "";

    $path = dirname(__DIR__) . "/runtime/temp/{$urlInfo['host']}/{$urlInfo['path']}_{$id}/{$tool}";
    $path = str_replace("//", "/", $path);
    if (!is_dir($path)) {
        mkdir($path, 0777, true);
    }

    $pathArr = [
        'path' => $path,
        'tool_result' => "{$path}/toolResult.json",
        'cmd_result' => "{$path}/cmdResult.json"
    ];


    return $pathArr;
}

function getMysql()
{


    return $_SERVER['DB_CONFIG'];
}


/**
 * Created by PhpStorm.
 * User: song
 * Date: 2018/9/11
 * Time: 下午2:30
 */


use app\model\BaseModel;
use think\facade\Db;
use think\facade\Log;


//spl_autoload_register('my_autoloader');
//function my_autoloader($className)
//{
//    $className = str_replace('\\', '/', $className);
//    require_once "./$className.php";
//}


function ActionIsExists($action)
{

    if (file_exists(__APP__ . "/action/{$action}Action.php")) {
        return true;
    }

    return false;
}

function getDirFileName($path): array
{
    $arr = array();
    $arr[] = $path;
    if (is_file($path)) {

    } else {
        if (is_dir($path)) {
            $data = scandir($path);
            if (!empty($data)) {
                foreach ($data as $value) {
                    if ($value != '.' && $value != '..') {
                        $sub_path = $path . "/" . $value;
                        $temp = getDirFileName($sub_path);
                        $arr = array_merge($temp, $arr);
                    }
                }

            }
        }
    }

    return $arr;
}

function getParam($key, $default = null)
{

    $paramAll = array_merge($_GET, $_POST);
    foreach ($paramAll as &$value) {
        if (is_string($value)) {
            $value = addslashes($value);
        }
    }
    return $paramAll[$key] ?? $default;
}

function getArrayField(array $data, array $fields)
{
    $result = [];
    foreach ($fields as $key) {
        if (isset($data[$key])) {
            $result[$key] = $data[$key];
        }
    }


    return $result;
}


/**
 * 写入日志
 * @param $content
 */
function addlog($content, $out = false)
{
    $content1 = $content;
    $content = ['app' => 'qing-scan-center', 'msg' => $content, 'time' => date('Y-m-d H:i:s')];
    $data = json_encode($content, JSON_UNESCAPED_UNICODE) . PHP_EOL;

    $date = date('Y-m-d');
//    file_put_contents("./logs/log{$date}.json", $data . PHP_EOL, FILE_APPEND);
    //Log::write($data . PHP_EOL);
    $dataArr = [
        'app' => 'qing-scan-center',
        'content' => is_array($content1) ? var_export($content1, true) : $content1,
    ];
    \think\facade\Db::name('log')->insert($dataArr);

    //删除5天前的日志
    $endTime = date('Y-m-d', time() - 86400 * 5);
    $list = Db::table('log')->whereTime('create_time', '<=', $endTime)->delete();

    if ($out or is_cli()) {
        echo $data;
    }
}

function getDirSize($dir)
{
    $handle = opendir($dir);
    while (false !== ($FolderOrFile = readdir($handle))) {
        if ($FolderOrFile != "." && $FolderOrFile != "..") {
            if (is_dir("$dir/$FolderOrFile")) {
                $sizeResult += getDirSize("$dir/$FolderOrFile");
            } else {
                $sizeResult += filesize("$dir/$FolderOrFile");
            }
        }
    }
    closedir($handle);
    return $sizeResult;
}

function getRealSize($size)
{
    $kb = 1024;   // Kilobyte
    $mb = 1024 * $kb; // Megabyte
    $gb = 1024 * $mb; // Gigabyte
    $tb = 1024 * $gb; // Terabyte
    if ($size < $kb) {
        return $size . " B";
    } else if ($size < $mb) {
        return round($size / $kb, 2) . " KB";
    } else if ($size < $gb) {
        return round($size / $mb, 2) . " MB";
    } else if ($size < $tb) {
        return round($size / $gb, 2) . " GB";
    } else {
        return round($size / $tb, 2) . " TB";
    }
}

function downCode($codePath, $prName, $codeUrl)
{
    if (!file_exists("{$codePath}/{$prName}")) {
        $cmd = "cd {$codePath}/ && git clone --depth=1 {$codeUrl}  $prName";
        systemLog($cmd);
    } else {
        $cmd = "cd {$codePath}/{$prName} && git pull ";
        systemLog($cmd);
    }
}

function cleanString($string)
{
    $string = preg_replace("/[^a-z0-9]/i", "", $string);

    return $string;
}

function addlogRaw($content)
{
    $date = date('Y-m-d');
    $time = date('Y-m-d H:i:s');
    $data = is_string($content) ? $content : var_export($content, true);
    $data .= "\n" . $time . "\n";

    file_put_contents("./logs/log{$date}_raw.txt", $data . PHP_EOL, FILE_APPEND);
}

//执行系统命令,并记录日志
function systemLog($shell)
{
    //转换成字符串
    $remark = "即将执行命令:{$shell}" . PHP_EOL;
    echo $remark;
    addlog($remark);
    //记录日志
    exec($shell, $output);

    if ($output) {
        echo implode("\n", $output) . PHP_EOL;
    }

    return $output;
}

function getAdderNameByIp($ip)
{
    $url = "http://freeapi.ipip.net/$ip";
    $data = json_decode(file_get_contents($url), true);

    if ($data[1] == '局域网') {
        return '局域网';
    }

    return $data[2];
}

/**
 * 统一返回接口数据
 *
 * @param array $data 数据内容
 * @param string $code 状态码
 * @param string $msg 提示信息
 */
function ajaxReturn($data = null, $code = 200, $msg = '操作成功')
{

    if ($data === null) {
        $data = new class {
        };
    }

    //json返回数据
    $result = json_encode(['code' => $code, 'data' => $data, 'msg' => $msg], JSON_UNESCAPED_UNICODE);


    exit($result);
}


//统一过滤
function I($name, $default = '')
{
    $value = $default;
    if (isset($_REQUEST[$name])) {
        $value = is_array($_REQUEST[$name]) ? $_REQUEST[$name] : addslashes($_REQUEST[$name]);
    }

    return $value;
}


function getClientIp()
{
    //strcasecmp 比较两个字符，不区分大小写。返回0，>0，<0。
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
    } elseif (!empty($_SERVER["REMOTE_ADDR"])) {
        $ip = $_SERVER["REMOTE_ADDR"];
    } elseif (is_cli()) {
        $ip = '127.0.0.1';
    } else {
        $ip = '8.8.8.8';
    }


    //正则校验IP地址
    $ip = preg_match('/[\d\.]{7,15}/', $ip, $matches) ? $matches [0] : '';

    return $ip;
}


//判断当前是否cli模式
function is_cli()
{
//    return preg_match("/cli/i", php_sapi_name()) ? true : false;
    return empty($_SERVER['HTTP_USER_AGENT']);
}

//判断字符串是否json格式
function is_not_json($str)
{
    return is_null(json_decode($str));
}


/**
 * 钉钉通知
 *
 * @param  $message
 * @param string $remote_server
 * @return array|mixed
 */
function dingdingNotice($message, $remote_server = '')
{

    $remote_server = !empty($remote_server) ? $remote_server : "https://oapi.dingtalk.com/robot/send?access_token=464cb2dd1f2d7e2b6520776837e81e421029a6b501a319568846751c5a745fd9";

    $data = array('msgtype' => 'text', 'text' => array('content' => $message));
    $post_string = json_encode($data);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $remote_server);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
}

/**
 * 通过curl获取数据
 * @param $url
 * @param null $isHearder
 * @param string $post
 * @param null $data
 * @param int $timeout
 * @return bool|string
 */
function http_request_code($url, $isHearder = null, $method = 'GET', $data = null, $timeout = 1)
{
    addlog(['开始进行CRUL请求', [$url, $isHearder, $method, $data, $timeout]], false);

    //初始化curl
    $ch = curl_init($url);

    //设置URL地址
    curl_setopt($ch, CURLOPT_URL, $url);

    //设置header信息
    if (!empty($isHearder)) {
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $isHearder);
    }
    //如果是post，则把data的数据传递过去
    if (($method == 'POST') && $data) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    }

    //如果是删除方法，则是以delete请求
    if ($method == 'DELETE') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
    }

    //设置超时时间，毫秒
    curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout * 100);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    //执行CURL时间
    $result = curl_exec($ch);

    //如果有异常，记录到日志当中
    $curl_errno = curl_errno($ch);
    if ($curl_errno > 0) {
        addlog(['CURL请求出错:', [curl_error($ch), $url, $isHearder, $method, $data, $timeout]], false);
    }

    //关闭URL，返回数据
    curl_close($ch);
    return $result;
}


function getGaoDeCity($ip)
{

    $url = 'http://restapi.amap.com/v3/ip?key=c0721f7b74d3a648ed1e09aea8e08477&ip=' . $ip;

    $result = BaseModel::curlExec($url, 'GET', $url);
    $result_arr = json_decode($result, true);

    $data['lng_log'] = substr($result_arr->rectangle, 0, strpos($result_arr->rectangle, ';') - 1);
    $data['province'] = $result_arr->province;
    $data['city'] = substr($result_arr->city, 0, -3);
    $data['detail'] = $result;
    $data['client_ip'] = $ip;
    $data['isp'] = getIspByIp($ip);

    return $data;
}

function getBaiduCity($ip)
{
    $cacheName = "./tmp/{$ip}.json";
    if (file_exists($cacheName)) {
        return json_decode(file_get_contents($cacheName), true);
    }

    $url = "http://api.map.baidu.com/location/ip?ip={$ip}&ak=Ik6CvCEkr44Qx0qFoxnVHFAR7R4Uaiza&coor=bd09ll";

    $result = BaseModel::curlExec($url, 'GET', $url);
    $result_arr = json_decode($result, true)['content'];

    $data['lng_log'] = "{$result_arr['point']['x']},{$result_arr['point']['y']}";
    $data['province'] = is_string($result_arr['address']) ? $result_arr['address'] : '';
    $data['city'] = $result_arr['address_detail']['city'];
    $data['detail'] = $result;
    $data['client_ip'] = $ip;
    $data['isp'] = getIspByIp($ip);

    file_put_contents($cacheName, json_encode($data));

    return $data;
}

function getIspByIp($ip)
{
    $url = "http://ip.taobao.com//service/getIpInfo.php?ip={$ip}";

    $result = BaseModel::curlExec($url, 'GET', $url);
    $result_arr = json_decode($result, true)['data'];

    return $result_arr['isp'] ?? '未知';
}

function getFileList($dir, $extName, &$fileList)
{
    $dir = rtrim($dir, '/');

    $files = [];
    if (@$handle = opendir($dir)) {
        while (($file = readdir($handle)) !== false) {
            if ($file != ".." && $file != ".") {
                if (is_dir($dir . "/" . $file)) { //如果是子文件夹，进行递归
                    $files[$file] = getFileList($dir . "/" . $file, $extName, $fileList);
                } else {
                    if (substr(strrchr($file, '.'), 1) == $extName) {
                        $fileList[] = rtrim($dir, '/') . '/' . $file;
                    }
                }
            }
        }
        closedir($handle);
    }
    return $fileList;
}

function trimName($str)
{
    $str = str_replace(" ", "___", $str);
    $pattern = '/[a-zA-Z0-9_\-]/u';
    preg_match_all($pattern, $str, $result);
    $temp = join('', $result[0]);

    return $temp;
}

function get_token($url)
{
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_USERPWD, "admin:wmmszg");
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

    curl_setopt($curl, CURLOPT_POST, 1);

    $response = curl_exec($curl);
    $err = curl_error($curl);

    curl_close($curl);
    unset($curl);

    if ($err) {
        return "cURL Error #:" . $err;
    } else {
        $token = json_decode($response, true);
        return $token;
    }

}


function curl_get_header($url)
{
    $oCurl = curl_init();
    // 设置请求头, 有时候需要,有时候不用,看请求网址是否有对应的要求
    $header[] = "Content-type: application/x-www-form-urlencoded";
    $user_agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.101 Safari/537.36 Edg/91.0.864.48";
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_HTTPHEADER, $header);
    // 返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
    curl_setopt($oCurl, CURLOPT_HEADER, true);
    // 是否不需要响应的正文,为了节省带宽及时间,在只需要响应头的情况下可以不要正文
    curl_setopt($oCurl, CURLOPT_NOBODY, true);
    // 使用上面定义的 ua
    curl_setopt($oCurl, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($oCurl, CURLOPT_TIMEOUT, 1);
    // 不用 POST 方式请求, 意思就是通过 GET 请求
    curl_setopt($oCurl, CURLOPT_POST, false);

    $sContent = curl_exec($oCurl);
    // 获得响应结果里的：头大小
    $headerSize = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
    // 根据头大小去获取头信息内容
    $header = substr($sContent, 0, $headerSize);

    curl_close($oCurl);

    $header = explode(PHP_EOL, $header);
    $header = array_filter(array_map('trim', $header));
    return $header;
}

function U($path, $params = false)
{
    $baseUrl = "/index.php?s=";

    $baseUrl .= $path;

    if (!empty($params)) {
        $baseUrl .= "&";
        if (is_string($params)) {
            $baseUrl .= $params;
        } elseif (is_array($params)) {
            $baseUrl .= http_build_query($params);
        }
    }


    return $baseUrl;
}


function syntax_highlight($code)
{
    // this matches --> "foobar" <--
    $code = preg_replace(
        '/"(.*?)"/U',
        '"<span style="color: #007F00">$1</span>"', $code
    );
    // hightlight functions and other structures like --> function foobar() <---
    $code = preg_replace(
        '/(\s)\b(.*?)((\b|\s)\()/U',
        '$1<span style="color: #aa0">$2</span>$3',
        $code
    );
    // Match comments (like /* */):
    $code = preg_replace(
        '/(\/\/)(.+)\s/',
        '<span style="color: #777; "> $0 </span>',
        $code
    );
    $code = preg_replace(
        '/(\/\*.*?\*\/)/s',
        '<span style="color: #777;  "> $0 </span>',
        $code
    );
    // hightlight braces:
    $code = preg_replace('/(\(|\[|\{|\}|\]|\)|\->)/', '<strong>$1</strong>', $code);
    // hightlight variables $foobar
    $code = preg_replace(
        '/(\$[a-zA-Z0-9_]+)/', '<span style="color: #ff5500">$1</span>', $code
    );
    /* The \b in the pattern indicates a word boundary, so only the distinct
    ** word "web" is matched, and not a word partial like "webbing" or "cobweb"
    */
    // special words and functions
    $code = preg_replace(
        '/\b(print|echo|new|function)\b/',
        '<span style="color: #cde">$1</span>', $code
    );
    return $code;
}

/**
 * 系统非常规MD5加密方法
 * @param string $str 要加密的字符串
 * @return string
 */
function ucenter_md5($str, $key = 'lyj0p2wtexiax32ijn23pantnyzdayu32hui3dlayuan1325zh3oonlg2xin7')
{
    return '' === $str ? '' : md5(md5(sha1($str) . $key) . '###xt');
}

/**
 * 系统解密方法
 * @param string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
 * @param string $key 加密密钥
 * @return string
 */
function think_decrypt($data, $key = '')
{
    $key = md5(empty($key) ? config('app.UC_AUTH_KEY') : $key);
    $data = str_replace(array('-', '_'), array('+', '/'), $data);
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $data = base64_decode($data);
    $expire = substr($data, 0, 10);
    $data = substr($data, 10);

    if ($expire > 0 && $expire < time()) {
        return '';
    }
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = $str = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        } else {
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return base64_decode($str);
}

/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key 加密密钥
 * @param int $expire 过期时间 单位 秒
 * @return string
 */
function think_encrypt($data, $key = '', $expire = 0)
{
    $key = md5(empty($key) ? config('app.UC_AUTH_KEY') : $key);
    $data = base64_encode($data);
    $x = 0;
    $len = strlen($data);
    $l = strlen($key);
    $char = '';

    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }

    $str = sprintf('%010d', $expire ? $expire + time() : 0);

    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1))) % 256);
    }
    return str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($str));
}


function curl_get_url_head($url)
{
    $oCurl = curl_init();
    // 设置请求头, 有时候需要,有时候不用,看请求网址是否有对应的要求
    $header[] = "Content-type: application/x-www-form-urlencoded";
    $user_agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.101 Safari/537.36 Edg/91.0.864.48";
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_HTTPHEADER, $header);
    // 返回 response_header, 该选项非常重要,如果不为 true, 只会获得响应的正文
    curl_setopt($oCurl, CURLOPT_HEADER, true);
    // 是否不需要响应的正文,为了节省带宽及时间,在只需要响应头的情况下可以不要正文
    curl_setopt($oCurl, CURLOPT_NOBODY, true);
    // 使用上面定义的 ua
    curl_setopt($oCurl, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($oCurl, CURLOPT_TIMEOUT, 1);
    // 不用 POST 方式请求, 意思就是通过 GET 请求
    curl_setopt($oCurl, CURLOPT_POST, false);

    $sContent = curl_exec($oCurl);

    // 获得响应结果里的：头大小
    $headerSize = curl_getinfo($oCurl, CURLINFO_HEADER_SIZE);
    $codeSize = curl_getinfo($oCurl, CURLINFO_HTTP_CODE);
    // 根据头大小去获取头信息内容
    $header = substr($sContent, 0, $headerSize);

    curl_close($oCurl);

    $header = explode(PHP_EOL, $header);
    $header = array_filter(array_map('trim', $header));
    if (!$header || !$codeSize) {
        return false;
    }
    return ['header' => $header, 'code' => $codeSize, 'content' => $sContent];
}


function curl_get($url)
{
    $header = array(
        'Accept: application/json',
    );
    $curl = curl_init();
    //设置抓取的url
    curl_setopt($curl, CURLOPT_URL, $url);
    //设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_HEADER, 0);
    // 超时设置,以秒为单位
    curl_setopt($curl, CURLOPT_TIMEOUT, 1);

    // 超时设置，以毫秒为单位
    // curl_setopt($curl, CURLOPT_TIMEOUT_MS, 500);

    // 设置请求头
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    //设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
    //执行命令
    $data = curl_exec($curl);

    curl_close($curl);

    return $data;
}

/**
 * get_ip_lookup  获取ip地址所在的区域及运营商
 * @param null $ip
 * @return bool|mixed
 */
function get_ip_lookup($ip)
{
    $result = file_get_contents("https://ip.taobao.com/outGetIpInfo?ip={$ip}&accessKey=alibaba-inc");
    return json_decode($result, true);
}

function assoc_getcsv($csv_path)
{
    $csv = array_map('str_getcsv', file($csv_path));
    $list = [];
    foreach ($csv as $k => $v) {
        if ($k) {
            $list[] = array_combine($csv[0], $v);
        }
    }
    return $list;
}

function in_array_strpos($word, $array)
{
    foreach ($array as $v) {
        if (strpos($word, $v) !== false) {
            return true;
        }
    }
    return false;
}

function xmlToArray($url)
{
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $xml = file_get_contents($url);
    $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
    return json_decode(json_encode($xmlstring), true);
}

function getPrimaryDomainName($url)
{
    $arr = parse_url($url);
    if (isset($arr['path'])) {
        $url = $arr['path'];
    } else {
        $url = $arr['host'];
    }
    $path = \think\facade\App::getRootPath() . 'vendor/jeremykendall/php-domain-parser/test_data/public_suffix_list.dat';
    $publicSuffixList = Rules::fromPath($path);
    $domain = Domain::fromIDNA2008($url);

    $result = $publicSuffixList->resolve($domain);
    return $result->registrableDomain()->toString();
}

function array_is_map($arr)
{
    return array_keys($arr) !== range(0, count($arr) - 1);
}

function getToken($userid)
{
    return md5(uniqid(mt_rand(), true) . $userid);
}

function testAgent($ip, $port)
{
    $url = 'https://www.baidu.com';
    $oCurl = curl_init();
    // 设置请求头, 有时候需要,有时候不用,看请求网址是否有对应的要求
    $header[] = "Content-type: application/x-www-form-urlencoded";
    $user_agent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.101 Safari/537.36 Edg/91.0.864.48";
    curl_setopt($oCurl, CURLOPT_URL, $url);
    curl_setopt($oCurl, CURLOPT_HTTPHEADER, $header);
    // 是否不需要响应的正文,为了节省带宽及时间,在只需要响应头的情况下可以不要正文
    curl_setopt($oCurl, CURLOPT_NOBODY, true);
    // 使用上面定义的 ua
    curl_setopt($oCurl, CURLOPT_USERAGENT, $user_agent);
    // 不用 POST 方式请求, 意思就是通过 GET 请求
    curl_setopt($oCurl, CURLOPT_POST, false);

    curl_setopt($oCurl, CURLOPT_PROXY, $ip);
    curl_setopt($oCurl, CURLOPT_PROXYPORT, $port);
    curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, false); //不验证证书

    curl_exec($oCurl);

    $codeSize = curl_getinfo($oCurl, CURLINFO_HTTP_CODE);

    curl_close($oCurl);

    return $codeSize;
}


/*
 * 随机拆分
 * @param $total_num int 总数
 * @param $total_copies int 总份数
 * @return string/array
 * */
function random_split($total_num, $total_copies)
{
    $result = []; //结果
    for ($i = $total_copies; $i > 0; $i--) {
        $ls_num = 0;
        $num = 0;
        if ($total_num > 0) {
            if ($i == 1) {
                $num += $total_num;
            } else {
                $max_num = floor($total_num / $i);
                $ls_num = mt_rand(1, $max_num);
                $num += $ls_num;
            }
        }
        $result[] = $num;
        $total_num -= $ls_num;
    }
    return $result;
}


/**
 * 循环获取目录以及所有子目录中的所有文件，结果是一个二维数组
 * @param $dir
 * @return array
 */

function getFilePath($dir, $filename, $level = 1)
{
    static $files = [];
    if (!is_dir($dir)) {
        return $files;
    }
    if ($level > 3) {
        return $files;
    }

    foreach (scandir($dir) as &$file_name) {
        if ($file_name == '.' || $file_name == '..' || (file_exists($file_name) && $file_name != $filename)) {
            continue;
        }
        if ($file_name == $filename) {
            $files[] = [
                'filepath' => $dir,
                'filename' => $file_name,
                'file' => $dir . "/{$filename}"
            ];
        }
        if (is_dir($dir . DIRECTORY_SEPARATOR . $file_name)) {
            getFilePath($dir . DIRECTORY_SEPARATOR . $file_name, $filename, $level + 1);
        }
    }
    return $files;
}

// 大写字母转"_"下划线
function cc_format($name)
{
    $name = lcfirst($name);
    $temp_array = array();
    for ($i = 0; $i < strlen($name); $i++) {
        $ascii_code = ord($name[$i]);
        if ($ascii_code >= 65 && $ascii_code <= 90) {
            if ($i == 0) {
                $temp_array[] = chr($ascii_code + 32);
            } else {
                $temp_array[] = '_' . chr($ascii_code + 32);
            }
        } else {
            $temp_array[] = $name[$i];
        }
    }
    return implode('', $temp_array);
}

// 随机生成端口
function rangeCrearePort()
{
    //计算机端口 - 1.系统保留端口(从0到1023) -2.动态端口(从1024到65535)
    //直接生成一个数组
    $ports_arr = range(1026, 65535);
    //受保护的端口,不能再生成使用
    $privite_arr = array(1158, 1433, 1434, 1521, 2082, 2083, 2100, 2222, 2601, 2604, 3128, 3306, 3312, 3311, 3389, 4440, 5432, 5900, 6379, 8080, 8081, 8089, 8888, 9090, 9200, 9300, 11211, 27017, 27018, 28017, 50070, 50030);
    //可用的端口数组
    $enable_arr = array_diff($ports_arr, $privite_arr);
    //在可用的中间随机生成一个
    $port_key = array_rand($enable_arr);
    return $port_key;
}

function whatwebArr($json)
{
    $arr = json_decode($json, true);
    $data = [];
    foreach ($arr as $v) {
        if ($v) {
            foreach ($v as $key => $val) {
                if ($val) {
                    foreach ($val as $value) {
                        $data[$key] = $value[0];
                    }
                }
            }
        }
    }
    return $data;
}


function getCurrentMilis() {
    $mill_time = microtime();
    $timeInfo = explode(' ', $mill_time);
    $milis_time = sprintf('%d%03d',$timeInfo[1],$timeInfo[0] * 1000);
    return $milis_time;
}