<?php

declare(strict_types=1);

namespace Modules\Academic\Domain\Enums;

enum QuestionType: string
{
    case SingleChoice = 'single_choice';
    case MultiSelect = 'multi_select';
    case TrueFalse = 'true_false';
    case Matching = 'matching';
    case Ordering = 'ordering';
    case Situational = 'situational';
}
