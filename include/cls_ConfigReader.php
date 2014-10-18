<?php
/**
 * Highly flexible class to parse a key=value configuration (ini) file.
 *
 * @version 1.0
 * @author Jesse Quijano
 */
class ConfigReader {
    public $ErrNo = 0;
    public $Error = "";
    
    private $Log = "";
    private $arryCommentMarkers = array("#","'","/*","//"); //add additional comment flags here (1 or two chars only)
    private $FileSize = 0;
    private $FileLines = 0;
    private $arryConfigItems = array();
    private $ConfigFile = "";
    private $ConfigLoaded = false;
    private $ParameterCount = 0;
    private $CommentCount = 0;
    private $WhiteSpaceCount = 0;
    
    const INT = 1;
    const FLOAT = 2;
    const STRING = 3;
    const BOOLEAN = 4;
    const ASSUME_BOOL = true;  //use this to change yes/no, on/off, true/false to actual boolean values
    
    public function __construct($logfile = "") {
        /* allow instantiation without passing the logfile */
        if($logfile != "") {
            if(!$this->isFileValid($logfile)) {
                return false; //error variables will be set in method
            }
            $this->ConfigFile = $logfile;
            
            /* could continue to auto-load the file here, but what fun would that be for the demo?  :) */
        }
    }
    
    /**
     * Set the location of the configuration file.
     * 
     * @param string $logfile Complete path to the configuration file.
     * @return boolean
     */
    public function setConfigFile($logfile) {
        if($logfile == "") {
            $this->raiseError(905, "Log file may not be blank.");
            return false;
        }
        if(!$this->isFileValid($logfile)) {
            return false;
        }
        $this->ConfigFile = $logfile;
        return true;
    }
    /**
     * Public facing method to load/read the configuration file.
     * 
     * @return boolean
     */
    public function loadConfigFile() {
        if($this->ConfigFile == "") {
            $this->raiseError(913,"You must first set the configuration file using setConfigFile().");
            return false;
        }
        if(!$this->readConfigFile()) {
            return false;
        }
        $this->ConfigLoaded = true;
        return true;
    }
    
    public function clearError() {
        $this->raiseError(0, "");
    }
    
    public function getConfigFileSize() {
        if($this->ConfigFile == "") {
            $this->raiseError(908, "You must first set the configuration file using setConfigFile().");
            return false;
        }
        return $this->FileSize;
    }
    
    public function getConfigLineCount() {
        if($this->ConfigFile == "") {
            $this->raiseError(910, "You must first set the configuration file using setConfigFile().");
            return false;
        }
        return $this->FileLines;
    }
    
    public function getParameterCount() {
        if(!$this->ConfigLoaded) {
            $this->raiseError(916, "You must first load the configuration file using loadConfigFile().");
            return false;
        }
        return $this->ParameterCount;   
    }
    
    public function getBlankLineCount() {
        if(!$this->ConfigLoaded) {
            $this->raiseError(917, "You must first load the configuration file using loadConfigFile().");
            return false;
        }
        return $this->WhiteSpaceCount;
    }
    
    public function getCommentCount() {
        if(!$this->ConfigLoaded) {
            $this->raiseError(918, "You must first load the configuration file using loadConfigFile().");
            return false;
        }
        return $this->CommentCount;
    }
    
    public function getParameterByName($name) {
        for($i=0 ; $i < $this->ParameterCount ; $i++) {
            if($this->arryConfigItems[$i]->name == $name) {
                return $this->arryConfigItems[$i]->value;
            }
        }
        return false;
    }
    
    public function Parameter($index) {
        return $this->arryConfigItems[$index];
    }
    /**
     * Performs several tests to determine if the supplied file is valid.  Does not determine whether there are valid configuration line items.
     * 
     * @param string $logpath Complete path to the configuration file.
     * @return boolean
     */
    private function isFileValid($logpath) {
        if(file_exists($logpath) === false) {
            $this->raiseError(900, "The specified file '" . $logpath . "' does not exist.");
            return false;
        }
        $ret = $this->FileSize = filesize($logpath);
        if($ret === false) {
            $this->raiseError(902, "Error retrieving file size.");
            return false;
        }
        
        if($this->FileSize == 0) {
            $this->raiseError(903, "The specified file is 0 bytes (no data).");
            return false;
        }
        
        if(!$this->countFileLines($logpath)) {
            $this->raiseError(909, "The file could not be opened.");
            return false;
        }
        return true;
    }
    /**
     * Counts the number of lines in the configuration file.  To guard against buffer overruns, we load the file chunk by chunk. (this is faster than "line by line" on larger files)
     * @param string $logpath Complete path to the configuration file.
     * @return boolean
     */
    private function countFileLines($logpath) {
        if(!$fp = fopen($logpath, "r")) {
            $this->raiseError(901, "The file could not be opened.");
            return false;
        }
        $lines = 0;
        while(!feof($fp)) {
            $lines += substr_count(fread($fp, 8192), "\n");           
        }
        fclose($fp);
        $this->FileLines = $lines +1;  //add 1 for final line (no \n).  Could have also started $lines at 1.
        return true;
    }
    
