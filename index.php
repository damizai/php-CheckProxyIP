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
$cfphptoken = '你的API令牌'; // Replace with your actual CFphptoken

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
        // 方法1: 使用Cloudflare API检查IP (如果Token有效)
        if (!empty($cfphptoken) && strlen($cfphptoken) > 20) {
            $cfResult = checkWithCloudflareAPI($proxyIP, $cfphptoken);
            if ($cfResult !== null) {
                return $cfResult;
            }
        }
        
        // 方法2: 检查是否能访问套了Cloudflare CDN的网站
        $cdnResult = checkCloudflareCDNAccess($proxyIP, $portRemote);
        if ($cdnResult['success']) {
            return $cdnResult;
        }
        
        // 方法3: 使用socket连接直接检查
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
 * 检查是否能访问套了Cloudflare CDN的网站
 * 利用Cloudflare屏蔽自己IP的特性进行检测
 * 
 * @param string $proxyIP 代理IP
 * @param int $portRemote 端口
 * @return array 检测结果
 */
function checkCloudflareCDNAccess($proxyIP, $portRemote) {
    // 使用Cloudflare保护的网站列表
    $cfSites = [
        'www.cloudflare.com',
        'dash.cloudflare.com',
        'developers.cloudflare.com',
        'www.cloudflare.tv',
        'blog.cloudflare.com'
    ];
    
    // 随机选择一个网站进行测试
    $testSite = $cfSites[array_rand($cfSites)];
    
    try {
        // 尝试连接
        $socket = @fsockopen($proxyIP, $portRemote, $errno, $errstr, 5);
        
        if (!$socket) {
            return [
                'success' => false,
                'proxyIP' => $proxyIP,
                'portRemote' => $portRemote,
                'source' => 'cf_block_check',
                'timestamp' => date('c'),
                'message' => "无法连接到代理: $errstr ($errno)"
            ];
        }
        
        // 构建HTTP请求
        $httpRequest = "GET / HTTP/1.1\r\n" .
                      "Host: $testSite\r\n" .
                      "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n" .
                      "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8\r\n" .
                      "Connection: close\r\n\r\n";
        
        // 发送请求
        fwrite($socket, $httpRequest);
        
        // 读取响应
        $responseData = '';
        while (!feof($socket)) {
            $responseData .= fread($socket, 8192);
        }
        fclose($socket);
        
        // 检查是否有效的响应
        $statusCode = null;
        if (preg_match('/^HTTP\/\d\.\d\s+(\d+)/i', $responseData, $matches)) {
            $statusCode = intval($matches[1]);
        }
        
        // 检查是否被Cloudflare屏蔽
        $isBlocked = false;
        
        // 1. 检查状态码是否为403或520（Cloudflare常见的屏蔽状态码）
        $isBlockedStatusCode = $statusCode == 403 || $statusCode == 520 || $statusCode == 521;
        
        // 2. 检查是否包含Cloudflare屏蔽页面的特征
        $hasBlockPage = strpos($responseData, 'Attention Required! | Cloudflare') !== false ||
                       strpos($responseData, 'security check to access') !== false ||
                       strpos($responseData, 'captcha-bypass') !== false ||
                       strpos($responseData, 'cf-error-code') !== false ||
                       strpos($responseData, 'cf_chl_') !== false;
        
        // 3. 检查是否包含“来源IP被屏蔽”的提示
        $hasIPBlockMessage = strpos($responseData, 'Your IP address has been blocked') !== false ||
                            strpos($responseData, 'Your IP') !== false && strpos($responseData, 'blocked') !== false;
        
        // 判断是否被Cloudflare屏蔽
        $isBlocked = $isBlockedStatusCode || $hasBlockPage || $hasIPBlockMessage;
        
        // 判断是否是Cloudflare的响应（无论是否屏蔽）
        $isCfResponse = strpos($responseData, 'cloudflare') !== false ||
                       strpos($responseData, 'CF-RAY') !== false ||
                       strpos($responseData, 'cf-ray') !== false;
        
        // 关键点：如果是Cloudflare的响应且被屏蔽，说明这是Cloudflare的IP
        // 如果是Cloudflare的响应但没有被屏蔽，说明这不是Cloudflare的IP
        $isCloudflareIP = $isCfResponse && $isBlocked;
        $isValidProxy = $isCfResponse && !$isBlocked;
        
        return [
            'success' => $isValidProxy, // 如果没有被屏蔽，则这是一个有效的代理
            'proxyIP' => $proxyIP,
            'portRemote' => $portRemote,
            'statusCode' => $statusCode,
            'source' => 'cf_block_check',
            'testSite' => $testSite,
            'isCloudflareIP' => $isCloudflareIP,
            'isBlocked' => $isBlocked,
            'isCfResponse' => $isCfResponse,
            'timestamp' => date('c'),
            'message' => $isValidProxy ? "有效代理：能访问套了CF的网站且未被屏蔽" : 
                         ($isCloudflareIP ? "IP是Cloudflare官方IP，被屏蔽" : "无法正确访问套了CF的网站")
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'proxyIP' => $proxyIP,
            'portRemote' => $portRemote,
            'source' => 'cf_block_check',
            'timestamp' => date('c'),
            'error' => $e->getMessage()
        ];
    }
}

