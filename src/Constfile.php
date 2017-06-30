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
     * @param string $const
     * @param mixed $value
     * @return $this
     */
    protected function set ($const, $value) {
        $this->values[$const]   = $value;
        return $this;
    }

    /**
     * Setter for "untyped" or "mixed" type constants -- use on your own responsibility!
     * @param string $const
     * @param mixed $value
     * @return $this
     */
    public function setAutomatic ($const, $value) {
        return $this->set($const, $value);
    }

    /**
     * Setter for integer constant
     * @param string $const
     * @param integer $value
     * @return $this
     */
    public function setInteger ($const, $value) {
        return $this->set($const, intVal($value));
    }

    /**
     * Setter for float constant
     * @param string $const
     * @param float $value
     * @return $this
     */
    public function setFloat ($const, $value) {
        return $this->set($const, floatval($value));
    }

    /**
     * Setter for string constant
     * @param string $const
     * @param string $value
     * @return $this
     */
    public function setString ($const, $value) {
        return $this->set($const, (string)$value);
    }

    /**
     * Setter for boolean constant
     * @param string $const
     * @param boolean $value
     * @return $this
     */
    public function setBoolean ($const, $value) {
        return $this->set($const, (boolean)$value);
    }

    /**
     * Setter for array constant
     * @param string $const
     * @param array $value
     * @return $this
     * @throws Spaceboy\Constfile\ConstfileException
     */
    public function setArray ($const, $value) {
        if (PHP_VERSION_ID < 70000) {
            throw new ConstfileException("Error creating {$const}: PHP 7 required for array constants");
        }

    }

    /**
     * Destroys constant
     * @param string const name
     * @return $this
     */
    public function clear ($const) {
        unset($this->values[$const]);
        return $this;
    }

    /**
     * Resets all settings to default; usefull for creating another config file
     * $return $this
     */
    public function reset () {
        $this->values   = [];
        $this->filename = static::DEFAULT_FILENAME;
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

}