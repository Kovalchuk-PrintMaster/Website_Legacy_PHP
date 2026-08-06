<?php

declare(strict_types=1);

namespace libraries;

/**
 * Canonical non-Goods image pipeline for managed admin entity fields.
 *
 * Goods remains owned by GoodsImageUploadOptimizer.
 * Selected settings galleries are managed explicitly. TinyMCE/editor
 * uploads remain outside this bounded pipeline.
 */
final class ManagedImageUploadOptimizer
{
    private string $userfilesRoot;

    /**
     * Explicit allow-list. Unlisted table/field pairs pass through unchanged.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $profiles;

    /** @var list<string> */
    private array $createdFiles = [];

    // FP_MANAGED_SAFE_SVG_SUPPORT_05F2A
    private const SVG_MAX_BYTES = 1048576;

    /** @var list<string> */
    private const SVG_ACCEPTED_MIME_TYPES = [
        'image/svg+xml',
        'application/xml',
        'text/xml',
        'text/plain',
        'application/octet-stream',
    ];

    public function __construct(
        ?string $userfilesRoot = null,
        ?MediaProcessingSettings $settings = null
    ) {
        $root = $userfilesRoot
            ?? dirname(__DIR__, 2) . '/base/userfiles';

        $this->userfilesRoot = rtrim(
            str_replace('\\', '/', $root),
            '/'
        );

        $settings = $settings ?? new MediaProcessingSettings();
        $config = $settings->all();
        $pngColors = $settings->pngPaletteSteps();
        $jpegQuality = (int)$config['jpeg_quality'];
        $jpegMinQuality = (int)$config['jpeg_min_quality'];

        $entityEdge = (int)$config['entity_max_edge'];
        $entity = [
            'max_width' => $entityEdge,
            'max_height' => $entityEdge,
            'max_bytes' => (int)$config['entity_max_kb'] * 1024,
            'format' => 'auto',
            'jpeg_quality' => $jpegQuality,
            'jpeg_min_quality' => $jpegMinQuality,
            'png_colors' => $pngColors,
        ];

        $newsEdge = (int)$config['news_max_edge'];
        $sliderEdge = (int)$config['slider_max_edge'];
        $settingsEdge = (int)$config['settings_max_edge'];
        $socialEdge = (int)$config['social_max_edge'];

        $this->profiles = [
            'advantages.img' => $entity + [
                'directory' => 'advantages',
                'label' => 'зображення переваги',
            ],
            'catalog.img' => $entity + [
                'directory' => 'catalog',
                'label' => 'зображення розділу каталогу',
            ],
            'filters.img' => $entity + [
                'directory' => 'filters',
                'label' => 'зображення фільтра',
            ],
            'filters_categories.img' => $entity + [
                'directory' => 'filters_categories',
                'label' => 'зображення групи фільтрів',
            ],
            'news.img' => [
                'directory' => 'news',
                'label' => 'зображення новини',
                'max_width' => $newsEdge,
                'max_height' => $newsEdge,
                'max_bytes' => (int)$config['news_max_kb'] * 1024,
                'format' => 'auto',
                'jpeg_quality' => $jpegQuality,
                'jpeg_min_quality' => $jpegMinQuality,
                'png_colors' => $pngColors,
            ],
            'sales.img' => [
                'directory' => 'frontend/home/slider',
                'label' => 'зображення слайда',
                'max_width' => $sliderEdge,
                'max_height' => $sliderEdge,
                'max_bytes' => (int)$config['slider_max_kb'] * 1024,
                'format' => 'jpeg',
                'jpeg_quality' => $jpegQuality,
                'jpeg_min_quality' => $jpegMinQuality,
                'png_colors' => $pngColors,
                'name_prefix' => 'slide',
            ],
            'settings.img' => [
                'directory' => 'settings',
                'label' => 'основне зображення налаштувань',
                'max_width' => $settingsEdge,
                'max_height' => $settingsEdge,
                'max_bytes' => (int)$config['settings_max_kb'] * 1024,
                'format' => 'auto',
                'jpeg_quality' => $jpegQuality,
                'jpeg_min_quality' => $jpegMinQuality,
                'png_colors' => $pngColors,
                'field_prefix' => true,
                'allow_svg' => true,
            ],
            'settings.img_years' => [
                'directory' => 'settings',
                'label' => 'зображення років роботи',
                'max_width' => $entityEdge,
                'max_height' => $entityEdge,
                'max_bytes' => (int)$config['entity_max_kb'] * 1024,
                'format' => 'auto',
                'jpeg_quality' => $jpegQuality,
                'jpeg_min_quality' => $jpegMinQuality,
                'png_colors' => $pngColors,
                'field_prefix' => true,
                'allow_svg' => true,
            ],
            'settings.promo_img' => [
                'directory' => 'settings',
                'label' => 'промозображення налаштувань',
                'max_width' => $settingsEdge,
                'max_height' => $settingsEdge,
                'max_bytes' => (int)$config['settings_max_kb'] * 1024,
                'format' => 'auto',
                'jpeg_quality' => $jpegQuality,
                'jpeg_min_quality' => $jpegMinQuality,
                'png_colors' => $pngColors,
                'field_prefix' => true,
            ],
            // FP_ABOUT_PROMO_GALLERY_05G11B
            'settings.about_promo_gallery_img' => [
                'directory' => 'settings/about-promo-gallery',
                'label' => 'промо-фотографії сторінки «Про нас»',
                'max_width' => $settingsEdge,
                'max_height' => $settingsEdge,
                'max_bytes' => (int)$config['settings_max_kb'] * 1024,
                'format' => 'auto',
                'jpeg_quality' => $jpegQuality,
                'jpeg_min_quality' => $jpegMinQuality,
                'png_colors' => $pngColors,
                'name_suffix' => 'promo-gallery',
                'multiple' => true,
            ],
            // FP_SETTINGS_MANAGED_GALLERIES_05G2A
            'settings.gallery_img' => [
                'directory' => 'settings/about-gallery',
                'label' => 'зображення галереї «Про нас»',
                'max_width' => $settingsEdge,
                'max_height' => $settingsEdge,
                'max_bytes' => (int)$config['settings_max_kb'] * 1024,
                'format' => 'auto',
                'jpeg_quality' => $jpegQuality,
                'jpeg_min_quality' => $jpegMinQuality,
                'png_colors' => $pngColors,
                'name_suffix' => 'gallery',
                'multiple' => true,
            ],
            'settings.home_groups_img' => [
                'directory' => 'settings/home-groups',
                'label' => 'зображення товарних груп головної',
                'max_width' => $settingsEdge,
                'max_height' => $settingsEdge,
                'max_bytes' => (int)$config['settings_max_kb'] * 1024,
                'format' => 'auto',
                'jpeg_quality' => $jpegQuality,
                'jpeg_min_quality' => $jpegMinQuality,
                'png_colors' => $pngColors,
                'name_suffix' => 'home-groups',
            ],
            'settings.home_groups_gallery_img' => [
                'directory' => 'settings/home-groups-gallery',
                'label' => 'зображення галереї товарних груп головної',
                'max_width' => $settingsEdge,
                'max_height' => $settingsEdge,
                'max_bytes' => (int)$config['settings_max_kb'] * 1024,
                'format' => 'auto',
                'jpeg_quality' => $jpegQuality,
                'jpeg_min_quality' => $jpegMinQuality,
                'png_colors' => $pngColors,
                'name_suffix' => 'home-groups-gallery',
                'multiple' => true,
            ],
            'settings.catalog_img' => [
                'directory' => 'settings/catalog',
                'label' => 'зображення каталогу',
                'max_width' => $settingsEdge,
                'max_height' => $settingsEdge,
                'max_bytes' => (int)$config['settings_max_kb'] * 1024,
                'format' => 'auto',
                'jpeg_quality' => $jpegQuality,
                'jpeg_min_quality' => $jpegMinQuality,
                'png_colors' => $pngColors,
                'name_suffix' => 'catalog',
            ],
            'settings.catalog_gallery_img' => [
                'directory' => 'settings/catalog-gallery',
                'label' => 'зображення галереї каталогу',
                'max_width' => $settingsEdge,
                'max_height' => $settingsEdge,
                'max_bytes' => (int)$config['settings_max_kb'] * 1024,
                'format' => 'auto',
                'jpeg_quality' => $jpegQuality,
                'jpeg_min_quality' => $jpegMinQuality,
                'png_colors' => $pngColors,
                'name_suffix' => 'catalog-gallery',
                'multiple' => true,
            ],
            'socials.img' => [
                'directory' => 'socials',
                'label' => 'іконка соціальної мережі',
                'max_width' => $socialEdge,
                'max_height' => $socialEdge,
                'max_bytes' => (int)$config['social_max_kb'] * 1024,
                'format' => 'auto',
                'jpeg_quality' => $jpegQuality,
                'jpeg_min_quality' => $jpegMinQuality,
                'png_colors' => $pngColors,
                'name_suffix' => 'icon',
                'allow_svg' => true,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $files
     * @param array<string, mixed> $context
     * @return array{files: array<string, mixed>, errors: list<string>,
     *               managed: list<string>}
     */
    public function optimizeFiles(
        array $files,
        string $table,
        array $context = [],
        int $recordId = 0
    ): array {
        $table = strtolower(trim($table));
        $result = $files;
        $errors = [];
        $managed = [];
        $sourceFiles = [];
        $this->createdFiles = [];

        if (!extension_loaded('imagick')) {
            foreach ($files as $field => $value) {
                if (!is_string($field)) {
                    continue;
                }

                $profile = $this->profiles[
                    $table . '.' . $field
                ] ?? null;

                if (!is_array($profile)) {
                    continue;
                }

                foreach (
                    $this->managedSourceValues(
                        $value,
                        $profile
                    )
                    as $sourceValue
                ) {
                    if (!$this->looksLikeSvgPath($sourceValue)) {
                        $errors[] =
                            'Не вдалося обробити зображення: '
                            . 'PHP Imagick недоступний.';
                        break 2;
                    }
                }
            }

            if ($errors !== []) {
                return [
                    'files' => $result,
                    'errors' => $errors,
                    'managed' => [],
                ];
            }
        }

        foreach ($files as $field => $value) {
            if (!is_string($field)) {
                continue;
            }

            $key = $table . '.' . $field;
            $profile = $this->profiles[$key] ?? null;

            if (!is_array($profile)) {
                continue;
            }

            $sourceValues = $this->managedSourceValues(
                $value,
                $profile
            );

            if ($sourceValues === []) {
                continue;
            }

            try {
                $optimizedValues = [];

                foreach ($sourceValues as $sourceValue) {
                    $sourceRelative = $this->normalizeRelativePath(
                        $sourceValue
                    );

                    if ($sourceRelative === null) {
                        throw new \RuntimeException(
                            'небезпечний або порожній шлях'
                        );
                    }

                    $source = $this->resolveExistingSource(
                        $sourceRelative
                    );
                    $optimizedValues[] = $this->optimizeOne(
                        $source,
                        $sourceRelative,
                        $table,
                        $field,
                        $profile,
                        $context,
                        $recordId
                    );
                    $sourceFiles[] = $source;
                }

                $result[$field] = !empty($profile['multiple'])
                    ? $optimizedValues
                    : $optimizedValues[0];
                $managed[] = $key;
            } catch (\Throwable $error) {
                $label = (string)(
                    $profile['label'] ?? 'зображення'
                );
                $errors[] =
                    'Не вдалося оптимізувати '
                    . $label
                    . ': '
                    . $this->safeErrorMessage($error);
            }
        }

        if ($errors !== []) {
            $this->removeCreatedFiles();

            return [
                'files' => $files,
                'errors' => $errors,
                'managed' => [],
            ];
        }

        foreach (array_unique($sourceFiles) as $source) {
            if (
                is_file($source)
                && !in_array(
                    $source,
                    $this->createdFiles,
                    true
                )
            ) {
                @unlink($source);
            }
        }

        return [
            'files' => $result,
            'errors' => [],
            'managed' => array_values(
                array_unique($managed)
            ),
        ];
    }

    /**
     * Normalizes the FileEdit single-file and gallery shapes into one list.
     *
     * @param mixed $value
     * @param array<string, mixed> $profile
     * @return list<string>
     */
    private function managedSourceValues(
        $value,
        array $profile
    ): array {
        if (empty($profile['multiple'])) {
            return is_string($value)
                && trim($value) !== ''
                ? [trim($value)]
                : [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                $value = $decoded;
            } elseif (trim($value) !== '') {
                $value = [trim($value)];
            }
        }

        if (!is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $item) {
            if (
                is_string($item)
                && trim($item) !== ''
            ) {
                $result[] = trim($item);
            }
        }

        return array_values(array_unique($result));
    }


    /**
     * @param array<string, mixed> $profile
     * @param array<string, mixed> $context
     */
    private function optimizeOne(
        string $source,
        string $sourceRelative,
        string $table,
        string $field,
        array $profile,
        array $context,
        int $recordId
    ): string {
        if ($this->isSvgSource($source)) {
            if (empty($profile['allow_svg'])) {
                throw new \RuntimeException(
                    'SVG не дозволено для цього поля'
                );
            }

            return $this->installValidatedSvg(
                $source,
                $table,
                $field,
                $profile,
                $context,
                $recordId
            );
        }

        $this->validateSource($source);

        $image = new \Imagick();
        $image->readImage($source);

        if ($image->getNumberImages() !== 1) {
            $image->clear();
            $image->destroy();

            throw new \RuntimeException(
                'анімовані або багатокадрові файли '
                . 'не підтримуються цим профілем'
            );
        }

        $image->setIteratorIndex(0);

        if (method_exists($image, 'autoOrient')) {
            $image->autoOrient();
        } elseif (method_exists($image, 'autoOrientImage')) {
            $image->autoOrientImage();
        }

        $maxWidth = max(1, (int)$profile['max_width']);
        $maxHeight = max(1, (int)$profile['max_height']);

        if (
            $image->getImageWidth() > $maxWidth
            || $image->getImageHeight() > $maxHeight
        ) {
            $image->thumbnailImage(
                $maxWidth,
                $maxHeight,
                true,
                false
            );
        }

        $format = (string)($profile['format'] ?? 'auto');

        if ($format === 'auto') {
            $format = $this->hasMeaningfulTransparency($image)
                ? 'png'
                : 'jpeg';
        }

        if (!in_array($format, ['jpeg', 'png'], true)) {
            $image->clear();
            $image->destroy();

            throw new \RuntimeException(
                'невідомий формат вихідного профілю'
            );
        }

        $extension = $format === 'jpeg' ? 'jpg' : 'png';
        $directory = trim(
            str_replace(
                ['\\', '..'],
                ['/', ''],
                (string)$profile['directory']
            ),
            '/'
        );

        if ($directory === '') {
            $image->clear();
            $image->destroy();

            throw new \RuntimeException(
                'профіль не визначив директорію'
            );
        }

        $targetDirectory = $this->userfilesRoot . '/' . $directory;

        if (
            !is_dir($targetDirectory)
            && !mkdir($targetDirectory, 0775, true)
            && !is_dir($targetDirectory)
        ) {
            $image->clear();
            $image->destroy();

            throw new \RuntimeException(
                'не вдалося створити директорію'
            );
        }

        $semantic = $this->semanticBaseName(
            $table,
            $field,
            $profile,
            $context,
            $recordId
        );
        [$target, $sequence] = $this->nextOutputPath(
            $targetDirectory,
            $semantic,
            $extension
        );
        $temporary = $target
            . '.part-'
            . bin2hex(random_bytes(6));

        try {
            $maxBytes = max(1024, (int)$profile['max_bytes']);

            if ($format === 'jpeg') {
                $this->writeJpeg(
                    $image,
                    $temporary,
                    $maxBytes,
                    (int)$profile['jpeg_quality'],
                    (int)$profile['jpeg_min_quality']
                );
            } else {
                $this->writePng(
                    $image,
                    $temporary,
                    $maxBytes,
                    (array)$profile['png_colors']
                );
            }

            $this->validateOutput(
                $temporary,
                $format,
                $maxWidth,
                $maxHeight,
                $maxBytes
            );

            if (!@rename($temporary, $target)) {
                throw new \RuntimeException(
                    'не вдалося атомарно встановити результат'
                );
            }

            $this->createdFiles[] = $target;
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }

            $image->clear();
            $image->destroy();
        }

        return $directory
            . '/'
            . basename($target);
    }

    private function looksLikeSvgPath(string $path): bool
    {
        return strtolower(
            (string)pathinfo($path, PATHINFO_EXTENSION)
        ) === 'svg';
    }

    private function isSvgSource(string $source): bool
    {
        if (!$this->looksLikeSvgPath($source)) {
            return false;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = strtolower(
            trim((string)$finfo->file($source))
        );

        return in_array(
            $detected,
            self::SVG_ACCEPTED_MIME_TYPES,
            true
        );
    }

    /**
     * SVG is a vector document, not a raster image. It intentionally bypasses
     * Imagick, but only after strict XML and active-content validation.
     *
     * @param array<string, mixed> $profile
     * @param array<string, mixed> $context
     */
    private function installValidatedSvg(
        string $source,
        string $table,
        string $field,
        array $profile,
        array $context,
        int $recordId
    ): string {
        if (!class_exists(\DOMDocument::class)) {
            throw new \RuntimeException(
                'PHP DOM недоступний для безпечної перевірки SVG'
            );
        }

        $maxBytes = min(
            self::SVG_MAX_BYTES,
            max(1024, (int)$profile['max_bytes'])
        );
        $this->validateSvgDocument($source, $maxBytes);

        $directory = trim(
            str_replace(
                ['\\', '..'],
                ['/', ''],
                (string)$profile['directory']
            ),
            '/'
        );

        if ($directory === '') {
            throw new \RuntimeException(
                'профіль не визначив директорію'
            );
        }

        $targetDirectory = $this->userfilesRoot . '/' . $directory;

        if (
            !is_dir($targetDirectory)
            && !mkdir($targetDirectory, 0775, true)
            && !is_dir($targetDirectory)
        ) {
            throw new \RuntimeException(
                'не вдалося створити директорію'
            );
        }

        $semantic = $this->semanticBaseName(
            $table,
            $field,
            $profile,
            $context,
            $recordId
        );
        [$target] = $this->nextOutputPath(
            $targetDirectory,
            $semantic,
            'svg'
        );
        $temporary = $target
            . '.part-'
            . bin2hex(random_bytes(6));

        try {
            if (!@copy($source, $temporary)) {
                throw new \RuntimeException(
                    'не вдалося підготувати SVG'
                );
            }

            $this->validateSvgDocument(
                $temporary,
                $maxBytes
            );

            if (!@rename($temporary, $target)) {
                throw new \RuntimeException(
                    'не вдалося атомарно встановити SVG'
                );
            }

            $this->createdFiles[] = $target;
        } finally {
            if (is_file($temporary)) {
                @unlink($temporary);
            }
        }

        return $directory
            . '/'
            . basename($target);
    }

    private function validateSvgDocument(
        string $path,
        int $maxBytes
    ): void {
        if (
            !is_file($path)
            || filesize($path) <= 0
        ) {
            throw new \RuntimeException(
                'SVG відсутній або порожній'
            );
        }

        if (filesize($path) > $maxBytes) {
            throw new \RuntimeException(
                'SVG перевищує дозволений розмір 1 MiB'
            );
        }

        $content = @file_get_contents($path);

        if (!is_string($content) || $content === '') {
            throw new \RuntimeException(
                'не вдалося прочитати SVG'
            );
        }

        if (strpos($content, "\0") !== false) {
            throw new \RuntimeException(
                'SVG містить нульові байти'
            );
        }

        if (
            preg_match(
                '~<!\s*(?:DOCTYPE|ENTITY)\b~i',
                $content
            )
        ) {
            throw new \RuntimeException(
                'DOCTYPE та ENTITY у SVG заборонені'
            );
        }

        $previous = libxml_use_internal_errors(true);
        libxml_clear_errors();
        $document = new \DOMDocument();

        try {
            $loaded = $document->loadXML(
                $content,
                LIBXML_NONET
                | LIBXML_NOBLANKS
                | LIBXML_COMPACT
            );
            $errors = libxml_get_errors();
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (!$loaded || $errors !== []) {
            throw new \RuntimeException(
                'SVG містить некоректний XML'
            );
        }

        if ($document->doctype !== null) {
            throw new \RuntimeException(
                'DOCTYPE у SVG заборонений'
            );
        }

        $root = $document->documentElement;

        if (
            !$root
            || strtolower($root->localName) !== 'svg'
            || !in_array(
                (string)$root->namespaceURI,
                ['', 'http://www.w3.org/2000/svg'],
                true
            )
        ) {
            throw new \RuntimeException(
                'кореневий елемент файла не є SVG'
            );
        }

        $xpath = new \DOMXPath($document);

        if (
            $xpath->query('//processing-instruction()')->length > 0
        ) {
            throw new \RuntimeException(
                'інструкції обробки XML у SVG заборонені'
            );
        }

        $elements = $xpath->query('//*');

        if ($elements->length > 20000) {
            throw new \RuntimeException(
                'SVG має надмірну складність'
            );
        }

        $deniedElements = [
            'script',
            'foreignobject',
            'iframe',
            'object',
            'embed',
            'audio',
            'video',
            'canvas',
            'applet',
            'image',
            'feimage',
            'cursor',
            'animate',
            'animatemotion',
            'animatetransform',
            'set',
            'include',
        ];

        foreach ($elements as $element) {
            if (!$element instanceof \DOMElement) {
                continue;
            }

            $elementName = strtolower(
                (string)$element->localName
            );

            if (
                in_array(
                    $elementName,
                    $deniedElements,
                    true
                )
            ) {
                throw new \RuntimeException(
                    'SVG містить заборонений елемент '
                    . $elementName
                );
            }

            if (
                $elementName === 'style'
                && $this->hasUnsafeSvgCss(
                    (string)$element->textContent
                )
            ) {
                throw new \RuntimeException(
                    'активний або зовнішній CSS у SVG заборонений'
                );
            }

            foreach ($element->attributes as $attribute) {
                if (!$attribute instanceof \DOMAttr) {
                    continue;
                }

                $attributeName = strtolower(
                    (string)$attribute->localName
                );
                $value = trim((string)$attribute->value);
                $compact = strtolower(
                    preg_replace(
                        '~[\x00-\x20]+~',
                        '',
                        $value
                    ) ?? ''
                );

                if (str_starts_with($attributeName, 'on')) {
                    throw new \RuntimeException(
                        'подієві атрибути у SVG заборонені'
                    );
                }

                if (
                    in_array(
                        $attributeName,
                        ['href', 'src', 'poster', 'base'],
                        true
                    )
                    && (
                        $value === ''
                        || !str_starts_with($value, '#')
                    )
                ) {
                    throw new \RuntimeException(
                        'зовнішні посилання у SVG заборонені'
                    );
                }

                if (
                    $attributeName === 'style'
                    && $this->hasUnsafeSvgCss($value)
                ) {
                    throw new \RuntimeException(
                        'активний або зовнішній CSS у SVG заборонений'
                    );
                }

                if (
                    preg_match(
                        '~(?:javascript|vbscript|data|file|'
                        . 'https?|ftp):|^//~i',
                        $compact
                    )
                ) {
                    throw new \RuntimeException(
                        'активні або зовнішні URI у SVG заборонені'
                    );
                }

                if (
                    $attributeName !== 'style'
                    && stripos($value, 'url(') !== false
                    && !preg_match(
                        '~^url\(\s*["\']?#[A-Za-z_][A-Za-z0-9_.:-]*'
                        . '["\']?\s*\)$~i',
                        $value
                    )
                ) {
                    throw new \RuntimeException(
                        'SVG містить зовнішнє CSS-посилання'
                    );
                }
            }
        }
    }

    private function hasUnsafeSvgCss(string $css): bool
    {
        if (
            preg_match(
                '~(?:@import|expression\s*\(|behavior\s*:|'
                . '-moz-binding\s*:|javascript\s*:|'
                . 'vbscript\s*:|data\s*:|file\s*:|'
                . 'https?\s*:|ftp\s*:|//)~i',
                $css
            )
        ) {
            return true;
        }

        if (
            !preg_match_all(
                '~url\(\s*([^)]+?)\s*\)~i',
                $css,
                $matches
            )
        ) {
            return false;
        }

        foreach ($matches[1] as $reference) {
            $reference = trim(
                (string)$reference,
                " \t\n\r\0\x0B\"'"
            );

            if (
                !preg_match(
                    '~^#[A-Za-z_][A-Za-z0-9_.:-]*$~',
                    $reference
                )
            ) {
                return true;
            }
        }

        return false;
    }

    private function validateSource(string $source): void
    {
        $info = @getimagesize($source);

        if (!is_array($info)) {
            throw new \RuntimeException(
                'файл не декодується як зображення'
            );
        }

        $mime = (string)($info['mime'] ?? '');
        $accepted = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/avif',
        ];

        if (!in_array($mime, $accepted, true)) {
            throw new \RuntimeException(
                'непідтримуваний MIME-тип'
            );
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $detected = (string)$finfo->file($source);

        if (!in_array($detected, $accepted, true)) {
            throw new \RuntimeException(
                'вміст файла не відповідає дозволеному зображенню'
            );
        }
    }

    private function writeJpeg(
        \Imagick $image,
        string $temporary,
        int $maxBytes,
        int $initialQuality,
        int $minimumQuality
    ): void {
        $width = max(1, $image->getImageWidth());
        $height = max(1, $image->getImageHeight());

        $canvas = new \Imagick();
        $canvas->newImage(
            $width,
            $height,
            new \ImagickPixel('white')
        );
        $canvas->setImageFormat('jpeg');
        $canvas->compositeImage(
            $image,
            \Imagick::COMPOSITE_OVER,
            0,
            0
        );
        $canvas->stripImage();
        $canvas->setInterlaceScheme(\Imagick::INTERLACE_PLANE);
        $canvas->setImageCompression(\Imagick::COMPRESSION_JPEG);

        $initialQuality = max(
            80,
            min(98, $initialQuality)
        );
        $minimumQuality = max(
            72,
            min($initialQuality, $minimumQuality)
        );
        $qualities = [];

        for (
            $quality = $initialQuality;
            $quality >= $minimumQuality;
            $quality -= 2
        ) {
            $qualities[] = $quality;
        }

        if (
            $qualities === []
            || end($qualities) !== $minimumQuality
        ) {
            $qualities[] = $minimumQuality;
        }

        $written = false;

        foreach ($qualities as $quality) {
            $canvas->setImageCompressionQuality($quality);
            $written = (bool)$canvas->writeImage($temporary);

            if (
                $written
                && is_file($temporary)
                && filesize($temporary) <= $maxBytes
            ) {
                break;
            }
        }

        $canvas->clear();
        $canvas->destroy();

        if (!$written || !is_file($temporary)) {
            throw new \RuntimeException(
                'Imagick не записав JPEG'
            );
        }

        if (filesize($temporary) > $maxBytes) {
            throw new \RuntimeException(
                'JPEG не вклався у дозволений бюджет'
            );
        }
    }

    /**
     * @param list<int> $colorSteps
     */
    private function writePng(
        \Imagick $image,
        string $temporary,
        int $maxBytes,
        array $colorSteps
    ): void {
        $steps = [];

        foreach ($colorSteps as $colors) {
            $colors = (int)$colors;

            if ($colors >= 32 && $colors <= 256) {
                $steps[] = $colors;
            }
        }

        if ($steps === []) {
            $steps = [256, 192, 128];
        }

        $written = false;

        foreach (array_values(array_unique($steps)) as $colors) {
            $candidate = clone $image;
            $candidate->setImageFormat('png');
            $candidate->setImageDepth(8);
            $candidate->setImageAlphaChannel(
                \Imagick::ALPHACHANNEL_ACTIVATE
            );
            $candidate->stripImage();
            $candidate->quantizeImage(
                $colors,
                \Imagick::COLORSPACE_SRGB,
                0,
                true,
                false
            );
            $candidate->setImageAlphaChannel(
                \Imagick::ALPHACHANNEL_ACTIVATE
            );
            $candidate->setImageFormat('png');
            $candidate->setOption('png:compression-level', '9');
            $candidate->setOption('png:compression-strategy', '1');
            $written = (bool)$candidate->writeImage($temporary);
            $candidate->clear();
            $candidate->destroy();

            if (
                $written
                && is_file($temporary)
                && filesize($temporary) <= $maxBytes
            ) {
                break;
            }
        }

        if (!$written || !is_file($temporary)) {
            throw new \RuntimeException(
                'Imagick не записав PNG'
            );
        }

        if (filesize($temporary) > $maxBytes) {
            throw new \RuntimeException(
                'PNG не вклався у дозволений бюджет'
            );
        }
    }

    private function validateOutput(
        string $path,
        string $format,
        int $maxWidth,
        int $maxHeight,
        int $maxBytes
    ): void {
        if (!is_file($path) || filesize($path) <= 0) {
            throw new \RuntimeException(
                'результат відсутній або порожній'
            );
        }

        if (filesize($path) > $maxBytes) {
            throw new \RuntimeException(
                'результат перевищив бюджет профілю'
            );
        }

        $info = @getimagesize($path);

        if (!is_array($info)) {
            throw new \RuntimeException(
                'результат не декодується'
            );
        }

        $expectedMime = $format === 'jpeg'
            ? 'image/jpeg'
            : 'image/png';

        if ((string)($info['mime'] ?? '') !== $expectedMime) {
            throw new \RuntimeException(
                'MIME результату не відповідає профілю'
            );
        }

        if (
            (int)$info[0] > $maxWidth
            || (int)$info[1] > $maxHeight
        ) {
            throw new \RuntimeException(
                'розміри результату перевищили профіль'
            );
        }
    }

    private function hasMeaningfulTransparency(
        \Imagick $image
    ): bool {
        try {
            if (!$image->getImageAlphaChannel()) {
                return false;
            }

            $extrema = $image->getImageChannelExtrema(
                \Imagick::CHANNEL_ALPHA
            );
            $range = \Imagick::getQuantumRange();
            $maximum = (float)(
                $range['quantumRangeLong']
                ?? $range['quantumRangeString']
                ?? 65535
            );

            $minimum = (float)(
                $extrema['minima']
                ?? $extrema['min']
                ?? $maximum
            );

            return $minimum < $maximum;
        } catch (\Throwable $error) {
            return false;
        }
    }

    /**
     * @param array<string, mixed> $profile
     * @param array<string, mixed> $context
     */
    private function semanticBaseName(
        string $table,
        string $field,
        array $profile,
        array $context,
        int $recordId
    ): string {
        $raw = '';

        foreach ([
            'about_name',
            'home_groups_title',
            'catalog_title',
            'name',
            'title',
            'sub_title',
            'alias',
            'external_alias',
        ] as $candidate) {
            if (
                isset($context[$candidate])
                && is_scalar($context[$candidate])
                && trim((string)$context[$candidate]) !== ''
            ) {
                $raw = (string)$context[$candidate];
                break;
            }
        }

        $slug = $this->slugify($raw);

        if ($slug === '') {
            $slug = $this->slugify($table . '-' . $field);
        }

        $parts = [];

        if (!empty($profile['name_prefix'])) {
            $parts[] = $this->slugify(
                (string)$profile['name_prefix']
            );
        }

        if ($table === 'sales' && $recordId > 0) {
            $parts[] = (string)$recordId;
        }

        if (!empty($profile['field_prefix'])) {
            $parts[] = $this->slugify($field);
        }

        $parts[] = $slug;

        if (!empty($profile['name_suffix'])) {
            $parts[] = $this->slugify(
                (string)$profile['name_suffix']
            );
        }

        $parts = array_values(array_filter(
            $parts,
            static fn(string $part): bool => $part !== ''
        ));

        return implode('-', $parts);
    }

    private function slugify(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (function_exists('mb_strtolower')) {
            $value = mb_strtolower($value, 'UTF-8');
        } else {
            $value = strtolower($value);
        }

        $map = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'h','ґ'=>'g',
            'д'=>'d','е'=>'e','є'=>'ie','ж'=>'zh','з'=>'z',
            'и'=>'y','і'=>'i','ї'=>'i','й'=>'i','к'=>'k',
            'л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p',
            'р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
            'х'=>'kh','ц'=>'ts','ч'=>'ch','ш'=>'sh',
            'щ'=>'shch','ь'=>'','ю'=>'iu','я'=>'ia',
            'ы'=>'y','э'=>'e','ъ'=>'',
        ];

        $value = strtr($value, $map);
        $value = preg_replace(
            '~[^a-z0-9]+~',
            '-',
            $value
        ) ?? '';
        $value = trim($value, '-');

        return substr($value, 0, 120);
    }

    /**
     * @return array{0: string, 1: int}
     */
    private function nextOutputPath(
        string $directory,
        string $base,
        string $extension
    ): array {
        for ($sequence = 1; $sequence <= 999; $sequence++) {
            $path = sprintf(
                '%s/%s_%02d.%s',
                rtrim($directory, '/'),
                $base,
                $sequence,
                $extension
            );

            if (!file_exists($path)) {
                return [$path, $sequence];
            }
        }

        throw new \RuntimeException(
            'вичерпано діапазон безпечних індексів'
        );
    }

    private function normalizeRelativePath(
        string $publicPath
    ): ?string {
        $path = trim(str_replace('\\', '/', $publicPath));

        if ($path === '') {
            return null;
        }

        $path = ltrim($path, '/');

        foreach ([
            'base/userfiles/',
            'userfiles/',
        ] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
            }
        }

        if (
            $path === ''
            || preg_match('~(^|/)\.\.(/|$)~', $path)
        ) {
            return null;
        }

        return trim($path, '/');
    }

    private function resolveExistingSource(
        string $relative
    ): string {
        $root = realpath($this->userfilesRoot);

        if ($root === false) {
            throw new \RuntimeException(
                'коренева директорія userfiles відсутня'
            );
        }

        $source = realpath(
            $this->userfilesRoot . '/' . $relative
        );

        if (
            $source === false
            || !is_file($source)
            || (
                $source !== $root
                && !str_starts_with(
                    str_replace('\\', '/', $source),
                    str_replace('\\', '/', $root) . '/'
                )
            )
        ) {
            throw new \RuntimeException(
                'вихідний файл відсутній або поза userfiles'
            );
        }

        return $source;
    }

    private function removeCreatedFiles(): void
    {
        foreach (array_reverse($this->createdFiles) as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        $this->createdFiles = [];
    }

    private function safeErrorMessage(
        \Throwable $error
    ): string {
        $message = trim($error->getMessage());

        if ($message === '') {
            return 'невідома помилка обробки';
        }

        return preg_replace(
            '~[\r\n\t]+~',
            ' ',
            $message
        ) ?? 'невідома помилка обробки';
    }
}
