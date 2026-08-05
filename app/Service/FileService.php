<?php

namespace App\Service;

use RuntimeException;

class FileService
{
    public const MAX_SIZE = 2097152;

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'bmp', 'pdf', 'xls', 'xlsx', 'doc', 'docx'];

    public static function upload(array $file): ?array
    {
        if (empty($file['name'])) {
            return null;
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::erroUpload($error));
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_SIZE) {
            throw new RuntimeException('Tamanho máximo do arquivo deve ser de 2 MB.');
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException('Tipo de arquivo não permitido.');
        }

        $safe = substr(sha1((string) $file['name'] . microtime()), 7, 14);
        $dir = basePath('public/files/helpdesk/' . $safe . '/');

        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new RuntimeException('Falha ao criar o diretório de upload.');
        }

        $tmp = $dir . $safe . '.' . $ext;

        if (!move_uploaded_file((string) $file['tmp_name'], $tmp)) {
            throw new RuntimeException('Falha ao salvar o arquivo em disco.');
        }

        return [
            'dir' => $dir,
            'safe' => $safe,
            'ext' => $ext,
        ];
    }

    public static function finalize(array $upload, int $idHist): string
    {
        $old = $upload['dir'] . $upload['safe'] . '.' . $upload['ext'];
        $new = $upload['dir'] . $idHist . '.' . $upload['ext'];

        if (!@rename($old, $new)) {
            @unlink($old);
            throw new RuntimeException('Falha ao salvar o anexo.');
        }

        return 'files/helpdesk/' . $upload['safe'] . '/' . $idHist . '.' . $upload['ext'];
    }

    private static function erroUpload(int $code): string
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'O arquivo excede o limite de 2 MB.';
            case UPLOAD_ERR_PARTIAL:
                return 'O upload do arquivo foi feito parcialmente.';
            case UPLOAD_ERR_NO_FILE:
                return 'Nenhum arquivo foi enviado.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Pasta temporária ausente no servidor.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Falha ao escrever o arquivo em disco.';
            case UPLOAD_ERR_EXTENSION:
                return 'Uma extensão do PHP interrompeu o upload.';
            default:
                return 'Erro desconhecido no upload do arquivo.';
        }
    }
}
