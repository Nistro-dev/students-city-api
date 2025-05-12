<?php
namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        $u1 = new User();
        $u1->setUsername('pending');
        $u1->setEmail('pending@example.com');
        $u1->setPassword($this->hasher->hashPassword($u1, 'pass1'));
        $u1->setRoles(['ROLE_USER']);
        $u1->setStatus('en_attente');
        $u1->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($u1);

        $u2 = new User();
        $u2->setUsername('active');
        $u2->setEmail('active@example.com');
        $u2->setPassword($this->hasher->hashPassword($u2, 'pass2'));
        $u2->setRoles(['ROLE_USER']);
        $u2->setStatus('active');
        $u2->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($u2);

        $admin = new User();
        $admin->setUsername('admin');
        $admin->setEmail('admin@example.com');
        $admin->setPassword($this->hasher->hashPassword($admin, 'adminpass'));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setStatus('active');
        $admin->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($admin);

        $manager->flush();
    }
}