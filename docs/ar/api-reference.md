# مرجع الواجهة البرمجية

تسرد هذه الصفحة الدوال العامة التي توفرها الحزمة. كل دالة مشروحة في سطر واحد.

## `ArabicSupport\Arabic`

### الإنشاء والواجهة السلسة

- `text(string $text): ArabicText` — ينشئ كائن `ArabicText` لاستخدام العمليات بأسلوب fluent.

### التنظيف والتعقيم

- `clean(string $text): string` — يزيل HTML ومحارف Unicode الخطرة والمسافات الزائدة مع الحفاظ على نص مقروء.
- `sanitize(string $text, ?ArabicPolicy $policy = null, bool $stripDiacritics = false, bool $stripTatweel = true, bool $lowercase = false, bool $keepPunctuation = true): string` — يعقّم النص بخيارات صريحة دون فرض تطبيع البحث.
- `sanitizePlain(string $text, bool $keepPunctuation = true): string` — ينتج نصًا مقروءًا بلا HTML أو تشكيل أو تطويل مع الحفاظ على الإملاء.
- `sanitizeForSearch(string $text): string` — يعقّم النص ويطبّعه بقوة للبحث والمقارنة.
- `stripHtml(string $text): string` — يزيل وسوم HTML ويفك ترميز الكيانات.
- `normalizeWhitespace(string $text, bool $preserveNewLines = false): string` — يضبط المسافات مع إمكانية الحفاظ على الأسطر.
- `normalizeInlineWhitespace(string $text): string` — يحوّل كل المسافات والأسطر إلى مسافات داخلية واحدة.
- `deepTrim(string $text): string` — يزيل المسافات ومحارف Unicode الخفية من طرفي النص.

### التطبيع

- `normalize(string $text, ArabicPolicy $policy = ArabicPolicy::Display): string` — يطبّع النص حسب سياسة واضحة: Strict أو Display أو Slug أو Search أو Security.
- `searchKey(string $text): string` — ينشئ مفتاحًا مطبعًا للبحث وأعمدة المقارنة.
- `normalizeLetters(...): string` — يطبّع صور الحروف العربية بخيارات دقيقة لكل تحويل.
- `stripDiacritics(string $text, bool $includeQuranMarks = true): string` — يزيل التشكيل العربي مع خيار إزالة العلامات القرآنية.
- `stripTatweel(string $text): string` — يزيل التطويل/الكشيدة.
- `stripeTatweel(string $text): string` — اسم قديم متوافق مع `stripTatweel()`.

### الروابط و ASCII

- `slug(string $text, SlugMode $mode = SlugMode::Unicode, string $separator = '-', int $maxWords = 8, int $maxLength = 180): string` — ينشئ slug عربيًا أو ASCII حسب الوضع المختار.
- `unicodeSlug(string $text, string $separator = '-', int $maxWords = 8, int $maxLength = 180): string` — ينشئ رابطًا عربيًا مقروءًا يحافظ على الهوية الإملائية.
- `asciiSlug(string $text, string $separator = '-', int $maxWords = 8, int $maxLength = 180): string` — ينشئ رابطًا ASCII فقط بالترميز اللاتيني التقريبي.
- `toAscii(string $text, bool $normalize = true): string` — يحوّل النص العربي إلى تقريب لاتيني/ASCII.

### الأرقام

- `digitsToLatin(string $text): string` — يحوّل الأرقام العربية والفارسية إلى أرقام لاتينية.
- `digitsToArabicIndic(string $text): string` — يحوّل الأرقام إلى العربية الهندية.
- `digitsToEasternArabic(string $text): string` — يحوّل الأرقام إلى الفارسية/العربية الشرقية.
- `normalizeDigits(string $text, DigitSet $target = DigitSet::Latin): string` — يحوّل الأرقام إلى مجموعة الأرقام المختارة.

### القوائم والملفات والأسماء والترقيم

