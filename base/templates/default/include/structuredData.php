<?php
/*
 * ForPrint server-rendered structured data v0.1.
 *
 * Sources:
 * - business identity and contacts: managed settings;
 * - social profiles: visible social rows;
 * - product identity and pricing: the current product row and canonical
 *   product-price helper;
 * - URLs: the canonical origin already resolved by header.php.
 *
 * No availability, reviews, ratings, delivery promises or return policies are
 * invented. Product markup is emitted only for a complete exact-price offer
 * or a range with a truthful lower bound.
 */

$fpSchemaController = strtolower(
    trim((string)$this->getController())
);
$fpSchemaGraph = [];

$fpSchemaCleanText = static function ($value): string {
    if (!is_scalar($value)) {
        return '';
    }

    $text = html_entity_decode(
        strip_tags((string)$value),
        ENT_QUOTES | ENT_HTML5,
        'UTF-8'
    );
    $text = preg_replace('/\s+/u', ' ', $text);

    return trim((string)$text);
};

$fpSchemaAbsoluteUrl = static function (
    $value
) use ($fpCanonicalOrigin): string {
    $value = trim((string)$value);

    if ($value === '') {
        return '';
    }

    if (preg_match('#^https?://#i', $value)) {
        return $value;
    }

    if (str_starts_with($value, '//')) {
        $scheme = str_starts_with(
            strtolower($fpCanonicalOrigin),
            'https://'
        ) ? 'https:' : 'http:';

        return $scheme . $value;
    }

    return rtrim($fpCanonicalOrigin, '/')
        . '/'
        . ltrim($value, '/');
};

$fpSchemaNumber = static function ($value): string {
    $number = max(0.0, (float)$value);

    if (abs($number - round($number)) < 0.001) {
        return (string)(int)round($number);
    }

    return rtrim(
        rtrim(
            number_format($number, 2, '.', ''),
            '0'
        ),
        '.'
    );
};

$fpSchemaBusinessId = rtrim(
    $fpCanonicalOrigin,
    '/'
) . '/#business';
$fpSchemaWebsiteId = rtrim(
    $fpCanonicalOrigin,
    '/'
) . '/#website';
$fpSchemaHomeUrl = rtrim(
    $fpCanonicalOrigin,
    '/'
) . '/';

$fpSchemaBusinessName = $fpSchemaCleanText(
    $this->set['business_name'] ?? ''
);

if ($fpSchemaBusinessName === '') {
    $fpSchemaBusinessName = 'ForPrint';
}

$fpSchemaAlternateName = $fpSchemaCleanText(
    $fpSiteName ?? ''
);

$fpSchemaPhone = $fpSchemaCleanText(
    $this->set['contacts_phone']
        ?? $this->set['phone']
        ?? ''
);
$fpSchemaEmail = trim(
    (string)(
        $this->set['contacts_email']
        ?? $this->set['email']
        ?? ''
    )
);
$fpSchemaAddress = $fpSchemaCleanText(
    $this->set['contacts_address']
        ?? $this->set['address']
        ?? ''
);

$fpSchemaLogo = $fpSchemaAbsoluteUrl(
    $this->img(
        (string)($this->set['img'] ?? '')
    )
);

$fpSchemaSameAs = [];

foreach (
    is_array($this->socials ?? null)
        ? $this->socials
        : []
    as $fpSchemaSocial
) {
    if (!is_array($fpSchemaSocial)) {
        continue;
    }

    $fpSchemaSocialUrl = trim(
        (string)(
            $fpSchemaSocial['external_alias']
            ?? $fpSchemaSocial['url']
            ?? ''
        )
    );

    if (
        preg_match(
            '#^https://#i',
            $fpSchemaSocialUrl
        )
        && filter_var(
            $fpSchemaSocialUrl,
            FILTER_VALIDATE_URL
        )
    ) {
        $fpSchemaSameAs[] = $fpSchemaSocialUrl;
    }
}

$fpSchemaSameAs = array_values(
    array_unique($fpSchemaSameAs)
);

$fpSchemaOpeningHours = [];