    /**
     * Reads the configuration file, line by line and calling getValue() to extract key/value pairs.
     * @return boolean
     */
    private function readConfigFile() {
        if(!$fp = fopen($this->ConfigFile, "r")) {
            $this->raiseError(907, "Could not open " . $this->ConfigFile);
            return false;
        }
        while(!feof($fp)) {
            $lineno = 1;
            $oneline = fgets($fp);
            if(!$this->getValue($oneline, $lineno)) {
                return false; //error set in method.
            }
            $lineno++;
        }
        fclose($fp);
        return true;
    }
    
    /**
     * Simply sets the objects error variables.
     * @param int $errno 
     * @param string $error 
     */
    private function raiseError($errno, $error) {
        $this->ErrNo = $errno;
        $this->Error = $error;
    }
    
    /**
     * Used to get a name/value pair.
     * 
     * Used strpos() instead of a regex match since we are looking for the FIRST occurance of a single character (simpler)
     * 
     * @param string $line One line pulled from configuratin file.
     * @param int $lineno (optional) Specifies the line number within the configuration file of $line.
     * @return boolean
     */
    private function getValue($line, $lineno=0) {
        /* first, does this line have any teeth? */
        if(!$this->isComment($line)) {
            /* next we see if the key/value marker exists */
            $pos = strpos($line, "=");
            if($pos === false) {
                /* nope, send them back where they came from */
                $this->raiseError(906, "Invalid configuration data found on line " . $lineno);
                return false;
            }
            /* it's good, trim out the good stuff */
            $key = trim(substr($line,0,$pos));
            $value = trim(substr($line,$pos+1));
            
            /* create the object to hold the data */
            $oParam = new KeyValuePair($key);
            
            /* type the data and assign it to the object */
            /* not as elegant as switch might be, but too many test conditions to be pretty */
            if(is_numeric($value) && !is_float($value)) {
                $oParam->datatype = self::INT;
                $oParam->value = (int)$value;
            } elseif(is_float($value)) {
                $oParam->datatype = self::FLOAT;
                $oParam->value = (float)$value;
            } elseif((strtolower($value) == "yes" || strtolower($value) == "on" || strtolower($value) == "true" || strtolower($value) == "y") && self::ASSUME_BOOL) {
                $oParam->datatype = self::BOOLEAN;
                $oParam->value = true;
            } elseif((strtolower($value) == "no" || strtolower($value) == "off" || strtolower($value) == "false" || strtolower($value) == "n") && self::ASSUME_BOOL) {
                $oParam->datatype = self::BOOLEAN;
                $oParam->value = false;
            } else {
                $oParam->datatype = self::STRING;
                $oParam->value = (string)$value;  //cast likely not necessary, but including to ensure proper typing.
            }
            
            /* now add the object to the array of Parameters and increase the count */
            $this->arryConfigItems[$this->ParameterCount] = $oParam;
            $this->ParameterCount++;
            
            $oParam = null;
        }
        return true;
    }
    
    /**
     * Tests to determine whether the supplied parameter should be skipped (comment , or blank line)
     * @param string $line Single line from configruation file.
     * @return boolean
     */
    private function isComment($line) {
        if(trim($line) == '\n' || trim($line) == "") {
            $this->WhiteSpaceCount++;
            return true;
        }
        if(in_array(trim(substr($line,0,1)),$this->arryCommentMarkers) || in_array(trim(substr($line,0,2)),$this->arryCommentMarkers)) {
            $this->CommentCount++;
            return true;
        }
        return false;
    }
}

/**
 * KeyValuePair short summary.
 *
 * KeyValuePair is a simple object to hold the configuration items.
 *
 * @version 1.0
 * @author Jesse Quijano
 */
class KeyValuePair {
    public $name = "";
    public $value = "";
    public $datatype = 0;
    
    public function __construct($name="", $value= "") {
        $this->name = $name;
        $this->value = $value;
    }
}