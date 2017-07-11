<?php

namespace Spaceboy\Constfile;

require('../src/Constfile.php');
require('../src/ConstfileException.php');

$cfManager = new Constfile();

try {
    $cfManager
        //->setCaseInsensitivity(TRUE)
        //->setValue('MY_ARRAY_1', ['a', 'b', 'c', 'x' => ['A', "B\nB", 'C']])
        ->setBoolean('MY_BOOL_1', TRUE, 'boolean true')
        ->setBoolean('MY_BOOL_2', 1, 'boolean true')
        ->setBoolean('MY_BOOL_3', 0, 'boolean false')
        ->setBoolean('MY_BOOL_4', rand(0, 9) > 4, 'boolean random')
        ->setBoolean('MY_BOOL_0', FALSE)
        ->clear('MY_BOOL_0')
        ->setInteger('MY_INT_1', TRUE, 'integer 1')
        ->setInteger('MY_INT_2', TRUE)
        ->setInteger('MY_INT_3', FALSE)
        ->setInteger('MY_INT_4', 3.14)
        ->setInteger('MY_INT_5', "5")
        ->setInteger('MY_INT_6', "#6")
        ->setInteger('MY_INT_7', "7.8")
        ->setString('MY_STRING_1', 'string')
        ->setString('MY_STRING_2', '2')
        ->setString('MY_STRING_3', 3)
        ->setString('MY_STRING_4', FALSE)
        ->setString('MY_STRING_5', TRUE)
        ->setString('MY_STRING_6', 'TRUE')
        ->setString('MY_STRING_7', 'string = "string"')
        ->setString('MY_STRING_8', 'string = "Barney\'s"')
        ->setFloat('MY_FLOAT_1', 0)
        ->setFloat('MY_FLOAT_2', 1)
        ->setFloat('MY_FLOAT_3', 3.14)
        ->setFloat('MY_FLOAT_4', "3.14")
        ->setFloat('MY_FLOAT_5', FALSE)
        ->setFloat('MY_FLOAT_6', TRUE)
        ->setFloat('MY_FLOAT_7', '')
        ->setDirname('../test/')
        //->setArray('MY_ARRAY_1', [])
        ->setCheckDefined(TRUE)
        ->export();
    $cfManager
        ->reset()
        ->setDirname('../test/')
        ->import('constfile.php')
        ->export('constfile2.php');
} catch (ConstfileException $ex) {
    echo "CONSTFILE ERROR: {$ex->getMessage()}\n";
} catch (\Exception $ex) {
    echo "UNKNOWN ERROR: {$ex->getMessage()}\n";
}
