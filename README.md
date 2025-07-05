# 代理记账业务管理系统

本系统专为中小代理记账公司开发，致力于帮助企业高效管理客户、合同、服务期、分段、周期性和临时收费，并提供到期提醒、催收提醒等功能。**系统基于 PHP + MySQL，需部署数据库服务器。**

---

## 快速部署

1. **环境要求**
   - PHP 7.4+（建议 8.x），需启用 PDO/MySQLi 扩展
   - MySQL 5.7+ 数据库服务器
   - 支持常见 Web 服务器（Apache/Nginx/IIS/自带 PHP 内置服务器等）

2. **部署步骤**
   - 克隆或下载全部代码。
   - 创建数据库（如 `CREATE DATABASE db_name DEFAULT CHARSET=utf8mb4;`）。
   - 执行根目录下的 `install.sql` 初始化数据库结构。
   - 配置数据库连接（如 `public/config.php`），填写数据库主机、库名、账号密码等信息。
   - 将 `public` 目录设置为 Web 根目录，确保有写入权限（如上传签章/附件等）。
   - 访问 `index.php` 登录并使用系统。

3. **数据库说明**
   - 所有业务表结构见 `install.sql`，包括：`contracts`、`contracts_agreement`、`contract_templates`、`service_periods`、`service_segments`、`payments`、`users` 等。
   - 数据库备份、迁移请用 MySQL 工具。

---

## 目录结构

请参考 [public 目录 GitHub浏览](https://github.com/lanbing1989/saas.wsx.tax/tree/main/public) 获取完整文件列表。
主要入口和功能页面如：

- `index.php`                    # 客户列表页/首页
- `contract_add.php`             # 新增客户
- `contract_edit.php`            # 编辑客户
- `contract_detail.php`          # 客户详情/服务期/临时收费
- `service_period_add.php`       # 新增/续费服务期
- `segment_add.php`              # 服务期分段
- `payment_list.php`             # 周期性收费记录
- `temp_payment.php`             # 临时收费管理
- `expire_remind.php`            # 到期提醒
- `remind_list.php`              # 催收提醒
- `user_manage.php`              # 用户管理
- `export_all_data.php`          # 一键导出所有业务数据
- 合同相关页：`ht_agreements.php`、`ht_agreement_sign.php`、`ht_contract_templates.php` 等

---

## 常见问题

- **如何初始化数据库？**  
  使用 `install.sql` 脚本在 MySQL 上新建所有业务表。
- **如何迁移/备份数据？**  
  使用 MySQL 相关工具（如 mysqldump）操作数据库。
- **是否还支持 SQLite？**  
  当前版本仅支持 MySQL，如需 SQLite 请使用旧版。
- **如果系统提示数据库连接错误？**  
  请检查 `config.php` 数据库配置是否正确、MySQL 服务是否正常。

---

## 许可协议

MIT License

---

> 完整功能、表结构及接口用法请结合 install.sql 和源码注释查阅。如需商业支持或定制开发请联系作者。
