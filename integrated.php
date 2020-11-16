<?php
/** @var DE\RUB\DYMOLabelsExternalModule\DYMOLabelsExternalModule $module */
$fw = $module->framework;
$pid = $fw->getProjectId();
$js_includes = array ($fw->getUrl("js/bs-custom-file-input.min.js"));

// Get a list of available label files.
$mpid = $fw->getSystemSetting("system-management-project");
$mp = $fw->getProject2($mpid);
$record_ids = $mp->getRecordIds("[integrated]<>'disabled'");
$labels = array();
foreach ($record_ids as $record_id) {
    $record = $mp->getRecord($record_id);
    $data = $record->getFieldValues(["name", "desc", "integrated", "whitelist", "file"]);
    $add = $data["integrated"][1] == "all";
    if (!$add) {
        $whitelist = array();
        foreach(explode(",", $data["whitelist"][1]) as $item) {
            $whitelist[] = trim($item);
        }
        $add = in_array($pid, $whitelist);
    }
    if ($add) {
        $labels[] = array(
            "id" => $record_id,
            "name" => $data["name"][1],
            "desc" => $data["desc"][1],
            "file" => $data["file"][1]
        );
    }
}

?>
<h3>DYMO LabelWriter Integration</h3>
<form>
<div class="custom-file">
  <input type="file" class="custom-file-input" id="customFile">
  <label class="custom-file-label" for="customFile">Choose file</label>
</div>
</form>
<pre>
    <?=print_r($labels)?>
</pre>
<?php 
foreach ($js_includes as $js) {
    print "<script src=\"{$js}\"></script>";
}
?>
<script>
    $(document).ready(function () {
        bsCustomFileInput.init()
    })
</script>