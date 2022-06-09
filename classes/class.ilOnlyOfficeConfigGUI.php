<?php

require_once __DIR__ . "/../vendor/autoload.php";

use srag\Plugins\OnlyOffice\StorageService\DTO\FileTemplate;
use srag\Plugins\OnlyOffice\StorageService\Infrastructure\File\ilDBFileChangeRepository;
use srag\Plugins\OnlyOffice\StorageService\Infrastructure\File\ilDBFileRepository;
use srag\Plugins\OnlyOffice\StorageService\Infrastructure\File\ilDbFileTemplateRepository;
use srag\Plugins\OnlyOffice\StorageService\Infrastructure\File\ilDBFileVersionRepository;
use srag\Plugins\OnlyOffice\StorageService\StorageService;
use srag\Plugins\OnlyOffice\Utils\OnlyOfficeTrait;
use srag\DIC\OnlyOffice\DICTrait;

/**
 * Class ilOnlyOfficeConfigGUI
 *
 * Generated by SrPluginGenerator v1.3.4
 *
 * @author Theodor Truffer <theo@fluxlabs.ch>
 * @author Sophie Pfister <sophie@fluxlabs.ch>
 */
class ilOnlyOfficeConfigGUI extends ilPluginConfigGUI
{

    use DICTrait;
    use OnlyOfficeTrait;
    const PLUGIN_CLASS_NAME = ilOnlyOfficePlugin::class;
    const CMD_CONFIGURE = "configure";
    const CMD_TEMPLATES = "configureTemplates";
    const CMD_CREATE_TEMPLATE = "createTemplate";
    const CMD_EDIT_TEMPLATE = "editTemplate";
    const CMD_SAVE_EDIT_TEMPLATE = "saveEditTemplate";
    const CMD_DELETE_TEMPLATE = "deleteTemplate";
    const CMD_UPDATE_CONFIGURE = "updateConfigure";
    const CMD_UPDATE_TEMPLATES = "updateTemplates";
    const CMD_CONFIRM_DELETE = "confirmDelete";
    const LANG_MODULE = "config";
    const TAB_CONFIGURATION = "configuration";
    const TAB_SUB_CONFIGURATION = "subConfiguration";
    const TAB_SUB_TEMPLATES = "templates";

    /**
     * @var StorageService
     */
    protected $storage_service;


    /**
     * ilOnlyOfficeConfigGUI constructor
     */
    public function __construct()
    {
        $this->storage_service = new StorageService(
            self::dic()->dic(),
            new ilDBFileVersionRepository(),
            new ilDBFileRepository(),
            new ilDBFileChangeRepository()
        );
    }


    /**
     * @inheritDoc
     */
    public function performCommand(/*string*/ $cmd)/*:void*/
    {
        $this->setTabs();

        $next_class = self::dic()->ctrl()->getNextClass($this);

        switch (strtolower($next_class)) {
            default:
                $cmd = self::dic()->ctrl()->getCmd();

                switch ($cmd) {
                    case self::CMD_CONFIGURE:
                    case self::CMD_TEMPLATES:
                    case self::CMD_CREATE_TEMPLATE:
                    case self::CMD_SAVE_EDIT_TEMPLATE:
                    case self::CMD_EDIT_TEMPLATE:
                    case self::CMD_DELETE_TEMPLATE:
                    case self::CMD_UPDATE_CONFIGURE:
                    case self::CMD_UPDATE_TEMPLATES:
                    case self::CMD_CONFIRM_DELETE:
                        if (!ilObjOnlyOfficeAccess::hasWriteAccess()) {
                            ilObjOnlyOfficeAccess::redirectNonAccess($this);
                        }
                        $this->{$cmd}();
                        break;

                    default:
                        break;
                }
                break;
        }
    }