/**
 * 使用Cloudflare IP列表检查IP
 * 
 * @param string $proxyIP 要检查的代理IP
 * @param string $cfToken 不再使用，保留参数以保持兼容性
 * @return array|null 检查结果或null（如果检查失败）
 */
function checkWithCloudflareAPI($proxyIP, $cfToken) {
    // 使用公开的Cloudflare IP列表缓存
    // 注意：这个列表可能不是实时更新的，但对于大多数检测来说已经足够
    $cfIPRanges = [
        // IPv4范围
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
        
        // IPv6范围
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32'
    ];
    
    try {
        // 检查IP是否在Cloudflare IP列表中
        $isCloudflareIP = false;
        
        foreach ($cfIPRanges as $cidr) {
            if (isIPInRange($proxyIP, $cidr)) {
                $isCloudflareIP = true;
                break;
            }
        }
        
        // 返回结果 - 注意：如果IP是Cloudflare的官方IP，那么它作为代理IP应该是失效的
        if ($isCloudflareIP) {
            return [
                'success' => false,  // 改为false，因为Cloudflare官方IP不能用作代理
                'proxyIP' => $proxyIP,
                'portRemote' => 443, 
                'source' => 'cloudflare_ip_check',
                'timestamp' => date('c'),
                'message' => 'IP是Cloudflare官方IP，不能用作代理'
            ];
        } else {
            // 如果不是Cloudflare IP，我们需要通过socket连接进一步验证
            // 返回null让代码继续使用socket方法验证
            return null;
        }
    } catch (Exception $e) {
        // 检查失败，返回null以便回退到socket方法
        return null;
    }
}

/**
 * 检查IP是否在CIDR范围内
 * 
 * @param string $ip 要检查的IP
 * @param string $cidr CIDR格式的IP范围
 * @return bool 是否在范围内
 */
function isIPInRange($ip, $cidr) {
    // 分离IP和掩码
    list($subnet, $mask) = explode('/', $cidr);
    
    // IPv4
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = ~((1 << (32 - $mask)) - 1);
        return ($ipLong & $maskLong) == ($subnetLong & $maskLong);
    }
    
    // IPv6 (简化处理)
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // 简单比较前缀，完整实现需要更复杂的IPv6处理
        $ipPrefix = substr($ip, 0, $mask / 4); // 每个十六进制字符代表4位
        $subnetPrefix = substr($subnet, 0, $mask / 4);
        return $ipPrefix == $subnetPrefix;
    }
    
    return false;
}

/**
 * Check if the response is a valid proxy response
 * 
 * @param string $responseText The HTTP response text
 * @return bool Whether it's a valid proxy response
 */
function isValidProxyResponse($responseText) {
    // 检查是否有HTTP状态码
    $statusMatch = preg_match('/^HTTP\/\d\.\d\s+(\d+)/i', $responseText, $matches);
    $statusCode = $statusMatch ? intval($matches[1]) : null;
    
    // 如果状态码不存在或不是200，直接返回false
    if ($statusCode === null || $statusCode !== 200) {
        return false;
    }
    
    // 检查是否包含特定的trace字段，这些字段必须同时存在
    $requiredFields = [
        'visit_scheme=', 
        'uag=',
        'colo=',
        'http=',
        'loc=',
        'tls=',
        'sni=',
        'warp='
    ];
    
    $allFieldsPresent = true;
    foreach ($requiredFields as $field) {
        if (strpos($responseText, $field) === false) {
            $allFieldsPresent = false;
            break;
        }
    }
    
    // 必须包含“h=speed.cloudflare.com”字段
    $hasHostField = strpos($responseText, 'h=speed.cloudflare.com') !== false;
    
    // 确保是Cloudflare的响应
    $isCloudflareResponse = strpos($responseText, 'cloudflare') !== false;
    
    // 所有条件都必须满足
    return $allFieldsPresent && $hasHostField && $isCloudflareResponse;
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
