<?php

namespace App\Controller;

use Throwable;
use App\Core\BaseController;
use App\Core\ErrorHandler;
use App\Core\Logger;
use App\Core\Request;
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

    public function listaView(Request $request): void
    {
        $permitidos = ['405',''];

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

    public function listaDownload(Request $request): void
    {
        $permitidos = ['405','0310'];

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
}