    /**
     *
     */
    protected function setTabs()/*: void*/
    {
        self::dic()->tabs()->addTab(self::TAB_CONFIGURATION, self::plugin()->translate("configuration", self::LANG_MODULE), self::dic()->ctrl()
            ->getLinkTargetByClass(self::class, self::CMD_CONFIGURE));

        self::dic()->tabs()->addSubTab(self::TAB_SUB_CONFIGURATION, self::plugin()->translate("tab_general", self::LANG_MODULE), self::dic()->ctrl()
            ->getLinkTargetByClass(self::class, self::CMD_CONFIGURE));

        self::dic()->tabs()->addSubTab(self::TAB_SUB_TEMPLATES, self::plugin()->translate("tab_templates", self::LANG_MODULE), self::dic()->ctrl()
            ->getLinkTargetByClass(self::class, self::CMD_TEMPLATES));

        self::dic()->locator()->addItem(ilOnlyOfficePlugin::PLUGIN_NAME, self::dic()->ctrl()->getLinkTarget($this, self::CMD_CONFIGURE));
    }


    /**
     *
     */
    protected function configure()/*: void*/
    {
        self::dic()->tabs()->activateTab(self::TAB_CONFIGURATION);
        self::dic()->tabs()->activateSubTab(self::TAB_SUB_CONFIGURATION);

        $form = self::onlyOffice()->config()->factory()->newFormInstance($this);

        self::output()->output($form);
    }


    protected function configureTemplates()
    {
        self::dic()->tabs()->activateTab(self::TAB_CONFIGURATION);
        self::dic()->tabs()->activateSubTab(self::TAB_SUB_TEMPLATES);

        global $ilToolbar;

        $ilToolbar->addButton(
            self::plugin()->translate("create_template", self::LANG_MODULE),
            self::dic()->ctrl()->getLinkTargetByClass(self::class, self::CMD_CREATE_TEMPLATE)
        );

        $tpl = self::plugin()->template("html/tpl.config_create_template.html");

        $text_templates = $this->storage_service->fetchTemplates("text");
        $table_templates = $this->storage_service->fetchTemplates("table");
        $presentation_templates = $this->storage_service->fetchTemplates("presentation");
        $templates = array_merge($text_templates, $table_templates, $presentation_templates);

        if (count($templates) >= 1) {
            $tpl->setVariable('TYPE_HEADER', self::plugin()->translate("table_type", self::LANG_MODULE));
            $tpl->setVariable('TITLE_HEADER', self::plugin()->translate("table_title", self::LANG_MODULE));
            $tpl->setVariable('DESCRIPTION_HEADER', self::plugin()->translate("table_description", self::LANG_MODULE));
            $tpl->setVariable('EXTENSION_HEADER', self::plugin()->translate("table_extension", self::LANG_MODULE));
            $tpl->setVariable('SETTINGS_HEADER', self::plugin()->translate("table_settings", self::LANG_MODULE));
        }

        /** @var FileTemplate $template */
        foreach ($templates as $template) {
            $tpl->setCurrentBlock("entry");
            $tpl->setVariable('TITLE', $template->getTitle());
            $tpl->setVariable('TYPE', self::plugin()->translate("form_input_create_file_" . $template->getType()));
            $tpl->setVariable('DESCRIPTION', empty($template->getDescription()) ? "-" : $template->getDescription());
            $tpl->setVariable('EXTENSION', $template->getExtension());
            $ctrlFormat = "%s&ootarget=%s&ooextension=%s";

            $ilSelect = new ilAdvancedSelectionListGUI();
            $ilSelect->setListTitle(self::plugin()->translate("table_options", self::LANG_MODULE));
            $ilSelect->addItem(
                self::plugin()->translate("table_edit", self::LANG_MODULE),
                "",
                self::dic()->ctrl()->getLinkTargetByClass(
                    self::class,
                    sprintf($ctrlFormat, self::CMD_EDIT_TEMPLATE, urlencode($template->getTitle()), urlencode($template->getExtension()))
                )
            );
            $ilSelect->addItem(
                self::plugin()->translate("table_delete", self::LANG_MODULE),
                "",
                self::dic()->ctrl()->getLinkTargetByClass(
                    self::class,
                    sprintf($ctrlFormat, self::CMD_CONFIRM_DELETE, urlencode($template->getTitle()), urlencode($template->getExtension()))
                )
            );
            $tpl->setVariable('SETTINGS', $ilSelect->getHTML());
            $tpl->parseCurrentBlock();
        }

        $content = $tpl->get();
        self::output()->output($content);
    }


    protected function createTemplate()
    {
        self::dic()->tabs()->activateTab(self::TAB_CONFIGURATION);
        self::dic()->tabs()->activateSubTab(self::TAB_SUB_TEMPLATES);

        $form = $this->initCreateTemplateForm()->getHTML();
        self::output()->output($form);
    }


