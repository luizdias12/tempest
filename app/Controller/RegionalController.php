<?php

namespace App\Controller;

use App\Core\BaseController;
use App\Core\Request;
use App\Core\Response;
use App\Service\AuthService;
use App\Service\RegionalService;
use Throwable;

class RegionalController extends BaseController
{
    public function indexView(Request $request): void
    {
        view('regional/index', [
            'title' => 'Regionais',
            'permiteGestao' => AuthService::hasPermission('ti'),
        ]);
    }

    public function listaRegional(): array
    {
        return $this->handle(fn() => $this->success(RegionalService::listaRegional()));
    }

    public function consulta(): array
    {
        return $this->handle(fn() => $this->success(RegionalService::consulta()));
    }

    public function byRegional(Request $request, int $regiao): array
    {
        return $this->handle(fn() => $this->success(RegionalService::byRegional($regiao)));
    }

    public function filialByRegional(): array
    {
        return $this->handle(fn() => $this->success(RegionalService::filialByRegional()));
    }

    public function byFilial(Request $request, int $filial): array
    {
        return $this->handle(fn() => $this->success(RegionalService::byFilial($filial)));
    }

    public function onlyGerente(): array
    {
        return $this->handle(fn() => $this->success(RegionalService::onlyGerente()));
    }

    public function onlysubGerente(): array
    {
        return $this->handle(fn() => $this->success(RegionalService::onlysubGerente()));
    }

    public function listaGerencia(): array
    {
        return $this->handle(fn() => $this->success(RegionalService::listaGerencia()));
    }

    public function regionalFilial(): array
    {
        return $this->handle(fn() => $this->success(RegionalService::regionalFilial()));
    }

    public function usuario(Request $request, string $cpf): array
    {
        return $this->handle(function () use ($cpf) {
            $dados = RegionalService::usuario($cpf);

            if ($dados === null) {
                return $this->error('Usuário não encontrado.', 404);
            }

            return $this->success($dados);
        });
    }

    public function gravaGerente(Request $request): array
    {
        return $this->handle(function () use ($request) {
            $codregional = (int) $request->input('codregional', 0);
            $codfilial = (int) $request->input('codfilial', 0);
            $g1 = $request->input('g1', null);
            $g2 = $request->input('g2', null);
            $emailG1 = trim((string) $request->input('email_g1', ''));
            $corpG1 = trim((string) $request->input('corp_g1', ''));
            $emailG2 = trim((string) $request->input('email_g2', ''));
            $corpG2 = trim((string) $request->input('corp_g2', ''));

            if ($codregional <= 0 || $codfilial <= 0) {
                return $this->error('Regional e filial são obrigatórios.', 400);
            }

            RegionalService::gravaGerente($codregional, $codfilial, $g1 ?: null, $g2 ?: null);

            if ($g1) {
                RegionalService::atualizarContato($g1, $emailG1 !== '' ? $emailG1 : null, $corpG1 !== '' ? $corpG1 : null);
            }
            if ($g2) {
                RegionalService::atualizarContato($g2, $emailG2 !== '' ? $emailG2 : null, $corpG2 !== '' ? $corpG2 : null);
            }

            return $this->success(['codregional' => $codregional], [], 'Registro gravado.');
        });
    }

    public function updateRegional(Request $request, int $id): array
    {
        return $this->handle(function () use ($request, $id) {
            $codregional = (int) $request->input('codregional', 0);
            $g1 = $request->input('g1', null);
            $g2 = $request->input('g2', null);
            $emailG1 = trim((string) $request->input('email_g1', ''));
            $corpG1 = trim((string) $request->input('corp_g1', ''));
            $emailG2 = trim((string) $request->input('email_g2', ''));
            $corpG2 = trim((string) $request->input('corp_g2', ''));

            if ($codregional <= 0) {
                return $this->error('Regional é obrigatória.', 400);
            }

            RegionalService::updateRegional($id, $codregional, $g1 ?: null, $g2 ?: null);

            if ($g1) {
                RegionalService::atualizarContato($g1, $emailG1 !== '' ? $emailG1 : null, $corpG1 !== '' ? $corpG1 : null);
            }
            if ($g2) {
                RegionalService::atualizarContato($g2, $emailG2 !== '' ? $emailG2 : null, $corpG2 !== '' ? $corpG2 : null);
            }

            return $this->success(['codregional' => $codregional], [], 'Registro atualizado.');
        });
    }

