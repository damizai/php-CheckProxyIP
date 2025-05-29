<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Check ProxyIP - 代理IP检测服务</title>
  <link rel="icon" href="<?php echo $网站图标; ?>" type="image/x-icon">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary-color: #3498db;
      --primary-dark: #2980b9;
      --secondary-color: #1abc9c;
      --success-color: #2ecc71;
      --warning-color: #f39c12;
      --error-color: #e74c3c;
      --bg-primary: #ffffff;
      --bg-secondary: #f8f9fa;
      --bg-tertiary: #e9ecef;
      --text-primary: #2c3e50;
      --text-secondary: #6c757d;
      --text-light: #adb5bd;
      --border-color: #dee2e6;
      --shadow-sm: 0 2px 4px rgba(0,0,0,0.1);
      --shadow-md: 0 4px 6px rgba(0,0,0,0.1);
      --shadow-lg: 0 10px 25px rgba(0,0,0,0.15);
      --border-radius: 12px;
      --border-radius-sm: 8px;
      --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
      --gradient-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      --card-bg: rgba(255, 255, 255, 0.9);
    }
    
    [data-theme="dark"] {
      --primary-color: #61dafb;
      --primary-dark: #0ea5e9;
      --secondary-color: #10b981;
      --success-color: #22c55e;
      --warning-color: #f59e0b;
      --error-color: #ef4444;
      --bg-primary: #1e293b;
      --bg-secondary: #0f172a;
      --bg-tertiary: #334155;
      --text-primary: #f1f5f9;
      --text-secondary: #cbd5e1;
      --text-light: #64748b;
      --border-color: #475569;
      --shadow-sm: 0 2px 4px rgba(0,0,0,0.3);
      --shadow-md: 0 4px 6px rgba(0,0,0,0.3);
      --shadow-lg: 0 10px 25px rgba(0,0,0,0.4);
      --gradient-bg: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
      --card-bg: rgba(30, 41, 59, 0.8);
    }
    
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }
    
    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      line-height: 1.6;
      color: var(--text-primary);
      background: var(--bg-primary);
      min-height: 100vh;
      position: relative;
      overflow-x: hidden;
    }
    
    body::before {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('https://cdn.pixabay.com/photo/2018/08/14/13/23/ocean-3605547_1280.jpg') no-repeat;
      background-size: cover;
      background-position: center;
      opacity: 0.3;
      z-index: -1;
    }
    
    /* 移除了body::after特效 */
    
    .container {
      max-width: 1000px;
      margin: 0 auto;
      padding: 20px;
    }
    
    .header {
      text-align: center;
      margin-bottom: 50px;
    }
    
    .main-title {
      font-size: clamp(2.5rem, 5vw, 4rem);
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 16px;
    }
    
    .subtitle {
      font-size: 1.2rem;
      color: rgba(255,255,255,0.9);
      font-weight: 400;
      margin-bottom: 8px;
    }
    
    .badge {
      display: inline-block;
      background: rgba(255,255,255,0.2);
      backdrop-filter: blur(10px);
      padding: 8px 16px;
      border-radius: 50px;
      color: white;
      font-size: 0.9rem;
      font-weight: 500;
      border: 1px solid rgba(255,255,255,0.3);
    }
    
    .card {
      background: var(--card-bg);
      border-radius: var(--border-radius);
      padding: 32px;
      box-shadow: var(--shadow-lg);
      margin-bottom: 32px;
      border: 1px solid var(--border-color);
      transition: var(--transition);
      backdrop-filter: blur(20px);
      position: relative;
      overflow: hidden;
    }
    
    .card::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
    }
    
    .card:hover {
      box-shadow: 0 10px 20px rgba(0,0,0,0.15);
    }
    
    .form-section {
      margin-bottom: 32px;
    }
    
    .form-label {
      display: block;
      font-weight: 600;
      font-size: 1.1rem;
      margin-bottom: 12px;
      color: var(--text-primary);
    }
    
    .input-group {
      display: flex;
      gap: 16px;
      align-items: flex-end;
      flex-wrap: wrap;
    }
    
    .input-wrapper {
      flex: 1;
      min-width: 300px;
      position: relative;
    }
    
    .form-input {
      width: 100%;
      padding: 16px 20px;
      border: 2px solid var(--border-color);
      border-radius: var(--border-radius-sm);
      font-size: 16px;
      font-family: inherit;
      transition: var(--transition);
      background: var(--bg-primary);
      color: var(--text-primary);
    }
    
    .form-input:focus {
      outline: none;
      border-color: var(--primary-color);
      box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
      transform: translateY(-1px);
    }
    
    .form-input::placeholder {
      color: var(--text-light);
    }
    
    .btn {
      padding: 16px 32px;
      border: none;
      border-radius: var(--border-radius-sm);
      font-size: 16px;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      min-width: 120px;
      position: relative;
      overflow: hidden;
    }
    
    .btn-primary {
      background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
      color: white;
      box-shadow: var(--shadow-md);
    }
    
    .result-section {
      margin-top: 32px;
      opacity: 0;
      transform: translateY(20px);
      transition: var(--transition);
    }
    
    .result-section.show {
      opacity: 1;
      transform: translateY(0);
    }
    
    .result-card {
      border-radius: var(--border-radius-sm);
      padding: 24px;
      margin-bottom: 16px;
      border-left: 4px solid;
      position: relative;
      overflow: hidden;
    }
    
    .result-success {
      background: linear-gradient(135deg, #d4edda, #c3e6cb);
      border-color: var(--success-color);
      color: #155724;
    }
    
    .result-error {
      background: linear-gradient(135deg, #f8d7da, #f5c6cb);
      border-color: var(--error-color);
      color: #721c24;
    }
    
    .result-warning {
      background: linear-gradient(135deg, #fff3cd, #ffeaa7);
      border-color: var(--warning-color);
      color: #856404;
    }
    
    .ip-grid {
      display: grid;
      gap: 16px;
      margin-top: 20px;
    }
    
    .ip-item {
      background: rgba(255,255,255,0.9);
      border: 1px solid var(--border-color);
      border-radius: var(--border-radius-sm);
      padding: 20px;
      position: relative;
    }
    
    .ip-status-line {
      display: flex;
      align-items: center;
      gap: 12px;
      flex-wrap: wrap;
    }
    
    .status-icon {
      font-size: 18px;
      margin-left: auto;
    }
    
    .copy-btn {
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      padding: 6px 12px;
      border-radius: 6px;
      font-size: 14px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      margin: 4px 0;
    }
    
    .copy-btn:hover {
      background: var(--primary-color);
      color: white;
      border-color: var(--primary-color);
    }
    
    .copy-btn.copied {
      background: var(--success-color);
      color: white;
      border-color: var(--success-color);
    }
    
    .info-tags {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      margin-top: 8px;
    }
    
    .tag {
      padding: 4px 8px;
      border-radius: 16px;
      font-size: 12px;
      font-weight: 500;
    }
    
    .tag-country {
      background: #e3f2fd;
      color: #1976d2;
    }
    
    .tag-as {
      background: #f3e5f5;
      color: #7b1fa2;
    }
    
    .api-docs {
      background: var(--bg-primary);
      border-radius: var(--border-radius);
      padding: 32px;
      box-shadow: var(--shadow-lg);
    }
    
    .section-title {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--text-primary);
      margin-bottom: 24px;
      position: relative;
      padding-bottom: 12px;
    }
    
    .section-title::after {
      content: "";
      position: absolute;
      bottom: 0;
      left: 0;
      width: 60px;
      height: 3px;
      background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
      border-radius: 2px;
    }
    
    .code-block {
      background: var(--bg-secondary);
      color: var(--text-primary);
      padding: 20px;
      border-radius: var(--border-radius-sm);
      font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
      font-size: 14px;
      overflow-x: auto;
      margin: 16px 0;
      border: 1px solid var(--border-color);
      position: relative;
    }
    
    .code-block::before {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 2px;
      background: linear-gradient(90deg, #48bb78, #38b2ac);
    }
    
    .highlight {
      color: var(--error-color);
      font-weight: 600;
    }
    
    .footer {
      text-align: center;
      padding: 20px 20px 20px;
      color: rgba(255,255,255,0.8);
      font-size: 14px;
      margin-top: 40px;
      border-top: 1px solid rgba(255,255,255,0.1);
    }
    
    .github-link {
      position: fixed;
      top: 20px;
      left: 20px;
      z-index: 1000;
      display: flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
      color: var(--text-primary);
      background: var(--card-bg);
      padding: 8px 12px;
      border-radius: var(--border-radius-sm);
      box-shadow: var(--shadow-md);
      backdrop-filter: blur(10px);
      border: 1px solid var(--border-color);
    }
    
    .github-link:hover {
      transform: translateY(-2px);
      box-shadow: var(--shadow-lg);
    }
    
    .github-link svg {
      width: 24px;
      height: 24px;
      fill: var(--primary-color);
    }
    
    .github-link span {
      font-weight: 600;
      font-size: 14px;
    }
    
    .loading-spinner {
      width: 20px;
      height: 20px;
      border: 2px solid rgba(255,255,255,0.3);
      border-top: 2px solid white;
      border-radius: 50%;
    }
    
    @media (max-width: 768px) {
      .container {
        padding: 16px;
      }
      
      .card {
        padding: 24px;
        margin-bottom: 24px;
      }
      
      .input-group {
        flex-direction: column;
        align-items: stretch;
      }
      
      .input-wrapper {
        min-width: auto;
      }
      
      .btn {
        width: 100%;
      }
      
      .github-corner svg {
        width: 60px;
        height: 60px;
      }
      
      .github-corner:hover .octo-arm {
        animation: none;
      }
      
      .github-corner .octo-arm {
        animation: none;
      }
      
      .main-title {
        font-size: 2.5rem;
      }
    }
    
    /* 主题切换开关 */
    .theme-switch-wrapper {
      display: flex;
      align-items: center;
      position: fixed;
      top: 20px;
      right: 20px;
      z-index: 100;
    }
    
    .theme-switch {
      display: inline-block;
      height: 34px;
      position: relative;
      width: 60px;
    }
    
    .theme-switch input {
      display: none;
    }
    
    .slider {
      background-color: var(--bg-tertiary);
      bottom: 0;
      cursor: pointer;
      left: 0;
      position: absolute;
      right: 0;
      top: 0;
      transition: .4s;
      border-radius: 34px;
    }
    
    .slider:before {
      background-color: white;
      bottom: 4px;
      content: "";
      height: 26px;
      left: 4px;
      position: absolute;
      transition: .4s;
      width: 26px;
      border-radius: 50%;
    }
    
    input:checked + .slider {
      background-color: var(--primary-color);
    }
    
    input:checked + .slider:before {
      transform: translateX(26px);
    }
    
    .slider-icons {
      color: white;
      display: flex;
      justify-content: space-between;
      padding: 5px 10px;
      position: relative;
    }
    
    .slider-icons span {
      font-size: 18px;
      line-height: 24px;
    }
    
    .toast {
      position: fixed;
      bottom: 20px;
      right: 20px;
      background: var(--bg-tertiary);
      color: var(--text-primary);
      padding: 12px 20px;
      border-radius: var(--border-radius-sm);
      box-shadow: var(--shadow-lg);
      transform: translateY(100px);
      opacity: 0;
      transition: var(--transition);
      z-index: 1000;
    }
    
    .toast.show {
      transform: translateY(0);
      opacity: 1;
    }
  </style>
</head>

<body>
  <!-- 主题切换开关 -->
  <div class="theme-switch-wrapper">
    <label class="theme-switch" for="checkbox">
      <input type="checkbox" id="checkbox" />
      <div class="slider">
        <div class="slider-icons">
          <span>🌙</span>
          <span>☀️</span>
        </div>
      </div>
    </label>
  </div>
  
  <a href="https://github.com/damizai/php-CheckProxyIP" target="_blank" class="github-link" aria-label="View source on Github">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
      <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
    </svg>
    <span>GitHub</span>
  </a>

  <div class="container">
    <header class="header">
      <h1 class="main-title">Check ProxyIP</h1>
    </header>

    <div class="card">
      <div class="form-section">
        <label for="proxyip" class="form-label">🔍 输入 ProxyIP 地址</label>
        <div class="input-group">
          <div class="input-wrapper">
            <input type="text" id="proxyip" class="form-input" placeholder="例如: 1.2.3.4:443 或 example.com" autocomplete="off">
          </div>
          <button id="checkBtn" class="btn btn-primary" onclick="checkProxyIP()">
            <span class="btn-text">检测</span>
            <div class="loading-spinner" style="display: none;"></div>
          </button>
        </div>
      </div>
      
      <div id="result" class="result-section"></div>
    </div>
    
    <div class="api-docs">
      <h2 class="section-title">📚 API 文档</h2>
      <p style="margin-bottom: 24px; color: var(--text-secondary); font-size: 1.1rem;">
        提供简单易用的 RESTful API 接口，支持批量检测和域名解析
      </p>
      
      <h3 style="color: var(--text-primary); margin: 24px 0 16px;">📍 检查ProxyIP</h3>
      <div class="code-block">
        <strong style="color: #68d391;">GET</strong> /index.php?path=check&proxyip=<span class="highlight">YOUR_PROXY_IP</span>
      </div>
      
      <h3 style="color: var(--text-primary); margin: 24px 0 16px;">💡 使用示例</h3>
      <div class="code-block">
curl "https://<?php echo $hostname; ?>/index.php?path=check&proxyip=1.2.3.4:443"
      </div>

      <h3 style="color: var(--text-primary); margin: 24px 0 16px;">🔗 响应Json格式</h3>
      <div class="code-block">
{<br>
&nbsp;&nbsp;"success": true|false, // 代理 IP 是否有效<br>
&nbsp;&nbsp;"proxyIP": "1.2.3.4", // 如果有效,返回代理 IP,否则为 -1<br>
&nbsp;&nbsp;"portRemote": 443, // 如果有效,返回端口,否则为 -1<br>
&nbsp;&nbsp;"timestamp": "2025-05-10T14:44:30.597Z" // 检查时间<br>
}<br>
      </div>
    </div>
    <footer class="footer">
      <p style="margin-top: 8px; opacity: 0.8;">© 2025 Check ProxyIP - 基于 PHP 构建的高性能 ProxyIP 验证服务 | 由 <strong>BiuXin</strong> 更改</p>感谢CMLiu
    </footer>
  </div>

  <div id="toast" class="toast"></div>


  <script>
    // 全局变量
    let isChecking = false;
    const ipCheckResults = new Map(); // 缓存IP检查结果
    
    // 主题切换功能
    const toggleSwitch = document.querySelector('.theme-switch input[type="checkbox"]');
    
    // 检查本地存储中的主题设置
    function getCurrentTheme() {
      return localStorage.getItem('theme') || 'light';
    }
    
    // 设置主题
    function setTheme(theme) {
      document.documentElement.setAttribute('data-theme', theme);
      localStorage.setItem('theme', theme);
      // 更新开关状态
      toggleSwitch.checked = theme === 'dark';
    }
    
    // 初始化主题
    const currentTheme = getCurrentTheme();
    setTheme(currentTheme);
    
    // 监听切换事件
    toggleSwitch.addEventListener('change', function(e) {
      if (e.target.checked) {
        setTheme('dark');
        showToast('已切换到暗色模式');
      } else {
        setTheme('light');
        showToast('已切换到亮色模式');
      }
    });
    
    // 添加前端的代理IP格式验证函数
    function isValidProxyIPFormat(input) {
      // 检查是否为域名格式
      const domainRegex = /^[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?)*$/;
      // 检查是否为IP格式
      const ipv4Regex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
      const ipv6Regex = /^\[?([0-9a-fA-F]{0,4}:){1,7}[0-9a-fA-F]{0,4}\]?$/;

      // 允许带端口的格式
      const withPortRegex = /^.+:\d+$/;
      const tpPortRegex = /^.+\.tp\d+\./;

      return domainRegex.test(input) ||
        ipv4Regex.test(input) ||
        ipv6Regex.test(input) ||
        withPortRegex.test(input) ||
        tpPortRegex.test(input);
    }
    
    // 初始化
    document.addEventListener('DOMContentLoaded', function() {
      const input = document.getElementById('proxyip');
      input.focus();
      
      // 直接解析当前URL路径
      const currentPath = window.location.pathname;
      let autoCheckValue = null;
      
      // 检查URL参数中的autocheck（保持兼容性）
      const urlParams = new URLSearchParams(window.location.search);
      autoCheckValue = urlParams.get('autocheck');
      
      // 如果没有autocheck参数，检查路径
      if (!autoCheckValue && currentPath.length > 1) {
        const pathContent = currentPath.substring(1); // 移除开头的 '/'
        
        // 检查路径是否为有效的代理IP格式
        if (isValidProxyIPFormat(pathContent)) {
          autoCheckValue = pathContent;
          // 清理URL，移除路径部分
          const newUrl = new URL(window.location);
          newUrl.pathname = '/';
          window.history.replaceState({}, '', newUrl);
        }
      }
      
      if (autoCheckValue) {
        input.value = autoCheckValue;
        // 如果来自URL参数，清除参数
        if (urlParams.has('autocheck')) {
          const newUrl = new URL(window.location);
          newUrl.searchParams.delete('autocheck');
          window.history.replaceState({}, '', newUrl);
        }
        
        // 延迟执行搜索，确保页面完全加载
        setTimeout(() => {
          if (!isChecking) {
            checkProxyIP();
          }
        }, 500);
      }
      
      // 输入框回车事件
      input.addEventListener('keypress', function(event) {
        if (event.key === 'Enter' && !isChecking) {
          checkProxyIP();
        }
      });
      
      // 添加事件委托处理复制按钮点击
      document.addEventListener('click', function(event) {
        if (event.target.classList.contains('copy-btn')) {
          const text = event.target.getAttribute('data-copy');
          if (text) {
            copyToClipboard(text, event.target);
          }
        }
      });
    });
    
    // 显示toast消息
    function showToast(message, duration = 3000) {
      const toast = document.getElementById('toast');
      toast.textContent = message;
      toast.classList.add('show');
      
      setTimeout(() => {
        toast.classList.remove('show');
      }, duration);
    }
    
    // 复制到剪贴板
    function copyToClipboard(text, element) {
      navigator.clipboard.writeText(text).then(() => {
        const originalText = element.textContent;
        element.classList.add('copied');
        element.textContent = '已复制 ✓';
        showToast('复制成功！');
        
        setTimeout(() => {
          element.classList.remove('copied');
          element.textContent = originalText;
        }, 2000);
      }).catch(err => {
        console.error('复制失败:', err);
        showToast('复制失败，请手动复制');
      });
    }
    
    // 创建复制按钮
    function createCopyButton(text) {
      return `<span class="copy-btn" data-copy="${text}">${text}</span>`;
    }
    
    // 检查是否为IP地址
    function isIPAddress(input) {
      const ipv4Regex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
      const ipv6Regex = /^\[?([0-9a-fA-F]{0,4}:){1,7}[0-9a-fA-F]{0,4}\]?$/;
      const ipv6WithPortRegex = /^\[[0-9a-fA-F:]+\]:\d+$/;
      const ipv4WithPortRegex = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?):\d+$/;
      
      return ipv4Regex.test(input) || ipv6Regex.test(input) || ipv6WithPortRegex.test(input) || ipv4WithPortRegex.test(input);
    }
    
    // 添加输入预处理函数
    function preprocessInput(input) {
      if (!input) return input;
      
      // 去除首尾空格
      let processed = input.trim();
      
      // 检查是否还有空格
      if (processed.includes(' ')) {
        // 只保留第一个空格前的内容
        processed = processed.split(' ')[0];
      }
      
      return processed;
    }
    
    // 主检测函数
    async function checkProxyIP() {
      if (isChecking) return;
      
      const proxyipInput = document.getElementById('proxyip');
      const resultDiv = document.getElementById('result');
      const checkBtn = document.getElementById('checkBtn');
      const btnText = checkBtn.querySelector('.btn-text');
      const spinner = checkBtn.querySelector('.loading-spinner');
      
      const rawInput = proxyipInput.value;
      const proxyip = preprocessInput(rawInput);
      
      // 如果预处理后的值与原值不同，更新输入框
      if (proxyip !== rawInput) {
        proxyipInput.value = proxyip;
        showToast('已自动清理输入内容');
      }
      
      if (!proxyip) {
        showToast('请输入代理IP地址');
        proxyipInput.focus();
        return;
      }
      
      // 设置加载状态
      isChecking = true;
      checkBtn.classList.add('btn-loading');
      checkBtn.disabled = true;
      btnText.style.display = 'none';
      spinner.style.display = 'block';
      resultDiv.classList.remove('show');
      
      try {
        if (isIPAddress(proxyip)) {
          await checkSingleIP(proxyip, resultDiv);
        } else {
          await checkDomain(proxyip, resultDiv);
        }
      } catch (err) {
        resultDiv.innerHTML = `
          <div class="result-card result-error">
            <h3>❌ 检测失败</h3>
            <p><strong>错误信息:</strong> ${err.message}</p>
            <p><strong>检测时间:</strong> ${new Date().toLocaleString()}</p>
          </div>
        `;
        resultDiv.classList.add('show');
      } finally {
        isChecking = false;
        checkBtn.classList.remove('btn-loading');
        checkBtn.disabled = false;
        btnText.style.display = 'block';
        spinner.style.display = 'none';
      }
    }
    
    // 检查单个IP
    async function checkSingleIP(proxyip, resultDiv) {
      const response = await fetch(`index.php?path=check&proxyip=${encodeURIComponent(proxyip)}`);
      const data = await response.json();
      
      if (data.success) {
        const ipInfo = await getIPInfo(data.proxyIP);
        const ipInfoHTML = formatIPInfo(ipInfo);
        
        resultDiv.innerHTML = `
          <div class="result-card result-success">
            <h3>✅ ProxyIP 有效</h3>
            <div style="margin-top: 20px;">
              <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap;">
                <strong>🌐 ProxyIP 地址:</strong>
                ${createCopyButton(data.proxyIP)}
                ${ipInfoHTML}
                <span style="color: var(--success-color); font-weight: 600; font-size: 18px;">✅</span>
              </div>
              <p><strong>🔌 端口:</strong> ${createCopyButton(data.portRemote.toString())}</p>
              <p><strong>🕒 检测时间:</strong> ${new Date(data.timestamp).toLocaleString()}</p>
            </div>
          </div>
        `;
      } else {
        resultDiv.innerHTML = `
          <div class="result-card result-error">
            <h3>❌ ProxyIP 失效</h3>
            <div style="margin-top: 20px;">
              <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px; flex-wrap: wrap;">
                <strong>🌐 IP地址:</strong>
                ${createCopyButton(proxyip)}
                <span style="color: var(--error-color); font-weight: 600; font-size: 18px;">❌</span>
              </div>
              ${data.error ? `<p><strong>错误信息:</strong> ${data.error}</p>` : ''}
              <p><strong>🕒 检测时间:</strong> ${new Date(data.timestamp).toLocaleString()}</p>
            </div>
          </div>
        `;
      }
      resultDiv.classList.add('show');
    }
    
    // 检查域名
    async function checkDomain(domain, resultDiv) {
      let portRemote = 443;
      let cleanDomain = domain;
      
      // 解析端口
      if (domain.includes('.tp')) {
        portRemote = domain.split('.tp')[1].split('.')[0] || 443;
      } else if (domain.includes('[') && domain.includes(']:')) {
        portRemote = parseInt(domain.split(']:')[1]) || 443;
        cleanDomain = domain.split(']:')[0] + ']';
      } else if (domain.includes(':')) {
        portRemote = parseInt(domain.split(':')[1]) || 443;
        cleanDomain = domain.split(':')[0];
      }
      
      // 解析域名
      const resolveResponse = await fetch(`index.php?path=resolve&domain=${encodeURIComponent(cleanDomain)}`);
      const resolveData = await resolveResponse.json();
      
      if (!resolveData.success) {
        throw new Error(resolveData.error || '域名解析失败');
      }
      
      const ips = resolveData.ips;
      if (!ips || ips.length === 0) {
        throw new Error('未找到域名对应的IP地址');
      }
      
      // 清空缓存
      ipCheckResults.clear();
      
      // 显示初始结果
      resultDiv.innerHTML = `
        <div class="result-card result-warning">
          <h3>🔍 域名解析结果</h3>
          <div style="margin-top: 20px;">
            <p><strong>🌐 ProxyIP 域名:</strong> ${createCopyButton(cleanDomain)}</p>
            <p><strong>🔌 端口:</strong> ${createCopyButton(portRemote.toString())}</p>
            <p><strong>📋 发现IP:</strong> ${ips.length} 个</p>
            <p><strong>🕒 解析时间:</strong> ${new Date().toLocaleString()}</p>
          </div>
          <div class="ip-grid" id="ip-grid">
            ${ips.map((ip, index) => `
              <div class="ip-item" id="ip-item-${index}">
                <div class="ip-status-line" id="ip-status-line-${index}">
                  <strong>IP:</strong>
                  ${createCopyButton(ip)}
                  <span id="ip-info-${index}" style="color: var(--text-secondary);">获取信息中...</span>
                  <span class="status-icon" id="status-icon-${index}">🔄</span>
                </div>
              </div>
            `).join('')}
          </div>
        </div>
      `;
      resultDiv.classList.add('show');
      
      // 并发检查所有IP和获取IP信息
      const checkPromises = ips.map((ip, index) => checkIPWithIndex(ip, portRemote, index));
      const ipInfoPromises = ips.map((ip, index) => getIPInfoWithIndex(ip, index));
      
      await Promise.all([...checkPromises, ...ipInfoPromises]);
      
      // 使用缓存的结果更新整体状态
      const validCount = Array.from(ipCheckResults.values()).filter(r => r.success).length;
      const totalCount = ips.length;
      const resultCard = resultDiv.querySelector('.result-card');
      
      if (validCount === totalCount) {
        resultCard.className = 'result-card result-success';
        resultCard.querySelector('h3').innerHTML = '✅ 所有IP均有效';
      } else if (validCount === 0) {
        resultCard.className = 'result-card result-error';
        resultCard.querySelector('h3').innerHTML = '❌ 所有IP均失效';
      } else {
        resultCard.className = 'result-card result-warning';
        resultCard.querySelector('h3').innerHTML = `⚠️ 部分IP有效 (${validCount}/${totalCount})`;
      }
    }
    
    // 检查单个IP（带索引）
    async function checkIPWithIndex(ip, port, index) {
      try {
        const cacheKey = `${ip}:${port}`;
        let result;
        
        // 检查是否已有缓存结果
        if (ipCheckResults.has(cacheKey)) {
          result = ipCheckResults.get(cacheKey);
        } else {
          // 调用API检查IP状态
          result = await checkIPStatus(cacheKey);
          // 缓存结果
          ipCheckResults.set(cacheKey, result);
        }
        
        const itemElement = document.getElementById(`ip-item-${index}`);
        const statusIcon = document.getElementById(`status-icon-${index}`);
        
        if (result.success) {
          itemElement.style.background = 'linear-gradient(135deg, #d4edda, #c3e6cb)';
          itemElement.style.borderColor = 'var(--success-color)';
          statusIcon.textContent = '✅';
          statusIcon.className = 'status-icon status-success';
          statusIcon.style.color = 'var(--success-color)';
          statusIcon.style.fontSize = '18px';
        } else {
          itemElement.style.background = 'linear-gradient(135deg, #f8d7da, #f5c6cb)';
          itemElement.style.borderColor = 'var(--error-color)';
          statusIcon.textContent = '❌';
          statusIcon.className = 'status-icon status-error';
          statusIcon.style.color = 'var(--error-color)';
          statusIcon.style.fontSize = '18px';
        }
      } catch (error) {
        console.error('检查IP失败:', error);
        const statusIcon = document.getElementById(`status-icon-${index}`);
        if (statusIcon) {
          statusIcon.textContent = '❌';
          statusIcon.className = 'status-icon status-error';
          statusIcon.style.color = 'var(--error-color)';
          statusIcon.style.fontSize = '18px';
        }
        // 将失败结果也缓存起来
        const cacheKey = `${ip}:${port}`;
        ipCheckResults.set(cacheKey, { success: false, error: error.message });
      }
    }
    
    // 获取IP信息（带索引）
    async function getIPInfoWithIndex(ip, index) {
      try {
        const ipInfo = await getIPInfo(ip);
        const infoElement = document.getElementById(`ip-info-${index}`);
        if (infoElement) {
          infoElement.innerHTML = formatIPInfo(ipInfo);
        }
      } catch (error) {
        console.error('获取IP信息失败:', error);
        const infoElement = document.getElementById(`ip-info-${index}`);
        if (infoElement) {
          infoElement.innerHTML = '<span style="color: var(--text-light);">信息获取失败</span>';
        }
      }
    }
    
    // 获取IP信息
    async function getIPInfo(ip) {
      try {
        const cleanIP = ip.replace(/[\[\]]/g, '');
        const response = await fetch(`index.php?path=ip-info&ip=${encodeURIComponent(cleanIP)}`);
        const data = await response.json();
        return data;
      } catch (error) {
        return null;
      }
    }
    
    // 格式化IP信息
    function formatIPInfo(ipInfo) {
      if (!ipInfo || ipInfo.status !== 'success') {
        return '<span style="color: var(--text-light);">信息获取失败</span>';
      }
      
      const country = ipInfo.country || '未知';
      const as = ipInfo.as || '未知';
      
      return `
        <span class="tag tag-country">${country}</span>
        <span class="tag tag-as">${as}</span>
      `;
    }
    
    // 检查IP状态
    async function checkIPStatus(ip) {
      try {
        const response = await fetch(`index.php?path=check&proxyip=${encodeURIComponent(ip)}`);
        const data = await response.json();
        return data;
      } catch (error) {
        return { success: false, error: error.message };
      }
    }
  </script>
</body>
</html>