- `stripOrderedListPrefixes(string $text): string` — يزيل ترقيم القوائم من بداية كل سطر.
- `safeFilename(string $filename, string $separator = '-'): string` — ينشئ اسم ملف آمنًا مع الحفاظ على العربية المقروءة.
- `name(string $name, int $maxWords = 8, bool $applyCorrections = true, bool $normalizeAlefMaqsura = false): string` — يطبّع اسمًا عربيًا عامًا للعرض بتحفظ.
- `fixPunctuation(string $text): string` — يضبط المسافات حول علامات الترقيم العربية واللاتينية.
- `normalizeConjunctionWaw(string $text): string` — يضبط واو العطف المستقلة مثل `و أحمد` إلى `وأحمد`.

### الطول والقص والمقتطفات

- `length(string $text, LengthUnit $unit = LengthUnit::Grapheme): int` — يحسب الطول بوحدة grapheme أو Unicode أو byte.
- `graphemeLength(string $text): int` — يحسب عدد الحروف المرئية للمستخدم.
- `unicodeLength(string $text): int` — يحسب عدد نقاط Unicode.
- `byteLength(string $text): int` — يحسب عدد بايتات UTF-8.
- `substr(string $text, int $start, ?int $length = null, LengthUnit $unit = LengthUnit::Grapheme): string` — يقتطع النص حسب وحدة الطول المختارة.
- `limit(string $text, int $limit, LengthUnit $unit = LengthUnit::Grapheme, string $end = '...'): string` — يقصر النص لطول نهائي لا يتجاوز الحد، مع احتساب اللاحقة.
- `excerpt(string $htmlOrText, int $limit = 200, string $end = ' ...'): string` — ينشئ مقتطفًا نظيفًا دون قطع الكلمات.

### الأمان والفحص

- `removeInvisible(string $text): string` — يزيل zero-width وbidi controls ومحارف التحكم غير الآمنة.
- `removeBidiControls(string $text): string` — يزيل محارف التحكم في اتجاه النص.
- `securityClean(string $text): string` — يزيل محارف Unicode الخفية والحساسة أمنيًا.
- `containsArabic(string $text): bool` — يتحقق من وجود نص عربي.
- `isArabic(string $text): bool` — يتحقق أن كل الحروف في النص عربية.
- `arabicRatio(string $text): float` — يعيد نسبة الحروف العربية إلى مجموع الحروف.
- `inspect(string $text): array` — يعيد تشخيصًا عن العربية والتشكيل والأرقام وHTML وUnicode المشبوه.
- `containsBadWords(string $text, array|string|null $words = null): bool` — يفحص النص ضد كلمات محظورة من array أو TXT أو JSON أو مصدر مختلط.

## `ArabicSupport\ArabicText`

- `make(string $text): self` — ينشئ pipeline نصيًا.
- `value(): string` — يعيد القيمة الحالية.
- `__toString(): string` — يحوّل الكائن إلى النص الحالي.
- `stripHtml(): self` — يزيل HTML من القيمة الحالية.
- `clean(): self` — ينظف القيمة الحالية بأمان.
- `sanitize(...): self` — يعقم القيمة الحالية بخيارات صريحة.
- `sanitizePlain(bool $keepPunctuation = true): self` — يطبق تعقيم النص المقروء.
- `sanitizeForSearch(): self` — يطبق تعقيم البحث.
- `normalizeWhitespace(bool $preserveNewLines = false): self` — يضبط المسافات.
- `normalizeInlineWhitespace(): self` — يحوّل المسافات إلى سطر واحد.
- `stripDiacritics(bool $includeQuranMarks = true): self` — يزيل التشكيل من القيمة الحالية.
- `stripTatweel(): self` — يزيل التطويل من القيمة الحالية.
- `normalize(ArabicPolicy $policy = ArabicPolicy::Display): self` — يطبق سياسة تطبيع على القيمة الحالية.
- `searchKey(): string` — يعيد مفتاح بحث من القيمة الحالية.
- `stripOrderedListPrefixes(): self` — يزيل ترقيم القوائم من القيمة الحالية.
- `fixPunctuation(): self` — يضبط الترقيم في القيمة الحالية.
- `securityClean(): self` — يزيل محارف Unicode الحساسة أمنيًا.
- `slug(...): string` — يعيد slug من القيمة الحالية.
- `unicodeSlug(...): string` — يعيد slug عربيًا مقروءًا.
- `asciiSlug(...): string` — يعيد slug بنمط ASCII.
- `excerpt(int $limit = 200, string $end = ' ...'): string` — يعيد مقتطفًا من القيمة الحالية.
- `limit(int $length, LengthUnit $unit = LengthUnit::Grapheme, string $end = '...'): self` — يقصر القيمة الحالية مع بقاء السلسلة قابلة للمتابعة.

