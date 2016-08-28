<?php

/**
 * PHPdragon_Sniffs_Functions_ValidDefaultValueSniff.
 *
 * A Sniff to ensure that parameters defined for a function that have a default
 * value come at the end of the function signature.
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class PHPdragon_Sniffs_NamingConventions_ValidMethodArgumentsNameSniff implements PHP_CodeSniffer_Sniff {

    public $style = 0;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
        return array(T_FUNCTION);
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {

        $memberProps = $phpcsFile->getMethodParameters($stackPtr);

        foreach ($memberProps as $param) {
            $testName = substr($param['name'], 1);

            //检查是否是骆驼峰
            if (0 == $this->style) {
                $this->checkIsCamelCaps($phpcsFile, $stackPtr, $testName);
            } else {
                $this->checkIsUnderscoreName($phpcsFile, $stackPtr, $testName);
            }
        }
    }

    public function checkIsCamelCaps(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $testName) {
        $methodName = $phpcsFile->getDeclarationName($stackPtr);
        if ($testName !== '' && PHP_CodeSniffer::isCamelCaps($testName, false, true, false) === false) {
            $error = 'method ' . $methodName . ' Argument name "$%s" is not in camel caps format';
            $errorData = array($testName);
            $phpcsFile->addError($error, $stackPtr, 'NotCamelCaps', $errorData);
            $phpcsFile->recordMetric($stackPtr, 'CamelCase method(' . $methodName . ') parameter name', 'no');
        } else {
            $phpcsFile->recordMetric($stackPtr, 'CamelCase method(' . $methodName . ') parameter name', 'yes');
        }
    }

    public function checkIsUnderscoreName(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $testName) {
        $methodName = $phpcsFile->getDeclarationName($stackPtr);
        
        if ($testName !== '' && PHP_CodeSniffer::isCamelCaps($testName, false, true, false) === true) {
        //if ($testName !== '' && PHP_CodeSniffer::isUnderscoreName($testName) === false) {
            $error = 'method ' . $methodName . ' Argument name "$%s" must use lowercase letters underlined';
            $errorData = array($testName);
            $phpcsFile->addError($error, $stackPtr, 'NotUnderscore', $errorData);
            $phpcsFile->recordMetric($stackPtr, 'CamelCase method(' . $methodName . ') parameter name', 'no');
        } else {
            $phpcsFile->recordMetric($stackPtr, 'CamelCase method(' . $methodName . ') parameter name', 'yes');
        }
    }

}
