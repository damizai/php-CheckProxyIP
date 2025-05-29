<?php
/**
 * CF Proxy IP Checker
 * PHP version of the Cloudflare Worker proxy checker
 * Uses CFphptoken instead of Cloudflare Workers API
 */

// Set error reporting and headers
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Access-Control-Allow-Origin: *');
header('Content-Type: text/html; charset=UTF-8');

// Configuration
$网站图标 = 'https://cf-assets.www.cloudflare.com/dzlvafdwdttg/19kSkLSfWtDcspvQI5pit4/c5630cf25d589a0de91978ca29486259/performance-acceleration-bolt.svg';
$cfphptoken = ''; // Replace with your actual CFphptoken

// Get the request URI and parse it
$request_uri = $_SERVER['REQUEST_URI'];
$url_parts = parse_url($request_uri);
$path = strtolower($url_parts['path']);
$hostname = $_SERVER['HTTP_HOST'];

// 检查是否有path参数
$path_param = isset($_GET['path']) ? strtolower($_GET['path']) : '';

// Handle different paths
if ($path === '/check' || $path === '/index.php/check' || $path_param === 'check') {
    handleCheckRequest();
} elseif ($path === '/resolve' || $path === '/index.php/resolve' || $path_param === 'resolve') {
    handleResolveRequest();
} elseif ($path === '/ip-info' || $path === '/index.php/ip-info' || $path_param === 'ip-info') {
    handleIPInfoRequest();
} elseif ($path === '/favicon.ico') {
    header('Location: ' . $网站图标, true, 302);
    exit;
} else {
    // Default: Show HTML page
    echo generateHTML($hostname, $网站图标);
}

/**
 * Handle check proxy IP request
 */
function handleCheckRequest() {
    global $cfphptoken;
    
    if (!isset($_GET['proxyip'])) {
        sendJsonResponse(['error' => 'Missing proxyip parameter'], 400);
        return;
    }
    
    $proxyIP = strtolower($_GET['proxyip']);
    
    if (empty($proxyIP)) {
        sendJsonResponse(['error' => 'Invalid proxyip parameter'], 400);
        return;
    }
    
    if (!strpos($proxyIP, '.') && !(strpos($proxyIP, '[') && strpos($proxyIP, ']'))) {
        sendJsonResponse(['error' => 'Invalid proxyip format'], 400);
        return;
    }
    
    $result = checkProxyIP($proxyIP, $cfphptoken);
    
    sendJsonResponse($result, $result['success'] ? 200 : 502);
}

/**
 * Handle domain resolve request
 */
function handleResolveRequest() {
    if (!isset($_GET['domain'])) {
        sendJsonResponse(['error' => 'Missing domain parameter'], 400);
        return;
    }
    
    $domain = $_GET['domain'];
    
    try {
        $ips = resolveDomain($domain);
        sendJsonResponse([
            'success' => true,
            'domain' => $domain,
            'ips' => $ips
        ]);
    } catch (Exception $e) {
        sendJsonResponse([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}

/**
 * Handle IP info request
 */
function handleIPInfoRequest() {
    $ip = isset($_GET['ip']) ? $_GET['ip'] : $_SERVER['REMOTE_ADDR'];
    
    if (empty($ip)) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'IP参数未提供',
            'code' => 'MISSING_PARAMETER',
            'timestamp' => date('c')
        ], 400);
        return;
    }
    
    if (strpos($ip, '[') !== false) {
        $ip = str_replace(['[', ']'], '', $ip);
    }
    
    try {
        // Use a proxy request to HTTP IP API
        $response = file_get_contents("http://ip-api.com/json/{$ip}?lang=zh-CN");
        
        if ($response === false) {
            throw new Exception('HTTP request failed');
        }
        
        $data = json_decode($response, true);
        
        // Add timestamp to successful response
        $data['timestamp'] = date('c');
        
        sendJsonResponse($data);
    } catch (Exception $e) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'IP查询失败: ' . $e->getMessage(),
            'code' => 'API_REQUEST_FAILED',
            'query' => $ip,
            'timestamp' => date('c'),
            'details' => [
                'errorType' => get_class($e),
                'stack' => $e->getMessage()
            ]
        ], 500);
    }
}

/**
 * Check if a proxy IP is valid
 * 
 * @param string $proxyIP The proxy IP to check
 * @param string $cfphptoken The CFphptoken for authentication
 * @return array Result of the check
 */
