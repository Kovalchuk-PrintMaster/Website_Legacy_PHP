<?php

declare(strict_types=1);

/**
 * ForPrint frontend-profile resolver and home feedback-gate smoke.
 *
 * READ ONLY.
 *
 * This smoke starts temporary PHP built-in servers on free local ports.
 * It never edits the local .env file.
 */

function fp_profile_smoke_fail(string $message): never
{
    fwrite(
        STDERR,
        "[FAIL] {$message}\n"
    );

    exit(2);
}

function fp_profile_smoke_free_port(): int
{
    for ($port = 18101; $port <= 18140; $port++) {
        $socket = @stream_socket_server(
            "tcp://127.0.0.1:{$port}",
            $errorCode,
            $errorMessage
        );

        if ($socket === false) {
            continue;
        }

        fclose($socket);

        return $port;
    }

    fp_profile_smoke_fail(
        'Could not find a free temporary port.'
    );
}

/**
 * @return array{status:int,body:string}
 */
function fp_profile_smoke_fetch(string $url): array
{
    $context = stream_context_create([
        'http' => [
            'timeout' => 3,
            'ignore_errors' => true,
        ],
    ]);

    $body = @file_get_contents(
        $url,
        false,
        $context
    );

    $status = 0;

    foreach ($http_response_header ?? [] as $header) {
        if (
            preg_match(
                '#^HTTP/\S+\s+(\d{3})#',
                $header,
                $matches
            ) === 1
        ) {
            $status = (int)$matches[1];
        }
    }

    return [
        'status' => $status,
        'body' => is_string($body) ? $body : '',
    ];
}

/**
 * @return array{status:int,body:string,log:string}
 */
function fp_profile_smoke_render(
    string $root,
    ?string $configuredProfile
): array {
    $port = fp_profile_smoke_free_port();

    $logPath = sys_get_temp_dir()
        . '/forprint_profile_smoke_'
        . $port
        . '.log';

    $environment = getenv();

    if (!is_array($environment)) {
        $environment = [];
    }

    unset(
        $environment['FP_WEB_FRONTEND_PROFILE']
    );

    if ($configuredProfile !== null) {
        $environment['FP_WEB_FRONTEND_PROFILE'] =
            $configuredProfile;
    }

    $command = [
        PHP_BINARY,
        '-d',
        'display_errors=1',
        '-S',
        "127.0.0.1:{$port}",
        '-t',
        $root . '/base',
    ];

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['file', $logPath, 'a'],
        2 => ['file', $logPath, 'a'],
    ];

    $process = proc_open(
        $command,
        $descriptors,
        $pipes,
        $root,
        $environment
    );

    if (!is_resource($process)) {
        fp_profile_smoke_fail(
            'Could not start temporary PHP server.'
        );
    }

    if (isset($pipes[0]) && is_resource($pipes[0])) {
        fclose($pipes[0]);
    }

    $result = [
        'status' => 0,
        'body' => '',
        'log' => '',
    ];

    try {
        $url = "http://127.0.0.1:{$port}/";

        for ($attempt = 0; $attempt < 30; $attempt++) {
            usleep(100000);

            $result = fp_profile_smoke_fetch(
                $url
            );

            if ($result['status'] === 200) {
                break;
            }

            $state = proc_get_status(
                $process
            );

            if (
                !is_array($state)
                || !($state['running'] ?? false)
            ) {
                break;
            }
        }

        $result['log'] = is_file($logPath)
            ? (string)file_get_contents($logPath)
            : '';
    } finally {
        proc_terminate($process);

        for ($attempt = 0; $attempt < 20; $attempt++) {
            usleep(50000);

            $state = proc_get_status(
                $process
            );

            if (
                !is_array($state)
                || !($state['running'] ?? false)
            ) {
                break;
            }
        }

        proc_close($process);

        if (is_file($logPath)) {
            unlink($logPath);
        }
    }

    return $result;
}

$root = dirname(__DIR__, 2);

$paths = [
    'base_user' =>
        $root . '/base/core/user/controllers/BaseUser.php',
    'home_controller' =>
        $root . '/base/core/user/controllers/IndexController.php',
    'home_index' =>
        $root . '/base/templates/default/index.php',
    'feedback' =>
        $root
        . '/base/templates/default/surfaces/home/feedback.php',
    'env_example' =>
        $root . '/config/env/website.local.example',
    'makefile' =>
        $root . '/Makefile',
    'visual_md' =>
        $root
        . '/docs/architecture/frontend_visual_system_v0_1.md',
    'visual_yaml' =>
        $root
        . '/docs/architecture/frontend_visual_system_v0_1.yaml',
    'capability_yaml' =>
        $root
        . '/docs/reference/interface_capability_registry_v0_1.yaml',
    'contract_md' =>
        $root
        . '/docs/reference/home_frontend_functional_contract_v0_1.md',
    'contract_yaml' =>
        $root
        . '/docs/reference/home_frontend_functional_contract_v0_1.yaml',
];

$content = [];

