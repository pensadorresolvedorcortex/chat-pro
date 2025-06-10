<?php
function composer_install(bool $cli = false): bool {
    $bin = trim(shell_exec('command -v composer'));
    if (!$bin && is_file(__DIR__ . '/composer.phar')) {
        $bin = 'php ' . escapeshellarg(__DIR__ . '/composer.phar');
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
    $cmd = $bin . ' install --no-interaction --no-dev';
    exec($cmd . ' 2>&1', $output, $code);
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
