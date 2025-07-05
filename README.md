# 代理记账客户管理系统 SaaS版

本系统专为中小代理记账公司 SaaS 场景开发，致力于帮助企业高效管理客户、合同、服务期、分段、周期性和临时收费，提供到期提醒、催收提醒、电子合同等功能。**系统基于 PHP + MySQL，需部署数据库服务器。** 支持多租户，适用于多分公司/多团队/做 SaaS 平台的需求。

---

## 主要区别（SaaS 版 vs 单体版）

- **多租户支持**：每个租户（公司/团队）数据完全隔离，支持平台级管理和租户自助。
- **平台管理功能**：支持租户/套餐/平台管理员/运营统计/租户日志等管理。
- **适用场景**：SaaS 版适合搭建为多家机构或多团队在线服务平台，单体版适合单公司自用。
- **用户结构**：有平台管理员、租户管理员和普通租户用户多级角色体系。
- **所有主业务表均含 `tenant_id` 字段，租户逻辑强隔离。**

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
   - 访问 `public/index.php` 登录并使用系统（支持租户注册、租户/平台分离入口）。

3. **数据库说明**
   - 所有业务表结构见 `install.sql`，包括但不限于：
     - `tenants`（租户）
     - `users`（用户，含平台与租户用户）
     - `contracts`（客户/合同）
     - `contracts_agreement`（合同签署/协议）
     - `contract_templates`（合同模板）
     - `service_periods`（服务期）
     - `service_segments`（分段）
     - `payments`（收费记录）
     - `tenant_packages`（租户套餐）
     - `tenant_orders`（租户订单）
     - `tenant_logs`（租户日志）
   - 数据库备份、迁移请用 MySQL 工具（如 mysqldump）。

---

## 目录结构

主要入口和常用功能页面（所有路径均以 `public/` 目录为根）：

- `index.php`                    # 客户列表页/首页（租户维度）
- `contract_add.php`             # 新增客户
- `contract_edit.php`            # 编辑客户
- `contract_detail.php`          # 客户详情/服务期/临时收费
- `service_period_add.php`       # 新增/续费服务期
- `segment_add.php`              # 服务期分段
- `payment_list.php`             # 周期性收费记录
- `temp_payment.php`             # 临时收费管理
- `expire_remind.php`            # 到期提醒
- `remind_list.php`              # 催收提醒
- `user_manage.php`              # 用户管理（租户管理员可见）
- `export_all_data.php`          # 一键导出所有业务数据
- 合同相关页：`ht_agreements.php`、`ht_agreement_sign.php`、`ht_contract_templates.php`、`ht_seal_templates.php` 等
- 平台管理页（平台管理员可见）：`platform_report.php`、`platform_tenant_manage.php`、`platform_package_manage.php`、`platform_admin_manage.php`、`platform_log_all.php` 等

> **注意：**  
> 原 README 里的“public 目录 GitHub 浏览”推荐链接为  
> `https://github.com/lanbing1989/Accounting-Agency-Customer-Management-System-Saas/tree/main/public`  
> 建议直接使用本仓库下的 public 目录浏览或以上述路径为准。

---

## 主要功能

- **多租户管理**：平台可同时服务多家代理记账公司/分支团队，数据严格隔离。
- **客户/合同/服务期/分段/收费管理**：同单体版，满足代理记账公司全流程管理需求。
- **合同电子签署**：支持客户在线签署合同，自动生成签名、盖章、查验二维码等。
- **租户套餐/订单/续费/升级管理**：支持套餐购买、续费、升级及分账。
- **平台统计与运营**：内置租户、客户、合同、收入等运营数据统计与分析。
- **租户日志/操作审计**：支持平台和租户层级的操作日志与溯源。
- **权限体系**：区分平台管理员、租户管理员、租户普通用户，权限灵活。
- **高可扩展性**：各业务表结构和页面支持自定义扩展字段，适配不同业务。

---

## 常见问题

- **如何初始化数据库？**  
  使用 `install.sql` 脚本在 MySQL 上新建所有业务表。
- **如何迁移/备份数据？**  
  使用 MySQL 工具（如 mysqldump）操作数据库。
- **是否还支持 SQLite？**  
  当前版本仅支持 MySQL，如需 SQLite 请使用旧版。
- **系统提示数据库连接错误怎么办？**  
  请检查 `public/config.php` 数据库配置是否正确、MySQL 服务是否正常。
- **如何区分平台管理员/租户管理员/租户用户？**  
  用户表有 role 字段和 tenant_id 字段，平台管理员 tenant_id=1，普通租户用户 tenant_id>1。
- **如何扩展字段或功能？**  
  直接修改数据库表结构和页面代码，系统结构清晰，易于扩展。
- **如何进行多租户的隔离？**  
  所有主表带 tenant_id 字段，平台端和租户端入口/数据/权限完全区分。

---

## 许可协议

MIT License

---

> 完整功能、表结构及接口用法请结合 install.sql 和源码注释查阅。如需商业支持或定制开发请联系作者。
