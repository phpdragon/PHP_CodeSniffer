<?php

/**
 * 一个文件只有一个类被声明
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Andy Grunwald <andygrunwald@gmail.com>
 * @copyright 2010-2014 Andy Grunwald
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class PHPdragon_Sniffs_Files_OneClassPerFileSniff implements PHP_CodeSniffer_Sniff {


    public function register() {
        return array(T_CLASS);
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
        $nextClass = $phpcsFile->findNext($this->register(), ($stackPtr + 1));
        if ($nextClass !== false) {
            $error = 'Only one class is allowed in a file';
            //$error = '一个文件只有一个类被声明';
            $phpcsFile->addError($error, $nextClass, 'MultipleFound');
        }
    }

}
