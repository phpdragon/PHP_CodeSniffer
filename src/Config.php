<?php

namespace PHP_CodeSniffer;

use PHP_CodeSniffer\Exceptions\RuntimeException;

/**
 * A class to process command line phpcs scripts.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * A class to process command line phpcs scripts.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class Config
{

    /**
     * The current version.
     *
     * @var string
     */
    const VERSION = '3.0.0';

    /**
     * Package stability; either stable, beta or alpha.
     *
     * @var string
     */
    const STABILITY = 'alpha';

    public $files;
    public $standards;
    public $verbosity;
    public $interactive;
    public $colors;
    public $explain;
    public $local;
    public $showSources;
    public $showProgress;
    public $tabWidth;
    public $encoding;
    public $extensions;
    public $sniffs;
    public $ignored;
    public $reportFile;
    public $generator;
    public $reports;
    public $reportWidth;
    public $errorSeverity;
    public $warningSeverity;
    public $suffix;
    public $noPatch;
    public $stdin;

    /**
     * Whether or not to kill the process when an unknown command line arg is found.
     *
     * If FALSE, arguments that are not command line options or file/directory paths
     * will be ignored and execution will continue.
     *
     * @var bool
     */
    public $dieOnUnknownArg;

    /**
     * An array of the current command line arguments we are processing.
     *
     * @var array
     */
    private $cliArgs = array();

    private static $configData = null;
    private $overriddenDefaults = array();


    /**
     * Creates a CLI object.
     *
     * @param array $cliArgs An array of values gathered from CLI args.
     *
     * @return void
     */
    public function __construct(array $cliArgs=array(), $dieOnUnknownArg=true)
    {
        $this->dieOnUnknownArg = $dieOnUnknownArg;

        if (empty($cliArgs) === true) {
            $cliArgs = $_SERVER['argv'];
            array_shift($cliArgs);

            $this->restoreDefaults();
            $this->setCommandLineValues($cliArgs);
        } else {
            exit('not done');
            /*
            $values       = array_merge($this->getDefaults(), $values);
            $this->values = $values;
            */
        }

        // Support auto terminal width.
        if ($this->reportWidth === 'auto'
            && preg_match('|\d+ (\d+)|', shell_exec('stty size 2>&1'), $matches) === 1
        ) {
            $this->reportWidth = (int) $matches[1];
        } else {
            $this->reportWidth = (int) $this->reportWidth;
        }

        if (isset($this->overriddenDefaults['standards']) === false
            && empty($this->files) === true
            && Config::getConfigData('default_standard') === null
        ) {
            // They did not supply a standard to use.
            // Look for a ruleset in the current directory.
            $default = getcwd().DIRECTORY_SEPARATOR.'phpcs.xml';
            if (is_file($default) === true) {
                $this->standards = array($default);
            }
        }

        $cleaned = array();

        // Check if the standard name is valid, or if the case is invalid.
        $installedStandards = Util\Standards::getInstalledStandards();
        foreach ($this->standards as $standard) {
            foreach ($installedStandards as $validStandard) {
                if (strtolower($standard) === strtolower($validStandard)) {
                    $standard = $validStandard;
                    break;
                }
            }

            $cleaned[] = $standard;
        }

        $this->standards = $cleaned;

    }//end __construct()

    /**
     * Set the command line values.
     *
     * @param array $args An array of command line arguments to process.
     *
     * @return void
     */
    public function setCommandLineValues($args)
    {
        #if (defined('PHP_CODESNIFFER_IN_TESTS') === true) {
        #    $this->values = array();
        #} else if (empty($this->values) === true) {
            #$this->restoreDefaults();
        #}

        $this->cliArgs = $args;
        $numArgs        = count($args);

        for ($i = 0; $i < $numArgs; $i++) {
            $arg = $this->cliArgs[$i];
            if ($arg === '') {
                continue;
            }

            if ($arg{0} === '-') {
                if ($arg === '-') {
                    // Asking to read from STDIN.
                    $this->stdin = true;
                    $this->overriddenDefaults['stdin'] = true;
                    continue;
                }

                if ($arg === '--') {
                    // Empty argument, ignore it.
                    continue;
                }

                if ($arg{1} === '-') {
                    $this->processLongArgument(substr($arg, 2), $i);
                } else {
                    $switches = str_split($arg);
                    foreach ($switches as $switch) {
                        if ($switch === '-') {
                            continue;
                        }

                        $this->processShortArgument($switch, $i);
                    }
                }
            } else {
                $this->processUnknownArgument($arg, $i);
            }//end if
        }//end for

    }//end setCommandLineValues()

    /**
     * Get a list of default values for all possible command line arguments.
     *
     * @return array
     */
    public function restoreDefaults()
    {
        #if (defined('PHP_CODESNIFFER_IN_TESTS') === true) {
        #    return array();
        #}

        // The default values for config settings.
        $this->files = array();
        $this->standards = array('PEAR');
        $this->verbosity = 0;
        $this->interactive = false;
        $this->colors = false;
        $this->explain = false;
        $this->local = false;
        $this->showSources = false;
        $this->showProgress = false;
        $this->tabWidth = 0;
        $this->encoding = 'utf-8';
        $this->extensions = array(
                             'php' => 'PHP',
                             'inc' => 'PHP',
                             'js'  => 'JS',
                             'css' => 'CSS',
                            );
        $this->sniffs = array();
        $this->ignored = array();
        $this->reportFile = null;
        $this->generator = null;
        $this->reports = array('full' => null);
        $this->reportWidth = 'auto';
        $this->errorSeverity = 5;
        $this->warningSeverity = 5;
        $this->suffix = '';
        $this->noPatch = false;
        $this->stdin = false;

        $standard = self::getConfigData('default_standard');
        if ($standard !== null) {
            $this->standards = array($standard);
        }

        $reportFormat = self::getConfigData('report_format');
        if ($reportFormat !== null) {
            $this->reports = array($reportFormat => null);
        }

        $tabWidth = self::getConfigData('tab_width');
        if ($tabWidth !== null) {
            $this->tabWidth = (int) $tabWidth;
        }

        $encoding = self::getConfigData('encoding');
        if ($encoding !== null) {
            $this->encoding = strtolower($encoding);
        }

        $severity = self::getConfigData('severity');
        if ($severity !== null) {
            $this->errorSeverity    = (int) $severity;
            $this->warningSeverity  = (int) $severity;
        }

        $severity = self::getConfigData('error_severity');
        if ($severity !== null) {
            $this->errorSeverity = (int) $severity;
        }

        $severity = self::getConfigData('warning_severity');
        if ($severity !== null) {
            $this->warningSeverity = (int) $severity;
        }

        $showWarnings = self::getConfigData('show_warnings');
        if ($showWarnings !== null) {
            $showWarnings = (bool) $showWarnings;
            if ($showWarnings === false) {
                $this->warningSeverity = 0;
            }
        }

        $reportWidth = self::getConfigData('report_width');
        if ($reportWidth !== null) {
            $this->reportWidth = $reportWidth;
        }

        $showProgress = self::getConfigData('show_progress');
        if ($showProgress !== null) {
            $this->showProgress = (bool) $showProgress;
        }

        $colors = self::getConfigData('colors');
        if ($colors !== null) {
            $this->colors = (bool) $colors;
        }
/*
        if (PHP_CodeSniffer::isPharFile(dirname(dirname(__FILE__))) === true) {
            // If this is a phar file, check for the standard in the config.
            $standard = PHP_CodeSniffer::getConfigData('standard');
            if ($standard !== null) {
                $defaults['standard'] = $standard;
            }
        }
*/
    }//end getDefaults()


    /**
     * Processes a short (-e) command line argument.
     *
     * @param string $arg The command line argument.
     * @param int    $pos The position of the argument on the command line.
     *
     * @return void
     */
    public function processShortArgument($arg, $pos)
    {
        switch ($arg) {
        case 'h':
        case '?':
            $this->printUsage();
            exit(0);
            break;
        case 'i' :
            Util\Standards::printInstalledStandards();
            exit(0);
            break;
        case 'v' :
            $this->verbosity++;
            $this->overriddenDefaults['verbosity'] = true;
            break;
        case 'l' :
            $this->local = true;
            $this->overriddenDefaults['local'] = true;
            break;
        case 's' :
            $this->showSources = true;
            $this->overriddenDefaults['showSources'] = true;
            break;
        case 'a' :
            $this->interactive = true;
            $this->overriddenDefaults['interactive'] = true;
            break;
        case 'e':
            $this->explain = true;
            $this->overriddenDefaults['explain'] = true;
            break;
        case 'p' :
            $this->showProgress = true;
            $this->overriddenDefaults['showProgress'] = true;
            break;
        case 'd' :
            $ini = explode('=', $this->cliArgs[($pos + 1)]);
            $this->cliArgs[($pos + 1)] = '';
            if (isset($ini[1]) === true) {
                ini_set($ini[0], $ini[1]);
            } else {
                ini_set($ini[0], true);
            }
            break;
        case 'n' :
            $this->warningSeverity = 0;
            $this->overriddenDefaults['warningSeverity'] = true;
            break;
        case 'w' :
            $this->warningSeverity = null;
            $this->overriddenDefaults['warningSeverity'] = true;
            break;
        default:
            if ($this->dieOnUnknownArg === false) {
                $this->values[$arg] = $arg;
            } else {
                $this->processUnknownArgument('-'.$arg, $pos);
            }
        }//end switch

    }//end processShortArgument()


    /**
     * Processes a long (--example) command line argument.
     *
     * @param string $arg The command line argument.
     * @param int    $pos The position of the argument on the command line.
     *
     * @return void
     */
    public function processLongArgument($arg, $pos)
    {
        switch ($arg) {
        case 'help':
            $this->printUsage();
            exit(0);
        case 'version':
            echo 'PHP_CodeSniffer version '.self::VERSION.' ('.self::STABILITY.') ';
            echo 'by Squiz (http://www.squiz.net)'.PHP_EOL;
            exit(0);
        case 'colors':
            $this->colors = true;
            $this->overriddenDefaults['colors'] = true;
            break;
        case 'no-colors':
            $this->colors = false;
            $this->overriddenDefaults['colors'] = true;
            break;
        case 'config-set':
            if (isset($this->cliArgs[($pos + 1)]) === false
                || isset($this->cliArgs[($pos + 2)]) === false
            ) {
                echo 'ERROR: Setting a config option requires a name and value'.PHP_EOL.PHP_EOL;
                $this->printUsage();
                exit(0);
            }

            $key     = $this->cliArgs[($pos + 1)];
            $value   = $this->cliArgs[($pos + 2)];
            $current = self::getConfigData($key);

            try {
                $this->setConfigData($key, $value);
            } catch (Exception $e) {
                echo $e->getMessage().PHP_EOL;
                exit(2);
            }

            if ($current === null) {
                echo "Config value \"$key\" added successfully".PHP_EOL;
            } else {
                echo "Config value \"$key\" updated successfully; old value was \"$current\"".PHP_EOL;
            }
            exit(0);
        case 'config-delete':
            if (isset($this->cliArgs[($pos + 1)]) === false) {
                echo 'ERROR: Deleting a config option requires the name of the option'.PHP_EOL.PHP_EOL;
                $this->printUsage();
                exit(0);
            }

            $key     = $this->cliArgs[($pos + 1)];
            $current = self::getConfigData($key);
            if ($current === null) {
                echo "Config value \"$key\" has not been set".PHP_EOL;
            } else {
                try {
                    $this->setConfigData($key, null);
                } catch (Exception $e) {
                    echo $e->getMessage().PHP_EOL;
                    exit(2);
                }

                echo "Config value \"$key\" removed successfully; old value was \"$current\"".PHP_EOL;
            }
            exit(0);
        case 'config-show':
            $data = self::getAllConfigData();
            $this->printConfigData($data);
            exit(0);
        case 'runtime-set':
            if (isset($this->cliArgs[($pos + 1)]) === false
                || isset($this->cliArgs[($pos + 2)]) === false
            ) {
                echo 'ERROR: Setting a runtime config option requires a name and value'.PHP_EOL.PHP_EOL;
                $this->printUsage();
                exit(0);
            }

            $key   = $this->cliArgs[($pos + 1)];
            $value = $this->cliArgs[($pos + 2)];
            $this->cliArgs[($pos + 1)] = '';
            $this->cliArgs[($pos + 2)] = '';
            PHP_CodeSniffer::setConfigData($key, $value, true);
            break;
        case 'no-patch':
            $this->noPatch = true;
            $this->overriddenDefaults['noPatch'] = true;
            break;
        default:
            if (substr($arg, 0, 7) === 'sniffs=') {
                $sniffs = explode(',', substr($arg, 7));
                foreach ($sniffs as $sniff) {
                    if (substr_count($sniff, '.') !== 2) {
                        echo "ERROR: The specified sniff code \"$sniff\" is invalid".PHP_EOL.PHP_EOL;
                        $this->printUsage();
                        exit(2);
                    }
                }

                $this->sniffs = $sniffs;
                $this->overriddenDefaults['sniffs'] = true;
            } else if (substr($arg, 0, 12) === 'report-file=') {
                $this->reportFile = PHP_CodeSniffer::realpath(substr($arg, 12));

                // It may not exist and return false instead.
                if ($this->reportFile === false) {
                    $this->reportFile = substr($arg, 12);
                }

                $this->overriddenDefaults['reportFile'] = true;

                if (is_dir($this->reportFile) === true) {
                    echo 'ERROR: The specified report file path "'.$this->reportFile.'" is a directory'.PHP_EOL.PHP_EOL;
                    $this->printUsage();
                    exit(2);
                }

                $dir = dirname($this->reportFile);
                if (is_dir($dir) === false) {
                    echo 'ERROR: The specified report file path "'.$this->reportFile.'" points to a non-existent directory'.PHP_EOL.PHP_EOL;
                    $this->printUsage();
                    exit(2);
                }

                if ($dir === '.') {
                    // Passed report file is a filename in the current directory.
                    $this->reportFile = getcwd().'/'.basename($this->reportFile);
                } else {
                    $dir = PHP_CodeSniffer::realpath(getcwd().'/'.$dir);
                    if ($dir !== false) {
                        // Report file path is relative.
                        $this->reportFile = $dir.'/'.basename($this->reportFile);
                    }
                }
            } else if (substr($arg, 0, 13) === 'report-width=') {
                $this->reportWidth = substr($arg, 13);
                $this->overriddenDefaults['reportWidth'] = true;
            } else if (substr($arg, 0, 7) === 'report='
                || substr($arg, 0, 7) === 'report-'
            ) {
                if ($arg[6] === '-') {
                    // This is a report with file output.
                    $split = strpos($arg, '=');
                    if ($split === false) {
                        $report = substr($arg, 7);
                        $output = null;
                    } else {
                        $report = substr($arg, 7, ($split - 7));
                        $output = substr($arg, ($split + 1));
                        if ($output === false) {
                            $output = null;
                        } else {
                            $dir = dirname($output);
                            if ($dir === '.') {
                                // Passed report file is a filename in the current directory.
                                $output = getcwd().'/'.basename($output);
                            } else {
                                $dir = PHP_CodeSniffer::realpath(getcwd().'/'.$dir);
                                if ($dir !== false) {
                                    // Report file path is relative.
                                    $output = $dir.'/'.basename($output);
                                }
                            }
                        }//end if
                    }//end if
                } else {
                    // This is a single report.
                    $report = substr($arg, 7);
                    $output = null;
                }//end if

                // Remove the default value so the CLI value overrides it.
                if (isset($this->overriddenDefaults['reports']) === false) {
                    $this->reports = array();
                }

                $this->reports[$report] = $output;
                $this->overriddenDefaults['reports'] = true;
            } else if (substr($arg, 0, 9) === 'standard=') {
                $standards = trim(substr($arg, 9));
                if ($standards !== '') {
                    $this->standards = explode(',', $standards);
                }

                $this->overriddenDefaults['standards'] = true;
            } else if (substr($arg, 0, 11) === 'extensions=') {
                $extensions    = explode(',', substr($arg, 11));
                $newExtensions = array();
                foreach ($extensions as $ext) {
                    $slash = strpos($ext, '/');
                    if ($slash !== false) {
                        // They specified the tokenizer too.
                        list($ext, $tokenizer) = explode('/', $ext);
                        $newExtensions[$ext]   = strtoupper($tokenizer);
                        continue;
                    }

                    if (isset($this->extensions[$ext]) === true) {
                        $newExtensions[$ext] = $this->extensions[$ext];
                    } else {
                        $newExtensions[$ext] = 'PHP';
                    }
                }

                $this->extensions = $newExtensions;
                $this->overriddenDefaults['extensions'] = true;
            } else if (substr($arg, 0, 7) === 'suffix=') {
                $this->suffix = explode(',', substr($arg, 7));
                $this->overriddenDefaults['suffix'] = true;
            } else if (substr($arg, 0, 9) === 'severity=') {
                $this->errorSeverity   = (int) substr($arg, 9);
                $this->warningSeverity = $this->errorSeverity;
                $this->overriddenDefaults['errorSeverity'] = true;
                $this->overriddenDefaults['warningSeverity'] = true;
            } else if (substr($arg, 0, 15) === 'error-severity=') {
                $this->errorSeverity = (int) substr($arg, 15);
                $this->overriddenDefaults['errorSeverity'] = true;
            } else if (substr($arg, 0, 17) === 'warning-severity=') {
                $this->warningSeverity = (int) substr($arg, 17);
                $this->overriddenDefaults['warningSeverity'] = true;
            } else if (substr($arg, 0, 7) === 'ignore=') {
                // Split the ignore string on commas, unless the comma is escaped
                // using 1 or 3 slashes (\, or \\\,).
                $ignored = preg_split(
                    '/(?<=(?<!\\\\)\\\\\\\\),|(?<!\\\\),/',
                    substr($arg, 7)
                );
                foreach ($ignored as $pattern) {
                    $pattern = trim($pattern);
                    if ($pattern === '') {
                        continue;
                    }

                    $this->ignored[$pattern] = 'absolute';
                }

                $this->overriddenDefaults['ignored'] = true;
            } else if (substr($arg, 0, 10) === 'generator=') {
                $this->generator = substr($arg, 10);
                $this->overriddenDefaults['generator'] = true;
            } else if (substr($arg, 0, 9) === 'encoding=') {
                $this->encoding = strtolower(substr($arg, 9));
                $this->overriddenDefaults['encoding'] = true;
            } else if (substr($arg, 0, 10) === 'tab-width=') {
                $this->tabWidth = (int) substr($arg, 10);
                $this->overriddenDefaults['tabWidth'] = true;
            } else {
                if ($this->dieOnUnknownArg === false) {
                    $eqPos = strpos($arg, '=');
                    if ($eqPos === false) {
                        $this->values[$arg] = $arg;
                    } else {
                        $value = substr($arg, ($eqPos + 1));
                        $arg   = substr($arg, 0, $eqPos);
                        $this->values[$arg] = $value;
                    }
                } else {
                    $this->processUnknownArgument('--'.$arg, $pos);
                }
            }//end if

            break;
        }//end switch

    }//end processLongArgument()


    /**
     * Processes an unknown command line argument.
     *
     * Assumes all unknown arguments are files and folders to check.
     *
     * @param string $arg The command line argument.
     * @param int    $pos The position of the argument on the command line.
     *
     * @return void
     */
    public function processUnknownArgument($arg, $pos)
    {
        // We don't know about any additional switches; just files.
        if ($arg{0} === '-') {
            if ($this->dieOnUnknownArg === false) {
                return;
            }

            echo "ERROR: option \"$arg\" not known".PHP_EOL.PHP_EOL;
            $this->printUsage();
            exit(2);
        }

        $file = Util\Common::realpath($arg);
        if (file_exists($file) === false) {
            if ($this->dieOnUnknownArg === false) {
                return;
            }

            echo 'ERROR: The file "'.$arg.'" does not exist.'.PHP_EOL.PHP_EOL;
            $this->printUsage();
            exit(2);
        } else {
            $this->files[] = $file;
            $this->overriddenDefaults['files'] = true;
        }

    }//end processUnknownArgument()

    /**
     * Prints out the usage information for this script.
     *
     * @return void
     */
    public function printUsage()
    {
        if (PHP_CODESNIFFER_CBF === true) {
            $this->printPHPCBFUsage();
        } else {
            $this->printPHPCSUsage();
        }

    }//end printUsage()


    /**
     * Prints out the usage information for PHPCS.
     *
     * @return void
     */
    public function printPHPCSUsage()
    {
        echo 'Usage: phpcs [-nwlsaepvi] [-d key[=value]] [--colors] [--no-colors]'.PHP_EOL;
        echo '    [--report=<report>] [--report-file=<reportFile>] [--report-<report>=<reportFile>] ...'.PHP_EOL;
        echo '    [--report-width=<reportWidth>] [--generator=<generator>] [--tab-width=<tabWidth>]'.PHP_EOL;
        echo '    [--severity=<severity>] [--error-severity=<severity>] [--warning-severity=<severity>]'.PHP_EOL;
        echo '    [--runtime-set key value] [--config-set key value] [--config-delete key] [--config-show]'.PHP_EOL;
        echo '    [--standard=<standard>] [--sniffs=<sniffs>] [--encoding=<encoding>]'.PHP_EOL;
        echo '    [--extensions=<extensions>] [--ignore=<patterns>] <file> - ...'.PHP_EOL;
        echo '        -             Check STDIN instead of local files and directories'.PHP_EOL;
        echo '        -n            Do not print warnings (shortcut for --warning-severity=0)'.PHP_EOL;
        echo '        -w            Print both warnings and errors (this is the default)'.PHP_EOL;
        echo '        -l            Local directory only, no recursion'.PHP_EOL;
        echo '        -s            Show sniff codes in all reports'.PHP_EOL;
        echo '        -a            Run interactively'.PHP_EOL;
        echo '        -e            Explain a standard by showing the sniffs it includes'.PHP_EOL;
        echo '        -p            Show progress of the run'.PHP_EOL;
        echo '        -v[v][v]      Print verbose output'.PHP_EOL;
        echo '        -i            Show a list of installed coding standards'.PHP_EOL;
        echo '        -d            Set the [key] php.ini value to [value] or [true] if value is omitted'.PHP_EOL;
        echo '        --help        Print this help message'.PHP_EOL;
        echo '        --version     Print version information'.PHP_EOL;
        echo '        --colors      Use colors in output'.PHP_EOL;
        echo '        --no-colors   Do not use colors in output (this is the default)'.PHP_EOL;
        echo '        <file>        One or more files and/or directories to check'.PHP_EOL;
        echo '        <encoding>    The encoding of the files being checked (default is iso-8859-1)'.PHP_EOL;
        echo '        <extensions>  A comma separated list of file extensions to check'.PHP_EOL;
        echo '                      (extension filtering only valid when checking a directory)'.PHP_EOL;
        echo '                      The type of the file can be specified using: ext/type'.PHP_EOL;
        echo '                      e.g., module/php,es/js'.PHP_EOL;
        echo '        <generator>   The name of a doc generator to use'.PHP_EOL;
        echo '                      (forces doc generation instead of checking)'.PHP_EOL;
        echo '        <patterns>    A comma separated list of patterns to ignore files and directories'.PHP_EOL;
        echo '        <report>      Print either the "full", "xml", "checkstyle", "csv"'.PHP_EOL;
        echo '                      "json", "emacs", "source", "summary", "diff"'.PHP_EOL;
        echo '                      "svnblame", "gitblame", "hgblame" or "notifysend" report'.PHP_EOL;
        echo '                      (the "full" report is printed by default)'.PHP_EOL;
        echo '        <reportFile>  Write the report to the specified file path'.PHP_EOL;
        echo '        <reportWidth> How many columns wide screen reports should be printed'.PHP_EOL;
        echo '                      or set to "auto" to use current screen width, where supported'.PHP_EOL;
        echo '        <sniffs>      A comma separated list of sniff codes to limit the check to'.PHP_EOL;
        echo '                      (all sniffs must be part of the specified standard)'.PHP_EOL;
        echo '        <severity>    The minimum severity required to display an error or warning'.PHP_EOL;
        echo '        <standard>    The name or path of the coding standard to use'.PHP_EOL;
        echo '        <tabWidth>    The number of spaces each tab represents'.PHP_EOL;

    }//end printPHPCSUsage()


    /**
     * Prints out the usage information for PHPCBF.
     *
     * @return void
     */
    public function printPHPCBFUsage()
    {
        echo 'Usage: phpcbf [-nwli] [-d key[=value]]'.PHP_EOL;
        echo '    [--standard=<standard>] [--sniffs=<sniffs>] [--suffix=<suffix>]'.PHP_EOL;
        echo '    [--severity=<severity>] [--error-severity=<severity>] [--warning-severity=<severity>]'.PHP_EOL;
        echo '    [--tab-width=<tabWidth>] [--encoding=<encoding>]'.PHP_EOL;
        echo '    [--extensions=<extensions>] [--ignore=<patterns>] <file> - ...'.PHP_EOL;
        echo '        -             Fix STDIN instead of local files and directories'.PHP_EOL;
        echo '        -n            Do not fix warnings (shortcut for --warning-severity=0)'.PHP_EOL;
        echo '        -w            Fix both warnings and errors (on by default)'.PHP_EOL;
        echo '        -l            Local directory only, no recursion'.PHP_EOL;
        echo '        -i            Show a list of installed coding standards'.PHP_EOL;
        echo '        -d            Set the [key] php.ini value to [value] or [true] if value is omitted'.PHP_EOL;
        echo '        --help        Print this help message'.PHP_EOL;
        echo '        --version     Print version information'.PHP_EOL;
        echo '        --no-patch    Do not make use of the "diff" or "patch" programs'.PHP_EOL;
        echo '        <file>        One or more files and/or directories to fix'.PHP_EOL;
        echo '        <encoding>    The encoding of the files being fixed (default is iso-8859-1)'.PHP_EOL;
        echo '        <extensions>  A comma separated list of file extensions to fix'.PHP_EOL;
        echo '                      (extension filtering only valid when checking a directory)'.PHP_EOL;
        echo '                      The type of the file can be specified using: ext/type'.PHP_EOL;
        echo '                      e.g., module/php,es/js'.PHP_EOL;
        echo '        <patterns>    A comma separated list of patterns to ignore files and directories'.PHP_EOL;
        echo '        <sniffs>      A comma separated list of sniff codes to limit the fixes to'.PHP_EOL;
        echo '                      (all sniffs must be part of the specified standard)'.PHP_EOL;
        echo '        <severity>    The minimum severity required to fix an error or warning'.PHP_EOL;
        echo '        <standard>    The name or path of the coding standard to use'.PHP_EOL;
        echo '        <suffix>      Write modified files to a filename using this suffix'.PHP_EOL;
        echo '                      ("diff" and "patch" are not used in this mode)'.PHP_EOL;
        echo '        <tabWidth>    The number of spaces each tab represents'.PHP_EOL;

    }//end printPHPCBFUsage()


    /**
     * Get a single config value.
     *
     * Config data is stored in the data dir, in a file called
     * CodeSniffer.conf. It is a simple PHP array.
     *
     * @param string $key The name of the config value.
     *
     * @return string|null
     * @see    setConfigData()
     * @see    getAllConfigData()
     */
    public static function getConfigData($key)
    {
        $phpCodeSnifferConfig = self::getAllConfigData();

        if ($phpCodeSnifferConfig === null) {
            return null;
        }

        if (isset($phpCodeSnifferConfig[$key]) === false) {
            return null;
        }

        return $phpCodeSnifferConfig[$key];

    }//end getConfigData()


    /**
     * Set a single config value.
     *
     * Config data is stored in the data dir, in a file called
     * CodeSniffer.conf. It is a simple PHP array.
     *
     * @param string      $key   The name of the config value.
     * @param string|null $value The value to set. If null, the config
     *                           entry is deleted, reverting it to the
     *                           default value.
     * @param boolean     $temp  Set this config data temporarily for this
     *                           script run. This will not write the config
     *                           data to the config file.
     *
     * @return boolean
     * @see    getConfigData()
     * @throws PHP_CodeSniffer_Exception If the config file can not be written.
     */
    public static function setConfigData($key, $value, $temp=false)
    {
        if ($temp === false) {
            $configFile = dirname(__FILE__).'/../CodeSniffer.conf';
            if (is_file($configFile) === false
                && strpos('@data_dir@', '@data_dir') === false
            ) {
                // If data_dir was replaced, this is a PEAR install and we can
                // use the PEAR data dir to store the conf file.
                $configFile = '@data_dir@/PHP_CodeSniffer/CodeSniffer.conf';
            }

            if (is_file($configFile) === true
                && is_writable($configFile) === false
            ) {
                $error = 'Config file '.$configFile.' is not writable';
                throw new RuntimeException($error);
            }
        }

        $phpCodeSnifferConfig = self::getAllConfigData();

        if ($value === null) {
            if (isset($phpCodeSnifferConfig[$key]) === true) {
                unset($phpCodeSnifferConfig[$key]);
            }
        } else {
            $phpCodeSnifferConfig[$key] = $value;
        }

        if ($temp === false) {
            $output  = '<'.'?php'."\n".' $phpCodeSnifferConfig = ';
            $output .= var_export($phpCodeSnifferConfig, true);
            $output .= "\n?".'>';

            if (file_put_contents($configFile, $output) === false) {
                return false;
            }
        }

        self::$configData = $phpCodeSnifferConfig;

        return true;

    }//end setConfigData()


    /**
     * Get all config data in an array.
     *
     * @return array<string, string>
     * @see    getConfigData()
     */
    public static function getAllConfigData()
    {
        if (self::$configData !== null) {
            return self::$configData;
        }

        $configFile = dirname(__FILE__).'/../CodeSniffer.conf';
        if (is_file($configFile) === false) {
            $configFile = '@data_dir@/PHP_CodeSniffer/CodeSniffer.conf';
        }

        if (is_file($configFile) === false) {
            self::$configData = array();
            return array();
        }

        include $configFile;
        self::$configData = $phpCodeSnifferConfig;
        return self::$configData;

    }//end getAllConfigData()


    /**
     * Prints out the gathered config data.
     *
     * @param array $data The config data to print.
     *
     * @return void
     */
    public function printConfigData($data)
    {
        $max  = 0;
        $keys = array_keys($data);
        foreach ($keys as $key) {
            $len = strlen($key);
            if (strlen($key) > $max) {
                $max = $len;
            }
        }

        if ($max === 0) {
            return;
        }

        $max += 2;
        ksort($data);
        foreach ($data as $name => $value) {
            echo str_pad($name.': ', $max).$value.PHP_EOL;
        }

    }//end printConfigData()



}//end class