<?php
/**
 * 平台配置工具类，建议全局 require_once 引入
 * 用于读取 platform_settings 表的全部配置项，并支持缓存与单项读取
 * 用法示例：
 *   require_once __DIR__.'/db.php';
 *   require_once __DIR__.'/platform_utils.php';
 *   $platform_settings = get_platform_settings($db);
 *   $platform_name = get_platform_setting($db, 'platform_name', '平台后台管理');
 *   $platform_logo = get_platform_setting($db, 'platform_logo');
 */

// 全局缓存（仅在当前请求周期内有效）
$GLOBALS['_platform_settings_cache'] = null;

/**
 * 获取全部平台配置（只查一次数据库，后续走缓存）
 * @param PDO $db
 * @return array
 */
function get_platform_settings($db) {
    global $_platform_settings_cache;
    if ($_platform_settings_cache !== null) return $_platform_settings_cache;
    $_platform_settings_cache = [];
    $stmt = $db->query("SELECT s_key, s_value FROM platform_settings");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
        $_platform_settings_cache[$row['s_key']] = $row['s_value'];
    }
    return $_platform_settings_cache;
}

/**
 * 获取单项平台配置
 * @param PDO $db
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function get_platform_setting($db, $key, $default = '') {
    $settings = get_platform_settings($db);
    return isset($settings[$key]) ? $settings[$key] : $default;
}

/**
 * 获取平台名称（便捷函数）
 * @param PDO $db
 * @return string
 */
function get_platform_name($db) {
    return get_platform_setting($db, 'platform_name', '易代账CRM-SaaS云平台');
}

/**
 * 获取平台LOGO URL（便捷函数）
 * @param PDO $db
 * @return string
 */
function get_platform_logo($db) {
    return get_platform_setting($db, 'platform_logo');
}

/**
 * 获取平台公告（便捷函数）
 * @param PDO $db
 * @return string
 */
function get_platform_announce($db) {
    return get_platform_setting($db, 'platform_announce');
}

/**
 * 获取SMTP邮箱配置（全部返回数组，便于发信）
 * @param PDO $db
 * @return array
 */
function get_smtp_settings($db) {
    $settings = get_platform_settings($db);
    // 可根据你的 platform_settings 表实际字段调整
    return [
        'host'       => isset($settings['smtp_host']) ? $settings['smtp_host'] : '',
        'port'       => isset($settings['smtp_port']) ? $settings['smtp_port'] : '',
        'user'       => isset($settings['smtp_user']) ? $settings['smtp_user'] : '',
        'pass'       => isset($settings['smtp_pass']) ? $settings['smtp_pass'] : '',
        'secure'     => isset($settings['smtp_secure']) ? $settings['smtp_secure'] : '',
        'from_email' => isset($settings['smtp_from_email']) ? $settings['smtp_from_email'] : '',
        'from_name'  => isset($settings['smtp_from_name']) ? $settings['smtp_from_name'] : '',
    ];
}

/**
 * 获取对象存储配置（预留）
 * @param PDO $db
 * @return array
 */
function get_oss_settings($db) {
    $settings = get_platform_settings($db);
    return [
        'provider' => isset($settings['oss_provider']) ? $settings['oss_provider'] : '',
        'key'      => isset($settings['oss_key']) ? $settings['oss_key'] : '',
        'secret'   => isset($settings['oss_secret']) ? $settings['oss_secret'] : '',
        'bucket'   => isset($settings['oss_bucket']) ? $settings['oss_bucket'] : '',
        'endpoint' => isset($settings['oss_endpoint']) ? $settings['oss_endpoint'] : '',
    ];
}

/**
 * 获取短信配置（预留）
 * @param PDO $db
 * @return array
 */
function get_sms_settings($db) {
    $settings = get_platform_settings($db);
    return [
        'provider' => isset($settings['sms_provider']) ? $settings['sms_provider'] : '',
        'appid'    => isset($settings['sms_appid']) ? $settings['sms_appid'] : '',
        'key'      => isset($settings['sms_key']) ? $settings['sms_key'] : '',
        'sign'     => isset($settings['sms_sign']) ? $settings['sms_sign'] : '',
    ];
}

/**
 * 获取支付配置（预留）
 * @param PDO $db
 * @return array
 */
function get_payment_settings($db) {
    $settings = get_platform_settings($db);
    return [
        'provider' => isset($settings['payment_provider']) ? $settings['payment_provider'] : '',
        'appid'    => isset($settings['payment_appid']) ? $settings['payment_appid'] : '',
        'key'      => isset($settings['payment_key']) ? $settings['payment_key'] : '',
    ];
}

/**
 * 获取API密钥（预留）
 * @param PDO $db
 * @return string
 */
function get_platform_api_key($db) {
    return get_platform_setting($db, 'api_access_key', '');
}
?>