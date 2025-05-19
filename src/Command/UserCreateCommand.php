<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Command to create a new user in the system.
 *
 * This command provides an interactive way to create a new user
 * by prompting for username and password.
 */
#[AsCommand(
    name: 'app:user-create',
    description: 'Create an user',
)]
class UserCreateCommand extends Command
{
    /**
     * Command constructor.
     *
     * @param EntityManagerInterface      $em             The Doctrine entity manager
     * @param UserPasswordHasherInterface $passwordHasher The password hasher service
     */
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    /**
     * Configures the command
     * This command doesn't require any additional configuration.
     */
    protected function configure(): void
    {
    }

    /**
     * Executes the command.
     *
     * Prompts for username and password, creates a new user,
     * hashes the password and saves the user to the database
     *
     * @param InputInterface  $input  The command input
     * @param OutputInterface $output The command output
     *
     * @return int Command exit code
     */
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
     * Creates a password question with validation.
     *
     * Creates an interactive question for password input with the following features:
     * - Hidden input (password is not displayed)
     * - Validation to ensure password is not empty
     * - Maximum 20 attempts
     *
     * @return Question The configured password question
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
