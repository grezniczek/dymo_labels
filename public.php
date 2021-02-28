<?php

namespace DE\RUB\DYMOLabelsExternalModule;

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
        $errors = array();

        // Check whether access is allowed - must be in project context and public 
        if (!($pid !== null
            && $fw->getSystemSetting("system-allow-public")
            && $fw->getProjectSetting("allow-public"))) {
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
                        if (preg_match("/^(T|DM|QR|R)_([A-Z0-9_]+)/", trim($key), $match) === 1) {
                            $type = (strlen(trim($value)) == 0) ? "R" : $match[1];
                            $name = $match[2];
                            $kvPair = array(
                                "name" => $name,
                                "type" => $type,
                                "value" => $value
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
            } else {
                $labels[$id] = $label;
            }
        }

        $data["errors"] = $errors;

        // Prepare configuration data
        $configSettings = array(
            "debug" => $fw->getProjectSetting("js-debug") == true,
            "canDownload" => false,
            "ajax" => "",
            "strings" => array(
                "noPrinters" => $fw->tt("pp_noprinters"),
                "noLabels" => $fw->tt("pp_nolabels"),
            ),
            "labels" => $labels,
            "print" => $data,
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
        <h3><?= $fw->tt("module_name") ?></h3>
        <div class="card border-danger printers-card">
            <div class="card-header">
                <?= $fw->tt("pp_printers") ?>
                <button class="float-right btn btn-xs btn-link" style="padding:0px;" data-command="refresh" data-toggle="tooltip" data-placement="top" title="<?= $fw->tt("pp_refresh") ?>"><i class="fas fa-redo-alt fa-xs"></i></button>
            </div>
            <div class="card-body"id="prlist">
                <table id="printers" class="printers table table-hover table-borderless" style="margin-bottom:0.5rem;margin-top:0.5rem;">
                    <tr class="printer-template">
                        <td class="printer-select">
                            <input type="radio" name="printer" id="" value="" disabled>
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
        <div id="error" style="color:red"></div>
        <div class="card border-danger labels-card mt-3">
            <div class="card-header">
                <?= $fw->tt("pp_labels") ?>
            </div>
            <div class="card-body">
                <table id="labels" class="labels table table-hover table-borderless" style="margin-bottom:0.5rem;margin-top:0.5rem;">
                    <tr class="no-labels">
                        <td>
                            <span class="text-danger"><?= $fw->tt("pp_nolabels") ?></span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <div class="dlem-copy">
            REDCap EM Version <?= $m->VERSION ?> &mdash; &copy;<?php print date('Y'); ?> Dr. Günther Rezniczek
        </div>
    </div>

    <div id="calibrate" class="hidden">
        <p>
            Zahlen sind Vielfache von 0.1 mm, d.h. ein Wert von 10 bedeutet eine Korrektur um 1 mm. Negative Werte
            bedeuten eine Verschiebung nach links bzw. oben. Der zulässige Wertebereich ist -30 bis +30.
        </p>
        <form>
            <table>
                <tr>
                    <td>Delta X</td>
                    <td><input type="number" min="-30" max="30" name="cal_dx" id="cal_dx" /></td>
                </tr>
                <tr>
                    <td>Delta Y</td>
                    <td><input type="number" min="-30" max="30" name="cal_dy" id="cal_dy" /></td>
                </tr>
                <tr>
                    <td></td>
                    <td>
                        <input type="hidden" id="cal_name" name="cal_name" />
                        <input type="button" value="Speichern" onclick="saveCalibration();" />
                    </td>
                </tr>
            </table>
        </form>
    </div>
    <script>
        $(function() {
            window.ExternalModules.DYMOLabelPrint_init(<?= json_encode($configSettings) ?>)
            $('[data-toggle=tooltip]').tooltip()
        });
    </script>
</body>

</html>
<?php
    }
}
publicEndpoint::process($module);
