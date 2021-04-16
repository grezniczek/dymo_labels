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
                        <input type="text" data-input-control="name" class="form-control form-control-sm" aria-describedby="dlem-name-desc" id="dlem-name" name="name" required />
                    </div>
                </div>
                <div class="form-group">
                    <div class="dlem-label">
                        <label for="dlem-desc"><?= $fw->tt("setup_labeldesc") ?></label>
                    </div>
                    <div class="dlem-description" id="dlem-desc-desc"><?= $fw->tt("setup_hint_labeldesc") ?></div>
                    <div class="dlem-field">
                        <textarea rows="1" data-input-control="desc" class="form-control form-control-sm autosize" aria-describedby="dlem-desc-desc" id="dlem-desc" name="desc"></textarea>
                    </div>
                </div>
                <div class="form-group">
                    <div class="dlem-label">
                        <label for="dlem-file"><?= $fw->tt("setup_labelfile") ?>DYMO Label File</label>
                    </div>
                    <div class="dlem-description" id="dlem-file-desc">
                        <?= $fw->tt("setup_hint_labelfile") ?>
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
                        <input pattern="[A-Za-z0-9]" type="text" data-input-control="objectname" class="form-control form-control-sm" aria-describedby="dlem-elo-name-desc" id="dlem-elo-name" name="name" required />
                    </div>
                </div>
                <div class="form-group">
                    <div class="dlem-label">
                        <label for="dlem-elo-desc"><?= $fw->tt("setup_labelobjectdesc") ?></label>
                    </div>
                    <div class="dlem-description" id="dlem-elo-desc-desc"><?= $fw->tt("setup_hint_labelobjectdesc") ?></div>
                    <div class="dlem-field">
                        <textarea rows="1" data-input-control="objectdesc" class="form-control form-control-sm autosize" aria-describedby="dlem-elo-desc-desc" id="dlem-elo-desc" name="desc"></textarea>
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
                        <input pattern="[A-Za-z0-9]" type="text" data-input-control="name" class="form-control form-control-sm" aria-describedby="dlem-rl-name-desc" id="dlem-rl-name" name="name" required />
                    </div>
                </div>
                <div class="form-group">
                    <div class="dlem-label">
                        <label for="dlem-rl-desc"><?= $fw->tt("setup_labeldesc") ?></label>
                    </div>
                    <div class="dlem-description" id="dlem-rl-desc-desc"><?= $fw->tt("setup_hint_labeldesc") ?></div>
                    <div class="dlem-field">
                        <textarea rows="1" data-input-control="desc" class="form-control form-control-sm autosize" aria-describedby="dlem-rl-desc-desc" id="dlem-rl-desc" name="desc"></textarea>
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
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered" role="document">
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
                <p><?= $fw->tt("setup_info_hinttarget") ?></p>
                <p><?= $fw->tt("setup_info_hintvalue") ?></p>
                <textarea class="form-control dlem-actiontag" rows="12" data-modal-content-html="tag"></textarea>
                <div class="d-none" data-public-endpoint-active>
                    <p class="mt-3"><b><?= $fw->tt("setup_info_publictitle") ?></b></p>
                    <p><?= $fw->tt("setup_info_publictext") ?></p>
                    <input class="form-control form-control-sm dlem-link" type="text" data-modal-value="link">
                </div>
            </div>
            <div class="modal-footer">
                <div class="dlem-label-id align-left" data-modal-content="id"></div>
                <button
                    type="button"
                    class="btn btn-success btn-sm d-none" data-public-endpoint-active
                    data-modal-action="copy-link"><i class="fas fa-copy"></i> <?= $fw->tt("setup_info_copylink") ?></button>
                <button
                    type="button"
                    class="btn btn-success btn-sm"
                    data-modal-action="copy-tag"><i class="fas fa-copy"></i> <?= $fw->tt("setup_info_copytag") ?></button>
                <button
                    type="button"
                    class="btn btn-secondary btn-sm"
                    data-modal-action="dismiss"><?=$fw->tt("setup_dismiss")?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Print -->
<div
    class="modal fade"
    id="modal-print"
    tabindex="-1"
    role="dialog"
    aria-labelledby="modal-print-title"
    aria-hidden="true"
    data-backdrop="static"
    data-keyboard="false"
>
    <div class="modal-dialog modal-md modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title dlem-print-title" id="modal-print-title">
                    <?= $fw->tt("setup_print_title")?>: <b>
                        <span class="dlem-label-name" data-modal-content="name"></span>
                    </b><br> 
                    <span class="dlem-label-desc" data-modal-content="desc"></span>
                </h5>
                <button type="button" class="close" data-modal-action="cancel" aria-label="<?= $fw->tt("dialog_close") ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div data-label-objects class="modal-body">
                <div class="form-group">
                    <b><label for="dlem-print-range"><?= $fw->tt("setup_print_range") ?></label></b>
                    <input type="text" data-print-range class="form-control form-control-sm" aria-describedby="dlem-print-range" id="dlem-print-range"></textarea>
                </div>
                <hr>
                <div data-item-template class="form-group">
                    <i data-labelobject-type="Graphic" class="fas fa-file-image"></i><i data-labelobject-type="Text" class="fas fa-file-alt"></i> <b><label data-labelobject-name for="">Label</label></b> <i>(<?= $fw->tt("setup_config_transform") ?> <span data-labelobject-transform>Datamatrix</span>)</i>
                    <textarea data-input-control="" class="form-control form-control-sm" aria-describedby="" id="" rows="1"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <div class="dlem-label-id align-left" data-modal-content="id"></div>
                <button
                    type="button"
                    class="btn btn-secondary btn-sm"
                    data-modal-action="cancel"><?=$fw->tt("dialog_cancel")?></button>
                <button
                    type="button"
                    class="btn btn-primary btn-sm"
                    data-modal-action="setup-print"><?= $fw->tt("pp_printsingle") ?></button>
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
