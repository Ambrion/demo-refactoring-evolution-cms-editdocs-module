<?php

declare(strict_types=1);

namespace EditDocs\Dto;

use EditDocs\ValueObject\ParentId;

/**
 * DTO для валидации входных данных massMove.
 * Использует Value Objects для типизированной валидации.
 */
final class EditDocsMassMoveInput
{
    /** @var ParentId */
    public ParentId $sourceParent;

    /** @var ParentId */
    public ParentId $targetParent;

    /** @var string|null */
    public ?string $error;

    /**
     * Приватный конструктор
     */
    private function __construct()
    {
    }

    /**
     * Создаёт DTO из $_POST
     *
     * @param array $post
     * @return self
     */
    public static function fromPost(array $post): self
    {
        $dto = new self();

        // Валидация через Value Object
        $sourceResult = ParentId::tryFromRaw($post['parent1'] ?? null);
        $targetResult = ParentId::tryFromRaw($post['parent2'] ?? null);

        if (!$sourceResult['valid']) {
            $dto->error = 'Source parent ID: ' . $sourceResult['error'];

            return $dto;
        }

        if (!$targetResult['valid']) {
            $dto->error = 'Target parent ID: ' . $targetResult['error'];

            return $dto;
        }

        $dto->sourceParent = $sourceResult['vo'];
        $dto->targetParent = $targetResult['vo'];
        $dto->error = null;

        return $dto;
    }

    /**
     * Проверка валидности
     */
    public function isValid(): bool
    {
        return $this->error === null;
    }
}
