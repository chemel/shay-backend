<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Entity\User;

#[AsCommand(
    name: 'app:user-create',
    description: 'Create an user',
)]
class UserCreateCommand extends Command
{
    private $em;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher)
    {
        $this->em = $em;
        $this->passwordHasher = $passwordHasher;

        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Create the user question
        $usernameQuestion = new Question('Please enter an username', 'admin');
        $username = $io->askQuestion($usernameQuestion);

        // Create the password question
        $passwordQuestion = $this->createPasswordQuestion();
        $plaintextPassword = $io->askQuestion($passwordQuestion);

        // Create the User
        $user = new User();
        $user->setUsername($username);

        // Hash the password
        $hashedPassword = $this->passwordHasher->hashPassword(
            $user,
            $plaintextPassword
        );
        $user->setPassword($hashedPassword);

        // Save the User
        $this->em->persist($user);
        $this->em->flush();

        $io->success('The user has been created !');

        return Command::SUCCESS;
    }

    /**
     * Create the password question
     */
    private function createPasswordQuestion(): Question
    {
        $passwordQuestion = new Question('Please enter a password', 'admin');

        return $passwordQuestion->setValidator(function ($value) {
            if ('' === trim($value)) {
                throw new InvalidArgumentException('The password must not be empty.');
            }

            return $value;
        })->setHidden(true)->setMaxAttempts(20);
    }
}
