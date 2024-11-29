<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class EmailVerifiedVoter implements VoterInterface
{
    public const IS_VERIFIED = 'IS_VERIFIED';

    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        $user = $token->getUser();

        // If the user is not logged in, deny access
        if (!$user instanceof User) {
            return self::ACCESS_DENIED;
        }

        foreach ($attributes as $attribute) {
            if ($attribute === self::IS_VERIFIED) {
                return $user->isVerified() ? self::ACCESS_GRANTED : self::ACCESS_DENIED;
            }
        }

        return self::ACCESS_ABSTAIN;
    }
}
