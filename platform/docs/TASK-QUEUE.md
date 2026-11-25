# 物格云 · 连续开发任务队列

- 模式：**队列执行**（不是定时任务）
- 规则：每轮只取第一个 `PENDING`；完成后改为 `DONE`，立即进入下一个；不等待用户。
- 每轮必须：代码/测试/数据/文档至少一项 + `checkpoint.php` + `verify.php` + `release-check.php` + git tag（不再复制 release）。
- 约束：不删除/不改写旧 `material/`、`.git`、旧库 `materiel`；旧 release 副本已删除，版本只由 git tag 追踪。
- 当前基线：v0.58.2 最终收口（92 表 / verify 344 / release-check 48；最终 release 见 `RELEASE` + git tag）。

## 队列状态

| ID | 任务 | 状态 | 验收 |
|---|---|---|---|
| Q01 | 点评防刷：一单一评、同用户/同 listing 去重、频率限制 | DONE | `reviewer_key` 唯一、5/小时、9 条策略测试 |
| Q02 | 点评审核工作流：pending→visible/hidden，审核备注，商家申诉 | DONE | 审核/申诉/审计已上线，9 条测试 |
| Q03 | 信用历史：信用曲线、等级变更、事件时间线 | DONE | 详情/商城展示曲线，6 条测试 |
| Q04 | 商家回复 + 评分分布 | DONE | `review_reply`、5–1 星分布已上线 |
| Q05 | 商家入驻/KYC：资质、认证状态、保证金/协议字段 | DONE | 卖家/运营 KYC 页 + 9 条测试 |
| Q06 | 图片上传：本地/对象存储抽象、主图/排序/病毒扫描接口 | DONE | 8 条测试 + 8095 实测 |
| Q07 | 商家数据隔离：卖家只看自己 store 的物料/点评 | DONE | MerchantContext + 8 条隔离测试 |
| Q08 | 45 表字段映射：material/listing/store 全字段映射与校验 | DONE | 33 列映射/9 条测试/审计 0 缺口 |
| Q09 | 支付/分账生产化：沙箱、回调验签、对账、退款 | DONE | 沙箱 E2E + 10 条测试；不真实收款 |
| Q10 | 结算/发票/佣金规则可配置 | DONE | 版本化规则/11 条测试/后台试算 |
| Q11 | API 鉴权/限流：token、scope、速率 | DONE | 11 条测试 + live 401/200 |
| Q12 | 性能/无障碍/真机：Lighthouse/axe/500/390 | DONE | 10 条 a11y + 报告；真机未做 |
| Q13 | 部署/监控/备份：Nginx/FPM、健康检查、备份演练 | DONE | healthcheck + 162KB 备份 + 11 条部署测试 |
| Q14 | 迁移纳入 007–013：生成器/runner/重建验证 | DONE | 新库 92 表，12 条测试 |
| Q15 | 全量回归：29+ 测试、E2E、live | DONE | regression 18 PASS + release READY |
| Q16 | 验收/CLOSURE：Go/No-Go、风险、签字 | DONE | 最终 CLOSURE + ACCEPTANCE |

## 已完成基线（不再执行）

- UI/领域/API/PDO/迁移/77 基础表
- 卖家提报/审核/幂等/审计
- DB 认证/限流/安全头
- 图片/溯源/评论树（首版）
- 点评/物料信用/商家信用/商城/商家后台
- 商家回复/评分分布/待审点评
- 测试：credit 9 / storefront 8 / comment-tree 7 / review-reply 6 / reputation-live 16

## 每轮固定动作

```powershell
php material\platform\bin\checkpoint.php --with-tests
php material\platform\bin\verify.php
$env:DB_USER='patent'; $env:DB_PASS='patent'; $env:DB_NAME='mats_platform_next2'
php material\platform\bin\release-check.php
php material\platform\bin\snapshot.php   # 仅更新 manifest/RELEASE
php material\platform\bin\git-release.php # git tag
```

## 下一轮

Q16 已完成；队列无 PENDING，目标收口。