/*
 * FP_SCHEMA_OPENING_HOURS_GROUP_KEYS_V0_1
 *
 * contacts_schedule is the same structured source rendered on /contacts/.
 * Canonical admin rows may use grouped keys such as "mon-fri" plus compact
 * single-day keys such as "sat" and "sun". Expand those keys into individual
 * Schema.org DayOfWeek values so the visible schedule and JSON-LD stay aligned.
 */
$fpSchemaScheduleRaw = trim(
    (string)($this->set['contacts_schedule'] ?? '')
);

if ($fpSchemaScheduleRaw !== '') {
    $fpSchemaSchedule = json_decode(
        $fpSchemaScheduleRaw,
        true
    );

    /*
     * Keep compatibility with historical admin flows that stored the JSON
     * document as a JSON string.
     */
    if (is_string($fpSchemaSchedule)) {
        $fpSchemaNestedSchedule = trim($fpSchemaSchedule);

        if (
            $fpSchemaNestedSchedule !== ''
            && in_array(
                $fpSchemaNestedSchedule[0] ?? '',
                ['{', '['],
                true
            )
        ) {
            $fpSchemaSchedule = json_decode(
                $fpSchemaNestedSchedule,
                true
            );
        }
    }

    if (
        is_array($fpSchemaSchedule)
        && is_array(
            $fpSchemaSchedule['weekly'] ?? null
        )
    ) {
        $fpSchemaDayMap = [
            'понеділок' => 'Monday',
            'пн' => 'Monday',
            'monday' => 'Monday',
            'mon' => 'Monday',
            'вівторок' => 'Tuesday',
            'вт' => 'Tuesday',
            'tuesday' => 'Tuesday',
            'tue' => 'Tuesday',
            'tues' => 'Tuesday',
            'середа' => 'Wednesday',
            'ср' => 'Wednesday',
            'wednesday' => 'Wednesday',
            'wed' => 'Wednesday',
            'четвер' => 'Thursday',
            'чт' => 'Thursday',
            'thursday' => 'Thursday',
            'thu' => 'Thursday',
            'thur' => 'Thursday',
            'thurs' => 'Thursday',
            "п'ятниця" => 'Friday',
            'п’ятниця' => 'Friday',
            'пт' => 'Friday',
            'friday' => 'Friday',
            'fri' => 'Friday',
            'субота' => 'Saturday',
            'сб' => 'Saturday',
            'saturday' => 'Saturday',
            'sat' => 'Saturday',
            'неділя' => 'Sunday',
            'нд' => 'Sunday',
            'sunday' => 'Sunday',
            'sun' => 'Sunday',
        ];

        $fpSchemaDayGroupMap = [
            'mon-fri' => [
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
            ],
            'mon–fri' => [
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
            ],
            'mon—fri' => [
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
            ],
            'пн-пт' => [
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
            ],
            'пн–пт' => [
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
            ],
            'пн—пт' => [
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
            ],
            'sat' => ['Saturday'],
            'сб' => ['Saturday'],
            'sun' => ['Sunday'],
            'нд' => ['Sunday'],
        ];

        foreach (
            $fpSchemaSchedule['weekly']
            as $fpSchemaScheduleRow
        ) {
            if (!is_array($fpSchemaScheduleRow)) {
                continue;
            }

            $fpSchemaScheduleStatus = strtolower(
                trim(
                    (string)(
                        $fpSchemaScheduleRow['status']
                        ?? 'open'
                    )
                )
            );

            if ($fpSchemaScheduleStatus === 'closed') {
                continue;
            }

            $fpSchemaScheduleKey = strtolower(
                trim(
                    (string)(
                        $fpSchemaScheduleRow['key']
                        ?? ''
                    )
                )
            );

            $fpSchemaScheduleLabel = strtolower(
                trim(
                    (string)(
                        $fpSchemaScheduleRow['day']
                        ?? $fpSchemaScheduleRow['label']
                        ?? $fpSchemaScheduleKey
                    )
                )
            );

            $fpSchemaScheduleOpen = trim(
                (string)(
                    $fpSchemaScheduleRow['open']
                    ?? ''
                )
            );
            $fpSchemaScheduleClose = trim(
                (string)(
                    $fpSchemaScheduleRow['close']
                    ?? ''
                )
            );

            if (
                !preg_match(
                    '/^\d{2}:\d{2}$/D',
                    $fpSchemaScheduleOpen
                )
                || !preg_match(
                    '/^\d{2}:\d{2}$/D',
                    $fpSchemaScheduleClose
                )
            ) {
                continue;
            }

            $fpSchemaMatchedDays = (
                $fpSchemaDayGroupMap[$fpSchemaScheduleKey]
                ?? $fpSchemaDayGroupMap[$fpSchemaScheduleLabel]
                ?? []
            );

            if (!$fpSchemaMatchedDays) {
                foreach (
                    $fpSchemaDayMap
                    as $fpSchemaDayLabel => $fpSchemaDay
                ) {
                    if (
                        $fpSchemaScheduleLabel === $fpSchemaDayLabel
                        || preg_match(
                            '/(?:^|[\s,;\/\-–—])'
                            . preg_quote(
                                $fpSchemaDayLabel,
                                '/'
                            )
                            . '(?:$|[\s,;\/\-–—])/u',
                            $fpSchemaScheduleLabel
                        )
                    ) {
                        $fpSchemaMatchedDays[] = $fpSchemaDay;
                    }
                }
            }

            $fpSchemaMatchedDays = array_values(
                array_unique($fpSchemaMatchedDays)
            );

            foreach (
                $fpSchemaMatchedDays
                as $fpSchemaMatchedDay
            ) {
                $fpSchemaOpeningHours[] = [
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => 'https://schema.org/'
                        . $fpSchemaMatchedDay,
                    'opens' => $fpSchemaScheduleOpen,
                    'closes' => $fpSchemaScheduleClose,
                ];
            }
        }
    }
}

