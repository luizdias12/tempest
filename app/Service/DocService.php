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
        return DocModel::temPermissao($idDoc, self::codfuncaoPai(), AuthService::getUserCpf());
    }

    public static function podeGerirDocumento(int $idDoc): bool
    {
        $dir = DocModel::diretorioDoDocumento($idDoc);

        return $dir !== null && self::podeGerirRegistroDiretorio($dir);
    }

    public static function podeGerirDiretorio(int $idDir): bool
    {
        $dir = DocModel::diretorioPorId($idDir);

        return $dir !== null && self::podeGerirRegistroDiretorio($dir);
    }

    public static function podeExcluirDiretorio(int $idDir): bool
    {
        $dir = DocModel::diretorioPorId($idDir);

        if ($dir === null) {
            return false;
        }

        if (($dir['visibilidade'] ?? 'publico') === 'restrito') {
            return self::podeGerirRegistroDiretorio($dir);
        }

        return AuthService::hasPermission('ti');
    }

    public static function podeExcluirSubdiretorio(int $idSubdir): bool
    {
        $sub = DocModel::subdiretorioPorId($idSubdir);

        return $sub !== null && self::podeExcluirDiretorio((int) $sub['id_dir']);
    }

    public static function diretorioRestrito(int $idDir): bool
    {
        $dir = DocModel::diretorioPorId($idDir);

        return $dir !== null && ($dir['visibilidade'] ?? 'publico') === 'restrito';
    }

    private static function podeGerirRegistroDiretorio(array $dir): bool
    {
        if (($dir['visibilidade'] ?? 'publico') !== 'restrito') {
            return true;
        }

        $cpf = AuthService::getUserCpf();

        return $cpf !== null && $cpf !== '' && (string) ($dir['criador_cpf'] ?? '') === $cpf;
    }

    private static function exigirGestaoDocumento(int $idDoc): void
    {
        if (!self::podeGerirDocumento($idDoc)) {
            throw new RuntimeException('Documento não encontrado.');
        }
    }

    private static function exigirEdicaoPermissoes(int $idDoc): void
    {
        self::exigirGestaoDocumento($idDoc);

        $dir = DocModel::diretorioDoDocumento($idDoc);

        if ($dir !== null && ($dir['visibilidade'] ?? 'publico') === 'restrito') {
            throw new RuntimeException('Documentos de pasta restrita não usam permissões por função.');
        }
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

        if ($doc === null || !self::podeGerirDocumento($idDoc)) {
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

    public static function abrirVersaoAdmin(int $idDoc, int $idVersao): ?array
    {
        if ($idDoc <= 0 || $idVersao <= 0) {
            return null;
        }

        if (!self::podeGerirDocumento($idDoc)) {
            return null;
        }

        $versao = DocModel::versaoArquivo($idDoc, $idVersao);

        if ($versao === null) {
            return null;
        }

        $caminho = self::caminhoAbsoluto($versao['caminho']);

        if (!is_file($caminho)) {
            return null;
        }

        $tipo = strtolower(pathinfo((string) $versao['caminho'], PATHINFO_EXTENSION));

        if ($tipo === '' || !isset(self::MIMES[$tipo])) {
            $tipo = strtolower((string) $versao['tipo']);
        }

        return [
            'id_doc' => $idDoc,
            'titulo' => $versao['titulo'],
            'caminho_absoluto' => $caminho,
            'basename' => basename($caminho),
            'tipo' => $tipo,
            'tamanho' => (int) $versao['tamanho'],
            'inline' => in_array($tipo, self::INLINE_EXTENSIONS, true),
        ];
    }

    public static function processarUpload(array $file, int $idDir, int $idSubdir, bool $geral, array $funcoes = []): array
    {
        $arquivo = self::validarArquivo($file);
        $nomeArquivo = $arquivo['nome'];
        $ext = $arquivo['ext'];

        $cpf = AuthService::getUserCpf() ?? 'sistema';
        $titulo = $file['name'] !== '' ? (string) $file['name'] : $nomeArquivo;

        $idDocExistente = DocModel::buscarDocPorArquivo($idDir, $idSubdir, $nomeArquivo);

        if ($idDocExistente !== null) {
            $versao = (DocModel::ultimaVersao($idDocExistente) ?? 0) + 1;
            $nomeArquivo = self::nomeVersao($nomeArquivo, $versao);
        }

        $dirs = self::diretoriosParaCaminho($idDir, $idSubdir);

        if ($dirs['restrito']) {
            $geral = false;
            $funcoes = [];
        }

        $relativo = 'documentos/' . $dirs['diretorio'] . '/' . $dirs['subdiretorio'] . '/' . $nomeArquivo;
        $absoluto = self::caminhoAbsoluto($relativo);

        $destino = dirname($absoluto);

        if (!is_dir($destino) && !mkdir($destino, 0755, true)) {
            throw new RuntimeException('Falha ao criar o diretório de destino.');
        }

        if (!move_uploaded_file((string) $file['tmp_name'], $absoluto)) {
            throw new RuntimeException('Falha ao salvar o arquivo em disco.');
        }

        if ($idDocExistente !== null) {
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
                @unlink($absoluto);

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

        return ['mensagem' => "Documento {$idDoc} - {$titulo} cadastrado com sucesso"];
    }

    public static function processarNovaVersao(int $idDoc, array $file): array
    {
        $doc = DocModel::documentoComVersao($idDoc);

        if ($doc === null) {
            throw new RuntimeException('Documento não encontrado.');
        }

        self::exigirGestaoDocumento($idDoc);

        $arquivo = self::validarArquivo($file);
        $nomeArquivo = $arquivo['nome'];

        $cpf = AuthService::getUserCpf() ?? 'sistema';
        $versao = (DocModel::ultimaVersao($idDoc) ?? 0) + 1;

        $nomeArquivo = self::nomeVersao($nomeArquivo, $versao);

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
        $diretorios = DocModel::listarDiretorios(AuthService::getUserCpf());

        foreach ($diretorios as &$dir) {
            $dir['restrito'] = ($dir['visibilidade'] ?? 'publico') === 'restrito';
        }
        unset($dir);

        return $diretorios;
    }

    public static function listarSubdiretorios(?int $idDir = null): array
    {
        return DocModel::listarSubdiretorios($idDir, AuthService::getUserCpf());
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

    public static function listarFuncoesFilhas(): array
    {
        try {
            return FuncaoPaiModel::listarFuncoesFilhas();
        } catch (Throwable $e) {
            Logger::exception($e);

            return [];
        }
    }

    public static function listarDocumentosAdmin(?int $idDir = null, ?int $idSubdir = null, ?string $nome = null, ?int $idFuncao = null): array
    {
        $documentos = DocModel::listarDocumentosAdmin($idDir, $idSubdir, $nome, $idFuncao, AuthService::getUserCpf());

        foreach ($documentos as &$doc) {
            $doc['existe'] = is_file(self::caminhoAbsoluto($doc['caminho']));
            $doc['tamanho_texto'] = self::formatarTamanho((int) $doc['tamanho']);
            $doc['restrito'] = ($doc['visibilidade'] ?? 'publico') === 'restrito';
        }
        unset($doc);

        return $documentos;
    }

    public static function permissoesPorDocumento(): array
    {
        return DocModel::permissoesPorDocumento(AuthService::getUserCpf());
    }

    public static function versoesPorDocumento(): array
    {
        $versoes = DocModel::versoesPorDocumento(AuthService::getUserCpf());

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
        self::exigirEdicaoPermissoes($idDoc);

        if (!DocModel::adicionarPermissao($idDoc, $codfuncao)) {
            throw new RuntimeException('Documento inválido ou função não informada.');
        }

        return 'Permissão adicionada ao documento.';
    }

    public static function removerPermissao(int $idDoc, string $codfuncao): string
    {
        self::exigirEdicaoPermissoes($idDoc);

        if (!DocModel::removerPermissao($idDoc, $codfuncao)) {
            throw new RuntimeException('Documento inválido ou função não informada.');
        }

        return 'Permissão removida do documento.';
    }

    public static function alternarGeral(int $idDoc, bool $geral): string
    {
        self::exigirEdicaoPermissoes($idDoc);

        if (!DocModel::alternarGeral($idDoc, $geral)) {
            throw new RuntimeException('Documento inválido.');
        }

        return $geral ? 'Acesso geral ativado para o documento: ' . $idDoc : 'Acesso geral desativado para o documento: ' . $idDoc;
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
        self::exigirGestaoDocumento($idDoc);

        return DocModel::restaurarVersao($idDoc, $idVersao);
    }

    public static function excluirVersao(int $idDoc, int $idVersao): string
    {
        self::exigirGestaoDocumento($idDoc);

        $info = DocModel::versaoInfo($idDoc, $idVersao);

        if ($info === null) {
            throw new RuntimeException('Versão não encontrada para este documento.');
        }

        if ($info['atual']) {
            throw new RuntimeException('A versão atual não pode ser excluída.');
        }

        if (!DocModel::excluirVersao($idDoc, $idVersao)) {
            throw new RuntimeException('Erro ao excluir a versão.');
        }

        $absoluto = self::caminhoAbsoluto($info['caminho']);

        if (DocModel::outrasVersoesComMesmoCaminho($info['caminho'], $idVersao) === 0 && is_file($absoluto)) {
            @unlink($absoluto);
        }

        return "Versão {$info['versao']} do documento excluída.";
    }

    public static function excluirDocumento(int $idDoc): bool
    {
        self::exigirGestaoDocumento($idDoc);

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

    public static function moverDocumento(int $idDoc, int $idDir, int $idSubdir): string
    {
        $doc = DocModel::documentoComVersao($idDoc);

        if ($doc === null) {
            throw new RuntimeException('Documento não encontrado.');
        }

        self::exigirGestaoDocumento($idDoc);

        if ($idDir <= 0 || $idSubdir <= 0) {
            throw new RuntimeException('Selecione o diretório e a subpasta de destino.');
        }

        if ((int) $doc['id_dir'] === $idDir && (int) $doc['id_subdir'] === $idSubdir) {
            throw new RuntimeException('O documento já está neste diretório.');
        }

        $destino = self::diretoriosParaCaminho($idDir, $idSubdir);
        $destinoRel = 'documentos/' . $destino['diretorio'] . '/' . $destino['subdiretorio'];
        $destinoAbs = self::caminhoAbsoluto($destinoRel);

        if (!is_dir($destinoAbs) && !mkdir($destinoAbs, 0755, true) && !is_dir($destinoAbs)) {
            throw new RuntimeException('Falha ao criar a pasta de destino.');
        }

        $versoes = DocModel::versoesCaminhos($idDoc);

        if (empty($versoes)) {
            throw new RuntimeException('Documento sem versões registradas.');
        }

        $plano = [];
        $nomesUsados = [];
        $mapaAntigos = [];

        foreach ($versoes as $versao) {
            $antigo = (string) $versao['caminho'];

            if (isset($mapaAntigos[$antigo])) {
                $plano[] = [
                    'id' => (int) $versao['id'],
                    'antigo' => $antigo,
                    'novo' => $mapaAntigos[$antigo]['novo'],
                    'nome_destino' => $mapaAntigos[$antigo]['nome_destino'],
                ];
                continue;
            }

            $nomeDestino = self::nomeDisponivelEmDestino($destinoAbs, basename($antigo), $nomesUsados);
            $nomesUsados[] = $nomeDestino;

            $item = [
                'id' => (int) $versao['id'],
                'antigo' => $antigo,
                'novo' => $destinoRel . '/' . $nomeDestino,
                'nome_destino' => $nomeDestino,
            ];

            $mapaAntigos[$antigo] = $item;
            $plano[] = $item;
        }

        $movidos = [];

        try {
            foreach ($plano as $item) {
                $antigoAbs = self::caminhoAbsoluto($item['antigo']);

                if (!is_file($antigoAbs)) {
                    continue;
                }

                $novoAbs = $destinoAbs . '/' . $item['nome_destino'];

                if (DocModel::versoesComMesmoCaminhoDeOutroDoc($item['antigo'], $idDoc) > 0) {
                    if (!copy($antigoAbs, $novoAbs)) {
                        throw new RuntimeException('Falha ao copiar o arquivo ' . $item['nome_destino'] . '.');
                    }

                    $movidos[] = ['tipo' => 'copiado', 'de' => $antigoAbs, 'para' => $novoAbs];
                    continue;
                }

                if (!rename($antigoAbs, $novoAbs)) {
                    throw new RuntimeException('Falha ao mover o arquivo ' . $item['nome_destino'] . '.');
                }

                $movidos[] = ['tipo' => 'movido', 'de' => $antigoAbs, 'para' => $novoAbs];
            }

            $pdo = DB::connect('mysql');
            $pdo->beginTransaction();

            if (!DocModel::moverDocumento($idDoc, $idDir, $idSubdir)) {
                throw new RuntimeException('Falha ao atualizar o documento.');
            }

            foreach ($plano as $item) {
                if (!DocModel::atualizarCaminhoVersao($item['id'], $item['novo'])) {
                    throw new RuntimeException('Falha ao atualizar o caminho das versões.');
                }
            }

            $pdo->commit();
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) {
                $pdo->rollBack();
            }

            self::reverterMovimentoArquivos($movidos);

            if ($e instanceof RuntimeException) {
                throw $e;
            }

            throw new RuntimeException('Não foi possível migrar o documento.');
        }

        $mensagem = "Documento movido para {$destino['diretorio']} / {$destino['subdiretorio']}.";

        if ($destino['restrito']) {
            $mensagem .= ' Atenção: a pasta de destino é restrita, então somente o criador da pasta verá o documento.';
        } else {
            $semAcesso = $doc['geral'] !== 'S' && DocModel::contarPermissoes($idDoc) === 0;

            if ($semAcesso) {
                $mensagem .= ' O documento está sem permissões e não aparecerá na área Documentos até ser definido como Geral ou receber funções.';
            }
        }

        return $mensagem;
    }

    private static function nomeDisponivelEmDestino(string $destinoAbs, string $nome, array $usados): string
    {
        $base = pathinfo($nome, PATHINFO_FILENAME);
        $ext = pathinfo($nome, PATHINFO_EXTENSION);

        if ($base === '' || $base === '.') {
            $base = 'arquivo';
        }

        $tentativa = 0;

        do {
            $sufixo = $tentativa === 0 ? '' : ($tentativa === 1 ? '_migrado' : '_migrado' . $tentativa);
            $candidato = $ext !== '' ? "{$base}{$sufixo}.{$ext}" : "{$base}{$sufixo}";
            $tentativa++;
        } while (in_array($candidato, $usados, true) || is_file($destinoAbs . '/' . $candidato));

        return $candidato;
    }

    private static function reverterMovimentoArquivos(array $movidos): void
    {
        foreach (array_reverse($movidos) as $item) {
            if ($item['tipo'] === 'movido') {
                if (is_file($item['para']) && !is_file($item['de'])) {
                    @rename($item['para'], $item['de']);
                }

                continue;
            }

            if (is_file($item['para'])) {
                @unlink($item['para']);
            }
        }
    }

    public static function criarDiretorio(string $nome, bool $restrito = false): string
    {
        $nome = self::validarNomePasta($nome, 'do diretório');
        $cpf = AuthService::getUserCpf();
        $visibilidade = $restrito ? 'restrito' : 'publico';

        if ($restrito && ($cpf === null || $cpf === '')) {
            throw new RuntimeException('Não foi possível identificar o criador da pasta.');
        }

        if (DocModel::diretorioExiste($nome)) {
            throw new RuntimeException('Não foi possível criar a pasta com esse nome.');
        }

        if (DocModel::criarDiretorio($nome, $visibilidade, $cpf) === null) {
            throw new RuntimeException('Erro ao criar o diretório.');
        }

        return $restrito
            ? "Diretório {$nome} criado com acesso restrito a você."
            : "Diretório {$nome} criado com sucesso.";
    }

    public static function criarSubdiretorio(int $idDir, string $nome): string
    {
        if ($idDir <= 0) {
            throw new RuntimeException('Selecione o diretório da subpasta.');
        }

        if (!self::podeGerirDiretorio($idDir)) {
            throw new RuntimeException('Diretório não encontrado.');
        }

        $nome = self::validarNomePasta($nome, 'da subpasta');

        if (DocModel::subdiretorioExiste($idDir, $nome)) {
            throw new RuntimeException("Já existe uma subpasta chamada {$nome} neste diretório.");
        }

        if (DocModel::criarSubdiretorio($idDir, $nome) === null) {
            throw new RuntimeException('Erro ao criar a subpasta.');
        }

        return "Subpasta {$nome} criada com sucesso.";
    }

    private static function validarNomePasta(string $nome, string $alvo): string
    {
        $nome = trim($nome);

        if ($nome === '') {
            throw new RuntimeException("Informe o nome {$alvo}.");
        }

        if (mb_strlen($nome) > 100) {
            throw new RuntimeException("O nome {$alvo} deve ter no máximo 100 caracteres.");
        }

        if (!preg_match('/^[\p{L}\p{N}][\p{L}\p{N} _\-().]*$/u', $nome)) {
            throw new RuntimeException("O nome {$alvo} contém caracteres não permitidos. Use letras, números, espaços, hífen, sublinhado, ponto e parênteses.");
        }

        return $nome;
    }

    public static function excluirDiretorio(int $idDir): string
    {
        if (!self::podeExcluirDiretorio($idDir)) {
            throw new RuntimeException('Diretório não encontrado.');
        }

        if (!DocModel::excluirDiretorio($idDir)) {
            throw new RuntimeException('Não é possível excluir: o diretório possui documentos.');
        }

        return 'Diretório excluído com sucesso.';
    }

    public static function excluirSubdiretorio(int $idSubdir): string
    {
        if (!self::podeExcluirSubdiretorio($idSubdir)) {
            throw new RuntimeException('Subpasta não encontrada.');
        }

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
        $dir = DocModel::diretorioPorId($idDir);
        $sub = DocModel::subdiretorioPorId($idSubdir);

        if (
            $dir === null
            || $sub === null
            || (int) $sub['id_dir'] !== $idDir
            || !self::podeGerirRegistroDiretorio($dir)
        ) {
            throw new RuntimeException('Diretório ou subdiretório inválido.');
        }

        foreach ([$dir['nome'], $sub['nome']] as $segmento) {
            if (
                $segmento === ''
                || str_contains($segmento, '/')
                || str_contains($segmento, '\\')
                || str_contains($segmento, "\0")
                || str_contains($segmento, '..')
            ) {
                throw new RuntimeException('Nome de diretório ou subpasta inválido.');
            }
        }

        return [
            'diretorio' => $dir['nome'],
            'subdiretorio' => $sub['nome'],
            'restrito' => ($dir['visibilidade'] ?? 'publico') === 'restrito',
        ];
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

    private static function nomeVersao(string $nomeArquivo, int $versao): string
    {
        $ext = strtolower(pathinfo($nomeArquivo, PATHINFO_EXTENSION));
        $base = pathinfo($nomeArquivo, PATHINFO_FILENAME);

        if ($base === '' || $base === '.') {
            $base = 'arquivo';
        }

        return $ext !== '' ? "{$base}_v{$versao}.{$ext}" : "{$base}_v{$versao}";
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
