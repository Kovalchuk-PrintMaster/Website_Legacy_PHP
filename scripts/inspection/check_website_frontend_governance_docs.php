<?php

declare(strict_types=1);

/**
 * ForPrint frontend governance documentation smoke.
 * READ ONLY.
 */

$root = dirname(__DIR__, 2);

$paths = [
    'capability_md' =>
        $root
        . '/docs/reference/'
        . 'disabled_and_deferred_interface_capabilities_v0_1.md',
    'capability_yaml' =>
        $root
        . '/docs/reference/'
        . 'interface_capability_registry_v0_1.yaml',
    'visual_md' =>
        $root
        . '/docs/architecture/'
        . 'frontend_visual_system_v0_1.md',
    'visual_yaml' =>
        $root
        . '/docs/architecture/'
        . 'frontend_visual_system_v0_1.yaml',
    'media_md' =>
        $root
        . '/docs/architecture/'
        . 'media_storage_and_image_processing_policy_v0_1.md',
    'docs_readme' =>
        $root . '/docs/README.md',
];

$content = [];

foreach ($paths as $label => $path) {
    if (!is_file($path)) {
        fwrite(
            STDERR,
            "[FAIL] Missing {$label}: {$path}\n"
        );
        exit(1);
    }

    $content[$label] =
        (string)file_get_contents($path);
}

$checks = [
    'cart controls are registered as hidden' =>
        str_contains(
            $content['capability_yaml'],
            'id: product_online_order_controls'
        )
        && str_contains(
            $content['capability_yaml'],
            'status: hidden'
        ),
    'cart header is approved to hide' =>
        str_contains(
            $content['capability_yaml'],
            'id: cart_header_entry'
        )
        && str_contains(
            $content['capability_yaml'],
            'status: approved_to_hide'
        ),
    'hidden does not mean deleted' =>
        str_contains(
            $content['capability_md'],
            'Hidden functionality is not considered deleted'
        ),
    'five canonical colors are recorded' =>
        str_contains(
            $content['visual_yaml'],
            'max_core_colors: 5'
        )
        && str_contains(
            $content['visual_yaml'],
            'hex: "#0F0F0F"'
        )
        && str_contains(
            $content['visual_yaml'],
            'hex: "#E1E1E1"'
        )
        && str_contains(
            $content['visual_yaml'],
            'hex: "#969696"'
        ),
    'font-family limit is recorded' =>
        str_contains(
            $content['visual_yaml'],
            'max_font_families: 5'
        )
        && str_contains(
            $content['visual_yaml'],
            'target_font_families: 3'
        ),
    'named profiles are recorded' =>
        str_contains(
            $content['visual_yaml'],
            'legacy:'
        )
        && str_contains(
            $content['visual_yaml'],
            'controlled_v1:'
        )
        && str_contains(
            $content['visual_yaml'],
            'future_redesign:'
        ),
    'surface roots are recorded' =>
        str_contains(
            $content['visual_yaml'],
            '[data-fp-surface="home"]'
        )
        && str_contains(
            $content['visual_yaml'],
            '[data-fp-surface="product"]'
        ),
    'product optimizer is documented' =>
        str_contains(
            $content['media_md'],
            'base/libraries/GoodsImageUploadOptimizer.php'
        )
        && str_contains(
            $content['media_md'],
            '700 × 525'
        )
        && str_contains(
            $content['media_md'],
            'JPEG quality: `98`'
        )
        && str_contains(
            $content['media_md'],
            'maximum `1600 px`'
        )
        && str_contains(
            $content['media_md'],
            'JPEG quality: `94`'
        ),
    'frontend media roots are documented' =>
        str_contains(
            $content['media_md'],
            'base/userfiles/frontend/home/'
        )
        && str_contains(
            $content['media_md'],
            'base/userfiles/frontend/home/slider/'
        )
        && str_contains(
            $content['media_md'],
            'base/userfiles/frontend/search/'
        ),
    'documentation index references contracts' =>
        str_contains(
            $content['docs_readme'],
            '## Frontend control governance'
        )
        && str_contains(
            $content['docs_readme'],
            'Frontend Visual System v0.1'
        )
        && str_contains(
            $content['docs_readme'],
            'Media Storage and Image Processing Policy v0.1'
        ),
];

echo "== ForPrint frontend governance documentation smoke ==\n";

foreach ($checks as $label => $passed) {
    printf(
        "[%s] %s\n",
        $passed ? 'OK' : 'FAIL',
        $label
    );

    if (!$passed) {
        exit(2);
    }
}

/* FP_HOME_FEEDBACK_GOVERNANCE_CHECKS */
$fpFeedbackCapabilityYaml = (string)file_get_contents(
    $root
    . '/docs/reference/interface_capability_registry_v0_1.yaml'
);

$fpFeedbackDeferredMd = (string)file_get_contents(
    $root
    . '/docs/reference/disabled_and_deferred_interface_capabilities_v0_1.md'
);

$fpFeedbackContractMd = (string)file_get_contents(
    $root
    . '/docs/reference/home_frontend_functional_contract_v0_1.md'
);

$fpFeedbackContractYaml = (string)file_get_contents(
    $root
    . '/docs/reference/home_frontend_functional_contract_v0_1.yaml'
);

$fpFeedbackGovernanceChecks = [
    'feedback capability is approved to hide' =>
        str_contains(
            $fpFeedbackCapabilityYaml,
            'id: home_feedback_form'
        )
        && str_contains(
            $fpFeedbackCapabilityYaml,
            'status: approved_to_hide'
        ),
    'feedback assessment is recorded' =>
        str_contains(
            $fpFeedbackCapabilityYaml,
            'assessment: legacy_presentation_only'
        ),
    'feedback source points to owned component' =>
        str_contains(
            $fpFeedbackCapabilityYaml,
            'base/templates/default/surfaces/home/feedback.php'
        ),
    'feedback profile visibility is governed' =>
        str_contains(
            $fpFeedbackCapabilityYaml,
            'legacy: visible'
        )
        && str_contains(
            $fpFeedbackCapabilityYaml,
            'controlled_v1: hidden'
        ),
    'deferred document records legacy presentation only' =>
        str_contains(
            $fpFeedbackDeferredMd,
            '**Assessment:** `legacy_presentation_only`'
        )
        && str_contains(
            $fpFeedbackDeferredMd,
            '**Status:** `approved_to_hide`'
        ),
    'home contracts record approved hiding' =>
        str_contains(
            $fpFeedbackContractMd,
            'approved_to_hide'
        )
        && str_contains(
            $fpFeedbackContractYaml,
            'support_status: approved_to_hide'
        ),
];

foreach ($fpFeedbackGovernanceChecks as $label => $passed) {
    printf(
        "[%s] %s\n",
        $passed ? 'OK' : 'FAIL',
        $label
    );

    if (!$passed) {
        exit(2);
    }
}

echo "All frontend governance documentation checks passed.\n";
