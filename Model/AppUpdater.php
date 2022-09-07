<?php

namespace Musicjerm\Bundle\JermBundle\Model;

use Symfony\Component\Process\Process;

class AppUpdater
{
    private string $projectDir;
    private ?string $gitUser;
    private ?string $gitPass;
    private ?string $gitRepo;
    public int $commitsAvailable = 0;
    public ?string $message = null;

    public function __construct(string $projectDir, ?string $gitUser, ?string $gitPass, ?string $gitRepo)
    {
        $this->projectDir = $projectDir;
        $this->gitUser = $gitUser;
        $this->gitPass = $gitPass;
        $this->gitRepo = $gitRepo;
    }

    public function fetchRemote(): bool
    {
        // ensure user and pass set in git config file

        // create and run new process
        $process = Process::fromShellCommandline("git -C $this->projectDir fetch");
        $process->run();

        // check if process worked
        if ($process->isSuccessful()){
            return true;
        }

        return false;
    }

    public function checkUpdates(): bool
    {
        // create and run new process
        $process = Process::fromShellCommandline("git -C $this->projectDir rev-list --count origin/master...master");
        $process->run();

        // check if process worked
        if ($process->isSuccessful()){
            $this->commitsAvailable = $process->getOutput();
            return true;
        }

        // set message and commits available to 0
        $this->commitsAvailable = 0;
        $this->message = 'Unable to check for updates.  ' . $process->getErrorOutput();
        return false;
    }

    public function getConfiguredUrl(): string
    {
        return "https://$this->gitUser:$this->gitPass@$this->gitRepo";
    }

    public function getConfig(): array
    {
        // create and run new process
        $process = Process::fromShellCommandline("git -C $this->projectDir config --list");
        $process->run();

        // set options array
        $options = array();

        // check if process worked, return string
        if ($process->isSuccessful()){
            foreach (explode("\n", $process->getOutput()) as $line){
                // handle string (setting=value)
                $exploded = explode('=', $line, 2);
                \count(explode('=', $line, 2)) !== 2 ?: $options[$exploded[0]] = $exploded[1];
            }
        }

        // return options array
        return $options;
    }

    public function setGitOption(string $option, $value): void
    {
        // create and run new process
        $process = Process::fromShellCommandline("git -C $this->projectDir config $option $value");
        $process->run();

        // check if process worked, set output message
        if ($process->isSuccessful()){
            $this->message = $process->getOutput();
        }else{
            $this->message = $process->getErrorOutput();
        }
    }

    public function pullUpdates(): bool
    {
        // create and run new process
        $process = Process::fromShellCommandline("git -C $this->projectDir pull https://$this->gitUser:$this->gitPass@$this->gitRepo");
        $process->run();

        // check if process worked, set output message
        if ($process->isSuccessful()){
            $this->message = $process->getOutput();
            return true;
        }

        // return false if error
        $this->message = $process->getErrorOutput();
        return false;
    }

    public function composerUpdate(string $method): bool
    {
        // create and run new process
        $process = Process::fromShellCommandline("php /usr/local/bin/composer $method -d $this->projectDir");
        $process->run();

        // check if process worked, set output message
        if ($process->isSuccessful()){
            $this->message = $process->getOutput();
            return true;
        }

        // return false if error
        $this->message = $process->getErrorOutput();
        return false;
    }

    public function doctrineUpdate(string $method): bool
    {
        // create and run new process
        $process = Process::fromShellCommandline("php $this->projectDir/bin/console doctrine:schema:update $method");
        $process->run();

        // check if process worked, set output message
        if ($process->isSuccessful()){
            $this->message = $process->getOutput();
            return true;
        }

        // return false if error
        $this->message = $process->getErrorOutput();
        return false;
    }

    public function clearCache(): bool
    {
        // create and run new process
        $process = Process::fromShellCommandline("php $this->projectDir/bin/console cache:clear");
        $process->run();

        // check if process worked, set output message
        if ($process->isSuccessful()){
            $this->message = $process->getOutput();
            return true;
        }

        // return false if error
        $this->message = $process->getErrorOutput();
        return false;
    }
}