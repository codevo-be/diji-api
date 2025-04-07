<?php

namespace Diji\Task\Enums;

enum TaskStatus: string
{
    case PENDING = 'pending';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    /**
     * Récupérer toutes les valeurs sous forme de tableau
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
