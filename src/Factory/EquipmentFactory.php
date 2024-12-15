<?php

namespace App\Factory;

use App\Entity\Equipment;
use App\Factory\CategoryFactory;
use App\Factory\UserFactory;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Equipment>
 */
final class EquipmentFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Equipment::class;
    }

    /**
     * Retourne les valeurs par défaut pour chaque champ.
     */
    protected function defaults(): array|callable
    {
        $stockQuantity = self::faker()->numberBetween(0, 50);
        $reservedQuantity = self::faker()->numberBetween(0, $stockQuantity);

        return [
            'name' => self::faker()->unique()->words(3, true),
            'brand' => self::faker()->company(),
            'model' => self::faker()->regexify('[A-Z]{3}-[0-9]{4}'),
            'serialNumber' => self::faker()->unique()->uuid(), // Numéro de série unique
            'purchaseDate' => self::faker()->dateTimeBetween('-5 years', 'now'),
            'warrantyDate' => self::faker()->optional()->dateTimeBetween('now', '+5 years'),
            'status' => self::faker()->randomElement(['available', 'in_use', 'maintenance']), // Statut valide
            'stockQuantity' => $stockQuantity,
            'reservedQuantity' => $reservedQuantity, // Toujours <= stockQuantity
            'minThreshold' => self::faker()->numberBetween(1, 5),
            'price' => self::faker()->randomFloat(2, 100, 5000), // Prix entre 100 et 5000
            'location' => self::faker()->city(),
            'description' => self::faker()->optional()->paragraph(),
            'createdAt' => self::faker()->dateTimeBetween('-1 year', 'now'),
            'lastMaintenanceDate' => self::faker()->optional()->dateTimeBetween('-1 year', 'now'),
            'nextMaintenanceDate' => self::faker()->optional()->dateTimeBetween('now', '+1 year'),
            'expirationDate' => self::faker()->optional()->dateTimeBetween('now', '+5 years'),
            'lowStockThreshold' => self::faker()->numberBetween(0, 10),
            'category' => CategoryFactory::random(), // Relation obligatoire
            'assignedUser' => UserFactory::random(), // Relation avec User
        ];
    }

    /**
     * Initialisation pour les actions après instanciation.
     */
    protected function initialize(): static
    {
        return $this;
    }
}
