<?php

/**
 * 确保整个PHP文件,没有空格或内联HTML。
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class PHPdragon_Sniffs_Files_InlineHTMLSniff implements PHP_CodeSniffer_Sniff {

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
        return array(T_INLINE_HTML);
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in
     *                                        the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
        // Ignore shebang lines.
        $tokens = $phpcsFile->getTokens();
        if (substr($tokens[$stackPtr]['content'], 0, 2) === '#!') {
            return;
        }

        $error = 'PHP files must only contain PHP code';
        //$error = 'PHP文件必须只包含PHP代码';
        $phpcsFile->addError($error, $stackPtr, 'Found');

        return $phpcsFile->numTokens;
    }

}
