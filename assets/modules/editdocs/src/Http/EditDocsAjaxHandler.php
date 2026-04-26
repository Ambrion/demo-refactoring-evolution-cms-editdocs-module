<?php

declare(strict_types=1);

namespace EditDocs\Http;

use editDocs;
use EditDocs\Dto\EditDocsMassMoveInput;
use EditDocs\Repository\EditDocsDocumentRepository;
use EditDocs\Service\EditDocsMassMoveService;
use DocumentParser;
use EditDocs\Service\EditDocsMassMoveServiceInterface;
use EditDocs\Validation\EditDocsMassMoveValidator;
use PHPMailer\PHPMailer\Exception;
use Throwable;

class EditDocsAjaxHandler
{
    protected DocumentParser $modx;
    private editDocs $editDocs;
    private HttpHeaderResponseSender|ResponseSenderInterface $responseSender;

    public function __construct(DocumentParser $modx, editDocs $editDocs, ResponseSenderInterface $responseSender = null)
    {
        $this->modx = $modx;
        $this->editDocs = $editDocs;
        $this->responseSender = $responseSender ?? new HttpHeaderResponseSender();
    }

    public function handleRequest(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->handleError('Only POST requests are allowed.', 405);
        }

        if (empty($_POST) && empty($_FILES)) {
            $this->handleError('No POST data or uploaded files received.', 400);
        }

        try {
            if (!empty($_POST['clear'])) {
                $this->handleClearCache();
            } elseif (!empty($_POST['edit'])) {
                $this->handleGetAllList();
            } elseif (!empty($_POST['id'])) {
                $this->handleEditDoc();
            } elseif (!empty($_FILES['myfile'])) {
                $this->handleUploadFile();
            } elseif (!empty($_POST['imp'])) {
                $this->handleImportExcel();
            } elseif (!empty($_POST['export'])) {
                $this->handleExport();
            } elseif (!empty($_POST['parent1']) && !empty($_POST['parent2'])) {
                $this->handleMassMove();
            } elseif (isset($_POST['parent1']) || isset($_POST['parent2'])) {
                $this->handleIncompleteMoveParams();
            } elseif (!empty($_POST['cls']) && $_POST['cls'] == 1) {
                $this->handleSessionCleanup();
            } elseif (!empty($_POST['save_config'])) {
                $this->handleSaveConfig();
            } elseif (!empty($_POST['getlist_files'])) {
                $this->handleGetListFiles();
            } elseif (!empty($_POST['cfg_file'])) {
                $this->handleLoadCfgFile();
            }
        } catch (Throwable $e) {
            // Централизованная обработка ошибок
            $this->handleException($e);
        }
    }

    protected function handleError(string $message, int $code): void
    {
        $this->responseSender->send(
            json_encode(['error' => $message], JSON_UNESCAPED_UNICODE),
            $code,
            ['Content-Type: application/json; charset=utf-8']
        );
    }

    /**
     * @throws Exception
     */
    protected function handleException(Throwable $e): void
    {
        // Логируем ошибку в MODX
        if (method_exists($this->modx, 'logEvent')) {
            $this->modx->logEvent(0, 3, 'AJAX Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), 'editDocs');
        }

        // Возвращаем безопасный ответ
        $this->handleError('Internal server error.', 500);

    }

    private function handleClearCache(): void
    {
        $this->editDocs->clearCache();
        echo $this->editDocs->lang['cleared'];
    }

    private function handleGetAllList(): void
    {
        echo $this->editDocs->getAllList();
    }

    private function handleEditDoc(): void
    {
        echo $this->editDocs->editDoc();
    }

    private function handleUploadFile(): void
    {
        echo $this->editDocs->uploadFile();
    }

    private function handleImportExcel(): void
    {
        echo $this->editDocs->importExcel();
    }

    private function handleExport(): void
    {
        echo $this->editDocs->export();
    }

    private function handleMassMove(): void
    {
        $input = EditDocsMassMoveInput::fromPost($_POST);
        $service = $this->createMassMoveService();
        echo $service->execute($input);
    }

    /**
     * Фабричный метод для создания сервиса массового перемещения.
     * Вынесен для возможности мокирования в тестах.
     * @protected для тестирования, можно сделать private + reflection, но protected удобнее
     */
    protected function createMassMoveService(): EditDocsMassMoveServiceInterface
    {
        $repository = new EditDocsDocumentRepository($this->modx);
        $validator = new EditDocsMassMoveValidator($repository, $this->editDocs);

        return new EditDocsMassMoveService($repository, $this->editDocs, $validator);
    }


    private function handleIncompleteMoveParams(): void
    {
        // Один параметр есть, другого нет → валидация на уровне контроллера
        echo '<div class="alert alert-danger">' . ($this->editDocs->lang['notall'] ?? 'Not all parameters') . '</div>';
    }

    private function handleSessionCleanup(): void
    {
        // Удаляем сессии после обработки
        $_SESSION['import_start'] = 2; // Начинаем импорт со второй строки файла
        $_SESSION['import_i'] = 0;
        $_SESSION['import_j'] = 0;
        $_SESSION['tabrows'] = '';
    }

    private function handleSaveConfig(): void
    {
        $this->editDocs->saveConfig($_POST);
    }

    private function handleGetListFiles(): void
    {
        echo $this->editDocs->loadListFiles($_POST['getlist_files']);
    }

    private function handleLoadCfgFile(): void
    {
        echo $this->editDocs->loadCfgFile($_POST['cfg_file']);
    }
}