/*
 * FP_SCHEMA_MANAGED_CONTACT_EMPTY_FALLBACK_V0_1
 *
 * The Contacts page treats an empty managed contacts_* value as
 * "use the legacy/general setting". PHP null-coalescing does not:
 * an existing empty string suppresses the fallback. Mirror the visible
 * Contacts page semantics here.
 */
if ($fpSchemaAddress === '') {
    $fpSchemaAddress = $fpSchemaCleanText(
        $this->set['address'] ?? ''
    );
}

if ($fpSchemaPhone === '') {
    $fpSchemaPhone = $fpSchemaCleanText(
        $this->set['phone'] ?? ''
    );
}

if ($fpSchemaEmail === '') {
    $fpSchemaEmail = trim(
        (string)($this->set['email'] ?? '')
    );
}

if (
    in_array(
        $fpSchemaController,
        ['index', 'contacts'],
        true
    )
) {
    $fpSchemaBusiness = [
        /*
         * FP_SCHEMA_CONTACT_LOCALBUSINESS_ONLY_V0_1
         * Homepage remains Organization; dedicated contacts page is LocalBusiness.
         */
        '@type' => (
            $fpSchemaController === 'contacts'
            && $fpSchemaAddress !== ''
        )
            ? 'LocalBusiness'
            : 'Organization',
        '@id' => $fpSchemaBusinessId,
        'name' => $fpSchemaBusinessName,
        'url' => $fpSchemaHomeUrl,
    ];

    if (
        $fpSchemaAlternateName !== ''
        && strcasecmp(
            $fpSchemaAlternateName,
            $fpSchemaBusinessName
        ) !== 0
    ) {
        $fpSchemaBusiness['alternateName'] = (
            $fpSchemaAlternateName
        );
    }

    if ($fpSchemaLogo !== '') {
        $fpSchemaBusiness['logo'] = [
            '@type' => 'ImageObject',
            'url' => $fpSchemaLogo,
        ];
        $fpSchemaBusiness['image'] = $fpSchemaLogo;
    }

    if ($fpSchemaPhone !== '') {
        $fpSchemaBusiness['telephone'] = $fpSchemaPhone;
    }

    if (
        $fpSchemaEmail !== ''
        && filter_var(
            $fpSchemaEmail,
            FILTER_VALIDATE_EMAIL
        )
    ) {
        $fpSchemaBusiness['email'] = $fpSchemaEmail;
    }

    if ($fpSchemaAddress !== '') {
        $fpSchemaBusiness['address'] = [
            '@type' => 'PostalAddress',
            'streetAddress' => $fpSchemaAddress,
            'addressCountry' => 'UA',
        ];
    }

    if ($fpSchemaSameAs) {
        $fpSchemaBusiness['sameAs'] = $fpSchemaSameAs;
    }

    if ($fpSchemaOpeningHours) {
        $fpSchemaBusiness[
            'openingHoursSpecification'
        ] = $fpSchemaOpeningHours;
    }

    $fpSchemaGraph[] = $fpSchemaBusiness;
}

