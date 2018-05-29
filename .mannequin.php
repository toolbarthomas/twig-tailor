<?php

use LastCall\Mannequin\Core\MannequinConfig;
use LastCall\Mannequin\Twig\TwigExtension;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Dotenv\Dotenv;

// Get environement specific variables we use for our workflow.
$env = new Dotenv();
$env->load(__DIR__.'/.env');

// Prevents Mannequin from running in production environments
if (getenv('TAILOR_ENV') != 'development') {
    throw new Exception("Mannequin only works in the development environment, please check `TAILOR_ENV` in: ./env", 1);
}

// Setup a default config. Each option should be defined within the dotenv file.
$config = [];

// The base path will define the cwd of our workflow.
$config['base_path'] = getenv('TAILOR_BASE_PATH') ?: __DIR__;

// Define a theme_path relative from the base_path option.
// Remove any base_path references from the theme_path.
$config['theme_path'] = str_replace($config['theme_path'], $config['base_path'], '') ?: '/src';

// Define all paths we look into. We use these paths to register a
$config['twig_paths'] = [
    [
        "path" => $config['base_path'] . $config['theme_path'] . '/components',
        "namespace" => 'components',
    ],
    [
        "path" => $config['base_path'] . $config['theme_path'] . '/pages',
        "namespace" => 'pages',
    ],
    [
        "path" => $config['base_path'] . $config['theme_path'] . '/layouts',
        "namespace" => 'layouts',
    ]
];

// Lookup all Twig templates
$twigTemplates = Finder::create()
    ->files()
    ->in(array_column($config['twig_paths'], 'path'))
    ->name('*.twig');

$twigExtension = new TwigExtension([
    'finder' => $twigTemplates,
    'twig_root' => $config['base_path'],
]);

// Iterate trough all defined Twig paths. Define a namespace for each path.
foreach ($config['twig_paths'] as $twig_path) {
    if (empty($twig_path['path']) || empty($twig_path['namespace'])) {
        continue;
    }

    $twigExtension->addTwigPath($twig_path['namespace'], $twig_path['path']);
}

// Create a Mannequin instance
$mannequin = MannequinConfig::create();

// Append Twig to Mannequin instance
$mannequin->addExtension($twigExtension);

// Defines the default stylesheet for our prototype.
$config['css_bundle_path'] = getenv('TAILOR_CSS_BUNDLE_PATH') ?: false;

// Append the bundled stylesheet
if ($config['css_bundle_path']) {
    $mannequin->setGlobalCSS([$config('css_bundle_path')]);
}

// Defines the default javascript for our prototype.
$config['js_bundle_path'] = getenv('TAILOR_JS_BUNDLE_PATH') ?: false;

// Append the bundled javascript
if ($config['js_bundle_path']) {
    $mannequin->setGlobalCSS([$config('js_bundle_path')]);
}

return $mannequin;
