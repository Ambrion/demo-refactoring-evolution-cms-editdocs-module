<?php

declare(strict_types=1);


namespace EditDocs\Service;

use EditDocs\Dto\EditDocsMassMoveInput;

/**
 * Контракт для операции массового перемещения документов.
 *
 * Возвращает строку (HTML) для совместимости с фронтом модуля.
 */
interface EditDocsMassMoveServiceInterface
{
    /**
     * Выполняет перемещение документов
     *
     * @param EditDocsMassMoveInput $input валидированные входные данные
     * @return string HTML-ответ для отображения во фронте
     */
    public function execute(EditDocsMassMoveInput $input): string;
}
