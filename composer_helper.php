<?php
function composer_install(bool $cli = false): bool {
    $bin = '';
    if (is_callable('shell_exec')) {
        $bin = trim(shell_exec('command -v composer'));
    } elseif (is_callable('exec')) {
        exec('command -v composer', $out, $status);
        if ($status === 0 && isset($out[0])) {
            $bin = trim($out[0]);
        }
    }
    if (!$bin && is_file(__DIR__ . '/composer.phar')) {
        $bin = 'php ' . escapeshellarg(__DIR__ . '/composer.phar');
    }
    if (!$bin) {
        // tenta baixar o composer.phar localmente
        $phar = __DIR__ . '/composer.phar';
        $url = 'https://getcomposer.org/download/latest-stable/composer.phar';
        if (@copy($url, $phar)) {
            chmod($phar, 0755);
            $bin = 'php ' . escapeshellarg($phar);
        }
    }
    if (!$bin) {
        if ($cli) {
            fwrite(STDERR, "Composer nao encontrado\n");
        } else {
            echo "Composer nao encontrado\n";
        }
        return false;
    }
    $output = [];
    $home = getenv('COMPOSER_HOME');
    if (!$home) {
        $home = __DIR__ . '/composer-home';
        if (!is_dir($home)) {
            mkdir($home, 0777, true);
        }
    }
    $cmd = 'COMPOSER_HOME=' . escapeshellarg($home) . ' ' . $bin . ' install --no-interaction --no-dev';
    if (is_callable('exec')) {
        exec($cmd . ' 2>&1', $output, $code);
    } else {
        if ($cli) {
            fwrite(STDERR, "Comando PHP exec() desabilitado\n");
        } else {
            echo "Comando PHP exec() desabilitado<br>";
        }
        return false;
    }
    foreach ($output as $line) {
        if ($cli) {
            echo $line . PHP_EOL;
        } else {
            echo htmlspecialchars($line) . "<br>";
            @ob_flush();
            @flush();
        }
    }
    return $code === 0;
}
?>
