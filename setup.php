<?php namespace DE\RUB\DYMOLabelsExternalModule;

use DE\RUB\REDCapEMLib\Crypto;

if (!class_exists("\DE\RUB\REDCapEMLib\Project")) include_once ("classes/Project.php");
if (!class_exists("\DE\RUB\REDCapEMLib\Crypto")) include_once ("classes/Crypto.php");

/**
 * This is setup plugin page
 */
class setupPluginPage { 
    
    /**
     * Processes the request.
     * @param DYMOLabelsExternalModule $m 
     * @return void 
     */
    static function process($m) {

        $fw = $m->framework;
        $pid = $fw->getProjectId();

        $m->includeCSS("css/dymo-labels.css");
        $m->includeCSS("css/3rd-party/datatables.min.css");

        $m->includeJS("js/3rd-party/dymo.connect.framework.js");
        $m->includeJS("js/3rd-party/datatables.min.js");
        $m->includeJS("js/3rd-party/autosize.min.js");
        $m->includeJS("js/3rd-party/bs-custom-file-input.min.js");
        $m->includeJS("js/dlem-config.js");

        // Ajax Setup.
        $crypto = Crypto::init($m);
        $ajax = array(
            "verification" => $crypto->encrypt(array(
                "random" => $crypto->genKey(),
                "userid" => $GLOBALS["userid"],
                "pid" => $pid,
                "timestamp" => time(),
            )),
            "endpoint" => $fw->getUrl("ajax.php")
        );

        $labels = $m->getLabels();

        // Prepare configuration data
        $configSettings = array(
            "debug" => $fw->getProjectSetting("js-debug") == true,
            "canDownload" => $fw->getProjectSetting("allow-download") == true,
            "ajax" => $ajax,
            "strings" => array (
                "chooseFile" => $fw->tt("setup_choosefile"),
                "nameRequired" => $fw->tt("setup_namerequired"),
                "actionInfo" => $fw->tt("setup_action_info"),
                "actionRename" => $fw->tt("setup_action_rename"),
                "actionConfigure" => $fw->tt("setup_action_configure"),
                "actionDownload" => $fw->tt("setup_action_download"),
                "actionPrint" => $fw->tt("setup_action_print"),
                "actionDelete" => $fw->tt("setup_action_delete"),
                "toastLabelUpdated" => $fw->tt("setup_toast_labelupdated"),
                "toastLabelAdded" => $fw->tt("setup_toast_labeladded"),
                "toastLabelRenamed" => $fw->tt("setup_toast_labelrenamed"),
                "toastLabelDeleted" => $fw->tt("setup_toast_labeldeleted"),
                "actionTagReplace" => $fw->tt("setup_info_actiontagreplace"),
                "widgetLabel" => $fw->tt("widget_label"),
            ),
            "labels" => $labels,
        );


?>
<div class="dymo-labels-container">
    <h3><?= $fw->tt("module_name")?></h3>
    <p><?= $fw->tt("setup_intro")?></p>
    <p><button type="button" data-command="add-new-label" class="btn btn-xs btn-rcgreen fs13"><i class="fa fa-plus"></i> <?= $fw->tt("setup_addnewlabel")?></button></p>
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



<p>
    <a href="<?=$fw->getUrl("post.php", true)?>">POST endpoint</a>
</p>

<?php
foreach($labels as $l) {
    print "<p><a target=\"_blank\" href=\"{$fw->getUrl("public.php", true)}&template={$l["id"]}\">{$l["name"]}</a></p>";
}
print "<p><a target=\"_blank\" href=\"{$fw->getUrl("public.php", true)}&template=18fcf868-3347-45a6-9f1d-f99a5a7686ea&T_CODE=12345678&T_TEXT=Text&T_DATE=30.01.2021&DM_DMLABEL=12345678&R_DMCAP&T_CODECAP=1234\\n6789&T_PROJECT=BBMHH&range=COPY:1-3\">BBMHH Test Label</a></p>";




?>

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
    <div class="modal-dialog modal-md modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-addNew-title">
                    <b><?= $fw->tt("setup_addnewlabel")?></b>
                </h5>
                <button type="button" class="close" data-modal-action="cancel" aria-label="<?= $fw->tt("dialog_close") ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="dlem-label">
                        <label for="dlem-name"><?= $fw->tt("setup_labelname") ?></label>
                    </div>
                    <div class="dlem-description" id="dlem-name-desc"><?= $fw->tt("setup_hint_labelname") ?></div>
                    <div class="dlem-field">
                        <input type="text" data-input-control="name" class="form-control" aria-describedby="dlem-name-desc" id="dlem-name" name="name" required />
                    </div>
                </div>
                <div class="form-group">
                    <div class="dlem-label">
                        <label for="dlem-desc"><?= $fw->tt("setup_labeldesc") ?></label>
                    </div>
                    <div class="dlem-description" id="dlem-desc-desc"><?= $fw->tt("setup_hint_labeldesc") ?></div>
                    <div class="dlem-field">
                        <textarea rows="1" data-input-control="desc" class="form-control autosize" aria-describedby="dlem-desc-desc" id="dlem-desc" name="desc"></textarea>
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
                            <input type="file" data-input-control="filename" class="custom-file-input" id="dlem-labelfile" required />
                            <label class="custom-file-label" for="dlem-labelfile"><?=$fw->tt("setup_choosefile")?></label>
                        </div>
                    </div>
                    <div class="dlem-alert alert-danger" id="dlem-labelfile-invalid" role="alert">
                        <?=$fw->tt("setup_invalidlabel")?>
                    </div> 
                    <div class="dlem-alert alert-success" id="dlem-labelfile-valid" role="alert">
                        <?=$fw->tt("setup_validlabel")?>
                    </div> 
                </div>
            </div>
            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary btn-sm"
                    data-modal-action="cancel"><?=$fw->tt("setup_cancel")?></button>
                <button type="button" data-dlem-action="add-label" class="btn btn-primary btn-sm" data-modal-action="add"><span class="when-enabled"><?=$fw->tt("setup_addlabel")?></span><span class="when-disabled"><i class="fas fa-spinner fa-spin"></i></span></button>
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
    <div class="modal-dialog modal-md modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-delete-title">
                    <b><?= $fw->tt("setup_deletelabel")?></b>
                </h5>
                <button type="button" class="close" data-modal-action="cancel" aria-label="<?= $fw->tt("dialog_close") ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="dlem-label-name" data-modal-content="name"></p>
                <p class="dlem-label-id" data-modal-content="id"></p>
                <p><?=$fw->tt("setup_confirmdeletetext")?></p>
            </div>
            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary btn-sm"
                    data-modal-action="cancel"><?=$fw->tt("setup_cancel")?></button>
                <button type="button" class="btn btn-danger btn-sm show-spinner" data-modal-action="confirm"><span class="when-enabled"><?=$fw->tt("setup_deletelabel")?></span><i class="fas fa-spinner fa-pulse when-disabled"></i></button>
            </div>
        </div>
    </div>
</div>


<!-- Modal: Error -->
<div
    class="modal fade"
    id="modal-error"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modal-error-title"
    aria-hidden="true"
    data-backdrop="static"
    data-keyboard="false"
    style="z-index: 99999;"
>
    <div class="modal-dialog modal-sm modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title dlem-error-title" id="modal-error-title">
                    <b><?= $fw->tt("setup_error_title")?></b>
                </h5>
                <button type="button" class="close" data-modal-action="dismiss" aria-label="<?= $fw->tt("dialog_close") ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><?=$fw->tt("setup_error_occured")?></p>
                <p class="dlem-error-msg" data-modal-content="error"></p>
            </div>
            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary btn-sm"
                    data-modal-action="dismiss"><?=$fw->tt("setup_dismiss")?></button>
            </div>
        </div>
    </div>
</div>


<!-- Modal: Config -->
<div
    class="modal fade"
    id="modal-config"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modal-config-title"
    aria-hidden="true"
    data-backdrop="static"
    data-keyboard="false"
>
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title dlem-config-title" id="modal-config-title">
                    <?= $fw->tt("setup_config_title")?>: <b>
                        <span class="dlem-label-name" data-modal-content="name"></span>
                    </b><br> 
                    <span class="dlem-label-desc" data-modal-content="desc"></span>
                </h5>
                <button type="button" class="close" data-modal-action="cancel" aria-label="<?= $fw->tt("dialog_close") ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-header">
                <div class="custom-control custom-switch">
                    <input data-modal-content="public" type="checkbox" class="custom-control-input" id="label-public">
                    <label class="custom-control-label" for="label-public"><?= $fw->tt("setup_config_public") ?></label>
                </div>
            </div>
            <div class="modal-body">
                <div class="dlem-no-labelobjects"><i><?= $fw->tt("setup_config_nolabelobjects") ?></i></div>
                <div class="mb-3 d-none" data-template="image-object" data-object-type="Graphic">
                    <div class="form-inline">
                        <button data-edit-action="" class="btn btn-sm btn-dark">
                            <i class="fas fa-file-image"></i>
                            <span data-content="name" data-toggle="tooltip" data-placement="top" title="Tooltip">
                                Object Name
                            </span>
                        </button>
                        <div class="form-control-text ml-2">
                            <?= $fw->tt("setup_config_transform") ?>
                        </div>
                        <select data-content="transform" class="form-control form-control-sm ml-2">
                            <option value="DM">Datamatrix</option>
                            <option value="PNG">PNG</option>
                            <option value="QR">QR Code</option>
                            <option value="R"><?= $fw->tt("setup_config_transform_remove") ?></option>
                        </select>
                        <div class="custom-control custom-switch ml-3">
                            <input type="checkbox" class="custom-control-input" data-content="readonly" id="">
                            <label class="custom-control-label" for=""><?= $fw->tt("setup_config_readonly") ?></label>
                        </div>
                        <div class="custom-control custom-switch ml-2">
                            <input type="checkbox" class="custom-control-input" data-content="allowempty" id="">
                            <label class="custom-control-label" for=""><?= $fw->tt("setup_config_allowempty") ?></label>
                        </div>
                    </div>
                    <div class="form-group mt-2">
                        <textarea placeholder="<?= $fw->tt("setup_config_defaultvalue") ?>" title="<?= $fw->tt("setup_config_defaultvalue") ?>" data-toggle="tooltip" data-placement="top" data-content="default" rows="2" class="form-control form-control-sm">This is a default value</textarea>
                    </div>
                </div>
                <div class="mb-3 d-none" data-template="text-object" data-object-type="Text">
                    <div class="form-inline">
                        <button data-edit-action="" class="btn btn-sm btn-dark">
                            <i class="fas fa-file-alt"></i>
                            <span data-content="name" data-toggle="tooltip" data-placement="top" title="Tooltip">
                                Object Name
                            </span>
                        </button>
                        <div class="form-control-text ml-2">
                            <?= $fw->tt("setup_config_transform") ?>
                        </div>
                        <select data-content="transform" class="form-control form-control-sm ml-2">
                            <option value="T"><?= $fw->tt("setup_config_transform_text") ?></option>
                            <option value="R"><?= $fw->tt("setup_config_transform_remove") ?></option>
                        </select>
                        <div class="custom-control custom-switch ml-3">
                            <input type="checkbox" class="custom-control-input" data-content="readonly" id="">
                            <label class="custom-control-label" for=""><?= $fw->tt("setup_config_readonly") ?></label>
                        </div>
                        <div class="custom-control custom-switch ml-2">
                            <input type="checkbox" class="custom-control-input" data-content="multiline" id="">
                            <label class="custom-control-label" for=""><?= $fw->tt("setup_config_multiline") ?></label>
                        </div>
                        <div class="custom-control custom-switch ml-2">
                            <input type="checkbox" class="custom-control-input" data-content="allowempty" id="">
                            <label class="custom-control-label" for=""><?= $fw->tt("setup_config_allowempty") ?></label>
                        </div>
                    </div>
                    <div class="form-group mt-2">
                        <textarea placeholder="<?= $fw->tt("setup_config_defaultvalue") ?>" title="<?= $fw->tt("setup_config_defaultvalue") ?>" data-toggle="tooltip" data-placement="top" data-content="default" rows="1" class="form-control form-control-sm">This is a default value</textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="dlem-label-id align-left" data-modal-content="id"></div>
                <button
                    type="button"
                    class="btn btn-secondary btn-sm"
                    data-modal-action="cancel"><?=$fw->tt("setup_cancel")?></button>
                <button
                    type="button"
                    class="btn btn-primary btn-sm"
                    data-modal-action="save"><span class="when-enabled"><?=$fw->tt("setup_save")?></span><span class="when-disabled"><i class="fas fa-spinner fa-spin"></i></span></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Rename label object name -->
<div
    class="modal fade"
    id="modal-editlabelobject"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modal-editlabelobject-title"
    aria-hidden="true"
    data-backdrop="static"
    data-keyboard="false"
>
    <div class="modal-dialog modal-md modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-editlabelobject-title">
                    <?= $fw->tt("setup_config_editlabelobject")?>
                    <b><span class="dlem-label-name" data-modal-content="objectname"></span></b>
                </h5>
                <button type="button" class="close" data-modal-action="cancel" aria-label="<?= $fw->tt("dialog_close") ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="dlem-label">
                        <label for="dlem-elo-name"><?= $fw->tt("setup_labelobjectname") ?></label>
                    </div>
                    <div class="dlem-description" id="dlem-elo-name-desc"><?= $fw->tt("setup_hint_labelobjectname") ?></div>
                    <div class="dlem-field">
                        <input pattern="[A-Za-z0-9]" type="text" data-input-control="objectname" class="form-control" aria-describedby="dlem-elo-name-desc" id="dlem-elo-name" name="name" required />
                    </div>
                </div>
                <div class="form-group">
                    <div class="dlem-label">
                        <label for="dlem-elo-desc"><?= $fw->tt("setup_labelobjectdesc") ?></label>
                    </div>
                    <div class="dlem-description" id="dlem-elo-desc-desc"><?= $fw->tt("setup_hint_labelobjectdesc") ?></div>
                    <div class="dlem-field">
                        <textarea rows="1" data-input-control="objectdesc" class="form-control autosize" aria-describedby="dlem-elo-desc-desc" id="dlem-elo-desc" name="desc"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button
                    type="button"
                    class="btn btn-secondary btn-sm"
                    data-modal-action="cancel"><?=$fw->tt("setup_cancel")?></button>
                <button type="button" class="btn btn-primary btn-sm" data-modal-action="edit-labelobject"><span class="when-enabled"><?=$fw->tt("setup_save")?></span><span class="when-disabled"><i class="fas fa-spinner fa-spin"></i></span></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Rename label -->
<div
    class="modal fade"
    id="modal-renamelabel"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modal-renamelabel-title"
    aria-hidden="true"
    data-backdrop="static"
    data-keyboard="false"
>
    <div class="modal-dialog modal-md modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modal-renamelabel-title">
                    <b><?= $fw->tt("setup_config_renamelabel")?></b>
                </h5>
                <button type="button" class="close" data-modal-action="cancel" aria-label="<?= $fw->tt("dialog_close") ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="dlem-label">
                        <label for="dlem-rl-name"><?= $fw->tt("setup_labelname") ?></label>
                    </div>
                    <div class="dlem-description" id="dlem-rl-name-desc"><?= $fw->tt("setup_hint_labelname") ?></div>
                    <div class="dlem-field">
                        <input pattern="[A-Za-z0-9]" type="text" data-input-control="name" class="form-control" aria-describedby="dlem-rl-name-desc" id="dlem-rl-name" name="name" required />
                    </div>
                </div>
                <div class="form-group">
                    <div class="dlem-label">
                        <label for="dlem-rl-desc"><?= $fw->tt("setup_labeldesc") ?></label>
                    </div>
                    <div class="dlem-description" id="dlem-rl-desc-desc"><?= $fw->tt("setup_hint_labeldesc") ?></div>
                    <div class="dlem-field">
                        <textarea rows="1" data-input-control="desc" class="form-control autosize" aria-describedby="dlem-rl-desc-desc" id="dlem-rl-desc" name="desc"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <div class="dlem-label-id align-left" data-modal-content="id"></div>
                <button
                    type="button"
                    class="btn btn-secondary btn-sm"
                    data-modal-action="cancel"><?=$fw->tt("setup_cancel")?></button>
                <button type="button" class="btn btn-primary btn-sm" data-modal-action="save"><span class="when-enabled"><?=$fw->tt("setup_save")?></span><span class="when-disabled"><i class="fas fa-spinner fa-spin"></i></span></button>
            </div>
        </div>
    </div>
</div>


<!-- Modal: Info -->
<div
    class="modal fade"
    id="modal-info"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modal-info-title"
    aria-hidden="true"
    data-backdrop="static"
    data-keyboard="false"
>
    <div class="modal-dialog modal-md modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title dlem-info-title" id="modal-info-title">
                    <?= $fw->tt("setup_info_title")?>: <b>
                        <span class="dlem-label-name" data-modal-content="name"></span>
                    </b><br> 
                    <span class="dlem-label-desc" data-modal-content="desc"></span>
                </h5>
                <button type="button" class="close" data-modal-action="dismiss" aria-label="<?= $fw->tt("dialog_close") ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><?= $fw->tt("setup_info_text") ?></p>
                <textarea class="form-control dlem-actiontag" rows="10" data-modal-content-html="tag"></textarea>
                <p><?= $fw->tt("setup_info_hinttarget") ?></p>
                <p><?= $fw->tt("setup_info_hintvalue") ?></p>
            </div>
            <div class="modal-footer">
                <div class="dlem-label-id align-left" data-modal-content="id"></div>
                <button
                    type="button"
                    class="btn btn-success btn-sm"
                    data-modal-action="copy"><i class="fas fa-copy"></i></button>
                <button
                    type="button"
                    class="btn btn-secondary btn-sm"
                    data-modal-action="dismiss"><?=$fw->tt("setup_dismiss")?></button>
            </div>
        </div>
    </div>
</div>

<!-- Success toast -->
<div class="position-fixed bottom-0 right-0 p-3" style="z-index: 99999; right: 0; bottom: 0;">
    <div id="dlem-successToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true" data-delay="2000" data-animation="true" data-autohide="true">
        <div class="toast-header">
            <svg class="bd-placeholder-img rounded mr-2" width="20" height="20" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" preserveAspectRatio="xMidYMid slice" focusable="false"><rect width="100%" height="100%" fill="#28a745"></rect></svg>
            <strong class="mr-auto"><?= $fw->tt("setup_toast_success") ?></strong>
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="<?= $fw->tt("dialog_close") ?>">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="toast-body" data-content="toast"></div>
    </div>
</div>

<?php
    }
}
setupPluginPage::process($module);