function checkProxyIP($proxyIP, $cfphptoken) {
    // Parse port from proxyIP
    $portRemote = 443;
    
    if (strpos($proxyIP, '.tp') !== false) {
        $matches = [];
        if (preg_match('/\.tp(\d+)\./', $proxyIP, $matches)) {
            $portRemote = intval($matches[1]);
        }
    } elseif (strpos($proxyIP, '[') !== false && strpos($proxyIP, ']:') !== false) {
        $parts = explode(']:', $proxyIP);
        $portRemote = intval($parts[1]);
        $proxyIP = $parts[0] . ']';
    } elseif (strpos($proxyIP, ':') !== false) {
        $parts = explode(':', $proxyIP);
        $portRemote = intval($parts[1]);
        $proxyIP = $parts[0];
    }
    
    try {
        // Create a socket connection
        $socket = @fsockopen($proxyIP, $portRemote, $errno, $errstr, 5);
        
        if (!$socket) {
            throw new Exception("Connection failed: $errstr ($errno)");
        }
        
        // Build HTTP GET request
        $httpRequest = "GET /cdn-cgi/trace HTTP/1.1\r\n" .
                      "Host: speed.cloudflare.com\r\n" .
                      "User-Agent: CheckProxyIP/PHPVersion\r\n" .
                      "Connection: close\r\n\r\n";
        
        // Send HTTP request
        fwrite($socket, $httpRequest);
        
        // Read HTTP response
        $responseData = '';
        while (!feof($socket)) {
            $responseData .= fread($socket, 8192);
        }
        fclose($socket);
        
        // Parse HTTP response
        $statusCode = null;
        if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/i', $responseData, $matches)) {
            $statusCode = intval($matches[1]);
        }
        
        // Determine if it's a valid proxy response
        $isSuccessful = isValidProxyResponse($responseData);
        
        // Build JSON response
        return [
            'success' => $isSuccessful,
            'proxyIP' => $proxyIP,
            'portRemote' => $portRemote,
            'statusCode' => $statusCode,
            'responseSize' => strlen($responseData),
            'responseData' => $responseData,
            'timestamp' => date('c'),
        ];
    } catch (Exception $e) {
        // Connection failed, return failure JSON
        return [
            'success' => false,
            'proxyIP' => -1,
            'portRemote' => -1,
            'timestamp' => date('c'),
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Check if the response is a valid proxy response
 * 
 * @param string $responseText The HTTP response text
 * @return bool Whether it's a valid proxy response
 */
function isValidProxyResponse($responseText) {
    $statusMatch = preg_match('/^HTTP\/\d\.\d\s+(\d+)/i', $responseText, $matches);
    $statusCode = $statusMatch ? intval($matches[1]) : null;
    $looksLikeCloudflare = strpos($responseText, 'cloudflare') !== false;
    $isExpectedError = strpos($responseText, 'plain HTTP request') !== false || 
                       strpos($responseText, '400 Bad Request') !== false;
    $hasBody = strlen($responseText) > 100;
    
    return $statusCode !== null && $looksLikeCloudflare && $isExpectedError && $hasBody;
}

/**
 * Resolve a domain to its IP addresses
 * 
 * @param string $domain The domain to resolve
 * @return array Array of IP addresses
 * @throws Exception If resolution fails
 */
function resolveDomain($domain) {
    if (strpos($domain, ':') !== false) {
        $domain = explode(':', $domain)[0];
    }
    
    try {
        $ips = [];
        
        // Get IPv4 addresses
        $ipv4Records = dns_get_record($domain, DNS_A);
        if (!empty($ipv4Records)) {
            foreach ($ipv4Records as $record) {
                if (isset($record['ip'])) {
                    $ips[] = $record['ip'];
                }
            }
        }
        
        // Get IPv6 addresses
        $ipv6Records = dns_get_record($domain, DNS_AAAA);
        if (!empty($ipv6Records)) {
            foreach ($ipv6Records as $record) {
                if (isset($record['ipv6'])) {
                    $ips[] = '[' . $record['ipv6'] . ']';
                }
            }
        }
        
        if (empty($ips)) {
            throw new Exception('No A or AAAA records found');
        }
        
        return $ips;
    } catch (Exception $e) {
        throw new Exception('DNS resolution failed: ' . $e->getMessage());
    }
}

/**
 * Send a JSON response
 * 
 * @param array $data The data to send
 * @param int $status The HTTP status code
 */
function sendJsonResponse($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

/**
 * Generate HTML for the main page
 * 
 * @param string $hostname The hostname
 * @param string $网站图标 The website icon URL
 * @return string The HTML content
 */
function generateHTML($hostname, $网站图标) {
    // 包含HTML模板文件
    ob_start();
    include 'proxyip.php';
    return ob_get_clean();
}
