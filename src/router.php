<?php
// router.php
function getCurrentPageId() {
    $requestUri = $_SERVER['REQUEST_URI'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    
    // 移除脚本名称和查询参数
    $path = str_replace(dirname($scriptName), '', $requestUri);
    $path = parse_url($path, PHP_URL_PATH);
    $path = trim($path, '/');
    
    // 如果是首页
    if (empty($path) || $path === 'index.php') {
        return 'home';
    }
    
    return $path;
}

function getParentPageId($pageId) {
    if ($pageId === 'home') {
        return null;
    }
    
    $parts = explode('/', $pageId);
    array_pop($parts);
    
    if (empty($parts)) {
        return 'home';
    }
    
    return implode('/', $parts);
}

function getBreadcrumbs($pageId, $data) {
    $breadcrumbs = [];
    $currentId = $pageId;
    
    while ($currentId !== null && $currentId !== 'home') {
        if (isset($data['pages'][$currentId])) {
            array_unshift($breadcrumbs, [
                'title' => $data['pages'][$currentId]['title'],
                'url' => '/' . $currentId
            ]);
        }
        $currentId = getParentPageId($currentId);
    }
    
    array_unshift($breadcrumbs, [
        'title' => $data['siteName'],
        'url' => '/'
    ]);
    
    return $breadcrumbs;
}