if ($fpSchemaController === 'index') {
    $fpSchemaGraph[] = [
        '@type' => 'WebSite',
        '@id' => $fpSchemaWebsiteId,
        'url' => $fpSchemaHomeUrl,
        'name' => $fpSchemaBusinessName,
        'inLanguage' => $fpDocumentLanguage,
        'publisher' => [
            '@id' => $fpSchemaBusinessId,
        ],
    ];
}

if (
    $fpSchemaController === 'product'
    && !empty($data)
    && is_array($data)
) {
    require_once dirname(__DIR__)
        . '/include/productCardHelpers.php';

    $fpSchemaPriceState = function_exists(
        'fp_product_price_state'
    )
        ? fp_product_price_state($data)
        : [
            'mode' => 'request',
            'current_price' => 0,
        ];

    $fpSchemaOffer = null;

    if (
        ($fpSchemaPriceState['mode'] ?? '') === 'exact'
        && (float)(
            $fpSchemaPriceState['current_price']
            ?? 0
        ) > 0
    ) {
        $fpSchemaOffer = [
            '@type' => 'Offer',
            'url' => $fpCanonicalUrl,
            'price' => $fpSchemaNumber(
                $fpSchemaPriceState['current_price']
            ),
            'priceCurrency' => 'UAH',
        ];
    } elseif (
        ($fpSchemaPriceState['mode'] ?? '') === 'range'
    ) {
        $fpSchemaLowPrice = max(
            0.0,
            (float)($data['price_from'] ?? 0)
        );
        $fpSchemaHighPrice = max(
            0.0,
            (float)($data['price_to'] ?? 0)
        );

        if (
            $fpSchemaLowPrice > 0
            && $fpSchemaHighPrice > 0
            && $fpSchemaLowPrice > $fpSchemaHighPrice
        ) {
            [
                $fpSchemaLowPrice,
                $fpSchemaHighPrice,
            ] = [
                $fpSchemaHighPrice,
                $fpSchemaLowPrice,
            ];
        }

        if ($fpSchemaLowPrice > 0) {
            $fpSchemaOffer = [
                '@type' => 'AggregateOffer',
                'url' => $fpCanonicalUrl,
                'lowPrice' => $fpSchemaNumber(
                    $fpSchemaLowPrice
                ),
                'priceCurrency' => 'UAH',
            ];

            if ($fpSchemaHighPrice > 0) {
                $fpSchemaOffer['highPrice'] = (
                    $fpSchemaNumber(
                        $fpSchemaHighPrice
                    )
                );
            }
        }
    }

    if ($fpSchemaOffer !== null) {
        $fpSchemaProductName = $fpSchemaCleanText(
            $data['name'] ?? ''
        );
        $fpSchemaProductDescription = (
            $fpSchemaCleanText(
                $this->description
                    ?? $data['description']
                    ?? $data['short_content']
                    ?? $data['content']
                    ?? ''
            )
        );
        $fpSchemaProductImages = [];

        $fpSchemaMainImage = $fpSchemaAbsoluteUrl(
            $this->img(
                (string)($data['img'] ?? '')
            )
        );

        if ($fpSchemaMainImage !== '') {
            $fpSchemaProductImages[] = (
                $fpSchemaMainImage
            );
        }

        $fpSchemaGalleryRaw = (
            $data['gallery_img']
            ?? []
        );
        $fpSchemaGallery = is_array(
            $fpSchemaGalleryRaw
        )
            ? $fpSchemaGalleryRaw
            : json_decode(
                (string)$fpSchemaGalleryRaw,
                true
            );

        if (is_array($fpSchemaGallery)) {
            foreach (
                $fpSchemaGallery
                as $fpSchemaGalleryImage
            ) {
                $fpSchemaGalleryUrl = (
                    $fpSchemaAbsoluteUrl(
                        $this->img(
                            (string)$fpSchemaGalleryImage
                        )
                    )
                );

                if ($fpSchemaGalleryUrl !== '') {
                    $fpSchemaProductImages[] = (
                        $fpSchemaGalleryUrl
                    );
                }
            }
        }

        $fpSchemaProductImages = array_values(
            array_unique(
                $fpSchemaProductImages
            )
        );

        if (
            $fpSchemaProductName !== ''
            && $fpSchemaProductImages
            && $fpSchemaProductDescription !== ''
        ) {
            $fpSchemaProduct = [
                '@type' => 'Product',
                '@id' => $fpCanonicalUrl . '#product',
                'name' => $fpSchemaProductName,
                'description' => (
                    $fpSchemaProductDescription
                ),
                'url' => $fpCanonicalUrl,
                'image' => $fpSchemaProductImages,
                'offers' => $fpSchemaOffer,
            ];

            $fpSchemaCategoryName = (
                $fpSchemaCleanText(
                    $category['name'] ?? ''
                )
            );

            if ($fpSchemaCategoryName !== '') {
                $fpSchemaProduct['category'] = (
                    $fpSchemaCategoryName
                );
            }

            $fpSchemaGraph[] = $fpSchemaProduct;
        }
    }
}


