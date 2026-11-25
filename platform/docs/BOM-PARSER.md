# BOM CSV 解析（v0.3.0）

> 首版支持 CSV（逗号/制表符/分号），不支持 `.xlsx`。Excel 解析需要 PhpSpreadsheet/队列，列入下一阶段。

## 1. 支持格式

```csv
mpn,qty,desc
TE 170364-1,200,4Pin 连接器
GRM188R71H104KA93D,5000,0402 电容
```

也支持：

- 中文表头：`型号,数量,描述`
- Tab 分隔 / 分号分隔
- UTF-8 BOM
- 无表头三列：`型号,数量,描述`
- 引号内逗号：`TE,200,"conn, 4pin"`

## 2. 限制

| 项 | 限制 |
|---|---|
| 文件大小 | ≤1MB |
| 行数 | ≤500 行 |
| 必填 | 型号/MPN、数量 |
| 数量 | 数字且 >0 |
| 编码 | UTF-8 |

## 3. API

`POST /api/v1/bom/parse`

```json
{ "csv": "mpn,qty,desc\nTE,200,conn\n" }
```

成功：

```json
{
  "data": {
    "lines": [{"mpn":"TE","qty":200,"desc":"conn","line":2}],
    "errors": [],
    "count": 1,
    "delimiter": ","
  },
  "meta": {"request_id":"req_..."},
  "error": null
}
```

失败（422）：

```json
{
  "data": null,
  "error": {
    "code": "BOM_PARSE_FAILED",
    "message": "BOM 解析有错误",
    "details": {"errors":[{"line":2,"message":"数量必须大于 0"}]}
  }
}
```

## 4. 代码

- `app/Domain/BomCsvParser.php`
- `tests/bom.php`（11 条）
- `tests/api.php`（含 parse 契约）
- `app/Api.php`（`/api/v1/bom/parse`）

## 5. 生产 TODO

1. `.xlsx` 用 PhpSpreadsheet 读取首个 sheet，限制 500 行/10MB。
2. 上传先落对象存储，再入队列解析；请求内只创建 job。
3. 病毒扫描/公式注入过滤；不执行宏。
4. 列映射 UI：让用户确认“哪列是 MPN/数量/描述”。
5. 解析结果与原始文件哈希一起存，支持追溯。
