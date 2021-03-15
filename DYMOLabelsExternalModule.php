<?php namespace DE\RUB\DYMOLabelsExternalModule;

use DE\RUB\REDCapEMLib\Crypto;
use Exception;
use ExternalModules\AbstractExternalModule;
use SimpleXMLElement;
use Throwable;

/**
 * Provides an integration of DYMO LabelWriter printers with REDCap.
 */
class DYMOLabelsExternalModule extends AbstractExternalModule {

    const atDymoLabel = "@DYMO-LABEL";

    /**
     * Control whether the plugin link is displayed.
     */
    function redcap_module_link_check_display($project_id, $link) {
        $fw = $this->framework;
        // Only show the link for the integrated plugin.
        // The 'show-link' setting shall not apply to super users.
        return ($link["id"] == "setup" && $project_id != null && (SUPER_USER || $fw->getProjectSetting("show-link"))) ? $link : null;
    }


    /**
     * Set module default settings when the module is enabled.
     */
    function redcap_module_project_enable($version, $project_id) {
        $fw = $this->framework;
        // Set default configuration.
        if ($project_id == null) {
            // System.
            if ($fw->getSystemSetting("system-allow-public") == null) {
                $fw->setSystemSetting("system-allow-public", false);
            }
            if ($fw->getSystemSetting("system-enable-post") == null) {
                $fw->setSystemSetting("system-enable-post", false);
            }
        }
        else {
            // Project 
            if ($fw->getProjectSetting("show-link") == null) {
                $fw->setProjectSetting("show-link", true);
            }
            if ($fw->getProjectSetting("allow-public") == null) {
                $fw->setProjectSetting("allow-public", false);
            }
            if ($fw->getProjectSetting("allow-download") == null) {
                $fw->setProjectSetting("allow-download", true);
            }
        }
    }


