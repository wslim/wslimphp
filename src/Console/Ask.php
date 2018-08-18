<?php
namespace Wslim\Console;

use Wslim\Console\Question\Choice;
use Wslim\Console\Question\Confirmation;

class Ask
{
    private static $stty;

    private static $shell;

    /** @var  Request */
    protected $request;

    /** @var  Response */
    protected $response;

    /** @var  Question */
    protected $question;

    public function __construct(Request $request, Response $response, Question $question)
    {
        $this->request    = $request;
        $this->response   = $response;
        $this->question = $question;
    }

    public function run()
    {
        if (!$this->request->isInteractive()) {
            return $this->question->getDefault();
        }

        if (!$this->question->getValidator()) {
            return $this->doAsk();
        }

        $that = $this;

        $interviewer = function () use ($that) {
            return $that->doAsk();
        };

        return $this->validateAttempts($interviewer);
    }

    protected function doAsk()
    {
        $this->writePrompt();

        $inputStream  = STDIN;
        $autocomplete = $this->question->getAutocompleterValues();

        if (null === $autocomplete || !$this->hasSttyAvailable()) {
            $ret = false;
            if ($this->question->isHidden()) {
                try {
                    $ret = trim($this->getHiddenResponse($inputStream));
                } catch (\RuntimeException $e) {
                    if (!$this->question->isHiddenFallback()) {
                        throw $e;
                    }
                }
            }

            if (false === $ret) {
                $ret = fgets($inputStream, 4096);
                if (false === $ret) {
                    throw new \RuntimeException('Aborted');
                }
                $ret = trim($ret);
            }
        } else {
            $ret = trim($this->autocomplete($inputStream));
        }

        $ret = strlen($ret) > 0 ? $ret : $this->question->getDefault();

        if ($normalizer = $this->question->getNormalizer()) {
            return $normalizer($ret);
        }

        return $ret;
    }

    private function autocomplete($inputStream)
    {
        $autocomplete = $this->question->getAutocompleterValues();
        $ret          = '';

        $i          = 0;
        $ofs        = -1;
        $matches    = $autocomplete;
        $numMatches = count($matches);

        $sttyMode = shell_exec('stty -g');

        shell_exec('stty -icanon -echo');

        while (!feof($inputStream)) {
            $c = fread($inputStream, 1);

            if ("\177" === $c) {
                if (0 === $numMatches && 0 !== $i) {
                    --$i;
                    $this->response->write("\033[1D");
                }

                if ($i === 0) {
                    $ofs        = -1;
                    $matches    = $autocomplete;
                    $numMatches = count($matches);
                } else {
                    $numMatches = 0;
                }

                $ret = substr($ret, 0, $i);
            } elseif ("\033" === $c) {
                $c .= fread($inputStream, 2);

                if (isset($c[2]) && ('A' === $c[2] || 'B' === $c[2])) {
                    if ('A' === $c[2] && -1 === $ofs) {
                        $ofs = 0;
                    }

                    if (0 === $numMatches) {
                        continue;
                    }

                    $ofs += ('A' === $c[2]) ? -1 : 1;
                    $ofs = ($numMatches + $ofs) % $numMatches;
                }
            } elseif (ord($c) < 32) {
                if ("\t" === $c || "\n" === $c) {
                    if ($numMatches > 0 && -1 !== $ofs) {
                        $ret = $matches[$ofs];
                        $this->response->write(substr($ret, $i));
                        $i = strlen($ret);
                    }

                    if ("\n" === $c) {
                        $this->response->write($c);
                        break;
                    }

                    $numMatches = 0;
                }

                continue;
            } else {
                $this->response->write($c);
                $ret .= $c;
                ++$i;

                $numMatches = 0;
                $ofs        = 0;

                foreach ($autocomplete as $value) {
                    if (0 === strpos($value, $ret) && $i !== strlen($value)) {
                        $matches[$numMatches++] = $value;
                    }
                }
            }

            $this->response->write("\033[K");

            if ($numMatches > 0 && -1 !== $ofs) {
                $this->response->write("\0337");
                $this->response->highlight(substr($matches[$ofs], $i));
                $this->response->write("\0338");
            }
        }

        shell_exec(sprintf('stty %s', $sttyMode));

        return $ret;
    }