foreach ($paths as $label => $path) {
    if (!is_file($path)) {
        fp_profile_smoke_fail(
            "Missing {$label}: {$path}"
        );
    }

    $content[$label] =
        (string)file_get_contents($path);
}

$sourceChecks = [
    'BaseUser owns allowed frontend profiles' =>
        str_contains(
            $content['base_user'],
            'FRONTEND_PROFILES'
        )
        && str_contains(
            $content['base_user'],
            "'controlled_v1'"
        )
        && str_contains(
            $content['base_user'],
            "'future_redesign'"
        ),
    'BaseUser owns environment-backed resolver' =>
        str_contains(
            $content['base_user'],
            'resolveFrontendProfile'
        )
        && str_contains(
            $content['base_user'],
            'FP_WEB_FRONTEND_PROFILE'
        )
        && str_contains(
            $content['base_user'],
            "return 'legacy';"
        ),
    'home controller uses resolver' =>
        str_contains(
            $content['home_controller'],
            '$this->frontendProfile = '
            . '$this->resolveFrontendProfile();'
        )
        && !str_contains(
            $content['home_controller'],
            '$this->frontendProfile = '
            . "'legacy';"
        ),
    'home feedback gate is at include boundary' =>
        str_contains(
            $content['home_index'],
            "\$this->frontendProfile !== 'controlled_v1'"
        )
        && str_contains(
            $content['home_index'],
            "/surfaces/home/feedback.php"
        ),
    'legacy feedback component remains unchanged in behavior' =>
        str_contains(
            $content['feedback'],
            '<form action="index.html" class="feedback__form">'
        )
        && preg_match(
            '/<(input|textarea|select)\b[^>]*\bname\s*=/i',
            $content['feedback']
        ) !== 1,
    'operator env example documents profile' =>
        str_contains(
            $content['env_example'],
            'FP_WEB_FRONTEND_PROFILE=legacy'
        ),
    'Makefile exposes profile state' =>
        str_contains(
            $content['makefile'],
            'FP_WEB_FRONTEND_PROFILE'
        ),
    'visual architecture records env resolver' =>
        str_contains(
            $content['visual_md'],
            '`FP_WEB_FRONTEND_PROFILE`'
        )
        && str_contains(
            $content['visual_yaml'],
            'configuration_key: FP_WEB_FRONTEND_PROFILE'
        ),
    'capability registry records implemented gate' =>
        str_contains(
            $content['capability_yaml'],
            'state: implemented'
        )
        && str_contains(
            $content['capability_yaml'],
            'boundary: base/templates/default/index.php'
        ),
    'home contracts record conditional profile gate' =>
        str_contains(
            $content['contract_md'],
            'environment-backed profile resolver'
        )
        && str_contains(
            $content['contract_yaml'],
            'conditional: true'
        )
        && str_contains(
            $content['contract_yaml'],
            'configuration_key: FP_WEB_FRONTEND_PROFILE'
        ),
];

echo "== ForPrint frontend-profile resolver smoke ==\n";

foreach ($sourceChecks as $label => $passed) {
    printf(
        "[%s] %s\n",
        $passed ? 'OK' : 'FAIL',
        $label
    );

    if (!$passed) {
        exit(2);
    }
}

$cases = [
    [
        'label' => 'unset profile falls back to legacy',
        'configured' => null,
        'expected' => 'legacy',
        'feedback' => true,
    ],
    [
        'label' => 'explicit legacy remains legacy',
        'configured' => 'legacy',
        'expected' => 'legacy',
        'feedback' => true,
    ],
    [
        'label' => 'controlled_v1 hides feedback',
        'configured' => 'controlled_v1',
        'expected' => 'controlled_v1',
        'feedback' => false,
    ],
    [
        'label' => 'future_redesign keeps undecided feedback visible',
        'configured' => 'future_redesign',
        'expected' => 'future_redesign',
        'feedback' => true,
    ],
    [
        'label' => 'invalid profile falls back to legacy',
        'configured' => 'not-a-profile',
        'expected' => 'legacy',
        'feedback' => true,
    ],
];

foreach ($cases as $case) {
    $result = fp_profile_smoke_render(
        $root,
        $case['configured']
    );

    $marker =
        'data-fp-frontend-profile="'
        . $case['expected']
        . '"';

    $feedbackVisible =
        str_contains(
            $result['body'],
            '<section class="feedback '
        )
        && str_contains(
            $result['body'],
            'feedback__form'
        );

    $passed =
        $result['status'] === 200
        && str_contains(
            $result['body'],
            'data-fp-surface="home"'
        )
        && str_contains(
            $result['body'],
            $marker
        )
        && $feedbackVisible
            === $case['feedback'];

    printf(
        "[%s] %s configured=%s expected=%s bytes=%d\n",
        $passed ? 'OK' : 'FAIL',
        $case['label'],
        $case['configured'] ?? '(unset)',
        $case['expected'],
        strlen($result['body'])
    );

    if (!$passed) {
        fwrite(
            STDERR,
            $result['log'] . "\n"
        );

        exit(3);
    }
}

echo "All frontend-profile resolver checks passed.\n";