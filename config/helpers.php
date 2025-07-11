<?php
function getSiteUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . '/healthy';
}

function getAssetUrl($path) {
    return getSiteUrl() . '/' . trim($path, '/');
}

function getAvatarUrl($avatarName = null) {
    if (empty($avatarName)) {
        return getAssetUrl('img/user.png');
    }
    // Loại bỏ path uploads/avatars nếu đã có trong tên file
    $avatarName = str_replace('uploads/avatars/', '', $avatarName);
    return getAssetUrl('uploads/avatars/' . $avatarName);
}