    function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance) {

        if (!class_exists("ActionTagHelper")) require_once("classes/ActionTagHelper.php");
        $labels = $this->getLabels();
        $usedLabels = array();
        $tags = ActionTagHelper::getActionTags([ self::atDymoLabel ], null, [ $instrument ]);
        // Construct widget HTML
        $html = "<!-- DYMO Labels EM -->\n<div style=\"display:none;\">";
        $widgetNo = 0;
        if (count($tags)) {
            foreach ($tags[self::atDymoLabel] as $field => $params) {
                foreach ($params as $json) {
                    $widgetParams = json_decode($json, true);
                    if ($widgetParams && isset($labels[$widgetParams["id"]])) {
                        $id = $widgetParams["id"];
                        $label = $labels[$id];
                        $usedLabels[$id] = $labels[$id];
                        $widgetNo++;
                        $button_label = strlen(trim($widgetParams["button"])) ? trim($widgetParams["button"]) : $this->tt("widget_label");
                        $button_style = isset($widgetParams["style"]) ? trim($widgetParams["style"]) : "margin-bottom:0.5rem;";
                        $button_class = isset($widgetParams["class"]) ? " ".trim($widgetParams["class"]) : "";
                        $range = strlen(trim($widgetParams["range"])) ? trim($widgetParams["range"]) : "";
                        $auto = " data-dlem-auto=\"" . ((isset($widgetParams["auto"]) && $widgetParams["auto"] == true) ? "1" : "0") . "\"";
                        if (strlen($range)) {
                            $range = \Piping::pipeSpecialTags($range, $project_id, $record, $event_id, $repeat_instance, null, false, null, $instrument, false, false);
                            $range = \Piping::replaceVariablesInLabel($range, $record, $event_id, $repeat_instance, null, false, $project_id, true, "", 1, false, false, $instrument, null, false, false, false);
                        }
                        $html .= "<span data-dlem-print-widget=\"{$widgetNo}\" data-dlem-label=\"{$id}\" data-dlem-field=\"{$field}\" data-dlem-target=\"{$widgetParams["target"]}\" data-dlem-eventid=\"{$event_id}\"{$auto}>";
                        $html .= "<button class=\"btn btn-primary btn-sm{$button_class}\" style=\"{$button_style}\">{$button_label}</button>";
                        $html .= "<span style=\"display:none;\">";
                        $html .= "<span data-dlem-range>{$range}</span>";
                        foreach($label["config"]["objects"] as $loi) {
                            if ($loi["transform"] == "R") continue;
                            $val = ($loi["readOnly"] || !isset($widgetParams["data"][$loi["name"]])) ?
                                $loi["default"] : $widgetParams["data"][$loi["name"]];
                            $val = \Piping::pipeSpecialTags($val, $project_id, $record, $event_id, $repeat_instance, null, false, null, $instrument, false, false);
                            $val = \Piping::replaceVariablesInLabel($val, $record, $event_id, $repeat_instance, null, false, $project_id, true, "", 1, false, false, $instrument, null, false, false, false);
                            $html .= "<span data-dlem-object=\"{$loi["name"]}\">{$val}</span>";
                        }
                        $html .= "</span></span>";
                        // $html = str_replace("\n", "<br>", $html);
                    }
                }
            }
        }
        $html .= "</div>\n";
        if ($widgetNo > 0) {
            $fw = $this->framework;

            if (!class_exists("\DE\RUB\REDCapEMLib\Crypto")) include_once ("classes/Crypto.php");

            // Ajax Setup (for calibration)
            $crypto = Crypto::init($this);
            $ajax = array(
                "verification" => $crypto->encrypt(array(
                    "random" => $crypto->genKey(),
                    "noauth" => "noauth",
                    "pid" => $project_id,
                    "timestamp" => time(),
                )),
                "endpoint" => $fw->getUrl("public-ajax.php", true)
            );

            // Output widget(s) html
            print $html;
            $this->includeCSS("css/print-widget.css");
            $this->includeJS("js/3rd-party/dymo.connect.framework.js"); // TODO - Inline for survey
            $this->includeJS("js/3rd-party/bwip-js-min.js"); // TODO - Inline for survey
            $this->includeJS("js/dlem.js"); // TODO - Inline for survey
            $settings = array(
                "debug" => $fw->getProjectSetting("js-debug") == true,
                "ajax" => $ajax,
                "canDownload" => false,
                "widgetEndpoint" => $fw->getUrl("public.php", true),
                "eventId" => $event_id * 1,
                "labels" => $usedLabels,
                "skipPrinting" => $this->getProjectSetting("skip-printing") == true,
                "strings" => array (
                    "invalidRange" => $this->tt("error_invalidrange")
                ),
            );
            print "<script>$(function() { window.ExternalModules.DYMOLabelWidget_init(" . json_encode($settings) . "); });</script>";

?>
<!-- DYMO Labels EM Print Modal -->
<div
    class="modal fade dlem-modal"
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
<div class="modal fade dlem-modal" id="dlem-modal-preview" tabindex="-1" role="dialog" aria-labelledby="modal-preview-title" aria-hidden="true" data-backdrop="static" data-keyboard="false">
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
<div class="modal fade dlem-modal" id="dlem-modal-calibrate" tabindex="-1" role="dialog" aria-labelledby="modal-calibrate-title" aria-hidden="true" data-backdrop="static" data-keyboard="false">
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

<?php
        }
    }



     /**
      * Includes a CSS file (either in-line or as a separate resource).
      * @param string $name The path of the CSS file relative to the module folder.
      * @param bool $inline Determines whether the styles will be inlined or loaded as a separate resource.
     */
    function includeCSS($name, $inline = false) {
        
        if ($inline) {
            $css = file_get_contents(__DIR__ . DS . $name);
            echo "<style>\n$css\n</style>\n";
        }
        else {
            $css = $this->framework->getUrl($name);
            $name = md5($name);
            echo "<script type=\"text/javascript\">
                    (function() {
                        var id = 'babel_css_$name'
                        if (!document.getElementById(id)) {
                            var head = document.getElementsByTagName('head')[0]
                            var link = document.createElement('link')
                            link.id = id
                            link.rel = 'stylesheet'
                            link.type = 'text/css'
                            link.href = '$css'
                            link.media = 'all'
                            head.appendChild(link)
                        }
                    })();
                </script>";
        }
    }

    /**
     * Includes a JS file (either in-line or as a separate resource).
     * @param string $name The path of the JS file relative to the module folder.
     * @param bool $inline Determines whether the code will be inlined or loaded as a separate resource.
     */
    function includeJS($name, $inline = false) {
        
        if ($inline) {
            $js = file_get_contents(__DIR__ . DS . $name);
            echo "<script type=\"text/javascript\">\n$js\n</script>\n";
        }
        else {
            $js = $this->framework->getUrl($name);
            echo "<script type=\"text/javascript\" src=\"$js\"></script>";
        }
    }



    /**
     * Adds a new label.
     * @param string $data The label data (associative array: name, desc, filename, xml).
     * @return string A GUID under which the label is saved.
     */
    function addLabel($data) {
        if (!class_exists("\DE\RUB\REDCapEMLib\Crypto")) include_once ("classes/Crypto.php");
        $guid = Crypto::getGuid();
        
        $label = array(
            "id" => $guid,
            "name" => $data["name"],
            "desc" => $data["desc"],
            "xml" => $this->validateXml($data["xml"]),
            "filename" => $data["filename"],
            "config" => $this->sanitizeLabelConfig($data["config"]),
        );
        $key = "label-{$guid}";
        $this->setProjectSetting($key, $label);
        return $label;
    }

    /**
     * Checks whether the given XML is valid
     * @param string $xml 
     * @return string The xml string
     * @throws Exception In case the XML cannot be parsed without errors.
     */
    function validateXml($xml) {
        $prev = libxml_use_internal_errors(true);
        $errors = 1;
        try {
            new SimpleXMLElement($xml, 0, false);
            $errors = count(libxml_get_errors());
        }
        catch (Throwable $err) {
        }
        libxml_clear_errors();
        libxml_use_internal_errors($prev);
        if ($errors > 0) {
            throw new Exception($this->tt("error_invalidxml"));
        }
        return $xml;
    }

    /**
     * Renames an existing label.
     * @param string $data The label data (associative array: id, name, desc).
     * @return string A GUID under which the label is saved.
     */
    function renameLabel($data) {
        $key = "label-{$data["id"]}";
        $label = $this->getProjectSetting($key);

        if ($label == null) {
            throw new Exception($this->tt("error_labelnotfound"));
        }
        $label["name"] = $data["name"];
        $label["desc"] = $data["desc"];
        $this->setProjectSetting($key, $label);
        return $label;
    }

    /**
     * Updates an existing label.
     * @param string $data The label data (associative array: name, desc, filename, xml).
     * @return string A GUID under which the label is saved.
     */
    function updateLabel($data) {
        $key = "label-{$data["id"]}";
        $label = $this->getProjectSetting($key);

        if ($label == null) {
            throw new Exception($this->tt("error_labelnotfound"));
        }
        $label["name"] = $data["name"];
        $label["desc"] = $data["desc"];
        $label["xml"] = $data["xml"];
        $label["config"] = $this->sanitizeLabelConfig($data["config"]);
        $this->setProjectSetting($key, $label);
        return $label;
    }

    function sanitizeLabelConfig($incoming) {
        $config = array (
            "public" => $incoming["public"] === true,
        );
        foreach ($incoming["objects"] as $name => $loi) {
            $type = $loi["type"] == "Graphic" ? "Graphic" : "Text";
            $transform = "R";
            if (($type == "Graphic" && in_array($loi["transform"], ["PNG", "DM", "QR", "R"], true)) ||
                ($type == "Text" && in_array($loi["transform"], ["T", "R"], true))) {
                $transform = $loi["transform"];
            }
            $object = array (
                "name" => $loi["name"],
                "desc" => $loi["desc"],
                "type" => $type, 
                "transform" => $transform,
                "multiline" => $loi["multiline"] === true,
                "readOnly" => $loi["readOnly"] === true,
                "allowEmpty" => $loi["allowEmpty"] === true,
                "default" => $loi["default"],
            );
            $config["objects"][$object["name"]] = $object;
        }
        return $config;
    }


    /**
     * Deletes a label.
     * @param string $id 
     * @return string 
     */
    function deleteLabel($id) {
        $labels = $this->getLabels();
        if (array_key_exists($id, $labels)) {
            $this->removeProjectSetting("label-{$id}");
            // Also delete all calibration data
            $settings = $this->getSystemSettings();
            foreach (array_keys($settings) as $key) {
                if (starts_with($key, "cal:{$id}")) {
                    $this->removeSystemSetting($key);
                }
            }
            return "";
        }
        return $this->tt("error_labelnotfound");
    }

    function getLabels() {
        $settings = $this->getProjectSettings();
        $labels = array();
        foreach ($settings as $key => $setting) {
            if (starts_with($key, "label-")) {
                $labels[$setting["value"]["id"]] = $setting["value"];
            }
        }
        return $labels;
    }

    function getLabel($id) {
        $labels = $this->getLabels();
        return (array_key_exists($id, $labels) ? $labels[$id] : false);
    }

    function storeCalibration($data) {
        $labels = $this->getLabels();
        $id = $data["id"];
        $printer = $data["printer"];
        if (array_key_exists($id, $labels) && strlen($printer)) {
            $md5 = md5($printer);
            $key = "cal:{$id}:{$md5}";
            $cal = $data["cal"];
            $this->setSystemSetting($key, $cal);
        }
        else {
            throw new Exception($this->tt("error_labelnotfound"));
        }
    }

    function getCalibration($data) {
        $calData = array();
        $labels = $this->getLabels();
        $id = $data["id"];
        if (!array_key_exists($id, $labels)) {
            throw new Exception($this->tt("error_labelnotfound"));
        }
        if (!is_array($data["printers"])) {
            throw new Exception("No printers specified.");
        }
        foreach ($data["printers"] as $printer) {
            $md5 = md5($printer);
            $key = "cal:{$id}:{$md5}";
            $cal = $this->getSystemSetting($key);
            if ($cal == null) {
                $cal = array ( "dx" => 0, "dy" => 0 );
            }
            $calData[$printer] = $cal;
        }
        return $calData;
    }

}