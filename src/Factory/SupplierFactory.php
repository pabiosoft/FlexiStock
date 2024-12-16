<?php

namespace App\Factory;

use App\Entity\Supplier;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Supplier>
 */
final class SupplierFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Supplier::class;
    }

    /**
     * Retourne les valeurs par défaut pour chaque champ.
     */
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->company(), // Nom réaliste d'une entreprise
            'contact' => self::faker()->name(), // Nom d'une personne de contact
            'email' => self::faker()->unique()->companyEmail(), // Email professionnel unique
            'address' => self::faker()->address(), // Adresse réaliste
            'phone' => self::faker()->optional()->phoneNumber(), // Numéro de téléphone
        ];
    }

    /**
     * Permet des actions après instanciation.
     */
    protected function initialize(): static
    {
        return $this;
    }
}
