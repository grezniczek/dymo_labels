<?php namespace DE\RUB\DYMOLabelsExternalModule;

use DE\RUB\REDCapEMLib\Crypto;
use DE\RUB\REDCapEMLib\Project;
use Exception;
use ExternalModules\AbstractExternalModule;

/**
 * Provides an integration of DYMO LabelWriter printers with REDCap.
 */
class DYMOLabelsExternalModule extends AbstractExternalModule {


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
            "xml" => $data["xml"],
            "filename" => $data["filename"],
        );
        $key = "label-{$guid}";
        $this->setProjectSetting($key, $label);
        return $guid;
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

}