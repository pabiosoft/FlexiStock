<?php

namespace App\Factory;

use App\Entity\User;
use App\Enum\UserRole;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @extends PersistentProxyObjectFactory<User>
 */
final class UserFactory extends PersistentProxyObjectFactory
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {}

    public static function class(): string
    {
        return User::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     */
    protected function defaults(): array|callable
    {
        return [
            'name' => self::faker()->name(),
            'email' => self::faker()->unique()->safeEmail(),
            'password' => 'password123', // Plain text, hashé plus tard
            'isVerified' => self::faker()->boolean(80), // 80% de chance d'être vérifié
            'role' => self::faker()->randomElement(UserRole::cases()),
            'createdAt' => self::faker()->dateTimeBetween('-1 year', 'now'),
        ];
    }

    /**
     * Initialisation pour hasher le mot de passe.
     */
    protected function initialize(): static
    {
        return $this->afterInstantiate(function (User $user): void {
            if ($user->getPassword()) {
                $hashedPassword = $this->passwordHasher->hashPassword($user, $user->getPassword());
                $user->setPassword($hashedPassword);
            }
        });
    }

    /**
     * Crée un utilisateur admin avec des informations spécifiques.
     */
    public static function createAdmin(): User
    {
        return self::createOne([
            'name' => 'Admin User',
            'email' => 'dev2@mo.com',
            'password' => 'flexiStock',
            'isVerified' => true,
            'role' => UserRole::ADMIN,
        ]);
    }
}