    protected function getHiddenResponse($inputStream)
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $exe = __DIR__ . '/hiddeninput.exe';

            $value = rtrim(shell_exec($exe));
            $this->response->writeln('');

            if (isset($tmpExe)) {
                unlink($tmpExe);
            }

            return $value;
        }

        if ($this->hasSttyAvailable()) {
            $sttyMode = shell_exec('stty -g');

            shell_exec('stty -echo');
            $value = fgets($inputStream, 4096);
            shell_exec(sprintf('stty %s', $sttyMode));

            if (false === $value) {
                throw new \RuntimeException('Aborted');
            }

            $value = trim($value);
            $this->response->writeln('');

            return $value;
        }

        if (false !== $shell = $this->getShell()) {
            $readCmd = $shell === 'csh' ? 'set mypassword = $<' : 'read -r mypassword';
            $controller = sprintf("/usr/bin/env %s -c 'stty -echo; %s; stty echo; echo \$mypassword'", $shell, $readCmd);
            $value   = rtrim(shell_exec($controller));
            $this->response->writeln('');

            return $value;
        }

        throw new \RuntimeException('Unable to hide the response.');
    }

    protected function validateAttempts($interviewer)
    {
        /** @var \Exception $error */
        $error    = null;
        $attempts = $this->question->getMaxAttempts();
        while (null === $attempts || $attempts--) {
            if (null !== $error) {
                $this->response->error($error->getMessage());
            }

            try {
                return call_user_func($this->question->getValidator(), $interviewer());
            } catch (\Exception $error) {
            }
        }

        throw $error;
    }

    /**
     * 显示问题的提示信息
     */
    protected function writePrompt()
    {
        $text    = $this->question->getQuestion();
        $default = $this->question->getDefault();

        switch (true) {
            case null === $default:
                $text = sprintf(' <info>%s</info>:', $text);

                break;

            case $this->question instanceof Confirmation:
                $text = sprintf(' <info>%s (yes/no)</info> [<comment>%s</comment>]:', $text, $default ? 'yes' : 'no');

                break;

            case $this->question instanceof Choice && $this->question->isMultiselect():
                $choices = $this->question->getChoices();
                $default = explode(',', $default);

                foreach ($default as $key => $value) {
                    $default[$key] = $choices[trim($value)];
                }

                $text = sprintf(' <info>%s</info> [<comment>%s</comment>]:', $text, implode(', ', $default));

                break;

            case $this->question instanceof Choice:
                $choices = $this->question->getChoices();
                $text    = sprintf(' <info>%s</info> [<comment>%s</comment>]:', $text, $choices[$default]);

                break;

            default:
                $text = sprintf(' <info>%s</info> [<comment>%s</comment>]:', $text, $default);
        }

        $this->response->writeln($text);

        if ($this->question instanceof Choice) {
            $width = max(array_map('strlen', array_keys($this->question->getChoices())));

            foreach ($this->question->getChoices() as $key => $value) {
                $this->response->writeln(sprintf("  [<comment>%-${width}s</comment>] %s", $key, $value));
            }
        }

        $this->response->write(' > ');
    }

    private function getShell()
    {
        if (null !== self::$shell) {
            return self::$shell;
        }

        self::$shell = false;

        if (file_exists('/usr/bin/env')) {
            $test = "/usr/bin/env %s -c 'echo OK' 2> /dev/null";
            foreach (['bash', 'zsh', 'ksh', 'csh'] as $sh) {
                if ('OK' === rtrim(shell_exec(sprintf($test, $sh)))) {
                    self::$shell = $sh;
                    break;
                }
            }
        }

        return self::$shell;
    }

    private function hasSttyAvailable()
    {
        if (null !== self::$stty) {
            return self::$stty;
        }

        exec('stty 2>&1', $response, $exitcode);

        return self::$stty = $exitcode === 0;
    }
}