    protected function initCreateTemplateForm(bool $edit = false)
    {
        $form = new ilPropertyFormGUI();
        $form->setTarget("_top");
        $form->setFormAction(self::dic()->ctrl()->getFormAction($this) . "&prevTitle=" . urlencode($_GET["ootarget"]) . "&prevExtension=" . urlencode($_GET["ooextension"]));

        // title
        $ti = new ilTextInputGUI(self::plugin()->translate("table_title", self::LANG_MODULE), "title");
        $ti->setSize(min(40, ilObject::TITLE_LENGTH));
        $ti->setMaxLength(ilObject::TITLE_LENGTH);
        $form->addItem($ti);

        // description
        $ta = new ilTextAreaInputGUI(self::plugin()->translate("table_description", self::LANG_MODULE), "desc");
        $ta->setCols(40);
        $ta->setRows(2);
        $form->addItem($ta);

        // file upload option
        $file_input = new ilFileInputGUI(self::plugin()->translate("form_input_file"), "file");
        $file_input->setRequired(!$edit);
        $form->addItem($file_input);

        if ($edit) {
            $form->setTitle(self::plugin()->translate("edit_template", self::LANG_MODULE));
            $form->addCommandButton(self::CMD_SAVE_EDIT_TEMPLATE, self::plugin()->translate("settings_save"));
        } else {
            $form->setTitle(self::plugin()->translate("create_template", self::LANG_MODULE));
            $form->addCommandButton(self::CMD_UPDATE_TEMPLATES, self::plugin()->translate("settings_save"));
        }

        $form->addCommandButton(self::CMD_TEMPLATES, self::plugin()->translate("settings_cancel"));

        return $form;
    }


    /**
     *
     */
    protected function updateConfigure()/*: void*/
    {
        self::dic()->tabs()->activateTab(self::TAB_CONFIGURATION);

        $form = self::onlyOffice()->config()->factory()->newFormInstance($this);

        if (!$form->storeForm()) {
            self::output()->output($form);

            return;
        }

        ilUtil::sendSuccess(self::plugin()->translate("configuration_saved", self::LANG_MODULE), true);

        self::dic()->ctrl()->redirect($this, self::CMD_CONFIGURE);
    }


    protected function updateTemplates()
    {
        $form = $this->initCreateTemplateForm();

        if (!$form->checkInput()) {
            $form->setValuesByPost();
            self::output()->output($form);
            return;
        }

        if (!self::dic()->upload()->hasBeenProcessed()) {
            self::dic()->upload()->process();
        }

        $results = self::dic()->upload()->getResults();
        $result = end($results);

        // Return if file extension not whitelisted by ILIAS instance
        if (!ilFileUtils::hasValidExtension($result->getName())) {
            ilUtil::sendFailure(self::plugin()->translate("template_invalid_extension", self::LANG_MODULE), true);
            $form->setValuesByPost();
            self::output()->output($form);
            return;
        }

        $path = $this->storage_service->createFileTemplate($result, $_POST["title"], $_POST["desc"]);

        // Return if file extension not recognized by OnlyOffice
        if (empty($path)) {
            ilUtil::sendFailure(self::plugin()->translate("template_unrecognised_extension", self::LANG_MODULE), true);
            $form->setValuesByPost();
            self::output()->output($form);
            return;
        }

        ilUtil::sendSuccess(self::plugin()->translate("template_saved", self::LANG_MODULE), true);
        self::dic()->ctrl()->redirect($this, self::CMD_TEMPLATES);
    }


    protected function editTemplate()
    {
        self::dic()->tabs()->activateTab(self::TAB_CONFIGURATION);
        self::dic()->tabs()->activateSubTab(self::TAB_SUB_TEMPLATES);

        $target = $_GET["ootarget"];
        $extension = $_GET["ooextension"];

        $template = $this->storage_service->fetchTemplate($target, $extension);

        if (!is_null($template)) {
            $value_array = [
                "title" => $template->getTitle(),
                "desc" => $template->getDescription(),
                "file" => $template->getPath()
            ];

            $form = $this->initCreateTemplateForm(true);
            $form->setValuesByArray($value_array);
            self::output()->output($form->getHTML());
        }

    }


