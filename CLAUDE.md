# CLAUDE.md — sr-architects/laravel-scribe-toolkit

> **AI Maintainer Instructions** — อ่านไฟล์นี้ก่อนทำงานทุกครั้ง

## Package Identity

**`sr-architects/laravel-scribe-toolkit`** คือ shared infrastructure package สำหรับ Laravel
ที่ทำหน้าที่ govern OpenAPI documentation pipeline ผ่าน Artisan commands และ Scribe strategy plugins

Package นี้ถูกใช้เป็น **upstream dependency** ของ application จริง
การเปลี่ยนแปลงที่ผิดพลาดมีผลกระทบในวงกว้าง — ให้ treat ทุก PR เหมือนแตะ production

---

## Quality Gates — ต้องผ่านทั้งหมดก่อน push ทุกครั้ง

```bash
composer ci   # PHPStan level 8 → PHPUnit
```

| Gate | เครื่องมือ | เกณฑ์ผ่าน |
|---|---|---|
| Static Analysis | PHPStan level 8 | 0 errors |
| Test Suite | PHPUnit 11 | 0 failures, 0 warnings |
| Line Coverage | PCOV | ≥ 80% (hard floor) |

ถ้า gate ใด gate หนึ่งไม่ผ่าน → ห้าม push

---

## Coverage Policy

### เกณฑ์

| ระดับ | Coverage | ความหมาย |
|---|---|---|
| **Hard Floor** | ≥ 80% | ขั้นต่ำที่ยอมรับได้ — ต่ำกว่านี้ PR ไม่ผ่าน |
| **Target** | ≥ 90% | เป้าหมายที่พยายามรักษา สำหรับ core infrastructure |
| **Ceiling** | ไม่บังคับ 100% | ดูเหตุผลด้านล่าง |

### เหตุผลที่ไม่บังคับ 100%

ตามแนวทางของ Google Engineering และสถาปัตยกรรมระดับสากล:

- กราฟของ **Cost of Test Maintenance** พุ่งแบบ exponential ใน 10% สุดท้าย
- Code ที่ยากจะ cover มักเป็น error path, platform-specific branch, หรือ third-party adapter boundary ที่ test ด้วย unit test แทบไม่ได้ value จริง
- ทรัพยากรที่เสียไปกับ coverage 90% → 100% ควรนำไปลงทุนใน integration test, documentation, หรือ feature ที่สร้าง value แทน
- **Law of Diminishing Returns**: ความเสี่ยงที่ลดลงต่อ 1% coverage ในช่วง 90-100% น้อยกว่าช่วง 70-80% มาก

### สิ่งที่ไม่ควร cover เพื่อให้ถึง 100%

- Trivial getter/setter ที่ไม่มี logic
- Dead code path ที่ framework จัดการให้ (เช่น `boot()` parent call)
- External API boundary ที่ต้อง mock อย่างซับซ้อนจน test ไม่มีความหมาย

---

## Architecture

```
src/
├── Commands/          # Artisan commands — แบ่งเป็น 2 กลุ่ม
│   ├── OpenApi*       # Pipeline commands (openapi:publish, generate, export, ...)
│   └── Scribe*        # Post-processing commands (scribe:add-*, scribe:inject-*)
├── Strategies/        # Scribe extraction strategy plugins
│   ├── NoApiKeyExtractor.php
│   ├── OptionalHeaderExtractor.php
│   ├── OptionalHeaderMetaExtractor.php
│   ├── PerEndpointSecurityGenerator.php
│   └── StripApiPrefix.php
└── ScribeToolkitServiceProvider.php   # Auto-registers commands + injects strategies
```

**Invariants ที่ต้องรักษา:**

- `ScribeToolkitServiceProvider` ต้อง auto-inject strategies 3 ตัว (`NoApiKeyExtractor`, `OptionalHeaderExtractor`, `OptionalHeaderMetaExtractor`) เข้า Scribe config เสมอ
- Commands ที่ delegate ไปยัง command อื่นต้องใช้ `protected runStep()` hook — ห้าม call `Artisan::call()` ตรง (ทำให้ test ไม่ได้)
- Strategy `__invoke()` ทุกตัวต้องมี `@param array<mixed>` และ `@return array<string, mixed>|null`

---

## Test Structure

```
tests/
├── Feature/
│   └── ServiceProviderTest.php    # Registration, config merging, strategy injection
└── Unit/
    ├── Commands/                  # 1 file ต่อ 1 command
    └── Strategies/                # 1 file ต่อ 1 strategy
```

### Pattern สำหรับ Command tests

Commands ที่ delegate ใช้ **stub subclass pattern** แทน Artisan mocking
(Testbench v10 มี `final` Kernel ทำให้ `Artisan::shouldReceive()` ใช้ไม่ได้):

```php
class StubMyCommand extends MyCommand
{
    public array $calls = [];
    public array $returnCodes = [];

    protected function runStep(string $command): int
    {
        $this->calls[] = $command;
        return $this->returnCodes[$command] ?? 0;
    }
}
```

Commands ที่ทำงานกับ YAML file โดยตรงใช้ `$this->artisan()` ผ่าน Testbench ได้ปกติ

### Helper methods ใน TestCase

```php
$this->writeYaml(array $spec)   // เขียน openapi.yaml ใน temp path
$this->readYaml(): array        // อ่านกลับมาหลัง command รัน
$this->minimalSpec(string $description = ''): array  // spec skeleton ขั้นต่ำ
```

### Strategy tests

ใช้ `$this->app->make(StrategyClass::class)` แทน `new StrategyClass()` เสมอ
(constructor ต้องการ `DocumentationConfig` ที่ต้อง resolve จาก container)

---

## PHPStan Rules

- Level: **8** (strict)
- `preg_replace()` ต้อง fallback: `?? $original`
- `file_get_contents()` ต้องเช็ค `=== false` ก่อนใช้
- `$this->option()` คืน `mixed` — ใช้ `is_string()` guard แทน cast ตรง
- `Route::getRoutes()` คืน `RouteCollectionInterface` — ต้องต่อ `.getRoutes()` เพื่อได้ iterable
- Override method ใน subclass ต้องไม่ annotate `$groupedEndpoints` ถ้า parent type เป็น shape tuple

---

## Commit Rules

- ห้ามใส่ `Co-Authored-By: Claude` หรือ AI attribution ใด ๆ ในทุก commit
- Commit message เขียนเป็น imperative mood, ภาษาอังกฤษ
- ถ้าแก้ bug ให้ระบุสาเหตุใน body ไม่ใช่แค่อาการ

---

## Commands Reference

```bash
composer analyse          # PHPStan level 8
composer test             # PHPUnit (no coverage)
composer test:coverage    # PHPUnit + PCOV → coverage/html/
composer ci               # analyse + test (ใช้ก่อน push เสมอ)
```

Coverage driver: **PCOV** (ติดตั้งผ่าน `brew install shivammathur/extensions/pcov@8.4`)
