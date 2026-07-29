# Implementation Plan — University Module Refactoring

## المشاكل المحددة وحلولها

---

## 1. إزالة try/catch من الكونترولر

### المشكلة
الكونترولر يمسك `AuthorizationException` و`Throwable` بنفسه ويرجع JSON يدوياً.  
هذا الأسلوب:
- يخفي الـ stack trace عن Laravel Exception Handler
- يمنع الـ request lifecycle من المشي بشكل طبيعي (logging، monitoring، etc.)
- يكرر نفس الكود في كل method

### الحل
**نحذف كل try/catch من الكونترولر** ونترك Laravel يتعامل مع:
- `AuthorizationException` → تحولها تلقائياً إلى 403
- `ModelNotFoundException` → تحولها إلى 404  
- أي `Throwable` → Exception Handler ينتج 500

**نعدل `App\Exceptions\Handler`** (أو `bootstrap/app.php`) لنضيف JSON formatting موحد لكل الأخطاء.

---

## 2. نقل منطق الفلترة إلى `UniversityQueryBuilder`

### المشكلة
منطق الفلترة `->when(name)->when(country)` موجود داخل الـ Action مباشرة.  
الـ Action المفروض يأخذ بيانات جاهزة ويرجع نتيجة — مو يبني query conditions.

### الحل
ننشئ `App\Models\Builders\UniversityQueryBuilder` يمتد `Illuminate\Database\Eloquent\Builder`  
ونضيف عليه methods مخصصة:

```php
// app/Models/Builders/UniversityQueryBuilder.php
class UniversityQueryBuilder extends Builder
{
    public function filterByName(?string $name): static
    public function filterByCountry(?string $country): static
    public function withoutTenantScope(): static   // يشيل UniversityScope
}
```

**University model** يرجع هذا الـ Builder بدل Builder الافتراضي:
```php
public function newEloquentBuilder($query): UniversityQueryBuilder
{
    return new UniversityQueryBuilder($query);
}
```

**ListUniversities Action** يصير نظيف:
```php
public function handle(int $perPage, array $filters = []): LengthAwarePaginator
{
    return University::query()
        ->withoutTenantScope()
        ->filterByName($filters['name'] ?? null)
        ->filterByCountry($filters['country'] ?? null)
        ->paginate($perPage);
}
```

---

## 3. إصلاح `LaravelUniversityContext` — مشكلة `uni_admin`

### المشكلة
الكود الحالي يبحث فقط عن `alumniProfile` أو `studentProfile`.  
الـ `uni_admin` ليس له profile بهذا الشكل — university_id موجود في جدول `university_admins`.  
النتيجة: `university_id = null` → الـ scope يرجع `whereNull(id)` → يُظهر صفر جامعات.

### الحل
نضيف `universityAdmin` relation على الـ User model، ونعدل `getUniversityId()` يفحص مصدر ثالث:

```php
// ترتيب الفحص في getUniversityId():
// 1. alumniProfile → major → faculty → university_id
// 2. studentProfile → major → faculty → university_id
// 3. universityAdmin → university_id  ← الجديد
```

---

## 4. إصلاح `LaravelUniversityContext` — مشكلة Lazy Loading

### المشكلة
`$profile?->major?->faculty?->university_id` يعمل Lazy Loading لـ 3 علاقات متسلسلة.  
مع وجود الـ Cache لمدة ساعة هذا يصير مرة واحدة لكن داخل الـ Cache callback نفسه 3 queries إضافية.

### الحل
نستخدم **query مباشر بـ join** بدل السير عبر العلاقات:

```php
// بدل:
$profile->loadMissing('major.faculty');
return $profile->major?->faculty?->university_id;

// نستخدم:
$universityId = AlumniProfile::query()
    ->where('user_id', $user->id)
    ->join('majors', 'majors.id', '=', 'alumni_profiles.major_id')
    ->join('faculties', 'faculties.id', '=', 'majors.faculty_id')
    ->value('faculties.university_id');
```

**query واحد بدل 3** — ويرجع `null` مباشرة إذا ما في profile.

---

## الملفات المتأثرة

### [MODIFY] [UniversityController.php](file:///c:/laragon/www/Alumni-Network/app/Http/Controllers/UniversityController.php)
- حذف جميع try/catch blocks
- حذف imports غير مستخدمة (`AuthorizationException`, `Throwable`, `NotFoundHttpException`)

### [NEW] [UniversityQueryBuilder.php](file:///c:/laragon/www/Alumni-Network/app/Models/Builders/UniversityQueryBuilder.php)
- `filterByName(?string $name): static`
- `filterByCountry(?string $country): static`
- `withoutTenantScope(): static`

### [MODIFY] [University.php](file:///c:/laragon/www/Alumni-Network/app/Models/University.php)
- إضافة `newEloquentBuilder()` يرجع `UniversityQueryBuilder`

### [MODIFY] [ListUniversities.php](file:///c:/laragon/www/Alumni-Network/app/V1/Actions/University/ListUniversities.php)
- استخدام `UniversityQueryBuilder` methods بدل `when()` الـ inline

### [MODIFY] [User.php](file:///c:/laragon/www/Alumni-Network/app/Models/User.php)
- إضافة `universityAdmin(): HasOne` relation

### [MODIFY] [LaravelUniversityContext.php](file:///c:/laragon/www/Alumni-Network/app/Services/LaravelUniversityContext.php)
- استبدال Lazy Loading بـ single JOIN query
- إضافة فحص `university_admins` كمصدر ثالث لـ `university_id`

---

## ملاحظة مهمة

> [!IMPORTANT]
> الـ `GetUniversity` action فيه try/catch لكنه فارغ تماماً (ما بيعمل شي غير `return $university`).  
> هل تريد حذف هذا الـ action بالكامل واستخدام Route Model Binding مباشرة في الكونترولر؟  
> أو نبقيه للتوسع المستقبلي؟

> [!NOTE]
> الـ Actions الأخرى (Create, Update, Delete) عندها try/catch + Log + rethrow.  
> هذا النمط صحيح — الـ logging هناك منطقي لأنه يضيف context (university_id, payload, etc.) قبل rethrow.  
> سنبقيه كما هو.

---

## خطة التحقق
1. `php artisan route:list` — التأكد من عدم وجود أخطاء
2. `php artisan test` — تشغيل الاختبارات الموجودة
3. اختبار يدوي: طلب بـ `uni_admin` — يجب أن يرجع جامعته فقط
4. اختبار يدوي: طلب بـ `super_admin` — يجب أن يرجع كل الجامعات
5. اختبار يدوي: طلب بـ guest — يجب أن يرجع كل الجامعات (public endpoint)
