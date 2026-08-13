<?php

namespace App\Enums\Admin\Skills;

enum SkillRuleOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case In = 'in';
    case NotIn = 'not_in';
    case Contains = 'contains';
    case NotContains = 'not_contains';
    case StartsWith = 'starts_with';
    case EndsWith = 'ends_with';
    case IsEmpty = 'is_empty';
    case IsNotEmpty = 'is_not_empty';
    case IsTrue = 'is_true';
    case IsFalse = 'is_false';
    case GreaterThan = 'greater_than';
    case GreaterThanOrEqual = 'greater_than_or_equal';
    case LessThan = 'less_than';
    case LessThanOrEqual = 'less_than_or_equal';
    case Between = 'between';
}
