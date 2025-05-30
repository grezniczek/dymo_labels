<?php namespace DE\RUB\DYMOLabelsExternalModule;

use Exception;
use ExternalModules\AbstractExternalModule;
use SimpleXMLElement;
use Throwable;

require_once ("classes/Crypto.php");

/**
 * Provides an integration of DYMO LabelWriter printers with REDCap.
 */
class DYMOLabelsExternalModule extends AbstractExternalModule {

    const atPrintLabel = "@PRINT-LABEL";
    const atPrintLabelLegacy = "@DYMO-LABEL";

    /**
     * Control whether the plugin link is displayed.
     */
    function redcap_module_link_check_display($project_id, $link) {
        // Only show the link for the integrated plugin.
        // The 'show-link' setting shall not apply to super users.
        return ($link["id"] == "setup" && $project_id != null && ($this->framework->getUser()->isSuperUser() || $this->framework->getProjectSetting("show-link"))) ? $link : null;
    }

    /**
     * Set module default settings when the module is enabled.
     */
    function redcap_module_project_enable($version, $project_id) {
        // Set default configuration.
        if ($project_id == null) {
            // System.
            if ($this->framework->getSystemSetting("system-block-public") == null) {
                $this->framework->setSystemSetting("system-block-public", false);
            }
            if ($this->framework->getSystemSetting("system-disable-post") == null) {
                $this->framework->setSystemSetting("system-disable-post", true);
            }
        }
        else {
            // Project 
            if ($this->framework->getProjectSetting("show-link") == null) {
                $this->framework->setProjectSetting("show-link", true);
            }
            if ($this->framework->getProjectSetting("allow-public") == null) {
                $this->framework->setProjectSetting("allow-public", false);
            }
            if ($this->framework->getProjectSetting("allow-download") == null) {
                $this->framework->setProjectSetting("allow-download", true);
            }
        }
    }


    function redcap_data_entry_form($project_id, $record, $instrument, $event_id, $group_id, $repeat_instance) {

        if (!class_exists("ActionTagHelper")) require_once("classes/ActionTagHelper.php");
        $labels = $this->getLabels();
        $usedLabels = array();
        $tags = ActionTagHelper::getActionTags([ self::atPrintLabel, self::atPrintLabelLegacy ], null, [ $instrument ]);
        // Construct widget HTML
        $html = "<!-- DYMO Labels EM -->\n<div style=\"display:none;\">";
        $widgetNo = 0;
        if (count($tags)) {
            foreach ($tags as $taggedFields) {
                foreach ($taggedFields as $field => $params) {
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
                            $html .= "<button class=\"btn btn-primary btn-sm{$button_class}\" style=\"{$button_style}\">{$button_label}<span class=\"when-disabled\" hidden> <i class=\"fas fa-spinner fa-spin\"></i></span></button>";
                            $html .= "<span style=\"display:none;\">";
                            $html .= "<span data-dlem-range>{$range}</span>";
                            foreach($label["config"]["objects"] as $loi) {
                                if ($loi["transform"] == "R") continue;
                                $val = ($loi["readOnly"] || !isset($widgetParams["data"][$loi["name"]])) ?
                                    $loi["default"] : $widgetParams["data"][$loi["name"]];
                                $val = \Piping::pipeSpecialTags($val, $project_id, $record, $event_id, $repeat_instance, null, false, null, $instrument, false, false);
                                $val = \Piping::replaceVariablesInLabel($val, $record, $event_id, $repeat_instance, null, false, $project_id, true, "", 1, $record == null, false, $instrument, null, false, false, false);
                                $html .= "<span data-dlem-object=\"{$loi["name"]}\">{$val}</span>";
                            }
                            $html .= "</span></span>";
                        }
                    }
                }
            }
        }
        $html .= "</div>\n";
        if ($widgetNo > 0) {
            // Ajax Setup (for calibration)
            $crypto = Crypto::init($this);
            $ajax = array(
                "verification" => $crypto->encrypt(array(
                    "random" => $crypto->genKey(),
                    "noauth" => "noauth",
                    "pid" => $project_id,
                    "timestamp" => time(),
                )),
                "endpoint" => $this->framework->getUrl("public-ajax.php", true)
            );

            // Output widget(s) html
            print $html;
            $this->includeCSS("css/print-widget.css");
            $this->includeJS("js/3rd-party/dymo.connect.framework.js"); // TODO - Inline for survey
            $this->includeJS("js/3rd-party/bwip-js-min.js"); // TODO - Inline for survey
            $this->includeJS("js/dlem.js"); // TODO - Inline for survey
            $settings = array(
                "debug" => $this->framework->getProjectSetting("js-debug") == true,
                "ajax" => $ajax,
                "canDownload" => false,
                "widgetEndpoint" => $this->framework->getUrl("public.php", true),
                "eventId" => $event_id * 1,
                "labels" => $usedLabels,
                "skipPrinting" => $this->getProjectSetting("skip-printing") == true,
                "strings" => array (
                    "invalidRange" => $this->tt("error_invalidrange")
                ),
            );
            print "<script>$(function() { window.ExternalModules.DYMOLabelWidget_init(" . json_encode($settings) . "); });</script>";

            $fw = $this->framework; // used in widget-modals
            include("widget-modals.php");
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
                $labels[$setting["id"]] = $setting;
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