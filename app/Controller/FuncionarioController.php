<?php

namespace App\Controller;

use Throwable;
use App\Core\BaseController;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
use App\Core\Response;
use App\Service\AuthService;
use App\Service\FuncionarioService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class FuncionarioController extends BaseController
{
    public function index(Request $request): array
    {
        return $this->handle(function () use ($request) {
            $page = max(1, (int) $request->query('page', 1));
            $limit = min(100, max(1, (int) $request->query('limit', 10)));

            $result = FuncionarioService::paginate($page, $limit);

            return $this->success(
                $result['data'],
                $result['meta'],
                'Funcionarios listados com sucesso'
            );
        });
    }

    public function findByChapa(Request $request, string $chapa): array
    {
        return $this->handle(
            fn() => $this->success(
                FuncionarioService::findByChapa($chapa),
                [],
                'Funcionário encontrado com sucesso'
            )
        );
    }

    public function findByNome(Request $request, string $nome): array
    {
        return $this->handle(
            fn() => $this->success(
                FuncionarioService::findByNome($nome),
                [],
                'Funcionário encontrado com sucesso'
            )
        );
    }

    public function indexView(Request $request): void
    {
        try {
            $page = max(1, (int) $request->query('page', 1));
            $limit = min(100, max(1, (int) $request->query('limit', 10)));
            $codfilial = $request->input('filial', $request->query('filial', ''));
            $secao = $request->input('secao', $request->query('secao', ''));
            $situacao = $request->input('situacao', $request->query('situacao', ''));
            $nome = $request->input('nome', $request->query('nome', ''));

            $result = FuncionarioService::ativos($page, $limit, $codfilial ?: null, $secao ?: null, $situacao ?: null, $nome ?: null);

            view('funcionarios/index', [
                'data' => $result['data'],
                'meta' => $result['meta'],
                'filial' => $codfilial,
                'secao' => $secao,
                'situacao' => $situacao,
                'nome' => $nome,
                'title' => 'Funcionários'
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);
            
            ErrorHandler::handle(500, $e->getMessage());
        }
    }

    public function sessaoView(Request $request): void
    {
        try {

            view('ti/sessao', [
                'data' => AuthService::getUser(),
                'title' => 'TESTE SESSÃO'
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);
            
            ErrorHandler::handle(500, $e->getMessage());
        }
    }

    public function listaView(Request $request): void
    {
        $permitidos = ['405','0318'];

        try {

            $page = max(1, (int) $request->query('page', 1));
            $limit = min(100, max(1, (int) $request->query('limit', 30)));

            $result = FuncionarioService::ti($page, $limit);

            if(!AuthService::hasAnyRole($permitidos)) {
                ErrorHandler::handle(403, 'Acesso não permitido!', false);
                return;
            }

            view('ti/lista', [
                'data' => $result['data'],
                'meta' => $result['meta']
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage());
        }
    }

    public function aniversariantesView(Request $request): void
    {
        try {
            $mes = $request->query('mes', date('n'));
            $mes = max(1, min(12, (int) $mes));

            $codfilial = $request->input('filial', $request->query('filial', ''));

            $data = FuncionarioService::aniversariantes($mes, $codfilial ?: null);

            view('funcionarios/aniversariantes', [
                'data' => $data,
                'mes' => $mes,
                'filial' => $codfilial,
                'title' => 'Aniversariantes'
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage());
        }
    }

    public function listaDownload(Request $request): void
    {
        $permitidos = ['405','0310','0318'];

        if(!AuthService::hasAnyRole($permitidos)) {
            ErrorHandler::handle(403, 'Acesso não permitido!', false);
            return;
        }

        try {
            $data = FuncionarioService::exportTi();

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $headers = ['Chapa', 'Nome', 'Filial', 'Filial Nome', 'Função', 'Salário', 'Data Admissão', 'Situação', 'Última Alteração', 'Motivo'];
            $sheet->fromArray([$headers], null, 'A1');

            $sheetData = [];
            foreach ($data as $usuario) {
                $sheetData[] = [
                    $usuario['chapa'],
                    $usuario['nome'],
                    $usuario['codfilial'],
                    ($usuario['codfilial'] . ' - ' . $usuario['filial']),
                    $usuario['funcao'],
                    $usuario['salario'],
                    $usuario['dataadmissao'] ?? '',
                    $usuario['situacao'],
                    $usuario['ultima_alteracao'] ?? '',
                    $usuario['motivo'] ?? '',
                ];
            }
            $sheet->fromArray($sheetData, null, 'A2');

            foreach (range('A', 'J') as $colLetter) {
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="ti_lista.xlsx"');

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage());
        }
    }

    public function admissoesView(Request $request): void
    {
        try {
            $data = FuncionarioService::admissoes();

            view('funcionarios/admissoes', [
                'data' => $data,
                'title' => 'Admissões'
            ]);
        } catch (Throwable $e) {
            Logger::exception($e);

            ErrorHandler::handle(500, $e->getMessage());
        }
    }

    public function admissoesJson(Request $request): void
    {
        try {
            $data = array_map(static fn(array $item): array => [
                'pendentes' => (int) ($item['pendentes'] ?? 0),
                'dataadmissao' => !empty($item['dataadmissao']) ? date('d/m/Y', strtotime($item['dataadmissao'])) : '-',
                'dataevento' => !empty($item['dataevento']) ? date('d/m/Y H:i:s', strtotime($item['dataevento'])) : '-',
            ], FuncionarioService::admissoes());

            Response::json(['data' => $data]);
        } catch (Throwable $e) {
            Logger::exception($e);

            Response::json(['error' => 'Erro ao carregar as admissões.'], 500);
        }
    }

}
