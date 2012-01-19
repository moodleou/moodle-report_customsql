<?php  

/**
 * PHP5 -> 4 Object Model Compatibility 
 * -------------------------------
 *
 * Author: David Grudl (aka DGX)
 * Version: 2005-12-03
 *
 * Web: http://www.dgx.cz
 * Blog: http://latrine.dgx.cz
 *
 * Copyright (c) 2005, David Grudl <dave@dgx.cz>
 * Licensed under GPL
 */




/**
 * Clone emulation
 *
 * Example: $obj = clone ($dolly)
 */
function clone_obj($obj) 
{
    // regenerate references (see http://latrine.dgx.cz/how-to-emulate-php5-object-model-in-php4)
    foreach($obj as $key => $value) {
        $obj->$key = & $value;               // reference to new variable
        $GLOBALS['$$HIDDEN$$'][] = & $value; // and generate reference
        unset($value); 
    }
    
    // call $obj->__clone()
    if (is_callable(array(&$obj, '__clone'))) $obj->__clone();
    
    return $obj;
}





/**
 * Exception emulation
 *
 * Example:
 *          /* try * / {
 *             throw (new Exception('Error message'));
 *
 *          } if (catch('Exception', $e)) {
 *
 *             echo 'Caught exception: ',  $e->getMessage();
 *          }
 */


/**
 * Last throwed exception
 */
$GLOBALS['$Exception$last'] = NULL;


function throw($exception) 
{
    if ($GLOBALS['$Exception$last']) 
        trigger_error();
        
    $GLOBALS['$Exception$last'] = $exception;
    return false;
}


function catch($name, &$exception) 
{
    if (is_a($GLOBALS['$Exception$last'], $name)) {
        $exception = $GLOBALS['$Exception$last'];
        $GLOBALS['$Exception$last'] = null;
        return true;
    }
    return false;
}



class Exception 
{
    var $message;   // exception message
    var $code;      // user defined exception code
    var $file;      // source filename of exception
    var $line;      // source line of exception
    var $trace;     // debug stack trace


    function __construct($message = '', $code = 0) 
    {
        $this->message = (string) $message;
        $this->code = (int) $code;       
        $this->trace = debug_backtrace();
        array_shift($this->trace); // constructor __construct()
        array_shift($this->trace); // PHP4 constructor Exception()
        $this->file = (string) $this->trace[0]['file'];
        $this->line = (int) $this->trace[0]['line'];
        array_shift($this->trace); // throwing Exception
    }
    

    function getMessage()
    {
        return $this->message;
    }
    
    
    function getCode()
    {
        return $this->code;
    }
    
    
    function getFile()
    {
        return $this->file;
    }

    
    function getLine()
    {
        return $this->line;
    }

    
    function getTrace()
    {
        return $this->trace;
    }

    
    function getTraceAsString() // formated string of trace
    {
        // todo
    }

    
    function __toString() // formated string for display
    {
        return "exception '" . get_class($this) . "'' with message '$this->message' in $this->file:$this->line Stack trace: " . $this->getTraceAsString();
    }


    function Exception()  /* PHP 4 constructor */
    {
        // generate references (see http://latrine.dgx.cz/how-to-emulate-php5-object-model-in-php4)
        foreach ($this as $key => $foo) $GLOBALS['$$HIDDEN$$'][] = & $this->$key;
    
        // call php5 constructor
        $args = func_get_args();
        call_user_func_array(array(&$this, '__construct'), $args);
    }
}


?>