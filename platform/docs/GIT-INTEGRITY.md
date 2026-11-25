# Git/旧代码完整性

> 只读检查，未执行 `git reset`/`clean`/`checkout -- .`。

## 实测（2026-11-24 20:20）

```text
git -C material diff --stat
 docs/plm/PLM-GB.pdf.crdownload | Bin 862922 -> 0 bytes
 1 file changed, 0 insertions(+), 0 deletions(-)

git -C material status --short
 D docs/plm/PLM-GB.pdf.crdownload
?? database/hardware.sql
?? platform/
```

## 结论

- 唯一 tracked 变更：`docs/plm/PLM-GB.pdf.crdownload` 已删除（会话开始前已存在，**未触碰**）。
- `database/hardware.sql` 是会话开始前未跟踪文件，**未触碰**。
- `platform/` 是本任务新增目录，未修改旧 `material/` 代码。
- `.git`、`class/mysql.php`、`material.php`、`header.php` 均存在。
- 没有执行任何删除旧代码/仓库历史的命令。
- 所有 release/迁移/测试库保留，不删除事故现场。

## 回归

- `verify.php` 的 legacy 检查：
  - `class/mysql.php`
  - `class/page.php`
  - `class/login.php`
  - `class/database.php`
  - `material.php`
  - `header.php`
  - `login.php`
  - `register.php`
  - `.git`
- 每次 `release-check.php` 都会重新检查。
