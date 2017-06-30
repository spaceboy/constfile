<?php

namespace Spaceboy\Constfile;

if (!defined('PHP_VERSION_ID')) {
    $version = explode('.', PHP_VERSION);
    define('PHP_VERSION_ID', ($version[0] * 10000 + $version[1] * 100 + $version[2]));
}

class ConstfileException extends \Exception {

    protected   $message    = 'Constfile exception';
}

class Constfile {

    const   DEFAULT_FILENAME        = 'constfile.php';

    /** @var array of values */
    protected   $values             = [];

    /** @var string dirname */
    protected   $dirName            = '';

    /** @var string filename */
    protected   $fileName           = '';

    /** @var bool case insensitivity */
    protected   $caseInsensitive    = FALSE;

    /** @var bool carefully check if const is already defined */
    protected   $checkDefined       = FALSE;

    /**
     * Setter for constant of any type
     * @param string $constName
     * @param mixed $value
     * @return $this
     */
    protected function set ($constName, $value) {
        $this->values[$constName]   = $value;
        return $this;
    }

    /**
     * Setter for "untyped" or "mixed" type constants -- use on your own responsibility!
     * @param string $constName
     * @param mixed $value
     * @return $this
     */
    public function setAutomatic ($constName, $value) {
        return $this->set($constName, $value);
    }

    /**
     * Setter for integer constant
     * @param string $constName
     * @param integer $value
     * @return $this
     */
    public function setInteger ($constName, $value) {
        return $this->set($constName, intVal($value));
    }

    /**
     * Setter for float constant
     * @param string $constName
     * @param float $value
     * @return $this
     */
    public function setFloat ($constName, $value) {
        return $this->set($constName, floatval($value));
    }

    /**
     * Setter for string constant
     * @param string $constName
     * @param string $value
     * @return $this
     */
    public function setString ($constName, $value) {
        return $this->set($constName, (string)$value);
    }

    /**
     * Setter for boolean constant
     * @param string $constName
     * @param boolean $value
     * @return $this
     */
    public function setBoolean ($constName, $value) {
        return $this->set($constName, (boolean)$value);
    }

    /**
     * Setter for array constant
     * @param string $constName
     * @param array $value
     * @return $this
     * @throws Spaceboy\Constfile\ConstfileException
     */
    public function setArray ($constName, $value) {
        if (PHP_VERSION_ID < 70000) {
            throw new ConstfileException("Error creating {$constName}: PHP 7 required for array constants.");
        }

    }

    /**
     * returns value of given constant
     * @param string $constName
     * @return mixed
     * @throws Spaceboy\Constfile\ConstfileException
     */
    public function getValue ($constName) {
        if (!array_key_exists($constName, $this->values)) {
            throw new ConstfileException("Unknown name of constant \"{$constName}\".");
        }
    }

    /**
     * returns array of values of all set constants
     * @return array
     */
    public function getValues () {
        return $this->values;
    }

    /**
     * Destroys constant
     * @param string const name
     * @return $this
     */
    public function clear ($constName) {
        unset($this->values[$constName]);
        return $this;
    }

    /**
     * Resets all settings to default; usefull for creating another config file
     * $return $this
     */
    public function reset () {
        $this->values   = [];
        $this->fileName = static::DEFAULT_FILENAME;
        $this->dirName  = '';
        return $this;
    }

    /**
     * Setter for case insensivity option
     * @param bool caseInsensitive
     * @return $this
     */
    public function setCaseInsensitivity ($value) {
        $this->caseInsensitive  = $value;
        return $this;
    }

    /**
     * Setter for checking if const is already defined
     * @param bool
     * @return $this
     */
    public function setCheckDefined ($value) {
        $this->checkDefined = $value;
        return $this;
    }

    /**
     * Sets output directory;
     * if not set, sets DIR of THIS file
     * @param string dirname
     * @return $this
     * @throws Spaceboy\Constfile\ConstfileException
     */
    public function setDirname ($dirName = NULL) {
        $dirName = (
            $dirName
            ? realpath($dirName)
            : dirname(realpath(__FILE__))
        );
        if (!file_exists($dirName)) {
            throw new ConstfileException("Directory \"{$dirName}\" not found.");
        }
        if (!is_dir($dirName)) {
            throw new ConstfileException("\"{$dirName}\" is not directory.");
        }
        $this->dirName  = $dirName;
        return $this;
    }

