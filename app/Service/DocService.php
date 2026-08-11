<?php

namespace App\Service;

use App\Model\Consinco\FuncaoPaiModel;
use App\Model\Mysql\DocModel;
use App\Core\DB;
use App\Core\Logger;
use RuntimeException;
use Throwable;

class DocService
{
    public const MAX_SIZE = 104857600;

    private const ALLOWED_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'bmp', 'jpg', 'jpeg', 'png', 'mp4'];

    private const INLINE_EXTENSIONS = ['pdf', 'jpg', 'jpeg', 'png', 'bmp', 'mp4'];

    private const ICONES = [
        'pdf' => 'fa-file-pdf',
        'xls' => 'fa-file-excel',
        'xlsx' => 'fa-file-excel',
        'doc' => 'fa-file-word',
        'docx' => 'fa-file-word',
        'ppt' => 'fa-file-powerpoint',
        'pptx' => 'fa-file-powerpoint',
        'bmp' => 'fa-file-image',
        'jpg' => 'fa-file-image',
        'jpeg' => 'fa-file-image',
        'png' => 'fa-file-image',
        'mp4' => 'fa-file-video',
    ];

    private const MIMES = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'bmp' => 'image/bmp',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'mp4' => 'video/mp4',
    ];

    public static function codfuncaoPai(): ?int
    {
        $codfuncao = AuthService::getUser()['codfuncao'] ?? null;

        if ($codfuncao === null || $codfuncao === '') {
            return null;
        }

        try {
            return FuncaoPaiModel::codfuncaoPai((string) $codfuncao);
        } catch (Throwable $e) {
            Logger::exception($e);

            return null;
        }
    }

    public static function arvore(): array
    {
        $pai = self::codfuncaoPai();
        $cpf = AuthService::getUserCpf() ?? '';

        $arvore = DocModel::arvore($pai, $cpf);

        foreach ($arvore as &$dir) {
            foreach ($dir['subdirs'] as &$sub) {
                foreach ($sub['docs'] as &$doc) {
                    $doc['existe'] = is_file(self::caminhoAbsoluto($doc['caminho']));
                    $doc['tamanho_texto'] = self::formatarTamanho($doc['tamanho']);
                    $doc['icone'] = self::iconeTipo($doc['tipo']);
                }
                unset($doc);
            }
            unset($sub);
        }
        unset($dir);

        return $arvore;
    }

    public static function permitido(int $idDoc): bool
    {
        return DocModel::temPermissao($idDoc, self::codfuncaoPai());
    }

    public static function abrir(int $idDoc): ?array
    {
        $doc = DocModel::documentoComVersao($idDoc);

        if ($doc === null || !self::permitido($idDoc)) {
            return null;
        }

        $caminho = self::caminhoAbsoluto($doc['caminho']);

        if (!is_file($caminho)) {
            return null;
        }

        $cpf = AuthService::getUserCpf();

        if ($cpf !== null && $cpf !== '') {
            try {
                DocModel::registrarAcesso($cpf, $idDoc, (int) $doc['id_versao']);
            } catch (Throwable $e) {
                Logger::exception($e);
            }
        }

        return [
            'id_doc' => $idDoc,
            'titulo' => $doc['titulo'],
            'caminho_absoluto' => $caminho,
            'basename' => basename($caminho),
            'tipo' => strtolower((string) $doc['tipo']),
            'tamanho' => (int) $doc['tamanho'],
            'inline' => in_array(strtolower((string) $doc['tipo']), self::INLINE_EXTENSIONS, true),
        ];
    }

    public static function abrirAdmin(int $idDoc): ?array
    {
        $doc = DocModel::documentoComVersao($idDoc);

        if ($doc === null) {
            return null;
        }

        $caminho = self::caminhoAbsoluto($doc['caminho']);

        if (!is_file($caminho)) {
            return null;
        }

        return [
            'id_doc' => $idDoc,
            'titulo' => $doc['titulo'],
            'caminho_absoluto' => $caminho,
            'basename' => basename($caminho),
            'tipo' => strtolower((string) $doc['tipo']),
            'tamanho' => (int) $doc['tamanho'],
            'inline' => in_array(strtolower((string) $doc['tipo']), self::INLINE_EXTENSIONS, true),
        ];
    }

    public static function processarUpload(array $file, int $idDir, int $idSubdir, bool $geral, array $funcoes = []): array
    {
        $arquivo = self::validarArquivo($file);
        $nomeArquivo = $arquivo['nome'];
        $ext = $arquivo['ext'];

        $dirs = self::diretoriosParaCaminho($idDir, $idSubdir);

        $relativo = 'documentos/' . $dirs['diretorio'] . '/' . $dirs['subdiretorio'] . '/' . $nomeArquivo;
        $absoluto = self::caminhoAbsoluto($relativo);

        $destino = dirname($absoluto);

        if (!is_dir($destino) && !mkdir($destino, 0755, true)) {
            throw new RuntimeException('Falha ao criar o diretório de destino.');
        }

        if (!move_uploaded_file((string) $file['tmp_name'], $absoluto)) {
            throw new RuntimeException('Falha ao salvar o arquivo em disco.');
        }

        $cpf = AuthService::getUserCpf() ?? 'sistema';
        $titulo = $file['name'] !== '' ? (string) $file['name'] : $nomeArquivo;

        $idDocExistente = DocModel::buscarDocPorArquivo($idDir, $idSubdir, $nomeArquivo);

        if ($idDocExistente !== null) {
            $versao = (DocModel::ultimaVersao($idDocExistente) ?? 0) + 1;

            $idVersao = DocModel::criarVersao([
                'id_doc' => $idDocExistente,
                'versao' => $versao,
                'rotulo' => (string) $versao,
                'caminho' => $relativo,
                'tamanho' => (int) filesize($absoluto),
                'dt_upload' => date('Y-m-d H:i:s'),
                'uploader' => $cpf,
            ]);

            if ($idVersao === null) {
                throw new RuntimeException('Erro ao registrar a nova versão.');
            }

            DocModel::definirVersaoAtual($idDocExistente, $idVersao);

            return ['mensagem' => "Nova versão ({$versao}) registrada para o documento {$titulo}."];
        }

        $idDoc = DocModel::criarDocumento([
            'titulo' => $titulo,
            'id_dir' => $idDir,
            'id_subdir' => $idSubdir,
            'tipo' => $ext,
            'geral' => $geral ? 'S' : 'N',
            'datacad' => date('Y-m-d H:i:s'),
            'uploader' => $cpf,
            'ativo' => 1,
        ]);

        if ($idDoc === null) {
            @unlink($absoluto);

            throw new RuntimeException('Erro ao criar o documento.');
        }

        $idVersao = DocModel::criarVersao([
            'id_doc' => $idDoc,
            'versao' => 1,
            'rotulo' => '1',
            'caminho' => $relativo,
            'tamanho' => (int) filesize($absoluto),
            'dt_upload' => date('Y-m-d H:i:s'),
            'uploader' => $cpf,
        ]);

        if ($idVersao === null) {
            @unlink($absoluto);

            throw new RuntimeException('Erro ao registrar a versão do documento.');
        }

        DocModel::definirVersaoAtual($idDoc, $idVersao);

        if (!$geral) {
            self::registrarPermissoes($idDoc, $funcoes);
        }

        return ['mensagem' => "Documento {$titulo} cadastrado com sucesso."];
    }

    public static function processarNovaVersao(int $idDoc, array $file): array
    {
        $doc = DocModel::documentoComVersao($idDoc);

        if ($doc === null) {
            throw new RuntimeException('Documento não encontrado.');
        }

        $arquivo = self::validarArquivo($file);
        $nomeArquivo = $arquivo['nome'];

        $dirs = self::diretoriosParaCaminho((int) $doc['id_dir'], (int) $doc['id_subdir']);

        $relativo = 'documentos/' . $dirs['diretorio'] . '/' . $dirs['subdiretorio'] . '/' . $nomeArquivo;
        $absoluto = self::caminhoAbsoluto($relativo);

        $destino = dirname($absoluto);

        if (!is_dir($destino) && !mkdir($destino, 0755, true)) {
            throw new RuntimeException('Falha ao criar o diretório de destino.');
        }

        if (!move_uploaded_file((string) $file['tmp_name'], $absoluto)) {
            throw new RuntimeException('Falha ao salvar o arquivo em disco.');
        }

        $cpf = AuthService::getUserCpf() ?? 'sistema';
        $versao = (DocModel::ultimaVersao($idDoc) ?? 0) + 1;

        $idVersao = DocModel::criarVersao([
            'id_doc' => $idDoc,
            'versao' => $versao,
            'rotulo' => (string) $versao,
            'caminho' => $relativo,
            'tamanho' => (int) filesize($absoluto),
            'dt_upload' => date('Y-m-d H:i:s'),
            'uploader' => $cpf,
        ]);

        if ($idVersao === null) {
            @unlink($absoluto);

            throw new RuntimeException('Erro ao registrar a nova versão.');
        }

        DocModel::definirVersaoAtual($idDoc, $idVersao);

        return ['mensagem' => "Nova versão ({$versao}) registrada para o documento {$doc['titulo']}."];
    }

    public static function listarDiretorios(): array
    {
        return DocModel::listarDiretorios();
    }

    public static function listarSubdiretorios(?int $idDir = null): array
    {
        return DocModel::listarSubdiretorios($idDir);
    }

    public static function listarFuncoes(): array
    {
        try {
            return FuncaoPaiModel::listarFuncoes();
        } catch (Throwable $e) {
            Logger::exception($e);

            return [];
        }
    }

    public static function listarDocumentosAdmin(?int $idDir = null, ?int $idSubdir = null, ?string $nome = null): array
    {
        $documentos = DocModel::listarDocumentosAdmin($idDir, $idSubdir, $nome);

        foreach ($documentos as &$doc) {
            $doc['existe'] = is_file(self::caminhoAbsoluto($doc['caminho']));
            $doc['tamanho_texto'] = self::formatarTamanho((int) $doc['tamanho']);
        }
        unset($doc);

        return $documentos;
    }

    public static function permissoesPorDocumento(): array
    {
        return DocModel::permissoesPorDocumento();
    }

    public static function versoesPorDocumento(): array
    {
        $versoes = DocModel::versoesPorDocumento();

        foreach ($versoes as &$lista) {
            foreach ($lista as &$versao) {
                $versao['existe'] = is_file(self::caminhoAbsoluto($versao['caminho']));
                $versao['tamanho_texto'] = self::formatarTamanho($versao['tamanho']);
            }
            unset($versao);
        }
        unset($lista);

        return $versoes;
    }

    public static function atualizarPermissoes(int $idDoc, bool $geral, array $funcoes = []): bool
    {
        return DocModel::atualizarPermissoes($idDoc, $geral, $funcoes);
    }

    public static function adicionarPermissao(int $idDoc, string $codfuncao): string
    {
        if (!DocModel::adicionarPermissao($idDoc, $codfuncao)) {
            throw new RuntimeException('Documento inválido ou função não informada.');
        }

        return 'Permissão adicionada ao documento.';
    }

    public static function removerPermissao(int $idDoc, string $codfuncao): string
    {
        if (!DocModel::removerPermissao($idDoc, $codfuncao)) {
            throw new RuntimeException('Documento inválido ou função não informada.');
        }

        return 'Permissão removida do documento.';
    }

    public static function alternarGeral(int $idDoc, bool $geral): string
    {
        if (!DocModel::alternarGeral($idDoc, $geral)) {
            throw new RuntimeException('Documento inválido.');
        }

        return $geral ? 'Acesso geral ativado para o documento.' : 'Acesso geral desativado para o documento.';
    }

    public static function copiarPermissoes(int $funcaoOrigem, int $funcaoDestino): string
    {
        if ($funcaoOrigem <= 0 || $funcaoDestino <= 0) {
            throw new RuntimeException('Selecione as funções de origem e destino.');
        }

        if ($funcaoOrigem === $funcaoDestino) {
            throw new RuntimeException('A função de destino deve ser diferente da origem.');
        }

        $copiadas = DocModel::copiarPermissoes($funcaoOrigem, $funcaoDestino);

        return "{$copiadas} permissões copiadas para a função de destino.";
    }

    public static function restaurarVersao(int $idDoc, int $idVersao): bool
    {
        return DocModel::restaurarVersao($idDoc, $idVersao);
    }

    public static function excluirDocumento(int $idDoc): bool
    {
        $doc = DocModel::documentoComVersao($idDoc);

        if ($doc === null) {
            return false;
        }

        if (!DocModel::excluirDocumento($idDoc)) {
            return false;
        }

        $caminho = self::caminhoAbsoluto($doc['caminho']);

        if (DocModel::outrosComMesmoCaminho($doc['caminho'], $idDoc) === 0 && is_file($caminho)) {
            @unlink($caminho);
        }

        return true;
    }

    public static function criarDiretorio(string $nome): string
    {
        $nome = trim($nome);

        if ($nome === '') {
            throw new RuntimeException('Informe o nome do diretório.');
        }

        if (DocModel::diretorioExiste($nome)) {
            throw new RuntimeException("Já existe um diretório chamado {$nome}.");
        }

        if (DocModel::criarDiretorio($nome) === null) {
            throw new RuntimeException('Erro ao criar o diretório.');
        }

        return "Diretório {$nome} criado com sucesso.";
    }

    public static function criarSubdiretorio(int $idDir, string $nome): string
    {
        $nome = trim($nome);

        if ($idDir <= 0) {
            throw new RuntimeException('Selecione o diretório da subpasta.');
        }

        if ($nome === '') {
            throw new RuntimeException('Informe o nome da subpasta.');
        }

        if (DocModel::subdiretorioExiste($idDir, $nome)) {
            throw new RuntimeException("Já existe uma subpasta chamada {$nome} neste diretório.");
        }

        if (DocModel::criarSubdiretorio($idDir, $nome) === null) {
            throw new RuntimeException('Erro ao criar a subpasta.');
        }

        return "Subpasta {$nome} criada com sucesso.";
    }

    public static function excluirDiretorio(int $idDir): string
    {
        if (!DocModel::excluirDiretorio($idDir)) {
            throw new RuntimeException('Não é possível excluir: o diretório possui documentos.');
        }

        return 'Diretório excluído com sucesso.';
    }

    public static function excluirSubdiretorio(int $idSubdir): string
    {
        if (!DocModel::excluirSubdiretorio($idSubdir)) {
            throw new RuntimeException('Não é possível excluir: a subpasta possui documentos.');
        }

        return 'Subpasta excluída com sucesso.';
    }

    private static function registrarPermissoes(int $idDoc, array $funcoes): void
    {
        foreach ($funcoes as $codfuncao) {
            $codfuncao = (string) $codfuncao;

            if ($codfuncao === '') {
                continue;
            }

            DB::select("
                INSERT IGNORE INTO doc_permissao (id_doc, codfuncao)
                VALUES (:id_doc, :codfuncao)
            ", ['id_doc' => $idDoc, 'codfuncao' => $codfuncao], 'mysql');
        }
    }

    private static function diretoriosParaCaminho(int $idDir, int $idSubdir): array
    {
        $dir = null;
        $sub = null;

        foreach (DocModel::listarDiretorios() as $d) {
            if ((int) $d['id'] === $idDir) {
                $dir = $d['nome'];
                break;
            }
        }

        foreach (DocModel::listarSubdiretorios($idDir) as $s) {
            if ((int) $s['id'] === $idSubdir) {
                $sub = $s['nome'];
                break;
            }
        }

        if ($dir === null || $sub === null) {
            throw new RuntimeException('Diretório ou subdiretório inválido.');
        }

        return ['diretorio' => $dir, 'subdiretorio' => $sub];
    }

    public static function caminhoAbsoluto(string $caminhoRelativo): string
    {
        return basePath('public/files/' . ltrim($caminhoRelativo, '/'));
    }

    public static function formatarTamanho(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 1, ',', '.') . ' GB';
        }

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 1, ',', '.') . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        }

        return $bytes . ' B';
    }

    public static function iconeTipo(string $tipo): string
    {
        return self::ICONES[$tipo] ?? 'fa-file';
    }

    public static function mimeTipo(string $tipo): string
    {
        return self::MIMES[$tipo] ?? 'application/octet-stream';
    }

    private static function validarArquivo(array $file): array
    {
        if (empty($file['name'])) {
            throw new RuntimeException('Nenhum arquivo enviado.');
        }

        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException(self::erroUpload($error));
        }

        if ((int) ($file['size'] ?? 0) > self::MAX_SIZE) {
            throw new RuntimeException('Tamanho máximo do arquivo deve ser de 100 MB.');
        }

        $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException('Tipo de arquivo não permitido.');
        }

        return [
            'nome' => self::sanitizarNome((string) $file['name']),
            'ext' => $ext,
        ];
    }

    private static function sanitizarNome(string $nome): string
    {
        $nome = str_replace(['/', '\\', "\0"], '-', basename($nome));

        return trim($nome) !== '' ? $nome : 'arquivo';
    }

    private static function erroUpload(int $code): string
    {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'O arquivo excede o limite permitido.';
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