    protected function saveEditTemplate()
    {
        $target = $_POST["title"];
        $description = $_POST["desc"];
        $prevTitle = $_GET["prevTitle"];
        $prevExtension = $_GET["prevExtension"];

        $form = $this->initCreateTemplateForm(true);

        if (!$form->checkInput()) {
            $form->setValuesByPost();
            self::output()->output($form);
            return;
        }

        $uploaded_file = $_FILES["file"]["name"];

        // If no file is uploaded, merely change title and description
        if (empty($uploaded_file)) {
            // Dont delete previous template
            $this->storage_service->modifyFileTemplate($prevTitle, $prevExtension, $target, $description);
        } else {

            self::dic()->upload()->process();
            $results = self::dic()->upload()->getResults();
            $result = end($results);

            // Return if file extension not whitelisted by ILIAS instance
            if (!ilFileUtils::hasValidExtension($result->getName())) {
                // Fix bug where previous title and name don't get saved into the form action
                $adjustedUrl = str_replace("prevTitle=", "prevTitle=" . urlencode($prevTitle), $form->getFormAction());
                $adjustedUrl = str_replace("prevExtension=", "prevExtension=" . urlencode($prevExtension), $adjustedUrl);
                $form->setFormAction($adjustedUrl);

                ilUtil::sendFailure(self::plugin()->translate("template_invalid_extension", self::LANG_MODULE), true);
                $template = $this->storage_service->fetchTemplate($prevTitle, $prevExtension);
                $value_array = [
                    "title" => $_POST["title"],
                    "desc" => $_POST["desc"],
                    "file" => $template->getPath()
                ];
                $form->setValuesByArray($value_array);
                self::output()->output($form);
                return;
            }

            // Return if file extension not recognized by OnlyOffice
            if (empty($path)) {
                // Fix bug where previous title and name don't get saved into the form action
                $adjustedUrl = str_replace("prevTitle=", "prevTitle=" . urlencode($prevTitle), $form->getFormAction());
                $adjustedUrl = str_replace("prevExtension=", "prevExtension=" . urlencode($prevExtension), $adjustedUrl);
                $form->setFormAction($adjustedUrl);
                
                ilUtil::sendFailure(self::plugin()->translate("template_unrecognised_extension", self::LANG_MODULE), true);
                $template = $this->storage_service->fetchTemplate($prevTitle, $prevExtension);
                $value_array = [
                    "title" => $_POST["title"],
                    "desc" => $_POST["desc"],
                    "file" => $template->getPath()
                ];
                $form->setValuesByArray($value_array);
                self::output()->output($form);
                return;
            }

            $success = $this->storage_service->deleteFileTemplate($target, $prevExtension);
            $path = $this->storage_service->createFileTemplate($result, $_POST["title"], $_POST["desc"]);
        }

        ilUtil::sendSuccess(self::plugin()->translate("template_edited", self::LANG_MODULE), true);
        self::dic()->ctrl()->redirect($this, self::CMD_TEMPLATES);
    }


    public function confirmDelete()
    {
        self::dic()->ctrl()->saveParameter($this, "ootarget");
        self::dic()->ctrl()->saveParameter($this, "ooextension");

        $conf = new ilConfirmationGUI();
        $conf->setFormAction(self::dic()->ctrl()->getFormAction($this));
        $conf->setHeaderText(self::plugin()->translate('config_template_delete'));

        $conf->addItem('tableview', 1, $_GET["ootarget"]);

        $conf->setConfirm(self::dic()->language()->txt('delete'), self::CMD_DELETE_TEMPLATE);
        $conf->setCancel(self::dic()->language()->txt('cancel'), self::CMD_TEMPLATES);

        self::output()->output($conf->getHTML());
    }


    protected function deleteTemplate()
    {
        $target = $_GET["ootarget"];
        $extension = $_GET["ooextension"];

        $success = $this->storage_service->deleteFileTemplate($target, $extension);

        if ($success) {
            ilUtil::sendSuccess(self::plugin()->translate("template_deleted", self::LANG_MODULE), true);
        }

        self::dic()->ctrl()->redirect($this, self::CMD_TEMPLATES);
    }
}
