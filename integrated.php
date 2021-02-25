<?php namespace DE\RUB\DYMOLabelsExternalModule;

use DE\RUB\REDCapEMLib\Project;
use DE\RUB\REDCapEMLib\Crypto;

/** @var DYMOLabelsExternalModule $module */

if (!class_exists("\DE\RUB\REDCapEMLib\Project")) include_once ("classes/Project.php");
if (!class_exists("\DE\RUB\REDCapEMLib\Crypto")) include_once ("classes/Crypto.php");

$fw = $module->framework;
$pid = $fw->getProjectId();

$module->includeCSS("css/dymo-labels.css");
$module->includeCSS("css/3rd-party/datatables.min.css");

$module->includeJS("js/3rd-party/datatables.min.js");
$module->includeJS("js/3rd-party/autosize.min.js");
$module->includeJS("js/3rd-party/bs-custom-file-input.min.js");
$module->includeJS("js/dlem.js");

// Ajax Setup.
$crypto = Crypto::init();
$ajax = array(
    "verification" => $crypto->encrypt(array(
        "random" => $crypto->genKey(),
        "userid" => $GLOBALS["userid"],
        "pid" => $pid,
        "timestamp" => time(),
    )),
    "endpoint" => $fw->getUrl("ajax.php")
);

$labels = $module->getLabels();

// Prepare configuration data
$configSettings = array(
    "debug" => $fw->getProjectSetting("js-debug") == true,
    "ajax" => $ajax,
    "strings" => array (
        "chooseFile" => $fw->tt("projadmin_choosefile"),
        "nameRequired" => $fw->tt("projadmin_namerequired"),
    ),
    "labels" => $labels,
)

?>
<div class="dymo-labels-container">
    <h3><?= $fw->tt("module_name")?></h3>
    <p><?= $fw->tt("projadmin_intro")?></p>
    <p><button type="button" data-toggle="modal" data-target="#modal-addNew" class="btn btn-xs btn-rcgreen fs13"><i class="fa fa-plus"></i> <?= $fw->tt("projadmin_addnewlabel")?></button></p>
    <table id="dlem-labels" class="table table-striped table-bordered table-hover" style="width:100%">
        <thead>
            <tr>
                <th scope="col">Name</th>
                <th scope="col">Description</th>
                <th scope="col">Actions</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>

<script>$(function() { window.ExternalModules.DYMOLabelConfig_init(<?=json_encode($configSettings)?>) });</script>

<!-- Modal: Add a new label -->
<div
    class="modal fade"
    id="modal-addNew"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modal-addNew-title"
    aria-hidden="true"
    data-backdrop="static"
    data-keyboard="false"
>
    <div class="modal-dialog modal-md modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-addNew-title">
                    <b><?= $fw->tt("projadmin_addnewlabel")?></b>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="dlem-label">
                        <label for="dlem-name">Name</label>
                    </div>
                    <div class="dlem-description" id="dlem-name-desc">
                        A name that identifies this label. This is shown in the list of labels in the label manager.
                    </div>
                    <div class="dlem-field">
                        <input type="text" class="form-control" aria-describedby="dlem-name-desc" id="dlem-name" name="name" required />
                    </div>
                </div>
                <div class="form-group">
                    <div class="dlem-label">
                        <label for="dlem-desc">Description</label>
                    </div>
                    <div class="dlem-description" id="dlem-desc-desc">
                        A short description for this label. This is shown as hover text in certain places (list of labels in the label manager, label widget).
                    </div>
                    <div class="dlem-field">
                        <textarea rows="1" class="form-control autosize" aria-describedby="dlem-desc-desc" id="dlem-desc" name="desc"></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <div class="dlem-label">
                        <label for="dlem-file">DYMO Label File</label>
                    </div>
                    <div class="dlem-description" id="dlem-file-desc">
                        The DYMO Label file that is used as a template for the labels.
                    </div>
                    <div class="dlem-field">
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="dlem-labelfile" required />
                            <label class="custom-file-label" for="dlem-labelfile"><?=$fw->tt("projadmin_choosefile")?></label>
                        </div>
                    </div>
                    <div class="dlem-alert alert-danger" id="dlem-labelfile-invalid" role="alert">
                        <?=$fw->tt("projadmin_invalidlabel")?>
                    </div> 
                    <div class="dlem-alert alert-success" id="dlem-labelfile-valid" role="alert">
                        <?=$fw->tt("projadmin_validlabel")?>
                    </div> 
                </div>
            </div>
            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary btn-sm"
                    data-dismiss="modal"><?=$fw->tt("projadmin_cancel")?></button>
                <button type="button" data-dlem-action="add-label" class="btn btn-primary btn-sm" data-dismiss="modal"><?=$fw->tt("projadmin_addlabel")?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Delete label -->
<div
    class="modal fade"
    id="modal-delete"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modal-delete-title"
    aria-hidden="true"
    data-backdrop="static"
    data-keyboard="false"
>
    <div class="modal-dialog modal-md modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-delete-title">
                    <b><?= $fw->tt("projadmin_deletelabel")?></b>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="dlem-delete-name" data-dlem-content="name"></p>
                <p class="dlem-delete-id" data-dlem-content="id"></p>
                <p><?=$fw->tt("projadmin_confirmdeletetext")?></p>
            </div>
            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary btn-sm"
                    data-dismiss="modal"><?=$fw->tt("projadmin_cancel")?></button>
                <button type="button" data-dlem-action="confirm-delete-label" class="btn btn-danger btn-sm" data-dismiss="modal"><?=$fw->tt("projadmin_deletelabel")?></button>
            </div>
        </div>
    </div>
</div>
