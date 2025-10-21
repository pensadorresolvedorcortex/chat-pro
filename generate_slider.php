<?php
/**
 * Generate an importable Slider Revolution zip from the JSON export file.
 *
 * Usage options:
 *   CLI:    php generate_slider.php [output_path]
 *   Web:    Upload this script alongside slider_step_by_step_export.txt and
 *           visit it in the browser to download slider_step_by_step.zip.
 */

declare(strict_types=1);

const EXPORT_FILENAME = 'slider_step_by_step_export.txt';
const DEFAULT_ZIP_FILENAME = 'slider_step_by_step.zip';

main();

/**
 * Entrypoint that dispatches between CLI and browser modes.
 */
function main(): void
{
    if (!file_exists(EXPORT_FILENAME)) {
        respond_with_error(
            "Arquivo de exportação '" . EXPORT_FILENAME . "' não encontrado. Certifique-se\n" .
            "de enviar este script e o arquivo JSON de exportação para a mesma pasta."
        );
    }

    $export = file_get_contents(EXPORT_FILENAME);
    if ($export === false) {
        respond_with_error('Não foi possível ler o arquivo de exportação.');
    }

    try {
        $zipBytes = build_zip_from_export($export);
    } catch (RuntimeException $exception) {
        respond_with_error($exception->getMessage());
    }

    if (PHP_SAPI === 'cli') {
        $outputPath = $GLOBALS['argv'][1] ?? DEFAULT_ZIP_FILENAME;
        if (!preg_match('/\.zip$/i', $outputPath)) {
            $outputPath .= '.zip';
        }

        if (file_put_contents($outputPath, $zipBytes) === false) {
            fwrite(STDERR, "Não foi possível gravar {$outputPath}." . PHP_EOL);
            exit(1);
        }

        $size = number_format(filesize($outputPath));
        fwrite(STDOUT, "Arquivo gerado com sucesso: {$outputPath} ({$size} bytes)" . PHP_EOL);
        exit(0);
    }

    $downloadName = DEFAULT_ZIP_FILENAME;
    if (isset($_GET['filename']) && preg_match('/^[A-Za-z0-9_-]+$/', $_GET['filename'])) {
        $downloadName = $_GET['filename'] . '.zip';
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $downloadName . '"');
    header('Content-Length: ' . strlen($zipBytes));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    echo $zipBytes;
}

/**
 * Build the slider zip in memory.
 *
 * @param string $export Json export contents
 * @return string Binary zip data
 */
function build_zip_from_export(string $export): string
{
    if (!class_exists('ZipArchive')) {
        throw new RuntimeException(
            'A extensão ZipArchive do PHP precisa estar habilitada para gerar o arquivo.'
        );
    }

    $tempFile = tempnam(sys_get_temp_dir(), 'revslider');
    if ($tempFile === false) {
        throw new RuntimeException('Não foi possível criar o arquivo temporário.');
    }

    $zip = new ZipArchive();
    $openResult = $zip->open($tempFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    if ($openResult !== true) {
        @unlink($tempFile);
        throw new RuntimeException('Falha ao iniciar o arquivo zip (código ' . $openResult . ').');
    }

    if (!$zip->addFromString('slider_export.txt', $export)) {
        $zip->close();
        @unlink($tempFile);
        throw new RuntimeException('Não foi possível adicionar slider_export.txt ao pacote.');
    }

    if (!$zip->close()) {
        @unlink($tempFile);
        throw new RuntimeException('Falha ao finalizar o arquivo zip.');
    }

    $zipBytes = file_get_contents($tempFile);
    @unlink($tempFile);

    if ($zipBytes === false) {
        throw new RuntimeException('Não foi possível ler o arquivo zip gerado.');
    }

    return $zipBytes;
}

/**
 * Output an error message to the browser or CLI and exit.
 */
function respond_with_error(string $message): void
{
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }

    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo $message;
    exit;
}
