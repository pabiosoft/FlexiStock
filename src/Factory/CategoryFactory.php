<?php

namespace App\Factory;

use App\Entity\Category;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Category>
 */
final class CategoryFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Category::class;
    }

    /**
     * Retourne les valeurs par défaut pour chaque champ.
     */
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->unique()->words(3, true) ?: 'Default Category Name', // Assure un nom valide
            'description' => self::faker()->optional()->sentence(10), // Description facultative
            'categoryOrder' => self::faker()->numberBetween(1, 100), // Ordre aléatoire
            'parent' => self::faker()->optional(0.2, null)->randomElement(CategoryFactory::all()), // Parent (relation récursive)
        ];
    }

    /**
     * Méthode d'initialisation si besoin.
     */
    protected function initialize(): static
    {
        return $this;
    }
}
