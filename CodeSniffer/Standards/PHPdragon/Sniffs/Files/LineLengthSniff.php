<?php

/**
 * 
 *
 * 检查文件中的所有行,如果超过80抛出警告，如果超过100字符抛出错误。
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class PHPdragon_Sniffs_Files_LineLengthSniff implements PHP_CodeSniffer_Sniff {

    /**
     * 单行代码最大长度80个字符
     *
     * @var int
     */
    public $lineLimit = 80;

    /**
     * 单行不得超过的长度
     *
     * Set to zero (0) to disable.
     *
     * @var int
     */
    public $absoluteLineLimit = 100;

    public function register() {
        return array(T_OPEN_TAG);
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();
        for ($i = 1; $i < $phpcsFile->numTokens; $i++) {
            if ($tokens[$i]['column'] === 1) {
                $this->checkLineLength($phpcsFile, $tokens, $i);
            }
        }

        $this->checkLineLength($phpcsFile, $tokens, $i);

        // Ignore the rest of the file.
        return ($phpcsFile->numTokens + 1);
    }

    /**
     * Checks if a line is too long.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param array                $tokens    The token stack.
     * @param int                  $stackPtr  The first token on the next line.
     *
     * @return null|false
     */
    protected function checkLineLength(PHP_CodeSniffer_File $phpcsFile, $tokens, $stackPtr) {
        // The passed token is the first on the line.
        $stackPtr--;

        if ($tokens[$stackPtr]['column'] === 1 && $tokens[$stackPtr]['length'] === 0
        ) {
            // Blank line.
            return;
        }

        if ($tokens[$stackPtr]['column'] !== 1 && $tokens[$stackPtr]['content'] === $phpcsFile->eolChar
        ) {
            $stackPtr--;
        }

        $lineLength = ($tokens[$stackPtr]['column'] + $tokens[$stackPtr]['length'] - 1);

        // Record metrics for common line length groupings.
        if ($lineLength <= 80) {
            $phpcsFile->recordMetric($stackPtr, 'Line length', '80 or less');
        } else if ($lineLength <= 120) {
            $phpcsFile->recordMetric($stackPtr, 'Line length', '81-120');
        } else if ($lineLength <= 150) {
            $phpcsFile->recordMetric($stackPtr, 'Line length', '121-150');
        } else {
            $phpcsFile->recordMetric($stackPtr, 'Line length', '151 or more');
        }

        if ($this->absoluteLineLimit > 0 && $lineLength > $this->absoluteLineLimit
        ) {
            $data = array(
                $this->absoluteLineLimit,
                $lineLength,
            );

            $error = 'Line exceeds maximum limit of %s characters; contains %s characters';
            //$error = '单行最大长度%s个字符; 当前包含%s个字符';
            $phpcsFile->addError($error, $stackPtr, 'MaxExceeded', $data);
        } else if ($lineLength > $this->lineLimit) {
            $data = array(
                $this->lineLimit,
                $lineLength,
            );

            $warning = 'Line exceeds %s characters; contains %s characters';
            //$warning = '单行最大长度%s个字符; 当前包含%s个字符';
            $phpcsFile->addWarning($warning, $stackPtr, 'TooLong', $data);
        }
    }

}