    /**
     * Sets output file name
     * @param string $fileName
     * @return $this
     */
    public function setFilename ($fileName) {
        $this->fileName = $fileName ?: self::DEFAULT_FILENAME;
        return $this;
    }

    /**
     * "Translates" string from "str = \"string\"" to 'str = "string"' form
     * @param string
     * @return string
     */
    private function parseString ($str) {
        if (!($len = strlen($str))) {
            return '';
        }
        switch ($str[0]) {
            case '\'':
                return str_replace("\'", "'", substr($str, 1, $len - 2));
                break;
            case '"':
                return str_replace('\"', '"', substr($str, 1, $len - 2));
                break;
        }
    }

    /**
     * Parses PHP tokens
     * @param array $tokens
     * @return $this
     * @throws Spaceboy\Constfile\ConstfileException
     */
    private function parse ($tokens) {
        $inDefine   = FALSE;
        $constName  = NULL;
        foreach ($tokens as $token) {
            if (!is_array($token)) {
                continue;
            }
            if (!$inDefine && (T_STRING != $token[0] || 'define' != $token[1])) {
                continue;
            }
            $inDefine   = TRUE;
            switch ($token[0]) {
                case T_LNUMBER:
                    $this->setInteger($constName, $token[1]);
                    $inDefine   = FALSE;
                    $constName  = NULL;
                    break;
                case T_DNUMBER:
                    $this->setFloat($constName, $token[1]);
                    $inDefine   = FALSE;
                    $constName  = NULL;
                    break;
                case T_CONSTANT_ENCAPSED_STRING:
                    if (is_null($constName)) {
                        $constName  = $this->parseString($token[1]);
                    } else {
                        $this->setString($constName, $this->parseString($token[1]));
                        $inDefine   = FALSE;
                        $constName  = NULL;
                    }
                    break;
                case T_STRING:
                    switch ($token[1]) {
                        case 'define':
                            break;
                        case 'TRUE':
                            $this->setBoolean($constName, TRUE);
                            $inDefine   = FALSE;
                            $constName  = NULL;
                            break;
                        case 'FALSE':
                            $this->setBoolean($constName, FALSE);
                            $inDefine   = FALSE;
                            $constName  = NULL;
                            break;
                        default:
                            throw new ConstfileException('Unable to decode value \"{$token[1]}\"');
                    }
                    break;
            }
        }
    }

    /**
     * Exports const to const file
     * @param string $fileName
     * @return boolean
     * @throws Spaceboy\Constfile\ConstfileException
     */
    public function export ($fileName = NULL) {
        $this->setFilename($fileName);
        if (!$this->dirName) {
            $this->setDirname(dirname(realpath(__FILE__)));
        }
        $caseInsensitive    = (
            $this->caseInsensitive
            ? ', TRUE'
            : ''
        );
        $output = '<?php'. PHP_EOL;
        foreach ($this->values AS $key => $val) {
            if (is_string($val)) {
                $val = '"'.str_replace('"', '\"', $val).'"';
            } elseif (is_bool($val)) {
                $val = ['FALSE', 'TRUE'][(int)$val];
            }
            if ($this->checkDefined) {
                $output .= "if (!defined('{$key}')) ";
            }
            $output .= "define('{$key}', {$val}{$caseInsensitive});".PHP_EOL;
        }
        return file_put_contents($this->dirName . DIRECTORY_SEPARATOR . $this->fileName, $output, LOCK_EX);
    }

    /**
     * Imports consts from PHP file
     * @param string $fileName
     * @return $this
     * @throws Spaceboy\Constfile\ConstfileException
     */
    public function import ($fileName) {
        if (!file_exists($fileName)) {
            throw new ConstfileException("File \"{$fileName}\" not found.");
        }
        if (!is_file($fileName)) {
            throw new ConstfileException("\"{$fileName}\" is not file.");
        }
        $this->parse(token_get_all(file_get_contents($fileName)));
        return $this;
    }

}