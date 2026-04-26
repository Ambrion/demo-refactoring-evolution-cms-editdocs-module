<?php

declare(strict_types=1);

namespace EditDocs\ValueObject;

use InvalidArgumentException;

/**
 * Value Object для ID родительского документа.
 *
 * Гарантирует, что любой экземпляр представляет валидный положительный integer ID.
 * Неизменяем (immutable) после создания.
 *
 * @package EditDocs\ValueObject
 */
final class ParentId
{
    /** @var int */
    private int $value;

    /**
     * Приватный конструктор — создание только через фабричные методы
     *
     * @param int $value
     * @throws InvalidArgumentException если значение невалидно
     */
    private function __construct(int $value)
    {
        if ($value < 1) {
            throw new InvalidArgumentException(
                sprintf('ParentId must be a positive integer, %d given', $value)
            );
        }

        $this->value = $value;
    }

    /**
     * Фабричный метод: создаёт VO из сырого значения
     *
     * @param mixed $value
     * @return self
     * @throws InvalidArgumentException если значение не может быть преобразовано
     */
    public static function fromRaw(mixed $value): self
    {
        // Приводим к int, если возможно
        $int = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($int === false) {
            throw new InvalidArgumentException(
                sprintf('Invalid ParentId value: %s', var_export($value, true))
            );
        }

        return new self($int);
    }

    /**
     * Фабричный метод: создаёт VO или возвращает ошибку (для использования в DTO)
     *
     * @param mixed $value
     * @return array ['valid' => bool, 'vo' => self|null, 'error' => string|null]
     */
    public static function tryFromRaw(mixed $value): array
    {
        try {
            return [
                'valid' => true,
                'vo' => self::fromRaw($value),
                'error' => null
            ];
        } catch (InvalidArgumentException $e) {
            return [
                'valid' => false,
                'vo' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Геттер: возвращает значение как int (для использования в SQL и т.д.)
     */
    public function toInt(): int
    {
        return $this->value;
    }

    /**
     * Сравнение по значению (для тестов и бизнес-логики)
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Строковое представление (для логирования, отладки)
     */
    public function __toString(): string
    {
        return (string)$this->value;
    }
}
