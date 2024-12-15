<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class TimeExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('ago', [$this, 'getTimeAgo']),
        ];
    }

    public function getTimeAgo(\DateTimeInterface $datetime)
    {
        $now = new \DateTime();
        $diff = $now->diff($datetime);

        if ($diff->y > 0) {
            return $diff->y . ' an' . ($diff->y > 1 ? 's' : '');
        }
        if ($diff->m > 0) {
            return $diff->m . ' mois';
        }
        if ($diff->d > 0) {
            return $diff->d . ' jour' . ($diff->d > 1 ? 's' : '');
        }
        if ($diff->h > 0) {
            return $diff->h . ' heure' . ($diff->h > 1 ? 's' : '');
        }
        if ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '');
        }

        return 'à l\'instant';
    }
}
