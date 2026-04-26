<?php declare(strict_types=1);

namespace EditDocs\Tests\Legacy;

use EditDocs\Service\EditDocsMassMoveServiceInterface;
use PHPUnit\Framework\TestCase;
use EditDocs\Http\EditDocsAjaxHandler;
use DocumentParser;
use editDocs;
use Throwable;

/**
 * Тестируемая подкласс-обёртка.
 * Переопределяет методы обработки ошибок для безопасного тестирования.
 */
class TestableEditDocsAjaxHandler extends EditDocsAjaxHandler
{
    private ?EditDocsMassMoveServiceInterface $massMoveServiceStub = null;
    private bool $testMode = true;

    public function setMassMoveServiceStub(EditDocsMassMoveServiceInterface $service): void
    {
        $this->massMoveServiceStub = $service;
    }

    public function setTestMode(bool $enabled): void
    {
        $this->testMode = $enabled;
    }

    protected function createMassMoveService(): EditDocsMassMoveServiceInterface
    {
        return $this->massMoveServiceStub ?? parent::createMassMoveService();
    }

    /**
     * 🔧 В тестовом режиме: не отправляем заголовки и не делаем exit
     */
    protected function handleError(string $message, int $code): void
    {
        if ($this->testMode) {
            echo $message;
            return;
        }

        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * 🔧 В тестовом режиме: не логируем в MODX и не отправляем заголовки
     */
    protected function handleException(Throwable $e): void
    {
        if ($this->testMode) {
            // В тестах просто делегируем в наш безопасный handleError
            $this->handleError($e->getMessage(), 500);
            return;
        }

        // Продакшен-поведение
        if (method_exists($this->modx, 'logEvent')) {
            $this->modx->logEvent(0, 3, 'AJAX Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), 'editDocs');
        }
        $this->handleError('Internal server error.', 500);
    }
}

class EditDocsMassMoveCharacterizationTest extends TestCase
{
    private array $originalPost = [];
    private array $originalFiles = [];
    private array $originalServer = [];

    protected function setUp(): void
    {
        $this->originalPost = $_POST;
        $this->originalFiles = $_FILES ?? [];
        $this->originalServer = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_POST = $this->originalPost;
        $_FILES = $this->originalFiles;
        $_SERVER = $this->originalServer;
    }

    /**
     * @dataProvider postCasesProvider
     */
    public function testCharacterizeMassMoveBehaviorAfterRefactor(array $postData, string $expectedOutput): void
    {
        $_POST = $postData;
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $modx = $this->createMock(DocumentParser::class);
        $editDocs = $this->createMock(editDocs::class);
        $editDocs->lang = ['notall' => 'Заполните оба поля'];

        $handler = new TestableEditDocsAjaxHandler($modx, $editDocs);
        $handler->setTestMode(true);

        $serviceStub = $this->getMockBuilder(EditDocsMassMoveServiceInterface::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute'])
            ->getMock();
        $serviceStub
            ->method('execute')
            ->willReturn('Успешное массовое перемещение');

        $handler->setMassMoveServiceStub($serviceStub);

        ob_start();
        $handler->handleRequest();
        $actualOutput = ob_get_clean();

        $this->assertSame($expectedOutput, $actualOutput);
    }

    public function testCharacterizeIncompleteMoveParams(): void
    {
        $_POST['parent1'] = 10;
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $modx = $this->createMock(DocumentParser::class);
        $editDocs = $this->createMock(editDocs::class);
        $editDocs->lang = ['notall' => 'Заполните оба поля'];

        $handler = new TestableEditDocsAjaxHandler($modx, $editDocs);
        $handler->setTestMode(true);

        ob_start();
        $handler->handleRequest();
        $actualOutput = ob_get_clean();

        $expected = '<div class="alert alert-danger">Заполните оба поля</div>';
        $this->assertSame($expected, $actualOutput);
    }

    public static function postCasesProvider(): array
    {
        return [
            'Оба параметра заполнены' => [
                ['parent1' => '10', 'parent2' => '20'],
                'Успешное массовое перемещение'
            ],
            'Только parent1' => [
                ['parent1' => '10'],
                '<div class="alert alert-danger">Заполните оба поля</div>'
            ],
            'Только parent2' => [
                ['parent2' => '20'],
                '<div class="alert alert-danger">Заполните оба поля</div>'
            ],
            'Оба пустые строки' => [
                ['parent1' => '', 'parent2' => ''],
                '<div class="alert alert-danger">Заполните оба поля</div>'
            ],
            'Оба отсутствуют' => [
                [],
                'No POST data or uploaded files received.'
            ],
            'Значение "0"' => [
                ['parent1' => '0', 'parent2' => '0'],
                '<div class="alert alert-danger">Заполните оба поля</div>'
            ],
        ];
    }
}
