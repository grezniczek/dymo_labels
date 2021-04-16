<?php namespace DE\RUB\DYMOLabelsExternalModule;

use DE\RUB\REDCapEMLib\Crypto;

if (!class_exists("\DE\RUB\REDCapEMLib\Crypto")) include_once ("classes/Crypto.php");

/**
 * This is the public project endpoint
 */
class publicEndpoint
{

    /**
     * Processes the request.
     * @param DYMOLabelsExternalModule $m 
     * @return void 
     */
    static function process($m)
    {
        $fw = $m->framework;
        $pid = $fw->getProjectId();

        // Check whether access is allowed - must be in project context and public 
        if ($fw->getSystemSetting("system-block-public") == true || 
            $pid == null || 
            $fw->getProjectSetting("allow-public") !== true) {
            // It's not.
            header("HTTP/1.1 403 Forbidden");
            exit;
        }

        $method = $_SERVER['REQUEST_METHOD'];
        if ($method === 'POST') {
            // POST - expecting JSON payload
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
        } else if ($method === 'GET') {
            // GET - parse parameters
            $kvPairs = array();
            $template = null;
            $auto = false;
            $ranges = array();
            $upper = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $lower = "abcdefghijklmnopqrstuvwxyz";

            $reserved = array(
                "prefix", "page", "pid", "pnid", "instance", "NOAUTH"
            );

            foreach ($_GET as $key => $value) {
                if (in_array($key, $reserved, true)) continue;

                $key = strtoupper($key);
                switch ($key) {
                    case 'TEMPLATE':
                        $template = $value;
                        break;
                    case 'AUTO':
                        $auto = true;
                        break;
                    case 'RANGE':
                        $rparts = explode(",", $value);
                        foreach ($rparts as $rpart) {
                            $range = null;
                            if (preg_match("/([A-Z0-9]+):([A-Z]|[0-9]+)-([A-Z]|[0-9]+)/i", trim($rpart), $match) === 1) {
                                $id = $match[1];
                                $start = $match[2];
                                $end = $match[3];
                                if ((is_numeric($start) && is_numeric($end)) ||
                                    (strpos($upper, $start) !== false && strpos($upper, $end) !== false) ||
                                    (strpos($lower, $start) !== false && strpos($lower, $end) !== false)
                                ) {
                                    $range = array(
                                        "id" => $id,
                                        "start" => $start,
                                        "end" => $end
                                    );
                                }
                            }
                            if ($range != null) {
                                array_push($ranges, $range);
                            } else {
                                array_push($errors, "Invalid range: '{$rpart}'.");
                            }
                        }
                        break;
                    default:
                        if (preg_match("/^(T|DM|QR|PNG|R)_([A-Z0-9_]+)/", trim($key), $match) === 1) {
                            $pvalue = str_replace("\r", "", $value);
                            $pvalue = str_replace("\\n", "\n", $pvalue);
                            $type = (strlen(trim($value)) == 0) ? "R" : $match[1];
                            $name = $match[2];
                            $kvPair = array(
                                "name" => $name,
                                "type" => $type,
                                "value" => $pvalue,
                            );
                            array_push($kvPairs, $kvPair);
                        }
                        break;
                }
            }

            function createLabel($template, $search, $replace)
            {
                $label = array();
                foreach ($template as $tpl_kv) {
                    $kv = array(
                        "name" => $tpl_kv['name'],
                        "type" => $tpl_kv['type'],
                        "value" => str_replace($search, $replace, $tpl_kv['value'])
                    );
                    array_push($label, $kv);
                }
                return $label;
            }

            // Expand range(s)
            $labels[] = $kvPairs;
            foreach ($ranges as $range) {
                $start = $range['start'];
                $end = $range['end'];
                $alpha_map = "";
                if (strpos($upper, $start) !== false) {
                    $alpha_map = $upper;
                } elseif (strpos($lower, $start) !== false) {
                    $alpha_map = $lower;
                }
                $start = strlen($alpha_map) ? strpos($alpha_map, $start) : (int)$start;
                $end = strlen($alpha_map) ? strpos($alpha_map, $end) : (int)$end;
                $delta = $start > $end ? -1 : 1;

                // Copy over existing labels and initalize array to hold the new ones
                $unexpanded = $labels;
                $labels = array();

                $i = $start;
                while (true) {
                    $replace_with = strlen($alpha_map) ? substr($alpha_map, $i, 1) : "$i";
                    $search = "{{$range['id']}}";

                    foreach ($unexpanded as $tpl) {
                        $label = createLabel($tpl, $search, $replace_with);
                        array_push($labels, $label);
                    }

                    $i = $i + $delta;
                    // Is the loop done?
                    if ($i == $end + $delta) break;
                }
            }

            // Assemble the JSON
            $data = array(
                "template" => $template,
                "auto" => $auto,
                "labels" => $labels,
                "errors" => array(),
            );
        }

        $labels = array();

        // Perform some checks:
        // A template is required
        if (!isset($data["template"])) {
            $data["errors"][] = "Missing required parameter 'template'.";
        } else {
            // Check that it exists
            $id = $data["template"];
            $label = $m->getLabel($id);
            if ($label === false) {
                $data["errors"][] = "Label '{$id}' does not exist.";
                $data["labels"] = array();
            } else {
                $labels[$id] = $label;
            }
        }

        $autoThreshold = 5;
        $allowAuto = $m->getProjectSetting("allow-autoprint") == true && count($data["labels"]) <= $autoThreshold;
        $data["auto"] = $data["auto"] && $allowAuto;

        // Ajax Setup.
        $crypto = Crypto::init($m);
        $ajax = array(
            "verification" => $crypto->encrypt(array(
                "random" => $crypto->genKey(),
                "noauth" => "noauth",
                "pid" => $pid,
                "timestamp" => time(),
            )),
            "endpoint" => $fw->getUrl("public-ajax.php", true)
        );

        // Prepare configuration data
        $configSettings = array(
            "debug" => $fw->getProjectSetting("js-debug") == true,
            "canDownload" => false,
            "ajax" => $ajax,
            "strings" => array(
                "noPrinters" => $fw->tt("pp_noprinters"),
                "noLabels" => $fw->tt("pp_nolabels"),
                "removed" => $fw->tt("pp_removed"),
            ),
            "labels" => $labels,
            "print" => $data,
            "skipPrinting" => $m->getProjectSetting("skip-printing") == true,
        );
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $fw->tt("module_name") ?></title>
    <link rel="shortcut icon" href="<?= APP_PATH_IMAGES ?>favicon.ico">
    <link rel="stylesheet" type="text/css" media="screen,print" href="<?= APP_PATH_WEBPACK ?>css/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" integrity="sha384-B0vP5xmATw1+K9KRQjQERJvTumQW0nPEzvF6L/Z6nronJ3oUOFUFpCjEUQouq2+l" crossorigin="anonymous">
    <link href="<?= $fw->getUrl("css/print.css") ?>" rel="stylesheet" />
    <script src="<?= $fw->getUrl("js/3rd-party/dymo.connect.framework.js") ?>"></script>
    <script src="<?= $fw->getUrl("js/3rd-party/jquery-3.5.1.min.js") ?>"></script>
    <script src="<?= $fw->getUrl("js/3rd-party/bwip-js-min.js") ?>"></script>
    <script src="<?= $fw->getUrl("js/dlem.js") ?>" type="text/javascript"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-Piv4xVNRyMGpqkS2by6br4gNJ7DXjqk09RmUpJ8jgGtD7zP9yug3goQfGII0yAns" crossorigin="anonymous"></script>
</head>

<body>
    <div class="container">
        <h3><?= $fw->tt("module_name") ?> <i class="fas fa-spinner fa-spin initializing"></i></h3>
        <div data-dlem-error style="color:red"></div>
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
            <div class="mt-3">
                <button class="btn btn-success btn-md" data-command="print"><?= $fw->tt("pp_print") ?></button>
                <button class="btn btn-link" data-command="calibrate"><?= $fw->tt("pp_calibrate") ?></button>
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
            <div class="dlem-copy">
                REDCap EM Version <?= $m->VERSION ?> &mdash; &copy;<?php print date('Y'); ?> Dr. Günther Rezniczek
            </div>
        </div>
    </div>


    <script>
        $(function() {
            window.ExternalModules.DYMOLabelPrint_init(<?= json_encode($configSettings) ?>)
            $('[data-toggle=tooltip]').tooltip()
        });
    </script>

    <!-- Modal: Preview -->
    <div class="modal fade" id="dlem-modal-preview" tabindex="-1" role="dialog" aria-labelledby="modal-preview-title" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-md modal-dialog-scrollable" role="document">
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
    <div class="modal fade" id="dlem-modal-calibrate" tabindex="-1" role="dialog" aria-labelledby="modal-calibrate-title" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-md modal-dialog-scrollable" role="document">
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

</body>
</html>
<?php
    }
}
publicEndpoint::process($module);
