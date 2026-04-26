<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * Фиктивный объект-заглушка для предсказуемых результатов.
 * В характеристических тестах мы не проверяем "правильность",
 * а документируем фактический вывод системы.
 */
class MassMoveObjStub
{
    public array $lang = ['notall' => 'Заполните оба поля'];

    public function massMove(): string
    {
        return 'Успешное массовое перемещение';
    }
}

class EditDocsMassMoveBaseCharacterizationTest extends TestCase
{
    private array $originalPost = [];

    protected function setUp(): void
    {
        // Обязательно сохраняем оригинальный $_POST, чтобы тесты не влияли друг на друга
        $this->originalPost = $_POST;
    }

    protected function tearDown(): void
    {
        $_POST = $this->originalPost;
    }

    /**
     * @dataProvider postCasesProvider
     */
    public function testCharacterizeMassMoveBehavior(array $postData, string $expectedOutput): void
    {
        $_POST = $postData;
        $obj = new MassMoveObjStub();

        // Захватываем вывод `echo` из тестируемого фрагмента
        ob_start();

        // ИССЛЕДУЕМЫЙ КОД
        if (!empty($_POST['parent1']) && !empty($_POST['parent2'])) {
            echo $obj->massMove();
        } else if (isset($_POST['parent1']) || isset($_POST['parent2'])) {
            echo '<div class="alert alert-danger">' . $obj->lang['notall'] . '</div>';
        }

        $actualOutput = ob_get_clean();

        // Фиксируем текущее поведение. Если логика изменится при рефакторинге, тест упадёт.
        $this->assertSame($expectedOutput, $actualOutput);
    }

    /**
     * Набор входных данных, покрывающий все ветки логики + характерные для PHP нюансы.
     */
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
                '' // Никакого вывода
            ],
            // Важный нюанс PHP: пустая строка "0" считается empty() == true
            'Значение "0"' => [
                ['parent1' => '0', 'parent2' => '0'],
                '<div class="alert alert-danger">Заполните оба поля</div>'
            ],
        ];
    }
}
