<?php
namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;

class AdminUserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            throw new CustomUserMessageAuthenticationException(
                'Vous n’avez pas la permission d’accéder à cette page.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        if (method_exists($user, 'getStatus') && $user->getStatus() !== 'active') {
            throw new CustomUserMessageAuthenticationException(
                'Votre compte a été désactivé.'
            );
        }
    }
}