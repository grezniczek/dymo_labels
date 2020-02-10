<?php
$fw = $module->framework;
$pid = $fw->getProjectId();

// Get a list of available label files.
$mpid = $fw->getSystemSetting("system-management-project");
$mpfields = explode(",", "record_id,name,desc,integrated,whitelist,public");
$rawdata = REDCap::getData($mpid, "array", null, $mpfields, null, null, false, false, false, "[integrated]<>'disabled'", false, false);
$labels = array();
foreach ($rawdata as $key => $value) {
    $data = array_pop($value);
    if ($data["integrated"] == "all") {
        unset($data["integrated"]);
        unset($data["whitelist"]);
        $labels[] = $data;
    }
    else if ($data["integrated"] == "whitelist") {
        $whitelist = array();
        foreach(explode(",", $data["whitelist"]) as $item) {
            $whitelist[] = trim($item);
        }
        unset($data["integrated"]);
        unset($data["whitelist"]);
        if (in_array($pid, $whitelist)) $labels[] = $data;
    }
}


?>
<h3>DYMO LabelWriter Integration</h3>
<p>
    Coming soon ...
</p>
<pre>
    <?=print_r($labels)?>
</pre>