<?php

/**
 * 检查每个文件只有一个Trait语法糖
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Alexander Obuhovich <aik.bold@gmail.com>
 * @copyright 2010-2014 Alexander Obuhovich
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @version   Release: @package_version@
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
class PHPdragon_Sniffs_Files_OneTraitPerFileSniff implements PHP_CodeSniffer_Sniff {

    public function register() {
        return array(T_TRAIT);
    }

    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
        $nextClass = $phpcsFile->findNext($this->register(), ($stackPtr + 1));
        if ($nextClass !== false) {
            $error = 'Only one trait is allowed in a file';
            //$error = '一个文件只能有一个Trait语法糖';
            $phpcsFile->addError($error, $nextClass, 'MultipleFound');
        }
    }

}
