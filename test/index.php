<?php

// move out of test for includes
chdir('..');
require_once './core/functions.php';

localnet_or_die();
permission_or_die(PERM_ADMIN);

// disable debug log while testing.
global $config;
$origDebug = $config['debug'];
$config['debug'] = 0;
?>

<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8"/>
        <title>Test Documentation</title>
        <style>
            body {
                text-rendering: optimizeLegibility;
                font-family: Source SansSerif Pro, Arial, sans-serif;
                font-variant-ligatures: common-ligatures;
                font-kerning: normal;
                margin-left: 2rem;
                background-color: #fff;
                color: #000;
            }

            body &gt; ul &gt; li {
                font-size: larger;
            }

            h2 {
                font-size: larger;
                text-decoration-line: underline;
                text-decoration-thickness: 2px;
                margin: 0;
                padding: 0.5rem 0;
            }

            ul {
                list-style: none;
                margin: 0 0 2rem;
                padding: 0 0 0 1rem;
                text-indent: -1rem;
            }

            .success:before {
                color: #4e9a06;
                content: '✓';
                padding-right: 0.5rem;
            }

            .defect {
                color: #a40000;
            }

            .defect:before {
                color: #a40000;
                content: '✗';
                padding-right: 0.5rem;
            }
        </style>
    </head>
    <body>
<?php

$testName = $_REQUEST['test'];

$testClasses = findTestClasses('./test', $testName);

// 'loadedExtensions', 'extensions', 'notLoadedExtensions' is just to avoid PHP 7.4 warnings.
$args = [];
$args['loadedExtensions'] = [];
$args['extensions'] = [];
$args['notLoadedExtensions'] = [];
$args['testdoxHTMLFile'] = true;
$args['cacheResult'] = false;

$warnings = [];
$stopOnError = false;

// File name and class name must match because TestSuite takes a CLASS NAME!!!
foreach ($testClasses as $name => $className) {
    $suite = new PHPUnit\Framework\TestSuite($name);
    ob_start();

    $runner = new PHPUnit\TextUI\TestRunner;
    $testResult = $runner->run($suite, $args, $warnings, $stopOnError);
    $result = ob_get_clean();

    preg_match('/&lt;body&gt;(.+?)&lt;\/body&gt;/si', $result, $body);
    echo html_entity_decode($body[1]);

    preg_match('/&lt;\/body&gt;.+?&lt;\/html&gt;(.+)/si', $result, $description);
    echo "<pre>"; echo $description[1]; echo "</pre><br><br>";
}

echo "</body></html>";

// Set debug back to the original value.
$config['debug'] = $origDebug;

/**
 * Find files that either starts with or ends with test.
 *
 * @param string    rootDir Look for test files in here
 * @param string    pattern If set then only test containing this pattern will be executed
 * @psalm-return array<string, string>
 */
function findTestClasses(string $rootDir, $pattern): array {
    $result = array();

    $directory = new \RecursiveDirectoryIterator($rootDir);
    $iterator = new \RecursiveIteratorIterator($directory);

    foreach ($iterator as $info) {
        $fileName = $info->getFilename();

        if ($pattern && stristr($fileName, $pattern) == false) {
            continue;
        }

        if (preg_match("/^(test(.+?))\.php$/i", $fileName, $matches) || preg_match("/^((.+?)test)\.php$/i", $fileName, $matches)) {
            $result[$matches[1]] = $matches[2];
            require_once($info->getPathname());
        }
    }

    return $result;
}

?>
