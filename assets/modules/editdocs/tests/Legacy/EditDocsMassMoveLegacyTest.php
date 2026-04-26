<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class EditDocsMassMoveLegacyTest extends TestCase
{
    // Приватные свойства для сохранения суперглобальных
    private $originalPost;
    private $originalSession;
    private $originalGet;
    private $originalCookie;
    private $originalFiles;
    private $originalServer;

    // Приватные свойства для моков
    private MockObject $mockModxDb;
    private MockObject $mockModx;
    private MockObject $mockEditDocs;

    protected function setUp(): void
    {
        global $_SESSION;
        if (!isset($_SESSION)) {
            $_SESSION = [];
        }

        // 1. Сохраняем оригинальные значения суперглобальных
        $this->originalPost = $_POST;
        $this->originalSession = $_SESSION;
        $this->originalGet = $_GET;
        $this->originalCookie = $_COOKIE;
        $this->originalFiles = $_FILES;
        $this->originalServer = $_SERVER;

        // 2. Создаём моки
        // Мок для $this->modx->db (только метод query)
        $this->mockModxDb = $this->getMockBuilder(stdClass::class)
            ->setMethods(['query']) // Мокаем только query
            ->getMock();

        // Мок для $this->modx (методы clearCache и getFullTableName)
        $this->mockModx = $this->getMockBuilder('DocumentParser') // Мокаем DocumentParser
        ->disableOriginalConstructor() // Отключаем оригинальный конструктор
        ->setMethods(['clearCache', 'getFullTableName']) // Мокаем clearCache и getFullTableName на $modx
        ->getMock();

        // Подключаем мок $modx->db к моку $modx
        $this->mockModx->db = $this->mockModxDb;

        // Мокаем getFullTableName *на $this->mockModx*, чтобы он возвращал правильное имя таблицы
        $this->mockModx->expects($this->any()) // Вызывается несколько раз
        ->method('getFullTableName')
            ->with('site_content')
            ->willReturn('modx_site_content');

        // Мок для editDocs
        $this->mockEditDocs = $this->getMockBuilder('editDocs')
            ->disableOriginalConstructor() // Отключаем конструктор editDocs
            ->getMock();

        // Устанавливаем моки через Reflection
        $modxProp = new \ReflectionProperty('editDocs', 'modx');
        $modxProp->setValue($this->mockEditDocs, $this->mockModx);

        $langProp = new \ReflectionProperty('editDocs', 'lang');
        $langProp->setValue($this->mockEditDocs, [
            'ok_move' => 'Documents moved successfully!',
            'error_tree' => 'Database error occurred!',
            'notall' => 'Not all parameters provided!'
        ]);

        // Для простоты, просто мокнем метод, чтобы он не делал ничего реального.
        $this->mockEditDocs->method('clearCache');
    }

    // Тест: успешное выполнение massMove
    public function testMassMoveSuccess(): void
    {
        // Подготовка $_POST
        $_POST['parent1'] = 10;
        $_POST['parent2'] = 20;
        $tableName = 'modx_site_content'; // Имя, возвращаемое getFullTableName

        // Настройка ожиданий для clearCache: ожидаем 1 вызов
        $this->mockEditDocs->expects($this->once())
            ->method('clearCache');

        // Ожидаем вызовы query в правильном порядке с правильными строками и возвращаемыми значениями
        $this->mockModxDb->expects($this->exactly(3))
            ->method('query')
            ->withConsecutive(
                ["UPDATE {$tableName} SET parent = {$_POST['parent2']} WHERE  parent = {$_POST['parent1']}"],
                ["UPDATE {$tableName} SET isfolder = 1 WHERE  id = {$_POST['parent2']}"],
                ["UPDATE {$tableName} SET isfolder = 0 WHERE  id = {$_POST['parent1']}"]
            )
            ->willReturnOnConsecutiveCalls(true, true, true); // Все запросы успешны

        // Выполнение метода massMove
        $massMoveMethod = new \ReflectionMethod('editDocs', 'massMove');
        $result = $massMoveMethod->invoke($this->mockEditDocs);

        // Проверка результата
        $this->assertStringContainsString('alert-success', $result);
        $this->assertStringContainsString('Documents moved successfully!', $result);
    }

    // Тест: ошибка при выполнении massMove (неудачный запрос)
    public function testMassMoveFailure(): void
    {
        $_POST['parent1'] = 10;
        $_POST['parent2'] = 20;
        $tableName = 'modx_site_content'; // Имя, возвращаемое getFullTableName

        // Настройка ожиданий для clearCache: ожидаем 1 вызов
        $this->mockEditDocs->expects($this->once())
            ->method('clearCache');

        // Ожидаем, что *только первый* query (перемещение) будет вызван и вернёт false (ошибка)
        // Следующие два query не должны выполняться в этом сценарии.
        $this->mockModxDb->expects($this->once())
        ->method('query')
            ->with("UPDATE {$tableName} SET parent = {$_POST['parent2']} WHERE  parent = {$_POST['parent1']}")
            ->willReturn(false); // Основной запрос неудачен

        $massMoveMethod = new \ReflectionMethod('editDocs', 'massMove');
        $result = $massMoveMethod->invoke($this->mockEditDocs);

        // Проверка результата - должна быть ошибка
        $this->assertStringContainsString('alert-danger', $result);
        $this->assertStringContainsString('Database error occurred!', $result);
    }

    // Вспомогательный метод, эмулируем логику ajax.php для massMove
    private function simulateAjaxLogicForMassMove($editDocsInstance, $postData): string
    {
        // Эмулируем $_POST
        $_POST = $postData;

        if (!empty($_POST['parent1']) && !empty($_POST['parent2'])) {
            // Вызываем метод massMove через Reflection
            $massMoveMethod = new \ReflectionMethod('editDocs', 'massMove');
            return $massMoveMethod->invoke($editDocsInstance);
        } else if (isset($_POST['parent1']) || isset($_POST['parent2'])) {
            return '<div class="alert alert-danger">' . $editDocsInstance->lang['notall'] . '</div>';
        }
        return ''; // Ничего не делаем, если условия не выполнены
    }

    public function testAjaxLogicWithValidParamsCallsMassMove(): void
    {
        $_POST['parent1'] = 10;
        $_POST['parent2'] = 20;
        $tableName = 'modx_site_content'; // Имя, возвращаемое getFullTableName

        // Настройка ожиданий для clearCache: ожидаем 1 вызов
        $this->mockEditDocs->expects($this->once())
            ->method('clearCache');

        // Настройка ожиданий для успешного сценария внутри massMove
        $this->mockModxDb->expects($this->exactly(3))
            ->method('query')
            ->withConsecutive(
                ["UPDATE {$tableName} SET parent = {$_POST['parent2']} WHERE  parent = {$_POST['parent1']}"],
                ["UPDATE {$tableName} SET isfolder = 1 WHERE  id = {$_POST['parent2']}"],
                ["UPDATE {$tableName} SET isfolder = 0 WHERE  id = {$_POST['parent1']}"]
            )
            ->willReturnOnConsecutiveCalls(true, true, true);

        $result = $this->simulateAjaxLogicForMassMove($this->mockEditDocs, $_POST);

        $this->assertStringContainsString('alert-success', $result);
        $this->assertStringContainsString('Documents moved successfully!', $result);
    }

    public function testAjaxLogicWithOneParamReturnsError(): void
    {
        // Только parent1
        $_POST = ['parent1' => 10];
        $result = $this->simulateAjaxLogicForMassMove($this->mockEditDocs, $_POST);
        $this->assertStringContainsString('alert-danger', $result);
        $this->assertStringContainsString('Not all parameters provided!', $result);

        // Только parent2
        $_POST = ['parent2' => 20];
        $result = $this->simulateAjaxLogicForMassMove($this->mockEditDocs, $_POST);
        $this->assertStringContainsString('alert-danger', $result);
        $this->assertStringContainsString('Not all parameters provided!', $result);
    }

    public function testAjaxLogicWithNoParamsReturnsEmpty(): void
    {
        $_POST = [];
        $result = $this->simulateAjaxLogicForMassMove($this->mockEditDocs, $_POST);
        $this->assertEmpty($result); // Ожидаем пустую строку, если ни одно условие не сработало
    }

    protected function tearDown(): void
    {
        // Восстанавливаем оригинальные значения суперглобальных
        $_POST = $this->originalPost;
        $_SESSION = $this->originalSession;
        $_GET = $this->originalGet;
        $_COOKIE = $this->originalCookie;
        $_FILES = $this->originalFiles;
        $_SERVER = $this->originalServer;
    }
}
