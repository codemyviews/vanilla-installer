<?php

namespace Vanilla\Installer\Console;

use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class NewCommand extends Command {
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
     * @param  \Symfony\Component\Console\Input\InputInterface $input
     * @param  \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $name = str_replace(['\\', '/'], '-', $name);

        $directory = getcwd() . '/' . $name;

        $this->verifyApplicationDoesntExist($directory);

        $output->writeln('<info>Crafting application...</info>');

        $composer = $this->findComposer();

        $commands = [
            ["git clone git@github.com:codemyviews/vanilla.git {$directory}", null],
            ["{$composer} install --no-scripts", $directory],
            ["{$composer} install --no-scripts", $directory],
            ["{$composer} install --no-scripts", "{$directory}/base-theme"],
            ["sed -ie 's/DummyThemeName/{$name}/g' style.css", "{$directory}/base-theme"],
            ["sed -ie \"s/'WP_DEBUG', false/'WP_DEBUG', true/g\" ./wp-config-sample.php", "{$directory}/wordpress"],
            ["rm -rf wordpress/wp-content/themes/*", $directory],
            ["cp base-theme/.env.example base-theme/.env", $directory],
            ["mv base-theme wordpress/wp-content/themes/{$name}", $directory],
            ["rm -rf .git", $directory],
            ["echo 'wp-config.php' > .gitignore", $directory],
            ["echo 'wp-content/uploads' >> .gitignore", $directory],
            ["echo '/vendor' >> .gitignore", $directory],
            ["echo '.env' >> .gitignore", $directory],
            ["npm install", "{$directory}/wordpress/wp-content/themes/{$name}"],
            ["npm run dev", "{$directory}/wordpress/wp-content/themes/{$name}"],
            ["mv wordpress/* {$directory}/", $directory],
            ["rm -rf wordpress", $directory]
        ];

        foreach ($commands as $arr) {
            list($command, $directory) = $arr;
            $process = new Process($command, $directory);

            if ($input->getOption('no-ansi')) {
                $commands = array_map(function ($value) {
                    return $value . ' --no-ansi';
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
     * @param  string $directory
     *
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
        if (file_exists(getcwd() . '/composer.phar')) {
            return '"' . PHP_BINARY . '" composer.phar';
        }

        return 'composer';
    }
}