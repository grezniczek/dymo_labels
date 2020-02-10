<?php

namespace RUB\DYMOLabelsExternalModule;

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
        // The 'project-showlink' setting shall not apply to super users.
        return ($link["id"] == "integrated" && $project_id != null && (SUPER_USER || $fw->getProjectSetting("project-showlink"))) ? $link : null;
    }


    /**
     * Set module default settings when the module is enabled.
     */
    function redcap_module_project_enable($version, $project_id) {
        $fw = $this->framework;
        // Set default configuration.
        if ($project_id == null) {
            // System.
            if ($fw->getSystemSetting("system-allow-anonymous") == null) {
                $fw->setSystemSetting("system-allow-anonymous", false);
            }
        }
        else {
            // Project 
            if ($fw->getProjectSetting("project-showlink") == null) {
                $fw->setProjectSetting("project-showlink", true, $project_id);
            }
        }
    }

}