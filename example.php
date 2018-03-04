<?php
require_once("include/cls_ConfigReader.php");

$oReader = new ConfigReader();

print "Object instantiated.";

if(!$oReader->setConfigFile("config/sample_config.ini")) {
    print "<br />Couldn't load config file: " . $oReader->Error;
    exit();
}
print"<br />Configuration file loaded.";

print "<br /><br /><strong>Let's output some meta data:</strong>";
print "<br />File size: " . $oReader->getConfigFileSize() . " bytes";
print "<br />Number of lines: " . $oReader->getConfigLineCount();

if(!$oReader->loadConfigFile()) {
    print "!!!";
    print "<br />" . $oReader->Error;
    exit();
}

print "<br />The config file contains " . $oReader->getCommentCount() . " comments.";
print "<br />The config file contains " . $oReader->getBlankLineCount() . " blank lines.";
print "<br />The file contained " . $oReader->getParameterCount() . " valid configuration parameters";

print "<strong><br /><br />Output name/value pairs:</strong>";
for($i=0 ; $i < $oReader->getParameterCount() ; $i++) {
    //$odb = new dBug($oReader->Parameter($i));
    print "<br />" . $oReader->Parameter($i)->name . "->" . $oReader->Parameter($i)->value;
    if($oReader->Parameter($i)->datatype == $oReader::BOOLEAN) {
        print "<br />... It's a bool! (output 1 (true) or blank (false)";
    }
}

print "<br /><br /><strong>Now we will get a parameter by name (server_id):</strong>";
$val = $oReader->getParameterByName("server_id");
if($val === false) {
    print "<br />The requested configuration parameter was not found!";
    exit();
}
print "<br />The value is: " . $val;

print "<br /><br /><strong>And now we'll search for a value that doesn't exist (server_dead):</strong>";
$val = $oReader->getParameterByName("server_dead");
if($val === false) {
    print "<br />The requested configuration parameter was not found!";
    //exit();  //let the script continue for more examples.
}

$oReader = null;

print "<br /><br /><strong>Let's try to load a file that doesn't exist:</strong>";
$oReader = new ConfigReader("/path/thisfileisnothere.ini");
if($oReader->ErrNo) {
    print "<br />" . $oReader->Error;
}

?>