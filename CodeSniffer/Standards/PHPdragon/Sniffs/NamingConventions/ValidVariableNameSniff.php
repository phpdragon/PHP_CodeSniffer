<?php

/**
 * Squiz_Sniffs_NamingConventions_ValidVariableNameSniff.
 *
 * PHP version 5
 *
 * @category  PHP
 * @package   PHP_CodeSniffer
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @author    Marc McIntyre <mmcintyre@squiz.net>
 * @copyright 2006-2014 Squiz Pty Ltd (ABN 77 084 670 600)
 * @license   https://github.com/squizlabs/PHP_CodeSniffer/blob/master/licence.txt BSD Licence
 * @link      http://pear.php.net/package/PHP_CodeSniffer
 */
if (class_exists('PHP_CodeSniffer_Standards_AbstractVariableSniff', true) === false) {
    throw new PHP_CodeSniffer_Exception('Class PHP_CodeSniffer_Standards_AbstractVariableSniff not found');
}

/**
 * Checks the naming of variables and member variables.
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
class PHPdragon_Sniffs_NamingConventions_ValidVariableNameSniff implements PHP_CodeSniffer_Sniff {

    /**
     * The end token of the current function that we are in.
     *
     * @var int
     */
    private $_endFunction = -1;

    /**
     * TRUE if a function is currently open.
     *
     * @var boolean
     */
    private $_functionOpen = false;

    /**
     * The current PHP_CodeSniffer file that we are processing.
     *
     * @var PHP_CodeSniffer_File
     */
    protected $currentFile = null;

    /**
     * 方法参数列表
     * @var type 
     */
    protected $methodParamProps = array();

    /**
     * Tokens to ignore so that we can find a DOUBLE_COLON.
     *
     * @var array
     */
    private $_ignore = array(
        T_WHITESPACE,
        T_COMMENT,
    );

    /**
     * The token types that this test wishes to listen to within the scope.
     *
     * @var array
     */
    private $_tokens = array();

    /**
     * The type of scope opener tokens that this test wishes to listen to.
     *
     * @var string
     */
    private $_scopeTokens = array();

    /**
     * True if this test should fire on tokens outside of the scope.
     *
     * @var boolean
     */
    private $_listenOutside = false;

    public function __construct() {
        $scopeTokens = array(
            T_CLASS,
            T_TRAIT,
            T_INTERFACE,
        );

        $tokens = array(
            T_FUNCTION,
            T_VARIABLE,
            T_DOUBLE_QUOTED_STRING,
            T_HEREDOC,
        );

        $invalidScopeTokens = array_intersect($scopeTokens, $tokens);
        if (empty($invalidScopeTokens) === false) {
            $invalid = implode(', ', $invalidScopeTokens);
            $error = "Scope tokens [$invalid] cant be in the tokens array";
            throw new PHP_CodeSniffer_Exception($error);
        }

        $this->_listenOutside = true;
        $this->_scopeTokens = array_flip($scopeTokens);
        $this->_tokens = $tokens;
    }

    public function register() {
        return $this->_tokens;
    }

    public final function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();

        $foundScope = false;
        foreach ($tokens[$stackPtr]['conditions'] as $scope => $code) {
            if (isset($this->_scopeTokens[$code]) === true) {
                $this->processTokenWithinScope($phpcsFile, $stackPtr, $scope);
                $foundScope = true;
            }
        }

        if ($this->_listenOutside === true && $foundScope === false) {
            $this->processTokenOutsideScope($phpcsFile, $stackPtr);
        }
    }

    /**
     * Processes the token in the specified PHP_CodeSniffer_File.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The PHP_CodeSniffer file where this
     *                                        token was found.
     * @param int                  $stackPtr  The position where the token was found.
     * @param array                $currScope The current scope opener token.
     *
     * @return void
     */
    protected final function processTokenWithinScope(PHP_CodeSniffer_File $phpcsFile, $stackPtr, $currScope) {
        if ($this->currentFile !== $phpcsFile) {
            $this->currentFile = $phpcsFile;
            $this->_functionOpen = false;
            $this->_endFunction = -1;
        }

        $tokens = $phpcsFile->getTokens();

        if ($stackPtr > $this->_endFunction) {
            $this->_functionOpen = false;
        }

        if ($tokens[$stackPtr]['code'] === T_FUNCTION && $this->_functionOpen === false) {
            $this->_functionOpen = true;

            $methodProps = $phpcsFile->getMethodProperties($stackPtr);

            //获取参数变量
            $this->methodParamProps = array();
            $methodParamProps = $phpcsFile->getMethodParameters($stackPtr);
            foreach ($methodParamProps as $param) {
                $this->methodParamProps[] = ltrim($param['name'], '$');
            }

            // If the function is abstract, or is in an interface,
            // then set the end of the function to it's closing semicolon.
            if ($methodProps['is_abstract'] === true || $tokens[$currScope]['code'] === T_INTERFACE) {
                $this->_endFunction = $phpcsFile->findNext(array(T_SEMICOLON), $stackPtr);
            } else {
                if (isset($tokens[$stackPtr]['scope_closer']) === false) {
                    $error = 'Possible parse error: non-abstract method defined as abstract';
                    $phpcsFile->addWarning($error, $stackPtr);
                    return;
                }

                $this->_endFunction = $tokens[$stackPtr]['scope_closer'];
            }
        }//end if

        if ($tokens[$stackPtr]['code'] === T_DOUBLE_QUOTED_STRING || $tokens[$stackPtr]['code'] === T_HEREDOC
        ) {
            // Check to see if this string has a variable in it.
            $pattern = '|(?<!\\\\)(?:\\\\{2})*\${?[a-zA-Z0-9_]+}?|';
            if (preg_match($pattern, $tokens[$stackPtr]['content']) !== 0) {
                $this->processVariableInString($phpcsFile, $stackPtr);
            }

            return;
        }

        if ($this->_functionOpen === true) {
            if ($tokens[$stackPtr]['code'] === T_VARIABLE) {
                $this->processVariable($phpcsFile, $stackPtr);
            }
        } else {
            // What if we assign a member variable to another?
            // ie. private $_count = $this->_otherCount + 1;.
            $this->processMemberVar($phpcsFile, $stackPtr);
        }
    }

    /**
     * Processes the token outside the scope in the file.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The PHP_CodeSniffer file where this
     *                                        token was found.
     * @param int                  $stackPtr  The position where the token was found.
     *
     * @return void
     */
    protected final function processTokenOutsideScope(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();
        // These variables are not member vars.
        if ($tokens[$stackPtr]['code'] === T_VARIABLE) {
            $this->processVariable($phpcsFile, $stackPtr);
        } else if ($tokens[$stackPtr]['code'] === T_DOUBLE_QUOTED_STRING || $tokens[$stackPtr]['code'] === T_HEREDOC
        ) {
            // Check to see if this string has a variable in it.
            $pattern = '|(?<!\\\\)(?:\\\\{2})*\${?[a-zA-Z0-9_]+}?|';
            if (preg_match($pattern, $tokens[$stackPtr]['content']) !== 0) {
                $this->processVariableInString($phpcsFile, $stackPtr);
            }
        }
    }

    /**
     * 检测会员变量
     * @param PHP_CodeSniffer_File $phpcsFile
     * @param type $stackPtr
     * @return type
     */
    protected function processVariable(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();
        $varName = ltrim($tokens[$stackPtr]['content'], '$');

        $phpReservedVars = array(
            '_SERVER',
            '_GET',
            '_POST',
            '_REQUEST',
            '_SESSION',
            '_ENV',
            '_COOKIE',
            '_FILES',
            'GLOBALS',
            'http_response_header',
            'HTTP_RAW_POST_DATA',
            'php_errormsg',
        );

        // If it's a php reserved var, then its ok.
        if (in_array($varName, $phpReservedVars) === true) {
            return;
        }

        /**
         * @author phpdragon <phpdragon@PHPdragon.com>
         * 如果是传入参数
         */
        if (in_array($varName, $this->methodParamProps)) {
            return;
        }

        //调用自身成员变量
        if ('this' == $varName) {
            $thisOperator = $phpcsFile->findNext(array(T_WHITESPACE), ($stackPtr + 1), null, true);
            //如果是 -> 符号
            if (T_OBJECT_OPERATOR == $tokens[$thisOperator]['code']) {
                $thisMemberVar = $phpcsFile->findNext(array(T_WHITESPACE), ($thisOperator + 1), null, true);
                $thisMemberVarName = $tokens[$thisMemberVar]['content'];

                $nextOperator = $phpcsFile->findNext(array(T_WHITESPACE), ($thisOperator + 2), null, true);
                //如果是方法调用
                if ('PHPCS_T_OPEN_PARENTHESIS' == $tokens[$nextOperator]['code']) {
                    //方法名不是骆驼峰
                    if (PHP_CodeSniffer::isCamelCaps($thisMemberVarName, false, true, false) === false) {
                        //TODO
                    }

                    return;
                }

                if ($this->isUppercase($thisMemberVarName)) {
                    $error = 'Member variables "%s" must use lowercase letters underlined';
                    $data = array($thisMemberVarName);
                    $phpcsFile->addError($error, $stackPtr, 'MemberVariableNotUppercase', $data);
                } else if (preg_match('|\d|', $thisMemberVarName) === 1) {
                    $warning = 'Member variable "%s" contains numbers but this is discouraged';
                    $data = array($thisMemberVarName);
                    $phpcsFile->addWarning($warning, $stackPtr, 'ContainsNumbers', $data);
                }
            }
        }

        //自身引用 self::$TEST;
        $selfOperator = $phpcsFile->findNext(array(T_WHITESPACE), ($stackPtr - 1), null, true);
        //如果是 ::
        if (T_DOUBLE_COLON == $tokens[$selfOperator]['code']) {
            //静态变量大写下划线
            if ($this->isLowercase($varName)) {
                $error = 'Static member variables "%s" must use uppercase letters underlined';
                $data = array($tokens[$stackPtr]['content']);
                $phpcsFile->addError($error, $stackPtr, 'StaticVariableNotUppercase', $data);
            } else if (preg_match('|\d|', $varName) === 1) {
                $warning = 'Variable "%s" contains numbers but this is discouraged';
                $data = array($varName);
                $phpcsFile->addWarning($warning, $stackPtr, 'ContainsNumbers', $data);
            }

            return;
        }


        $objOperator = $phpcsFile->findNext(array(T_WHITESPACE), ($stackPtr + 1), null, true);
        //如果该变量是个引用对象
        if ($tokens[$objOperator]['code'] === T_OBJECT_OPERATOR) {
            $var = $phpcsFile->findNext(array(T_WHITESPACE), ($objOperator + 1), null, true);
            //内部成员变量
            if ($tokens[$var]['code'] === T_STRING) {
                // Either a var name or a function call, so check for bracket.
                $bracket = $phpcsFile->findNext(array(T_WHITESPACE), ($var + 1), null, true);
                if ($tokens[$bracket]['code'] !== T_OPEN_PARENTHESIS) {
                    $objVarName = $tokens[$var]['content'];

                    if ($this->isUppercase($varName)) {
                        $error = 'Variables "%s" must use lowercase letters underlined';
                        $data = array($objVarName);
                        $phpcsFile->addError($error, $var, 'VariableNotLowercase', $data);
                    } else if (preg_match('|\d|', $objVarName) === 1) {
                        $warning = 'Variable "%s" contains numbers but this is discouraged';
                        $data = array($varName);
                        $phpcsFile->addWarning($warning, $stackPtr, 'ContainsNumbers', $data);
                    }
                }
            }

            return;
        }

        //其他变量检测
        if ($this->isUppercase($varName)) {
            $error = 'Variable "%s" must use lowercase letters underlined';
            $data = array($varName);
            $phpcsFile->addError($error, $stackPtr, 'VariableNotLowercase', $data);
        } else if (preg_match('|\d|', $varName) === 1) {
            $warning = 'Variable "%s" contains numbers but this is discouraged';
            $data = array($varName);
            $phpcsFile->addWarning($warning, $stackPtr, 'ContainsNumbers', $data);
        }
    }

    /**
     * 检测成员变量
     * @param PHP_CodeSniffer_File $phpcsFile
     * @param type $stackPtr
     */
    protected function processMemberVar(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();
        $varName = ltrim($tokens[$stackPtr]['content'], '$');
        $memberProps = $phpcsFile->getMemberProperties($stackPtr);
        $is_public = ($memberProps['scope'] === 'public');
        $is_static = $memberProps['is_static'];

        if ($is_static && $this->isUppercase($varName) === false) {
            $error = 'Static member variable "%s" must use uppercase letters underlined';
            $data = array($varName);
            $phpcsFile->addError($error, $stackPtr, 'StaticMemberVarNotUppercase', $data);
        }

        if (!$is_static && $this->isLowercase($varName) === false) {
            $error = 'Member variable "%s" must use lowercase letters underlined';
            $data = array($varName);
            $phpcsFile->addError($error, $stackPtr, 'MemberVarNotLowercase', $data);
        }

        if (preg_match('|\d|', $varName) === 1) {
            $warning = 'Member variable "%s" contains numbers but this is discouraged';
            $data = array($varName);
            $phpcsFile->addWarning($warning, $stackPtr, 'MemberVarContainsNumbers', $data);
        }
    }

    /**
     * 检测双引号包裹的变量
     * @param PHP_CodeSniffer_File $phpcsFile
     * @param type $stackPtr
     * @return type
     */
    protected function processVariableInString(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
        $tokens = $phpcsFile->getTokens();

        $phpReservedVars = array(
            '_SERVER',
            '_GET',
            '_POST',
            '_REQUEST',
            '_SESSION',
            '_ENV',
            '_COOKIE',
            '_FILES',
            'GLOBALS',
            'http_response_header',
            'HTTP_RAW_POST_DATA',
            'php_errormsg',
        );

        $matches = array();
        if (preg_match_all('|[^\\\]\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)|', $tokens[$stackPtr]['content'], $matches) !== 0) {
            foreach ($matches[1] as $varName) {
                // If it's a php reserved var, then its ok.
                if (in_array($varName, $phpReservedVars) === true) {
                    continue;
                }

                //在双引号中，必须使用大括号包裹变量
                if (false === strpos($tokens[$stackPtr]['content'], '{$' . $varName)) {
                    if ('this' == $varName) {
                        $error = 'In double-quoted strings, the Variable "$%s->[variable]" must use curly braces, like this "{$this-[variable]}"';
                    } else {
                        $error = 'In double-quoted strings, the Variable "$%s" must use curly braces, like this "{$variable}" ';
                    }

                    $data = array($varName);
                    $phpcsFile->addWarning($error, $stackPtr, 'VariableInStringMustUseCurlyBraces', $data);
                }

                if ($this->isUppercase($varName)) {
                    $error = 'Variable "%s" must use lowercase letters underlined';
                    $data = array($varName);
                    $phpcsFile->addError($error, $stackPtr, 'VariableNotLowercase', $data);
                } else if (preg_match('|\d|', $varName) === 1) {
                    $warning = 'Variable "%s" contains numbers but this is discouraged';
                    $data = array($varName);
                    $phpcsFile->addWarning($warning, $stackPtr, 'ContainsNumbers', $data);
                }
            }
        }
    }

    private function isLowercase($string) {
        if (preg_match('|[a-z]|', $string) === 0) {
            return false;
        }

        return true;
    }

    private function isUppercase($string) {
        if (preg_match('|[A-Z]|', $string) === 0) {
            return false;
        }

        return true;
    }

}
