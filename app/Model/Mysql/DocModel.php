<?php

namespace App\Model\Mysql;

use App\Core\DB;

class DocModel
{
    public static function arvore(?int $codfuncaoPai, string $cpf): array
    {
        $visibilidade = "(
            (
                dir.visibilidade = 'publico'
                AND (
                    doc.geral = 'S'
                    OR EXISTS (
                        SELECT 1
                        FROM doc_permissao dp
                        WHERE dp.id_doc = doc.id_doc
                        AND dp.codfuncao = :pai
                    )
                )
            )
            OR (
                dir.visibilidade = 'restrito'
                AND dir.criador_cpf = :cpfDono
            )
        )";

        $diretorios = DB::select("
            SELECT dir.id, dir.nome, dir.visibilidade
            FROM doc_diretorio dir
            WHERE dir.ativo = 1
              AND (
                  dir.visibilidade = 'publico'
                  OR dir.criador_cpf = :cpfDono
              )
              AND EXISTS (
                  SELECT 1
                  FROM doc_documento doc
                  WHERE doc.id_dir = dir.id
                    AND doc.ativo = 1
                    AND {$visibilidade}
              )
            ORDER BY dir.exibicao, dir.nome
        ", ['pai' => $codfuncaoPai, 'cpfDono' => $cpf], 'mysql');

        $subdiretorios = DB::select("
            SELECT s.id, s.nome, s.id_dir
            FROM doc_subdiretorio s
            JOIN doc_diretorio dir ON dir.id = s.id_dir
            WHERE s.ativo = 1
              AND dir.ativo = 1
              AND (
                  dir.visibilidade = 'publico'
                  OR dir.criador_cpf = :cpfDono
              )
              AND EXISTS (
                  SELECT 1
                  FROM doc_documento doc
                  WHERE doc.id_subdir = s.id
                    AND doc.ativo = 1
                    AND {$visibilidade}
              )
            ORDER BY s.exibicao, s.nome
        ", ['pai' => $codfuncaoPai, 'cpfDono' => $cpf], 'mysql');

        $docs = DB::select("
            SELECT
                doc.id_doc,
                doc.titulo,
                doc.tipo,
                doc.id_dir,
                doc.id_subdir,
                v.id AS id_versao,
                v.versao,
                v.rotulo,
                v.tamanho,
                v.caminho,
                (NOT EXISTS (
                    SELECT 1 FROM doc_acesso a
                    WHERE a.cpf = :cpf AND a.id_doc = doc.id_doc
                )) AS novo,
                (EXISTS (
                    SELECT 1 FROM doc_acesso a
                    WHERE a.cpf = :cpf AND a.id_doc = doc.id_doc
                    AND a.id_versao < doc.id_versao_atual
                )) AS atualizado
            FROM doc_documento doc
            JOIN doc_diretorio dir ON dir.id = doc.id_dir
            JOIN doc_versao v ON v.id = doc.id_versao_atual
            WHERE doc.ativo = 1
              AND {$visibilidade}
            ORDER BY doc.titulo
        ", ['pai' => $codfuncaoPai, 'cpf' => $cpf, 'cpfDono' => $cpf], 'mysql');

        $arvore = [];

        foreach ($diretorios as $dir) {
            $arvore[$dir['id']] = [
                'id' => (int) $dir['id'],
                'nome' => $dir['nome'],
                'restrito' => ($dir['visibilidade'] ?? 'publico') === 'restrito',
                'qt_novo' => 0,
                'qt_atualizado' => 0,
                'subdirs' => [],
            ];
        }

        foreach ($subdiretorios as $sub) {
            if (!isset($arvore[$sub['id_dir']])) {
                continue;
            }

            $arvore[$sub['id_dir']]['subdirs'][$sub['id']] = [
                'id' => (int) $sub['id'],
                'nome' => $sub['nome'],
                'qt_novo' => 0,
                'qt_atualizado' => 0,
                'docs' => [],
            ];
        }

        foreach ($docs as $doc) {
            $item = [
                'id_doc' => (int) $doc['id_doc'],
                'titulo' => $doc['titulo'],
                'tipo' => strtolower((string) $doc['tipo']),
                'versao' => (int) $doc['versao'],
                'rotulo' => $doc['rotulo'],
                'tamanho' => (int) $doc['tamanho'],
                'caminho' => $doc['caminho'],
                'novo' => (bool) $doc['novo'],
                'atualizado' => (bool) $doc['atualizado'],
            ];

            $dirId = (int) $doc['id_dir'];
            $subId = (int) $doc['id_subdir'];

            if (!isset($arvore[$dirId]['subdirs'][$subId])) {
                continue;
            }

            $arvore[$dirId]['subdirs'][$subId]['docs'][] = $item;

            if ($item['novo']) {
                $arvore[$dirId]['subdirs'][$subId]['qt_novo']++;
                $arvore[$dirId]['qt_novo']++;
            }

            if ($item['atualizado']) {
                $arvore[$dirId]['subdirs'][$subId]['qt_atualizado']++;
                $arvore[$dirId]['qt_atualizado']++;
            }
        }

        return array_values($arvore);
    }

    public static function documentoComVersao(int $idDoc): ?array
    {
        return DB::first("
            SELECT
                doc.id_doc,
                doc.titulo,
                doc.tipo,
                doc.id_dir,
                doc.id_subdir,
                doc.geral,
                doc.id_versao_atual,
                v.id AS id_versao,
                v.versao,
                v.caminho,
                v.tamanho
            FROM doc_documento doc
            JOIN doc_versao v ON v.id = doc.id_versao_atual
            WHERE doc.id_doc = :id
              AND doc.ativo = 1
        ", ['id' => $idDoc], 'mysql');
    }

    public static function versaoArquivo(int $idDoc, int $idVersao): ?array
    {
        return DB::first("
            SELECT
                doc.titulo,
                doc.tipo,
                v.versao,
                v.caminho,
                v.tamanho
            FROM doc_versao v
            JOIN doc_documento doc ON doc.id_doc = v.id_doc
            WHERE v.id_doc = :doc
              AND v.id = :versao
              AND doc.ativo = 1
        ", ['doc' => $idDoc, 'versao' => $idVersao], 'mysql');
    }

    public static function diretorioPorId(int $idDir): ?array
    {
        return DB::first("
            SELECT id, nome, visibilidade, criador_cpf
            FROM doc_diretorio
            WHERE id = :id
              AND ativo = 1
        ", ['id' => $idDir], 'mysql');
    }

    public static function subdiretorioPorId(int $idSubdir): ?array
    {
        return DB::first("
            SELECT id, id_dir, nome
            FROM doc_subdiretorio
            WHERE id = :id
              AND ativo = 1
        ", ['id' => $idSubdir], 'mysql');
    }

    public static function diretorioDoDocumento(int $idDoc): ?array
    {
        return DB::first("
            SELECT dir.id, dir.nome, dir.visibilidade, dir.criador_cpf
            FROM doc_documento doc
            JOIN doc_diretorio dir ON dir.id = doc.id_dir
            WHERE doc.id_doc = :id
              AND doc.ativo = 1
        ", ['id' => $idDoc], 'mysql');
    }

    public static function temPermissao(int $idDoc, ?int $codfuncaoPai, ?string $cpf = null): bool
    {
        $row = DB::first("
            SELECT 1
            FROM doc_documento doc
            JOIN doc_diretorio dir ON dir.id = doc.id_dir
            WHERE doc.id_doc = :id
              AND doc.ativo = 1
              AND (
                  (
                      dir.visibilidade = 'restrito'
                      AND dir.criador_cpf = :cpfDono
                  )
                  OR (
                      dir.visibilidade = 'publico'
                      AND (
                          doc.geral = 'S'
                          OR EXISTS (
                              SELECT 1 FROM doc_permissao dp
                              WHERE dp.id_doc = doc.id_doc AND dp.codfuncao = :pai
                          )
                      )
                  )
              )
        ", ['id' => $idDoc, 'pai' => $codfuncaoPai, 'cpfDono' => $cpf ?? ''], 'mysql');

        return $row !== null;
    }

    public static function registrarAcesso(string $cpf, int $idDoc, int $idVersao): void
    {
        DB::select("
            INSERT INTO doc_acesso (cpf, id_doc, id_versao, dt_view)
            VALUES (:cpf, :id_doc, :id_versao, NOW())
            ON DUPLICATE KEY UPDATE
                id_versao = IF(id_versao < VALUES(id_versao), VALUES(id_versao), id_versao),
                dt_view = NOW()
        ", ['cpf' => $cpf, 'id_doc' => $idDoc, 'id_versao' => $idVersao], 'mysql');
    }

    public static function criarDocumento(array $dados): ?int
    {
        return DB::insert('doc_documento', $dados, 'mysql');
    }

    public static function criarVersao(array $dados): ?int
    {
        return DB::insert('doc_versao', $dados, 'mysql');
    }

    public static function definirVersaoAtual(int $idDoc, int $idVersao): bool
    {
        return DB::update('doc_documento', 'id_doc', $idDoc, [
            'id_versao_atual' => $idVersao,
            'dt_alteracao' => date('Y-m-d H:i:s'),
        ], 'mysql');
    }

    public static function ultimaVersao(int $idDoc): ?int
    {
        $row = DB::first("
            SELECT MAX(versao) AS ultima
            FROM doc_versao
            WHERE id_doc = :id
        ", ['id' => $idDoc], 'mysql');

        if ($row === null) {
            return null;
        }

        return $row['ultima'] !== null ? (int) $row['ultima'] : null;
    }

    public static function buscarDocPorArquivo(int $idDir, int $idSubdir, string $nomeArquivo): ?int
    {
        $row = DB::first("
            SELECT doc.id_doc
            FROM doc_documento doc
            JOIN doc_versao v ON v.id = doc.id_versao_atual
            WHERE doc.id_dir = :dir
              AND doc.id_subdir = :subdir
              AND doc.ativo = 1
              AND v.caminho LIKE :caminho
            ORDER BY v.versao DESC
            LIMIT 1
        ", [
            'dir' => $idDir,
            'subdir' => $idSubdir,
            'caminho' => '%/' . $nomeArquivo,
        ], 'mysql');

        if ($row === null) {
            return null;
        }

        return $row['id_doc'] !== null ? (int) $row['id_doc'] : null;
    }

    public static function listarDiretorios(?string $cpf = null): array
    {
        return DB::select("
            SELECT id, nome, visibilidade
            FROM doc_diretorio
            WHERE ativo = 1
              AND (visibilidade = 'publico' OR criador_cpf = :cpfDono)
            ORDER BY exibicao, nome
        ", ['cpfDono' => $cpf ?? ''], 'mysql');
    }

    public static function listarSubdiretorios(?int $idDir = null, ?string $cpf = null): array
    {
        $sql = "
            SELECT s.id, s.id_dir, s.nome
            FROM doc_subdiretorio s
            JOIN doc_diretorio dir ON dir.id = s.id_dir
            WHERE s.ativo = 1
              AND dir.ativo = 1
              AND (dir.visibilidade = 'publico' OR dir.criador_cpf = :cpfDono)
        ";
        $params = ['cpfDono' => $cpf ?? ''];

        if ($idDir !== null) {
            $sql .= " AND s.id_dir = :dir";
            $params['dir'] = $idDir;
        }

        $sql .= " ORDER BY s.exibicao, s.nome";

        return DB::select($sql, $params, 'mysql');
    }

    public static function listarDocumentosAdmin(?int $idDir = null, ?int $idSubdir = null, ?string $nome = null, ?int $idFuncao = null, ?string $cpf = null): array
    {
        $sql = "
            SELECT
                doc.id_doc,
                doc.titulo,
                doc.tipo,
                doc.geral,
                doc.id_dir,
                doc.id_subdir,
                d.nome AS diretorio,
                d.visibilidade,
                s.nome AS subdiretorio,
                v.versao,
                v.rotulo,
                v.caminho,
                v.tamanho,
                v.dt_upload
            FROM doc_documento doc
            JOIN doc_diretorio d ON d.id = doc.id_dir
            JOIN doc_subdiretorio s ON s.id = doc.id_subdir
            JOIN doc_versao v ON v.id = doc.id_versao_atual
            WHERE doc.ativo = 1
              AND (d.visibilidade = 'publico' OR d.criador_cpf = :cpfDono)
        ";

        $params = ['cpfDono' => $cpf ?? ''];

        if ($idDir > 0) {
            $sql .= " AND doc.id_dir = :dir";
            $params['dir'] = $idDir;
        }

        if ($idSubdir > 0) {
            $sql .= " AND doc.id_subdir = :subdir";
            $params['subdir'] = $idSubdir;
        }

        if ($nome !== null && $nome !== '') {
            $sql .= " AND LOWER(doc.titulo) LIKE LOWER(:nome)";
            $params['nome'] = '%' . addcslashes($nome, '\\%_') . '%';
        }

        if ($idFuncao !== null && $idFuncao > 0) {
            $sql .= "
                AND (
                    doc.geral = 'S'
                    OR EXISTS (
                        SELECT 1 FROM doc_permissao fp
                        WHERE fp.id_doc = doc.id_doc
                          AND fp.codfuncao = :funcao
                    )
                )
            ";
            $params['funcao'] = $idFuncao;
        }

        $sql .= "
            ORDER BY d.exibicao, d.nome, s.exibicao, s.nome, doc.titulo
        ";

        return DB::select($sql, $params, 'mysql');
    }

    /**
     * Documentos ativos com diretório, subdiretório e versão atual.
     * Usado pelo indexador da Vivi; não aplica permissões (elas são indexadas junto).
     *
     * @return array<int, array<string, mixed>>
     */
    public static function listarParaIndexacao(bool $apenasGerais = false, ?int $limite = null): array
    {
        $sql = "
            SELECT
                d.id_doc,
                d.titulo,
                d.tipo,
                d.geral,
                d.id_dir,
                d.id_subdir,
                COALESCE(dir.nome, '') AS diretorio,
                COALESCE(dir.visibilidade, 'publico') AS visibilidade,
                COALESCE(sub.nome, '') AS subdiretorio,
                d.id_versao_atual,
                v.id AS id_versao,
                v.versao,
                v.caminho,
                v.tamanho,
                v.dt_upload
            FROM doc_documento d
            LEFT JOIN doc_diretorio dir ON dir.id = d.id_dir
            LEFT JOIN doc_subdiretorio sub ON sub.id = d.id_subdir
            JOIN doc_versao v ON v.id = d.id_versao_atual
            WHERE d.ativo = 1
        ";

        if ($apenasGerais) {
            $sql .= " AND d.geral = 'S'";
        }

        $sql .= ' ORDER BY d.id_doc';

        if ($limite !== null && $limite > 0) {
            $sql .= ' LIMIT ' . (int) $limite;
        }

        return DB::select($sql, [], 'mysql');
    }

    public static function permissoesPorDocumento(?string $cpf = null): array
    {
        $docs = DB::select("
            SELECT doc.id_doc, doc.geral
            FROM doc_documento doc
            JOIN doc_diretorio dir ON dir.id = doc.id_dir
            WHERE doc.ativo = 1
              AND (dir.visibilidade = 'publico' OR dir.criador_cpf = :cpfDono)
        ", ['cpfDono' => $cpf ?? ''], 'mysql');

        $perms = DB::select("
            SELECT id_doc, codfuncao
            FROM doc_permissao
        ", [], 'mysql');

        $out = [];

        foreach ($docs as $doc) {
            $out[(int) $doc['id_doc']] = [
                'geral' => $doc['geral'] === 'S',
                'funcoes' => [],
            ];
        }

        foreach ($perms as $perm) {
            $idDoc = (int) $perm['id_doc'];

            if (isset($out[$idDoc])) {
                $out[$idDoc]['funcoes'][] = (string) $perm['codfuncao'];
            }
        }

        return $out;
    }

    public static function versoesPorDocumento(?string $cpf = null): array
    {
        $rows = DB::select("
            SELECT
                v.id_doc,
                v.id,
                v.versao,
                v.rotulo,
                v.caminho,
                v.tamanho,
                v.dt_upload,
                v.uploader,
                (v.id = doc.id_versao_atual) AS atual
            FROM doc_versao v
            JOIN doc_documento doc ON doc.id_doc = v.id_doc
            JOIN doc_diretorio dir ON dir.id = doc.id_dir
            WHERE doc.ativo = 1
              AND (dir.visibilidade = 'publico' OR dir.criador_cpf = :cpfDono)
            ORDER BY v.id_doc, v.versao DESC
        ", ['cpfDono' => $cpf ?? ''], 'mysql');

        $out = [];

        foreach ($rows as $row) {
            $out[(int) $row['id_doc']][] = [
                'id' => (int) $row['id'],
                'versao' => (int) $row['versao'],
                'rotulo' => $row['rotulo'],
                'caminho' => $row['caminho'],
                'tamanho' => (int) $row['tamanho'],
                'dt_upload' => $row['dt_upload'],
                'uploader' => $row['uploader'],
                'atual' => (bool) $row['atual'],
            ];
        }

        return $out;
    }

    public static function atualizarPermissoes(int $idDoc, bool $geral, array $funcoes): bool
    {
        $existe = DB::first("
            SELECT 1
            FROM doc_documento
            WHERE id_doc = :id
        ", ['id' => $idDoc], 'mysql');

        if ($existe === null) {
            return false;
        }

        DB::update('doc_documento', 'id_doc', $idDoc, [
            'geral' => $geral ? 'S' : 'N',
        ], 'mysql');

        $stmt = DB::connect('mysql')->prepare("DELETE FROM doc_permissao WHERE id_doc = :id");
        $stmt->execute(['id' => $idDoc]);

        if (!$geral) {
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

        return true;
    }

    public static function adicionarPermissao(int $idDoc, string $codfuncao): bool
    {
        if ($codfuncao === '') {
            return false;
        }

        $existe = DB::first("
            SELECT 1
            FROM doc_documento
            WHERE id_doc = :id
        ", ['id' => $idDoc], 'mysql');

        if ($existe === null) {
            return false;
        }

        DB::select("
            INSERT IGNORE INTO doc_permissao (id_doc, codfuncao)
            VALUES (:id_doc, :codfuncao)
        ", ['id_doc' => $idDoc, 'codfuncao' => $codfuncao], 'mysql');

        return true;
    }

    public static function removerPermissao(int $idDoc, string $codfuncao): bool
    {
        if ($codfuncao === '') {
            return false;
        }

        $stmt = DB::connect('mysql')->prepare("
            DELETE FROM doc_permissao
            WHERE id_doc = :id_doc
              AND codfuncao = :codfuncao
        ");

        return $stmt->execute(['id_doc' => $idDoc, 'codfuncao' => $codfuncao]);
    }

    public static function alternarGeral(int $idDoc, bool $geral): bool
    {
        $existe = DB::first("
            SELECT 1
            FROM doc_documento
            WHERE id_doc = :id
        ", ['id' => $idDoc], 'mysql');

        if ($existe === null) {
            return false;
        }

        DB::update('doc_documento', 'id_doc', $idDoc, [
            'geral' => $geral ? 'S' : 'N',
        ], 'mysql');

        if ($geral) {
            $stmt = DB::connect('mysql')->prepare("DELETE FROM doc_permissao WHERE id_doc = :id");
            $stmt->execute(['id' => $idDoc]);
        }

        return true;
    }

    public static function copiarPermissoes(int $funcaoOrigem, int $funcaoDestino): int
    {
        $stmt = DB::connect('mysql')->prepare("
            INSERT IGNORE INTO doc_permissao (id_doc, codfuncao)
            SELECT dp.id_doc, :destino
            FROM doc_permissao dp
            JOIN doc_documento doc ON doc.id_doc = dp.id_doc
            JOIN doc_diretorio dir ON dir.id = doc.id_dir
            WHERE dp.codfuncao = :origem
              AND dir.visibilidade = 'publico'
        ");

        $stmt->execute(['destino' => (string) $funcaoDestino, 'origem' => (string) $funcaoOrigem]);

        return $stmt->rowCount();
    }

    public static function versaoInfo(int $idDoc, int $idVersao): ?array
    {
        $row = DB::first("
            SELECT
                v.id,
                v.versao,
                v.caminho,
                (v.id = doc.id_versao_atual) AS atual
            FROM doc_versao v
            JOIN doc_documento doc ON doc.id_doc = v.id_doc
            WHERE v.id = :versao
              AND v.id_doc = :doc
        ", ['versao' => $idVersao, 'doc' => $idDoc], 'mysql');

        if ($row === null) {
            return null;
        }

        return [
            'id' => (int) $row['id'],
            'versao' => (int) $row['versao'],
            'caminho' => $row['caminho'],
            'atual' => (bool) $row['atual'],
        ];
    }

    public static function excluirVersao(int $idDoc, int $idVersao): bool
    {
        $info = self::versaoInfo($idDoc, $idVersao);

        if ($info === null || $info['atual']) {
            return false;
        }

        $stmt = DB::connect('mysql')->prepare("DELETE FROM doc_versao WHERE id = :id");

        return $stmt->execute(['id' => $idVersao]);
    }

    public static function outrasVersoesComMesmoCaminho(string $caminho, int $idVersao): int
    {
        $row = DB::first("
            SELECT COUNT(*) AS total
            FROM doc_versao
            WHERE caminho = :caminho
              AND id <> :id
        ", ['caminho' => $caminho, 'id' => $idVersao], 'mysql');

        return (int) ($row['total'] ?? 0);
    }

    public static function restaurarVersao(int $idDoc, int $idVersao): bool
    {
        $versao = DB::first("
            SELECT id
            FROM doc_versao
            WHERE id = :versao
              AND id_doc = :doc
        ", ['versao' => $idVersao, 'doc' => $idDoc], 'mysql');

        if ($versao === null) {
            return false;
        }

        return DB::update('doc_documento', 'id_doc', $idDoc, [
            'id_versao_atual' => $idVersao,
            'dt_alteracao' => date('Y-m-d H:i:s'),
        ], 'mysql');
    }

    public static function excluirDocumento(int $idDoc): bool
    {
        $stmt = DB::connect('mysql')->prepare("DELETE FROM doc_documento WHERE id_doc = :id");

        return $stmt->execute(['id' => $idDoc]);
    }

    public static function outrosComMesmoCaminho(string $caminho, int $idDoc): int
    {
        $row = DB::first("
            SELECT COUNT(*) AS total
            FROM doc_versao v
            JOIN doc_documento doc ON doc.id_doc = v.id_doc
            WHERE v.caminho = :caminho
              AND doc.id_doc <> :id
              AND doc.ativo = 1
        ", ['caminho' => $caminho, 'id' => $idDoc], 'mysql');

        return (int) ($row['total'] ?? 0);
    }

    public static function criarDiretorio(string $nome, string $visibilidade = 'publico', ?string $cpf = null): ?int
    {
        return DB::insert('doc_diretorio', [
            'nome' => $nome,
            'visibilidade' => $visibilidade,
            'criador_cpf' => $cpf,
            'exibicao' => $nome,
            'ativo' => 1,
        ], 'mysql');
    }

    public static function criarSubdiretorio(int $idDir, string $nome): ?int
    {
        return DB::insert('doc_subdiretorio', [
            'id_dir' => $idDir,
            'nome' => $nome,
            'exibicao' => $nome,
            'ativo' => 1,
        ], 'mysql');
    }

    public static function diretorioExiste(string $nome): bool
    {
        $row = DB::first("
            SELECT 1
            FROM doc_diretorio
            WHERE nome = :nome
        ", ['nome' => $nome], 'mysql');

        return $row !== null;
    }

    public static function subdiretorioExiste(int $idDir, string $nome): bool
    {
        $row = DB::first("
            SELECT 1
            FROM doc_subdiretorio
            WHERE id_dir = :dir
              AND nome = :nome
        ", ['dir' => $idDir, 'nome' => $nome], 'mysql');

        return $row !== null;
    }

    public static function excluirDiretorio(int $idDir): bool
    {
        $temDocs = (int) (DB::first("
            SELECT COUNT(*) AS total
            FROM doc_documento
            WHERE id_dir = :id
        ", ['id' => $idDir], 'mysql')['total'] ?? 0);

        if ($temDocs > 0) {
            return false;
        }

        $stmt = DB::connect('mysql')->prepare("DELETE FROM doc_diretorio WHERE id = :id");

        return $stmt->execute(['id' => $idDir]);
    }

    public static function excluirSubdiretorio(int $idSubdir): bool
    {
        $temDocs = (int) (DB::first("
            SELECT COUNT(*) AS total
            FROM doc_documento
            WHERE id_subdir = :id
        ", ['id' => $idSubdir], 'mysql')['total'] ?? 0);

        if ($temDocs > 0) {
            return false;
        }

        $stmt = DB::connect('mysql')->prepare("DELETE FROM doc_subdiretorio WHERE id = :id");

        return $stmt->execute(['id' => $idSubdir]);
    }
}
