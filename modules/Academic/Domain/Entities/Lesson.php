<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Entities;

use Modules\Academic\Domain\Entities\ContentBlocks\ContentBlock;
use Modules\Academic\Domain\Exceptions\InvalidBlockPosition;
use Modules\Academic\Domain\Exceptions\InvalidContentBlock;
use Modules\Academic\Domain\Exceptions\InvalidLessonPosition;
use Modules\Academic\Domain\ValueObjects\CurriculumCode;
use Modules\Academic\Domain\ValueObjects\LessonId;

final readonly class Lesson
{
    private const int MAX_TITLE_LENGTH = 180;

    private const int MAX_SUMMARY_LENGTH = 5000;

    /**
     * @param  list<ContentBlock>  $blocks
     */
    private function __construct(
        private LessonId $id,
        private CurriculumCode $code,
        private string $title,
        private ?string $summary,
        private ?int $durationMinutes,
        private int $position,
        private array $blocks,
    ) {}

    /**
     * @param  list<ContentBlock>  $blocks
     */
    public static function create(
        LessonId $id,
        CurriculumCode $code,
        string $title,
        ?string $summary,
        ?int $durationMinutes,
        int $position,
        array $blocks,
    ): self {
        $title = trim($title);
        $summary = self::normalizeOptionalText($summary);

        if ($title === '' || mb_strlen($title) > self::MAX_TITLE_LENGTH) {
            throw InvalidContentBlock::create();
        }

        if ($summary !== null && mb_strlen($summary) > self::MAX_SUMMARY_LENGTH) {
            throw InvalidContentBlock::create();
        }

        if ($durationMinutes !== null && $durationMinutes < 1) {
            throw InvalidContentBlock::create();
        }

        if ($position < 1) {
            throw InvalidLessonPosition::create();
        }

        if ($blocks === []) {
            throw InvalidContentBlock::create();
        }

        foreach ($blocks as $index => $block) {
            if ($block->position() !== $index + 1) {
                throw InvalidBlockPosition::create();
            }
        }

        return new self($id, $code, $title, $summary, $durationMinutes, $position, $blocks);
    }

    public function id(): LessonId
    {
        return $this->id;
    }

    public function code(): CurriculumCode
    {
        return $this->code;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function summary(): ?string
    {
        return $this->summary;
    }

    public function durationMinutes(): ?int
    {
        return $this->durationMinutes;
    }

    public function position(): int
    {
        return $this->position;
    }

    /** @return list<ContentBlock> */
    public function blocks(): array
    {
        return $this->blocks;
    }

    private static function normalizeOptionalText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
