<?php

namespace Vanilla\Installer\Console;

use RuntimeException;
use Symfony\Component\Process\Process;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NewCommand extends Command
{
    /**
     * Configure the command options.
     *
     * @return void
     */
    protected function configure()
    {
        $this
            ->setName('new')
            ->setDescription('Create a new wordpress installation with Vanilla theme.')
            ->addArgument('name', InputArgument::REQUIRED);
    }

    /**
     * Execute the command.
     *
     * @param  \Symfony\Component\Console\Input\InputInterface  $input
     * @param  \Symfony\Component\Console\Output\OutputInterface  $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $directory = getcwd().'/'.$name;

        $this->verifyApplicationDoesntExist($directory);

        $output->writeln('<info>Crafting application...</info>');

        $composer = $this->findComposer();

        $commands = [
            ["git clone git@github.com:codemyviews/vanilla.git {$directory}", null],
            ["{$composer} install --no-scripts", $directory],
            ["{$composer} install --no-scripts", "{$directory}/base-theme"],
            ["rm -rf wordpress/wp-content/themes/*", $directory],
            ["mv base-theme wordpress/wp-content/themes/{$name}", $directory],
            ["rm -rf .git", $directory]
        ];

        foreach ($commands as $arr) {
            list($command, $directory) = $arr;
            $process = new Process($command, $directory);

            if ($input->getOption('no-ansi')) {
                $commands = array_map(function ($value) {
                    return $value.' --no-ansi';
                }, $commands);
            }

            if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
                $process->setTty(true);
            }

            $process->run(function ($type, $line) use ($output) {
                $output->write($line);
            });
        }
        $output->writeln('<comment>Application ready! Build something amazing.</comment>');
    }

    /**
     * Verify that the application does not already exist.
     *
     * @param  string  $directory
     * @return void
     */
    protected function verifyApplicationDoesntExist($directory)
    {
        if ((is_dir($directory) || is_file($directory)) && $directory != getcwd()) {
            throw new RuntimeException('Application already exists!');
        }
    }

    /**
     * Get the composer command for the environment.
     *
     * @return string
     */
    protected function findComposer()
    {
        if (file_exists(getcwd().'/composer.phar')) {
            return '"'.PHP_BINARY.'" composer.phar';
        }

        return 'composer';
    }
}