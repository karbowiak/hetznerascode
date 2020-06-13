<?php

namespace Hac\Helpers;

use Hac\Bootstrap;
use InvalidArgumentException;
use Joli\JoliNotif\Notification;
use Joli\JoliNotif\NotifierFactory;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Command\Command as SymfonyConsole;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Command extends SymfonyConsole
{
    /**
     * Name that the command is called with including the parameters
     */
    protected string $signature = '';
    /**
     * Description of the command
     */
    protected string $description = '';
    /**
     * Is command hidden from list view?
     */
    public Bootstrap $container;
    protected bool $hidden = false;
    protected InputInterface $input;
    protected OutputInterface $output;

    public function __construct(Bootstrap $container, string $name = null)
    {
        parent::__construct($name);
        $name = $this->parseArguments();
        $this->setDescription($this->description);
        $this->setName($name);
        $this->setHidden($this->hidden);
        $this->container = $container;
    }

    /**
     * @return mixed
     */
    private function parseArguments()
    {
        $definition = $this->parse($this->signature);
        foreach ($definition[1] as $argument) {
            $this->getDefinition()->addArgument($argument);
        }
        foreach ($definition[2] as $option) {
            $this->getDefinition()->addOption($option);
        }

        return $definition[0];
    }

    /**
     * Parse the given console command definition into an array.
     *
     * @param string $expression
     *
     * @return array
     *
     * @throws \InvalidArgumentException
     */
    private function parse($expression): array
    {
        $name = $this->findName($expression);
        if (preg_match_all('/\{\s*(.*?)\s*\}/', $expression, $matches)) {
            if (count($matches[1])) {
                return array_merge([$name], $this->parameters($matches[1]));
            }
        }

        return [$name, [], []];
    }

    /**
     * Extract the name of the command from the expression.
     *
     * @param string $expression
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    private function findName($expression): string
    {
        if (!preg_match('/\S+/', $expression, $matches)) {
            throw new InvalidArgumentException('Unable to determine command name from signature.');
        }

        return $matches[0];
    }

    /**
     * Extract all of the parameters from the tokens.
     *
     * @param array $tokens
     *
     * @return array
     */
    private function parameters(array $tokens): array
    {
        $arguments = [];
        $options = [];
        foreach ($tokens as $token) {
            if (preg_match('/-{2,}(.*)/', $token, $matches)) {
                $options[] = $this->parseOption($matches[1]);
            } else {
                $arguments[] = $this->parseArgument($token);
            }
        }

        return [$arguments, $options];
    }

    /**
     * Parse an option expression.
     *
     * @param string $token
     *
     * @return \Symfony\Component\Console\Input\InputOption
     */
    private function parseOption($token): ?InputOption
    {
        [$token, $description] = $this->extractDescription($token);
        $matches = preg_split('/\s*\|\s*/', $token, 2);
        if (isset($matches[1])) {
            $shortcut = $matches[0];
            $token = $matches[1];
        } else {
            $shortcut = null;
        }

        switch (true) {
            case $this->endsWith($token, '='):
                $inputOption = new InputOption(trim($token, '='), $shortcut, InputOption::VALUE_OPTIONAL, $description);
                break;
            case $this->endsWith($token, '=*'):
                $inputOption = new InputOption(
                    trim($token, '=*'),
                    $shortcut,
                    InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                    $description
                );
                break;
            case preg_match('/(.+)\=\*(.+)/', $token, $matches):
                $inputOption = new InputOption(
                    $matches[1],
                    $shortcut,
                    InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                    $description,
                    preg_split('/,\s?/', $matches[2])
                );
                break;
            case preg_match('/(.+)\=(.+)/', $token, $matches):
                $inputOption = new InputOption(
                    $matches[1],
                    $shortcut,
                    InputOption::VALUE_OPTIONAL,
                    $description,
                    $matches[2]
                );
                break;
            default:
                $inputOption = new InputOption($token, $shortcut, InputOption::VALUE_NONE, $description);
                break;
        }

        return $inputOption;
    }

    /**
     * Parse the token into its token and description segments.
     *
     * @param string $token
     *
     * @return array
     */
    private function extractDescription($token): array
    {
        $parts = preg_split('/\s+:\s+/', trim($token), 2);

        return count($parts) === 2 ? $parts : [$token, ''];
    }

    /**
     * @param string $input
     * @param string $element
     *
     * @return bool
     */
    private function endsWith(string $input, string $element): bool
    {
        $length = strlen($element);
        if ($length === 0) {
            return true;
        }

        return (substr($input, -$length) === $element);
    }

    /**
     * Parse an argument expression.
     *
     * @param string $token
     *
     * @return \Symfony\Component\Console\Input\InputArgument
     */
    private function parseArgument($token): ?InputArgument
    {
        [$token, $description] = $this->extractDescription($token);
        switch (true) {
            case $this->endsWith($token, '?*'):
                $inputArgument = new InputArgument(trim($token, '?*'), InputArgument::IS_ARRAY, $description);
                break;
            case $this->endsWith($token, '*'):
                $inputArgument = new InputArgument(
                    trim($token, '*'),
                    InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                    $description
                );
                break;
            case $this->endsWith($token, '?'):
                $inputArgument = new InputArgument(trim($token, '?'), InputArgument::OPTIONAL, $description);
                break;
            case preg_match('/(.+)\=\*(.+)/', $token, $matches):
                $inputArgument = new InputArgument(
                    $matches[1],
                    InputArgument::IS_ARRAY,
                    $description,
                    preg_split('/,\s?/', $matches[2])
                );
                break;
            case preg_match('/(.+)\=(.+)/', $token, $matches):
                $inputArgument = new InputArgument($matches[1], InputArgument::OPTIONAL, $description, $matches[2]);
                break;
            default:
                $inputArgument = new InputArgument($token, InputArgument::REQUIRED, $description);
                break;
        }

        return $inputArgument;
    }

    /**
     * @param $name
     *
     * @return bool|string|string[]|null
     */
    public function __get($name)
    {
        if ($this->input->hasArgument($name)) {
            return $this->input->getArgument($name);
        }
        if ($this->input->hasOption($name)) {
            return $this->input->getOption($name);
        }
        return null;
    }

    /**
     * @param string $input
     * @param array $options
     * @param bool $newLine
     *
     * @return mixed
     */
    public function out(string $input, $newLine = true)
    {
        return $this->output->write($input, $newLine);
    }

    /**
     * This one returns what the user inputs
     *
     * @param string $question
     * @param mixed $default
     *
     * @return bool
     */
    public function ask(string $question, $default = '') {
        $helper = $this->getHelper('question');
        $q = new Question($question . ' ', $default);

        return $helper->ask($this->input, $this->output, $q);
    }

    /**
     * This one is usually asked for Y/n questions
     *
     * @param $question
     * @param $default
     *
     * @return bool
     */
    public function askWithConfirmation(string $question, bool $default = true): bool {
        $helper = $this->getHelper('question');
        $q = new ConfirmationQuestion($question . ' ', $default);

        if (!$helper->ask($this->input, $this->output, $q)) {
            return false;
        }

        return true;
    }

    /**
     * Ask a question consisting of multiple answers
     *
     * @param string $question
     * @param array  $options
     * @param int    $default
     * @param bool   $multipleChoice
     *
     * @return mixed
     */
    public function askWithOptions(string $question, array $options, string $default = '0', bool $multipleChoice = false) {
        $helper = $this->getHelper('question');
        $q = new ChoiceQuestion($question . ' ', $options, $default);

        if($multipleChoice) {
            $q->setMultiSelect(true);
        }

        return $helper->ask($this->input, $this->output, $q);
    }

    /**
     * @param string $title
     * @param string $body
     *
     * @return bool
     */
    public function notify(string $title, string $body): bool
    {
        $notifier = NotifierFactory::create();
        $notification = (new Notification())
            ->setTitle($title)
            ->setBody($body);

        return $notifier->send($notification);
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        try {
            $this->handle();
            return 0;
        } catch (\Exception $e) {
            return $e->getCode();
        }
    }

    /**
     *
     */
    public function handle(): void
    {
    }
}
