<?php

declare(strict_types=1);

namespace EditDocs\Validation;

use EditDocs\Dto\EditDocsMassMoveInput;
use EditDocs\Exceptions\ValidationError;

/**
 * Интерфейс для валидатора данных перемещения ветки.
 * Определяет метод, который должен реализовать любой валидатор,
 * отвечающий за проверку EditDocsMassMoveInput.
 */
interface EditDocsMassMoveValidatorInterface
{
    /**
     * Выполняет все проверки для перемещения на основе предоставленного ввода.
     *
     * @param EditDocsMassMoveInput $input Входные данные для проверки.
     *
     * @throws ValidationError Если какие-либо проверки не проходят.
     *                          Конкретный тип исключения (например, SameParentError)
     *                          может указывать на причину сбоя.
     */
    public function validate(EditDocsMassMoveInput $input): void;
}
