<?php
/**
 * PHP Cloudflare Proxy Checker（API专用纯后端版，无HTML）
 * 检查IP是否为CF官方节点，CF官方IP直接返回不可用
 * 2024.6
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// -------- Cloudflare 官方 IP段 --------
$cf_ip_ranges = [
    // IPv4
    '173.245.48.0/20','103.21.244.0/22','103.22.200.0/22','103.31.4.0/22',
    '141.101.64.0/18','108.162.192.0/18','190.93.240.0/20','188.114.96.0/20',
    '197.234.240.0/22','198.41.128.0/17','162.158.0.0/15','104.16.0.0/13',
    '104.24.0.0/14','172.64.0.0/13','131.0.72.0/22',
    // IPv6
    '2400:cb00::/32','2606:4700::/32','2803:f800::/32','2405:b500::/32',
    '2405:8100::/32','2a06:98c0::/29','2c0f:f248::/32'
];

// --------- 路由分发 ----------
$request_uri = $_SERVER['REQUEST_URI'];
$url_parts = parse_url($request_uri);
$path = strtolower($url_parts['path']);
$path_param = isset($_GET['path']) ? strtolower($_GET['path']) : '';

if ($path === '/check' || $path === '/index.php/check' || $path_param === 'check') {
    handleCheckRequest();
} elseif ($path === '/resolve' || $path === '/index.php/resolve' || $path_param === 'resolve') {
    handleResolveRequest();
} elseif ($path === '/ip-info' || $path === '/index.php/ip-info' || $path_param === 'ip-info') {
    handleIPInfoRequest();
} elseif ($path === '/favicon.ico') {
    header('HTTP/1.1 404 Not Found'); exit;



} else {
    // 默认：输出页面
    include 'proxyip.php';
    exit;
}

// ======== 业务实现 ========

function handleCheckRequest() {
    global $cf_ip_ranges;
    header('Content-Type: application/json; charset=UTF-8');
    $proxyIP = isset($_GET['proxyip']) ? trim(strtolower($_GET['proxyip'])) : '';
    if (!$proxyIP) sendJson(['success' => false, 'error' => 'Missing proxyip parameter'], 400);

    // 端口解析
    $portRemote = 443;
    if (strpos($proxyIP, '.tp') !== false && preg_match('/\.tp(\d+)\./', $proxyIP, $m)) {
        $portRemote = intval($m[1]);
    } elseif (strpos($proxyIP, '[') !== false && strpos($proxyIP, ']:') !== false) {
        $arr = explode(']:', $proxyIP);
        $portRemote = intval($arr[1]);
        $proxyIP = $arr[0] . ']';
    } elseif (substr_count($proxyIP, ':') == 1 && strpos($proxyIP, ']') === false) {
        list($ip1, $port1) = explode(':', $proxyIP, 2);
        if (is_numeric($port1)) { $proxyIP = $ip1; $portRemote = intval($port1); }
    }

    // 检查是否CF官方IP段
    $ip_for_check = $proxyIP;
    if (strpos($proxyIP, '[') === 0 && strpos($proxyIP, ']') !== false) {
        $ip_for_check = trim($proxyIP, '[]');
    }
    foreach ($cf_ip_ranges as $cidr) {
        if (isIPInRange($ip_for_check, $cidr)) {
            sendJson([
                'success' => false,
                'proxyIP' => $proxyIP,
                'portRemote' => $portRemote,
                'statusCode' => null,
                'responseSize' => 0,
                'responseData' => '',
                'timestamp' => date('c'),
                'error' => '该IP属于Cloudflare官方节点，不可用作代理'
            ], 200);
        }
    }

    // 非CF官方段，做连接检测
    $result = checkProxyIP($proxyIP, $portRemote);
    sendJson($result, $result['success'] ? 200 : 502);
}

function handleResolveRequest() {
    header('Content-Type: application/json; charset=UTF-8');
    $domain = isset($_GET['domain']) ? $_GET['domain'] : '';
    if (!$domain) sendJson(['success' => false, 'error' => 'Missing domain parameter'], 400);
    try {
        $ips = resolveDomain($domain);
        sendJson(['success' => true, 'domain' => $domain, 'ips' => $ips]);
    } catch (Exception $e) {
        sendJson(['success' => false, 'error' => $e->getMessage()], 500);
    }
}

function handleIPInfoRequest() {
    header('Content-Type: application/json; charset=UTF-8');
    $ip = isset($_GET['ip']) ? $_GET['ip'] : ($_SERVER['REMOTE_ADDR'] ?? '');
    if (!$ip) sendJson([
        'status' => 'error', 'message' => 'IP参数未提供',
        'code' => 'MISSING_PARAMETER', 'timestamp' => date('c')
    ], 400);
    $ip = str_replace(['[', ']'], '', $ip);
    $resp = @file_get_contents("http://ip-api.com/json/" . urlencode($ip) . "?lang=zh-CN");
    if (!$resp) {
        sendJson([
            'status' => 'error', 'message' => 'IP查询失败', 'code' => 'API_REQUEST_FAILED',
            'query' => $ip, 'timestamp' => date('c')
        ], 500);
    }
    $data = json_decode($resp, true);
    $data['timestamp'] = date('c');
    sendJson($data);
}

function checkProxyIP($proxyIP, $portRemote = 443) {
    $timeout = 2;
    $host = (filter_var($proxyIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) ? "[$proxyIP]" : $proxyIP;
    $json = [
        'success' => false, 'proxyIP' => $proxyIP, 'portRemote' => $portRemote,
        'statusCode' => null, 'responseSize' => 0, 'responseData' => '',
        'timestamp' => date('c')
    ];
    $fp = @stream_socket_client(
        "tcp://$host:$portRemote",
        $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT
    );
    if (!$fp) {
        $json['error'] = "连接失败: $errstr ($errno)";
        return $json;
    }
    stream_set_timeout($fp, $timeout);
    $httpRequest =
        "GET /cdn-cgi/trace HTTP/1.1\r\n" .
        "Host: speed.cloudflare.com\r\n" .
        "User-Agent: CheckProxyIP/PHP\r\n" .
        "Connection: close\r\n\r\n";
    fwrite($fp, $httpRequest);
    $response = '';
    while (!feof($fp)) {
        $response .= fread($fp, 4096);
        $info = stream_get_meta_data($fp);
        if ($info['timed_out']) { $json['error'] = '响应超时'; break; }
    }
    fclose($fp);
    $json['responseData'] = $response;
    $json['responseSize'] = strlen($response);
    if (preg_match('/^HTTP\/\d+\.\d+\s+(\d+)/im', $response, $matches)) {
        $json['statusCode'] = intval($matches[1]);
    }
    $looksLikeCloudflare = stripos($response, "cloudflare") !== false;
    $isExpectedError = stripos($response, "plain HTTP request") !== false
        || stripos($response, "400 Bad Request") !== false;
    $hasBody = strlen($response) > 100;
    if ($json["statusCode"] && $looksLikeCloudflare && $isExpectedError && $hasBody) {
        $json["success"] = true;
    }
    return $json;
}

function resolveDomain($domain) {
    $domain = preg_replace('/:\d+$/', '', $domain);
    $ips = [];
    $a = dns_get_record($domain, DNS_A);
    $aaaa = dns_get_record($domain, DNS_AAAA);
    foreach($a as $rec) if (!empty($rec['ip'])) $ips[] = $rec['ip'];
    foreach($aaaa as $rec) if (!empty($rec['ipv6'])) $ips[] = "[".$rec['ipv6']."]";
    if (!$ips) throw new Exception('No A/AAAA records found');
    return $ips;
}

function isIPInRange($ip, $cidr) {
    if (strpos($cidr, '/') === false) return $ip === $cidr;
    list($subnet, $mask) = explode('/', $cidr);
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $ip_long = ip2long($ip);
        $subnet_long = ip2long($subnet);
        $mask_long = ~((1 << (32 - $mask)) - 1);
        return ($ip_long & $mask_long) === ($subnet_long & $mask_long);
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        $ip_bin = inet_pton($ip);
        $subnet_bin = inet_pton($subnet);
        $ip_bits = unpack('H*', $ip_bin)[1];
        $subnet_bits = unpack('H*', $subnet_bin)[1];
        $mask_len = intval($mask);
        $full_bytes = intval($mask_len / 8);
        $remaining_bits = $mask_len % 8;
        if (substr($ip_bits, 0, $full_bytes * 2) !== substr($subnet_bits, 0, $full_bytes * 2)) {
            return false;
        }
        if ($remaining_bits === 0) return true;
        $ip_byte = hexdec(substr($ip_bits, $full_bytes * 2, 2));
        $subnet_byte = hexdec(substr($subnet_bits, $full_bytes * 2, 2));
        $mask = 0xFF << (8 - $remaining_bits) & 0xFF;
        return ($ip_byte & $mask) === ($subnet_byte & $mask);
    }
    return false;
}

function sendJson($data, $code = 200) {
    http_response_code($code);
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); exit;
}
