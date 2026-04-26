<?php declare(strict_types=1);

namespace EditDocs\Tests\Legacy;

use EditDocs\Tests\Stubs\TestResponseSender;
use PHPUnit\Framework\TestCase;
use EditDocs\Http\EditDocsAjaxHandler;
use EditDocs\Service\EditDocsMassMoveService;
use EditDocs\Dto\EditDocsMassMoveInput;

class EditDocsMassMoveFinalTest extends TestCase
{
    private array $originalPost = [];
    private array $originalServer = [];
    private $mockModx;
    private $mockEditDocs;

    protected function setUp(): void
    {
        $this->originalPost = $_POST;
        $this->originalServer = $_SERVER;
        $_SERVER['REQUEST_METHOD'] = 'POST';

        if (!class_exists('DocumentParser', false)) {
            require_once __DIR__ . '/../Stubs/LegacyStubs.php';
        }

        $this->mockModx = $this->getMockBuilder(\DocumentParser::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['logEvent', 'clearCache', 'getFullTableName'])
            ->getMock();

        $this->mockModx->method('logEvent')->willReturnCallback(function () {
        });
        $this->mockModx->method('clearCache')->willReturnCallback(function () {
        });
        $this->mockModx->method('getFullTableName')
            ->willReturnCallback(fn(string $table): string => 'modx_' . $table);

        $this->mockEditDocs = $this->getMockBuilder(\editDocs::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->mockEditDocs->lang = [
            'notall' => 'Заполните оба поля',
            'cleared' => 'Кэш очищен',
        ];
    }

    protected function tearDown(): void
    {
        $_POST = $this->originalPost;
        $_SERVER = $this->originalServer;
        $this->mockModx = null;
        $this->mockEditDocs = null;
    }

    /**
     * @dataProvider massMoveSuccessProvider
     *
     * Успешные сценарии: вывод через echo → используем ob_start()
     */
    public function testHandleRequest_MassMove_Success(array $postData, string $expectedOutput): void
    {
        $_POST = $postData;

        $responseSender = new TestResponseSender();

        $handler = $this->getMockBuilder(EditDocsAjaxHandler::class)
            ->setConstructorArgs([$this->mockModx, $this->mockEditDocs, $responseSender])
            ->onlyMethods(['createMassMoveService'])
            ->getMock();

        $mockService = $this->createMock(EditDocsMassMoveService::class);
        $mockService
            ->method('execute')
            ->with($this->isInstanceOf(EditDocsMassMoveInput::class))
            ->willReturn($expectedOutput);

        $handler
            ->method('createMassMoveService')
            ->willReturn($mockService);

        // Вывод через echo → захватываем через ob_start()
        ob_start();
        $handler->handleRequest();
        $actualOutput = ob_get_clean();

        $this->assertSame($expectedOutput, $actualOutput);
    }

    /**
     * Ошибка валидации: вывод через echo → используем ob_start()
     */
    public function testHandleRequest_IncompleteMoveParams_Error(): void
    {
        $_POST = ['parent1' => '10'];

        $responseSender = new TestResponseSender();
        $handler = new EditDocsAjaxHandler($this->mockModx, $this->mockEditDocs, $responseSender);

        // Вывод через echo → захватываем через ob_start()
        ob_start();
        $handler->handleRequest();
        $actualOutput = ob_get_clean();

        $this->assertSame(
            '<div class="alert alert-danger">Заполните оба поля</div>',
            $actualOutput
        );
    }

    /**
     * Нет параметров → срабатывает проверка в handleRequest() → handleError() → responseSender
     */
    public function testHandleRequest_NoMoveParams_NoOutput(): void
    {
        $responseSender = new TestResponseSender();
        $handler = new EditDocsAjaxHandler($this->mockModx, $this->mockEditDocs, $responseSender);

        $_POST = [];
        $handler->handleRequest();

        // Ошибка отправляется через responseSender → проверяем lastContent
        $this->assertSame(
            '{"error":"No POST data or uploaded files received."}',
            $responseSender->lastContent
        );
        $this->assertSame(400, $responseSender->lastStatusCode);
    }

    /**
     * Значения "0" → empty('0') === true → ошибка валидации → echo
     */
    public function testHandleRequest_MassMove_WithZeroValues_TriggersError(): void
    {
        $_POST = ['parent1' => '0', 'parent2' => '0'];

        $responseSender = new TestResponseSender();
        $handler = new EditDocsAjaxHandler($this->mockModx, $this->mockEditDocs, $responseSender);

        // Вывод через echo → захватываем через ob_start()
        ob_start();
        $handler->handleRequest();
        $actualOutput = ob_get_clean();

        $this->assertSame(
            '<div class="alert alert-danger">Заполните оба поля</div>',
            $actualOutput
        );
    }

    /**
     * Исключение → handleException() → handleError() → responseSender
     */
    public function testHandleRequest_Exception_ReturnsJsonError(): void
    {
        $_POST = ['parent1' => '10', 'parent2' => '20'];

        $responseSender = new TestResponseSender();

        $handler = $this->getMockBuilder(EditDocsAjaxHandler::class)
            ->setConstructorArgs([$this->mockModx, $this->mockEditDocs, $responseSender])
            ->onlyMethods(['createMassMoveService'])
            ->getMock();

        $handler
            ->method('createMassMoveService')
            ->willThrowException(new \RuntimeException('Test exception'));

        // Запрещаем вывод в буфер, чтобы случайно не перехватить echo
        $this->expectOutputString('');

        $handler->handleRequest();

        $this->assertStringContainsString('"error":"Internal server error."', $responseSender->lastContent);
        $this->assertSame(500, $responseSender->lastStatusCode);
    }

    public static function massMoveSuccessProvider(): array
    {
        return [
            'Оба параметра — строки' => [
                ['parent1' => '10', 'parent2' => '20'],
                'Успешное массовое перемещение'
            ],
            'Оба параметра — числа как строки' => [
                ['parent1' => '1', 'parent2' => '100'],
                'Успешное массовое перемещение'
            ],
            'Параметры с пробелами (не empty)' => [
                ['parent1' => ' ', 'parent2' => '  '],
                'Успешное массовое перемещение'
            ],
        ];
    }
}
