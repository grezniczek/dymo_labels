<!-- DYMO Labels EM Print Modal -->
<div
    class="modal fade dlem-widget-modal"
    id="dlem-widget-modal-print"
    tabindex="-1"
    role="dialog"
    aria-labelledby="dlem-modal-print-title"
    aria-hidden="true"
    data-backdrop="static"
    data-keyboard="false"
>
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="dlem-modal-print-title">
                    <?= $fw->tt("widget_modal_title")?>: <b>
                        <span class="dlem-label-name" data-modal-content="name"></span>
                    </b><br> 
                    <span style="font-size: 80%;" data-modal-content="desc"></span>
                </h5>
                <button type="button" class="close" data-modal-action="dismiss" aria-label="<?= $fw->tt("dialog_close") ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="padding:1rem;min-height:10rem;">
                <div class="initializing" style="text-align:center;margin:3rem auto;">
                    <i class="fas fa-spinner fa-spin fa-3x"></i>
                </div>
                <div class="initialized">
                    <div class="card border-danger printers-card">
                        <div class="card-header">
                            <?= $fw->tt("pp_printers") ?>
                            <button class="float-right btn btn-xs btn-link" style="padding:0px;" data-command="refresh" data-toggle="tooltip" data-placement="top" title="<?= $fw->tt("pp_refresh") ?>"><i class="fas fa-redo-alt fa-xs"></i></button>
                        </div>
                        <div class="card-body" data-dlem-prlist>
                            <table data-dlem-printers class="printers table table-hover table-borderless" style="margin-bottom:0.5rem;margin-top:0.5rem;">
                                <tr class="printer-template">
                                    <td class="printer-select">
                                        <input type="radio" name="printer" id="" value="">
                                    </td>
                                    <td class="printer-info">
                                        <label class="printer-name" for="">Printer</label>
                                        <span class="twinturbo">
                                            &mdash; <i><?= $fw->tt("pp_roll") ?></i>
                                            <input class="printer-roll-left" type="radio" name="printer-roll" id="" value="l" checked>
                                            <label class="printer-roll-left" for=""><?= $fw->tt("pp_roll_left") ?></label>
                                            <input class="printer-roll-right" type="radio" name="printer-roll" id="" value="r">
                                            <label class="printer-roll-right" for=""><?= $fw->tt("pp_roll_right") ?></label>
                                        </span>
                                    </td>
                                    <td class="printer-status">
                                        <span class="printer-offline"><?= $fw->tt("pp_offline") ?></span>
                                    </td>
                                </tr>
                                <tr class="no-printer">
                                    <td class="text-danger" colspan="3"><?= $fw->tt("pp_noprinters") ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="card border-danger labels-card mt-3">
                        <div class="card-header">
                            <?= $fw->tt("pp_labels") ?>
                        </div>
                        <div class="card-body">
                            <table class="labels table table-hover table-borderless" style="margin-bottom:0.5rem;margin-top:0.5rem;">
                                <thead class="labels-header"></thead>
                                <tbody class="labels-body">
                                    <tr class="no-labels">
                                        <td class="no-labels">
                                            <span class="text-danger"><?= $fw->tt("pp_nolabels") ?></span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="mt-3" data-dlem-error></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-link btn-sm align-left initialized" data-command="calibrate"><?= $fw->tt("pp_calibrate") ?></button>
                <button class="btn btn-success btn-sm initialized" data-command="print"><?= $fw->tt("pp_print") ?></button>
                <button
                    type="button"
                    class="btn btn-secondary btn-sm"
                    data-modal-action="close"><?=$fw->tt("dialog_close")?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Preview -->
<div class="modal fade dlem-widget-modal" id="dlem-modal-preview" tabindex="-1" role="dialog" aria-labelledby="modal-preview-title" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md modal-dialog-scrollable modal-dialog-centered" role="dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title dlem-preview-title" id="modal-preview-title">
                    <b><?= $fw->tt("pp_preview") ?></b>
                </h5>
                <button type="button" class="close" data-modal-action="close" aria-label="<?= $fw->tt("dialog_close") ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body label-preview">
                <img class="label-preview" />
            </div>
            <div class="modal-footer">
                <button type="button" data-command="print-single" class="btn btn-secondary btn-sm"><?= $fw->tt("pp_printsingle") ?></button>
                <button type="button" class="btn btn-primary btn-sm" data-modal-action="close"><?= $fw->tt("dialog_close") ?></button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Calibrate -->
<div class="modal fade dlem-widget-modal" id="dlem-modal-calibrate" tabindex="-1" role="dialog" aria-labelledby="modal-calibrate-title" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-md modal-dialog-scrollable modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title dlem-calibrate-title" id="modal-calibrate-title">
                    <b><?= $fw->tt("pp_calibrate") ?></b>
                </h5>
                <button type="button" class="close" data-modal-action="cancel" aria-label="<?= $fw->tt("dialog_close") ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><?= $fw->tt("pp_calibration_info") ?></p>
                <form>
                    <div class="form-group">
                        <label for="cal_dx"><?= $fw->tt("pp_offsetdx") ?></label>
                        <input type="number" min="-30" max="30" class="form-control" id="offset-dx" value >
                    </div>
                    <div class="form-group">
                        <label for="cal_dy"><?= $fw->tt("pp_offsetdy") ?></label>
                        <input type="number" min="-30" max="30" class="form-control" id="offset-dy" value >
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-modal-action="cancel">
                    <?= $fw->tt("dialog_cancel") ?>
                </button>
                <button type="button" class="btn btn-success btn-sm" data-modal-action="apply" data-dismiss="modal">
                    <?= $fw->tt("dialog_apply") ?>
                </button>
            </div>
        </div>
    </div>
</div>
