<?php

declare(strict_types=1);

$configurator = require __DIR__ .'/vendor/sigwin/infra/resources/PHP/php-cs-fixer.php';

$header = <<<'EOF'
This file is part of the yassg project.

(c) sigwin.hr

This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

$config = $configurator(__DIR__, $header);
$config
    ->getFinder()
        ->exclude('resources/init/demo/src');

return $config;