    public function updatefilialReg(Request $request, int $filial): array
    {
        return $this->handle(function () use ($request, $filial) {
            $codRegional = (int) $request->input('cod_regional', 0);

            if ($codRegional <= 0) {
                return $this->error('Regional é obrigatória.', 400);
            }

            RegionalService::updatefilialReg($filial, $codRegional);

            return $this->success(['cod_regional' => $codRegional], [], 'Filial atualizada.');
        });
    }

    public function alteraRegional(Request $request, int $regiao): array
    {
        return $this->handle(function () use ($request, $regiao) {
            $nome = trim((string) $request->input('nome', ''));

            if ($nome === '') {
                return $this->error('Nome da regional é obrigatório.', 400);
            }

            RegionalService::alteraRegional($regiao, $nome);

            return $this->success(['nome' => $nome], [], 'Regional atualizada.');
        });
    }

    public function deletaGerente(Request $request, int $id): array
    {
        return $this->handle(function () use ($id) {
            $removido = RegionalService::deletaGerente($id);

            if (!$removido) {
                return $this->error('Registro não encontrado.', 404);
            }

            return $this->success(null, [], 'Gerência excluída.');
        });
    }

    public function criaRegional(Request $request): array
    {
        return $this->handle(function () use ($request) {
            $regiao = (int) $request->input('regiao', 0);
            $nome = trim((string) $request->input('nome', ''));
            $cpf = preg_replace('/\D/', '', (string) $request->input('cpf', '')) ?: null;

            if ($regiao <= 0) {
                return $this->error('Número da regional é obrigatório.', 400);
            }

            if ($nome === '') {
                return $this->error('Nome da regional é obrigatório.', 400);
            }

            if (RegionalService::regionalExiste($regiao)) {
                return $this->error('Já existe uma regional com o número informado.', 409);
            }

            RegionalService::gravaRegional($regiao, $nome, $cpf);

            return $this->success(['regiao' => $regiao], [], 'Regional criada.');
        });
    }

    public function excluiRegional(Request $request, int $regiao): array
    {
        return $this->handle(function () use ($regiao) {
            $removido = RegionalService::deletaRegional($regiao);

            if (!$removido) {
                return $this->error('Regional não encontrada.', 404);
            }

            return $this->success(null, [], 'Regional excluída.');
        });
    }

    public function vinculaFilial(Request $request): array
    {
        return $this->handle(function () use ($request) {
            $codRegional = (int) $request->input('cod_regional', 0);
            $filial = trim((string) $request->input('filial', ''));

            if ($codRegional <= 0) {
                return $this->error('Regional é obrigatória.', 400);
            }

            if ($filial === '') {
                return $this->error('Filial é obrigatória.', 400);
            }

            if (RegionalService::filialVinculada($codRegional, $filial)) {
                return $this->error('Filial já está vinculada a esta regional.', 409);
            }

            RegionalService::gravaFilial($codRegional, $filial);

            return $this->success(['cod_regional' => $codRegional, 'filial' => $filial], [], 'Filial vinculada.');
        });
    }

    public function desvinculaFilial(Request $request, int $id): array
    {
        return $this->handle(function () use ($id) {
            $removido = RegionalService::deletaFilial($id);

            if (!$removido) {
                return $this->error('Vínculo não encontrado.', 404);
            }

            return $this->success(null, [], 'Filial desvinculada.');
        });
    }
}