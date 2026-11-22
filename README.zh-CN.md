# Mloun Bulk Category Creator for WooCommerce（简体中文）

从简单的文本列表批量创建 WooCommerce 商品分类、品牌和全局属性值，自动跳过已存在的条目。

本仓库是**免费版**源码（v1.2.1），与 WordPress.org 上架的代码一致。

[English](README.md) | **简体中文**

- WordPress.org：<https://wordpress.org/plugins/mloun-bulk-category-creator-for-woocommerce/>
- 作者：[Mloun](https://mloun.com)
- 许可协议：GPLv2 或更高版本

## 环境要求

| 项目 | 版本 |
|---|---|
| WordPress | 6.5 或更高 |
| WooCommerce | 6.0 或更高（通过 `Requires Plugins: woocommerce` 声明依赖） |
| PHP | 7.4 或更高 |

## 功能特性

- **批量创建商品分类** —— 完整支持多级层级。行内写的父级优先；"父级分类"下拉框只作为未指定父级的行的兜底。
- **批量创建品牌** —— 兼容 Perfect WooCommerce Brands（`pwb-brand`）、YITH WooCommerce Brands（`yith_product_brand`）和 WooCommerce Brands（`product_brand`）；其他品牌插件可通过过滤器注册自己的分类法。
- **批量创建全局属性及属性值** —— 写入属性值之前会先注册 `pa_*` 分类法，属性类型按 WooCommerce 白名单校验。
- **跳过重复项** —— 分类和品牌按 **父级 + 名称/Slug** 匹配，因此不同父级下的同名子分类可以共存，不会被误判为重复。已存在的条目不会被覆盖。
- **三态结果** —— 每一行都会报告为 `创建成功`、`已跳过`（已存在）或 `失败`（并给出原因，例如父级不存在）。
- **分批 AJAX 处理** —— 大数据量按可配置批次发送（默认每批 50 条、间隔 1000 毫秒），避免触发 `max_execution_time` 超时。
- **设置页** —— 批次大小、批次间隔、可选的日志文件（写入 `uploads/`），全部基于 Settings API。
- **可翻译** —— 英文界面，附带 `languages/*.pot`；翻译通过 translate.wordpress.org 管理。

## 安装

1. 在后台 **插件 → 安装插件** 中搜索 "Mloun Bulk Category Creator" 安装，或上传 zip 安装包。
2. 启用插件。WooCommerce 必须已启用 —— 插件已声明 `Requires Plugins: woocommerce`。
3. 在后台菜单中打开 **Bulk Add Categories**。

## 数据格式

每行一条，字段之间用竖线 `|` 分隔。空行、以及以 `#` 或 `//` 开头的行会被忽略。

**分类** —— `名称 | 父级 | 描述 | Slug`

```
Electronics
Mobile Phones | Electronics | Smartphones and tablets | mobile-phones
Laptops | Electronics | Notebook computers | laptops
Smart Watches
Headphones
```

**品牌** —— `名称 | 描述 | Slug`

```
Apple | Apple Inc. | apple
Samsung | Samsung Electronics | samsung
```

**属性** —— `名称 | 属性值（逗号分隔） | 类型 | Slug`

```
Color | Red,Blue,Black,White | select | color
Size | S,M,L,XL,XXL | select | size
Material | Cotton,Polyester,Wool,Silk | select | material
```

支持的属性类型：`select`、`text`、`textarea`、`number`、`email`、`color`、`date`、`url`。

## 结果状态

| 状态 | 含义 |
|---|---|
| 创建成功 | 条目（或新的属性值）已写入 |
| 已跳过 | 条目已存在 —— 不会覆盖任何内容 |
| 失败 | 真实错误：名称为空、父级不存在、数据库错误等 |

父级写错永远记为失败。插件绝不会悄悄把子项创建到顶级。

## 开发者钩子

| 钩子 | 类型 | 用途 |
|---|---|---|
| `bulk_add_cat_process_types` | filter | 注册额外的操作类型 |
| `bulk_add_cat_max_items_per_request` | filter | 单次请求可接受的条目上限（默认 2000） |
| `bulk_add_cat_brand_taxonomies` | filter | 注册其他品牌插件的分类法 |
| `bulk_add_cat_category_created` | action | 分类创建完成后触发 |
| `bulk_add_cat_brand_created` | action | 品牌创建完成后触发 |
| `bulk_add_cat_attribute_created` | action | 全局属性创建完成后触发 |

## 更新日志

完整记录见 [`readme.txt`](readme.txt)。近期版本：

- **1.2.1** —— Slug 改为 `mloun-bulk-category-creator-for-woocommerce`（WordPress.org 只允许 WooCommerce 商标以 `for-woocommerce`、`with-woocommerce`、`using-woocommerce` 或 `and-woocommerce` 的形式出现在 slug 中）。
- **1.2.0** —— 按 WordPress.org 审核意见更名为 *Mloun Bulk Category Creator for WooCommerce*；新增 `Requires Plugins: woocommerce` 头；移除 `load_plugin_textdomain()` 与随附的 `.po`/`.mo`（翻译改由 translate.wordpress.org 管理）。
- **1.1.7** —— 文本域与 WordPress.org slug 对齐。
- **1.1.6** —— 行内指定的父级永远优先；Plugin Check 加固（用 `WP_Filesystem` 替代 `rmdir()`/`unlink()`/`file_put_contents()`）。
- **1.1.5** —— 修复重复检测：分类与品牌按"父级 + 名称/Slug"匹配。
- **1.1.4** —— 修复卸载时的致命错误，以及后台样式在子菜单页被跳过的问题。

## PRO 版

PRO 版增加了：CSV/Excel 导入与列映射、Dry Run 预演、更新模式（upsert）、按批次快照的一键撤销、图片/Logo 批量上传，以及自动更新 —— 详见 <https://mloun.com>。

## 许可协议

GNU General Public License v2.0 或更高版本 —— 见 [LICENSE](LICENSE)。