## الأصناف المتخصصة

### التنظيف

- `OrderedListPrefixStripper::strip(string $text): string` — يزيل ترقيم القوائم من كل سطر.
- `TextCleaner::stripHtml(string $text, bool $preserveBlockSpaces = true): string` — يزيل HTML مع الحفاظ على حدود الكلمات.
- `TextCleaner::keepTextCharacters(string $text, string $extra = '', bool $keepPunctuation = true, string $replacement = ''): string` — يحتفظ بالحروف والأرقام والمسافات وعلامات مختارة.
- `TextCleaner::keepSlugCharacters(string $text): string` — يحتفظ بما يصلح قبل إنشاء slug.
- `TextCleaner::clean(string $text): string` — ينظف HTML ومحارف Unicode الخفية والمسافات.
- `TextCleaner::sanitize(...): string` — يعقم النص بخيارات قابلة للتخصيص.
- `TextCleaner::sanitizePlain(string $text, bool $keepPunctuation = true): string` — ينتج نصًا مقروءًا دون تشكيل أو تطويل.
- `TextCleaner::sanitizeForSearch(string $text): string` — ينتج نصًا مناسبًا للبحث.
- `TextCleaner::normalizeWhitespace(string $text, bool $preserveNewLines = false): string` — يضبط المسافات.
- `TextCleaner::normalizeInlineWhitespace(string $text): string` — يضبط المسافات كسطر واحد.
- `TextCleaner::deepTrim(string $text, bool $preserveNewLines = false): string` — يقص المسافات ومحارف التحكم من الطرفين.
- `UnicodeSecurityCleaner::removeBidiControls(string $text): string` — يزيل محارف اتجاه النص.
- `UnicodeSecurityCleaner::removeZeroWidthCharacters(string $text): string` — يزيل محارف zero-width.
- `UnicodeSecurityCleaner::removeControlCharacters(string $text, bool $keepNewLines = true): string` — يزيل محارف التحكم مع خيار حفظ الأسطر.
- `UnicodeSecurityCleaner::removeInvisibleCharacters(string $text, bool $keepNewLines = true): string` — يزيل المحارف الخفية والحساسة أمنيًا.
- `UnicodeSecurityCleaner::clean(string $text, bool $keepNewLines = true): string` — اختصار لإزالة المحارف الخفية والحساسة.
- `UnicodeSecurityCleaner::hasBidiControls(string $text): bool` — يكشف محارف اتجاه النص.
- `UnicodeSecurityCleaner::hasZeroWidthCharacters(string $text): bool` — يكشف محارف zero-width.
- `UnicodeSecurityCleaner::hasControlCharacters(string $text, bool $keepNewLines = true): bool` — يكشف محارف التحكم.
- `UnicodeSecurityCleaner::hasInvisibleCharacters(string $text, bool $keepNewLines = true): bool` — يكشف المحارف الخفية والحساسة.
- `UnicodeSecurityCleaner::hasSuspiciousUnicode(string $text, bool $keepNewLines = true): bool` — يكشف Unicode مشبوهًا.
- `WhitespaceNormalizer::normalize(string $text, bool $preserveNewLines = false): string` — يضبط المسافات مع خيار حفظ الأسطر.
- `WhitespaceNormalizer::normalizeInline(string $text): string` — يحوّل كل المسافات إلى مسافات داخلية.
- `WhitespaceNormalizer::deepTrim(string $text, bool $preserveNewLines = false): string` — يقص فواصل Unicode ومحارف التحكم من الطرفين.

### التطبيع والأرقام والأسماء

