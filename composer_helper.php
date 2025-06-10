<?php

function run_command(string $cmd, &$output = null, &$code = null): bool {
    $output = [];
    $code = 1;
    if (is_callable('exec')) {
        exec($cmd, $output, $code);
        return $code === 0;
    }
    if (is_callable('shell_exec')) {
        $res = shell_exec($cmd);
        if ($res !== null) {
            $output = preg_split('/\r?\n/', trim($res));
            $code = 0;
            return true;
        }
    }
    if (function_exists('proc_open')) {
        $descs = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
        $proc = proc_open($cmd, $descs, $pipes);
        if (is_resource($proc)) {
            $output = explode("\n", stream_get_contents($pipes[1]));
            $err = stream_get_contents($pipes[2]);
            $code = proc_close($proc);
            if ($err) $output = array_merge($output, explode("\n", $err));
            return $code === 0;
        }
    }
    return false;
}

function composer_install(bool $cli = false): bool {
    $bin = '';
    if (!run_command('command -v composer', $out, $status) && $status !== 0) {
        $out = [];
    }
    if (!empty($out[0])) {
        $bin = trim($out[0]);
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
    $cmd = 'COMPOSER_HOME=' . escapeshellarg($home) . ' ' . $bin . ' install --no-interaction --no-dev 2>&1';
    run_command($cmd, $output, $code);
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
