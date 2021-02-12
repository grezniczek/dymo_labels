<?php
/** @var DE\RUB\DYMOLabelsExternalModule\DYMOLabelsExternalModule $module */

use DE\RUB\REDCapEMLib\Project;

if (!class_exists("\DE\RUB\REDCapEMLib\Project")) include_once ("classes/Project.php");

$fw = $module->framework;
$pid = $fw->getProjectId();

$module->includeCSS("css/dymo-labels.css");
$module->includeCSS("css/datatables.min.css");
$module->includeJS("js/datatables.min.js");



// Get a list of available label files.
$mpid = $fw->getSystemSetting("system-management-project");
$mp = Project::get($fw, $mpid);

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
<div class="dymo-labels-container">
    <h3><?= $fw->tt("module_name")?></h3>
    <p><?= $fw->tt("projadmin_intro")?></p>
    <p><button class="btn btn-xs btn-rcgreen fs13"><i class="fa fa-plus"></i> <?= $fw->tt("projadmin_addnewlabel")?></button></p>
    <table id="dymo-labels" class="table table-striped table-bordered table-hover" style="width:100%">
        <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">ID</th>
                <th scope="col">Name</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th scope="row">1</th>
                <td>AX2C</td>
                <td>
                    A quite long label name
                </td>
                <td>
                    Configure | View | Remove
                </td>
            </tr>
        </tbody>
    </table>
</div>



<script>
    $(function() {
        $('#dymo-labels').DataTable();
    })
</script>