- `ArabicNormalizer::normalize(string $text, ArabicPolicy $policy = ArabicPolicy::Display): string` — يطبق سياسة التطبيع.
- `ArabicNormalizer::normalizeLetters(...): string` — يطبق تحويلات الحروف العربية الصريحة.
- `ArabicNormalizer::searchKey(string $text): string` — ينشئ مفتاح بحث.
- `DiacriticsStripper::strip(string $text, bool $includeQuranMarks = true): string` — يزيل العلامات العربية.
- `DiacriticsStripper::has(string $text): bool` — يكشف وجود علامات عربية.
- `TatweelStripper::strip(string $text): string` — يزيل التطويل.
- `TatweelStripper::has(string $text): bool` — يكشف وجود التطويل.
- `ArabicDigits::toLatin(string $text): string` — يحوّل الأرقام المدعومة إلى لاتينية.
- `ArabicDigits::toArabicIndic(string $text): string` — يحوّل الأرقام إلى عربية هندية.
- `ArabicDigits::toEasternArabic(string $text): string` — يحوّل الأرقام إلى فارسية/عربية شرقية.
- `ArabicDigits::normalize(string $text, DigitSet $target = DigitSet::Latin): string` — يحوّل الأرقام إلى المجموعة المختارة.
- `ArabicDigits::hasArabicDigits(string $text): bool` — يكشف وجود أرقام عربية أو فارسية.
- `ArabicNameNormalizer::normalize(...): string` — يطبّع الاسم المعروض بتحفظ.

### الروابط والمقتطفات والترقيم والفلترة والأنماط

- `ArabicSlugger::slug(...): string` — ينشئ slug حسب `SlugMode`.
- `ArabicSlugger::unicode(...): string` — ينشئ slug عربيًا مقروءًا.
- `ArabicSlugger::ascii(...): string` — ينشئ slug بنمط ASCII.
- `ArabicTransliterator::toAscii(string $text, bool $normalize = true): string` — ينقل العربية إلى ASCII تقريبي.
- `UniqueSlugger::unique(string $text, callable $exists, SlugMode $mode = SlugMode::Unicode, string $separator = '-'): string` — ينشئ slug فريدًا باستخدام callback للتحقق من الوجود.
- `TextExcerpt::excerpt(string $htmlOrText, int $limit = 200, string $end = ' ...'): string` — ينشئ مقتطفًا دون قطع الكلمات.
- `ArabicPunctuation::addSpaceAfterPunctuation(string $text): string` — يضيف مسافات ناقصة بعد علامات الترقيم.
- `ArabicPunctuation::normalize(string $text): string` — يضبط علامات الترقيم والمسافات.
- `ArabicPunctuation::normalizeConjunctionWaw(string $text): string` — يضبط واو العطف المستقلة.
- `ProfanityFilter::contains(string $text): bool` — يفحص النص ضد الكلمات المحظورة الممررة.
- `ProfanityWordsLoader::load(array|string|null $source): array` — يحمّل كلمات محظورة من array أو TXT أو JSON أو مصادر مختلطة.
- `ArabicPatterns::charClass(string $fragment): string` — يضع fragment داخل character class.
- `ArabicPatterns::orderedListPrefix(): string` — يعيد regex لترقيم القوائم.
- `ArabicPatterns::arabicName(): string` — يعيد regex صالحًا للأسماء العربية.
- `ArabicPatterns::arabic(): string` — يعيد regex لكشف النص العربي.
- `ArabicPatterns::diacritics(bool $includeQuranMarks = true): string` — يعيد regex للتشكيل العربي.
- `ArabicPatterns::tatweel(): string` — يعيد regex للتطويل.
- `ArabicPatterns::bidiControls(): string` — يعيد regex لمحارف اتجاه النص.
- `ArabicPatterns::zeroWidth(): string` — يعيد regex لمحارف zero-width.
- `ArabicPatterns::slugAllowed(): string` — يعيد regex للمحارف غير المسموحة قبل slug.
- `ArabicPatterns::slug(string $separator = '-'): string` — يعيد regex للتحقق من slug.
- `ArabicPatterns::controlExceptNewLines(): string` — يعيد regex لمحارف التحكم مع استثناء الأسطر.
- `ArabicPatterns::allControlCharacters(): string` — يعيد regex لكل محارف التحكم.
- `ArabicPatterns::invisibleCharacters(): string` — يعيد regex موحدًا للمحارف الخفية والحساسة أمنيًا.