/*
 * FP_SCHEMA_CANONICAL_BREADCRUMB_JSONLD_V0_1
 *
 * Canonical breadcrumb data is built once in
 * BaseUser::buildBreadcrumbItems().
 *
 * The visible breadcrumb template renders navigation only.
 * This centralized structured-data owner emits the same
 * canonical trail as JSON-LD.
 *
 * The final breadcrumb item may omit its URL because it
 * represents the current document.
 */
$fpSchemaBreadcrumbSource = (
    isset($this->breadcrumbItems)
    && is_array($this->breadcrumbItems)
)
    ? array_values($this->breadcrumbItems)
    : [];

$fpSchemaBreadcrumbElements = [];

foreach (
    $fpSchemaBreadcrumbSource
    as $fpSchemaBreadcrumbItem
) {
    if (!is_array($fpSchemaBreadcrumbItem)) {
        continue;
    }

    $fpSchemaBreadcrumbName = $fpSchemaCleanText(
        $fpSchemaBreadcrumbItem['label'] ?? ''
    );

    if ($fpSchemaBreadcrumbName === '') {
        continue;
    }

    $fpSchemaBreadcrumbElement = [
        '@type' => 'ListItem',
        'position' => (
            count($fpSchemaBreadcrumbElements) + 1
        ),
        'name' => $fpSchemaBreadcrumbName,
    ];

    $fpSchemaBreadcrumbUrl = trim(
        (string)(
            $fpSchemaBreadcrumbItem['url']
            ?? ''
        )
    );

    if ($fpSchemaBreadcrumbUrl !== '') {
        $fpSchemaBreadcrumbAbsoluteUrl = (
            $fpSchemaAbsoluteUrl(
                $fpSchemaBreadcrumbUrl
            )
        );

        if (
            $fpSchemaBreadcrumbAbsoluteUrl !== ''
        ) {
            $fpSchemaBreadcrumbElement['item'] = (
                $fpSchemaBreadcrumbAbsoluteUrl
            );
        }
    }

    $fpSchemaBreadcrumbElements[] = (
        $fpSchemaBreadcrumbElement
    );
}

if (
    count($fpSchemaBreadcrumbElements) >= 2
) {
    $fpSchemaGraph[] = [
        '@type' => 'BreadcrumbList',
        '@id' => (
            $fpCanonicalUrl . '#breadcrumb'
        ),
        'itemListElement' => (
            $fpSchemaBreadcrumbElements
        ),
    ];
}

if ($fpSchemaGraph) {
    $fpSchemaDocument = [
        '@context' => 'https://schema.org',
        '@graph' => $fpSchemaGraph,
    ];
    $fpSchemaJson = json_encode(
        $fpSchemaDocument,
        JSON_UNESCAPED_UNICODE
        | JSON_UNESCAPED_SLASHES
        | JSON_HEX_TAG
        | JSON_HEX_AMP
        | JSON_HEX_APOS
        | JSON_HEX_QUOT
    );

    if (is_string($fpSchemaJson)) {
        echo '<script type="application/ld+json">'
            . $fpSchemaJson
            . '</script>';
    }
}
