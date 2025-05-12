<?php
namespace App\Security;

use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class FrontUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (method_exists($user, 'getStatus') && $user->getStatus() === 'en_attente') {
            if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {
                throw new CustomUserMessageAuthenticationException(
                    'Votre compte est en attente de validation, contactez un administrateur.'
                );
            }
        }
    }
}