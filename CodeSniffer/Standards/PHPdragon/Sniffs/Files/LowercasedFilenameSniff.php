<?php

/**
 * 检查所有文件名称都是小写的。
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Andy Grunwald <andygrunwald@gmail.com>
 * @copyright 2010-2014 Andy Grunwald
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class PHPdragon_Sniffs_Files_LowercasedFilenameSniff implements PHP_CodeSniffer_Sniff {

    public function register() {
        return array(T_OPEN_TAG);
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
        $filename = $phpcsFile->getFilename();
        if ($filename === 'STDIN') {
            return;
        }

        $filename = basename($filename);
        $lowercaseFilename = strtolower($filename);
        if ($filename !== $lowercaseFilename) {
            $data = array(
                $filename,
                $lowercaseFilename,
            );
            $error = 'Filename "%s" doesn\'t match the expected filename "%s"';
            //$error = '文件名 "%s" 不是一个匹配的小写命名："%s"';
            $phpcsFile->addError($error, $stackPtr, 'NotFound', $data);
            $phpcsFile->recordMetric($stackPtr, 'Lowercase filename', 'no');
        } else {
            $phpcsFile->recordMetric($stackPtr, 'Lowercase filename', 'yes');
        }

        // Ignore the rest of the file.
        return ($phpcsFile->numTokens + 1);
    }

}
