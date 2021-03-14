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
        $tags = ActionTagHelper::getActionTags([ self::atDymoLabel ], null, [ $instrument ]);
        
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