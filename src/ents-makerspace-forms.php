<?php

/**
 * Class Am_Plugin_EntsMakerspaceForms
 */
class Am_Plugin_EntsMakerspaceForms extends Am_Plugin
{
    const PLUGIN_STATUS = self::STATUS_PRODUCTION;
    const PLUGIN_COMM = self::COMM_FREE;
    const PLUGIN_REVISION = "1.0.0";

    function onAdminMenu(Am_Event $event)
    {
        $usersMenu = $event->getMenu()->findById("users");
        $addUserItem = $usersMenu->findById("users-insert");
        $addUserItem->label = ___("Add User (All Options)");
        $addUserItem->order = 10;
        $addMemberPage = array(
            "id" => "ents-add-member",
            "controller" => "admin-ents-add-member",
            "action" => "add",
            "label" => ___("Add Member"),
            "order" => 0,
            "resource" => "ents-add-member"
        );
        $usersMenu->addPage($addMemberPage);
    }
}

class AdminEntsAddMemberController extends Am_Mvc_Controller
{

    public function checkAdminPermissions(Admin $admin)
    {
        return $admin->hasPermission("ents-add-member");
    }

    public function addAction()
    {
        $this->view->title = ___("Add Member");

        $memberInterestOptions = array(
            "Woodworking",
            "Metalworking",
            "Blacksmithing",
            "Electronics",
            "3D Printing/Modeling",
            "CNC Machining",
            "Pottery",
            "Socialising",
            "Crafting",
            "Cosplay/Costume Design"
        );

        $options = array();
        foreach ($memberInterestOptions as $option) $options[$option] = $option; // convert to key:value pairs for magic select

        $userCustomFields = $this->getDi()->userTable->customFields();
        $form = new Am_Form();

        $fs = $form->addFieldSet()->setLabel(___("Member Information"));
        $firstName = $fs->addText("firstName")->setLabel(___("First Name"))->addFilter("trim")->addRule("required");
        $lastName = $fs->addText("lastName")->setLabel(___("Last Name"))->addFilter("trim")->addRule("required");
        $email = $fs->addText("email")->setLabel(___("Email Address"))->addFilter("trim")->addRule("required");

        if ($userCustomFields->get("fob"))
            $fobNumber = $fs->addText("fobNumber")->setLabel(___("Fob Number"))->addFilter("trim")->addRule("required");
        else $fobNumber = null;

        $fs = $form->addFieldSet()->setLabel(___("Address Verification"));
        $address = $fs->addText("address")->setLabel(___("Address"))->addFilter("trim")->addRule("required");
        $city = $fs->addText("city")->setLabel(___("City"))->addFilter("trim")->addRule("required");
        $province = $fs->addText("province")->setLabel(___("Province"))->addFilter("trim")->addRule("required");
        $country = $fs->addSelect('country')->setLabel(___("Country"))->loadOptions($this->getDi()->countryTable->getOptions())->setValue("CA");
        $postalCode = $fs->addText("postalCode")->setLabel(___("Postal Code"))->addFilter("trim")->addRule("required");

        if($userCustomFields->get("id_type"))
            $idType = $fs->addText("idType", array("value" => "Alberta Driver's License"))->setLabel("Photo ID Checked\nA small description of what kind of ID was used to verify the above address and name.")->addFilter("trim")->addRule("required");
        else $idType = null;

        if($userCustomFields->get("waiver_signed")) {
            $fs = $form->addFieldSet()->setLabel(___("Additional Information"));
            $fs->addAdvCheckbox("waiver_signed")->setLabel(___("Waiver Signed"))->addRule("required");
        }else $waiverSigned = null;

        $fs = $form->addFieldSet()->setLabel(___("Interests and Projects"));
        $interests = $fs->addMagicSelect("interests")->loadOptions($options)->setLabel(___("Interests\nMore than one may be selected"));
        $projects = $fs->addTextarea("notes", array("style" => "height: 70px; width: 70%;"))->setLabel(___("Projects / Other Interests"));

        $form->addElement("submit", null, array("value" => ___("Add Member")));

        if($form->isSubmitted() && $form->validate()) {
            $notes = htmlspecialchars($projects->getValue());
            $interests = $interests->getValue(); // array

            $noteContent = "";
            if (count($interests) > 0) {
                $noteContent .= "Areas of Interest:\n";
                foreach ($interests as $interest) $noteContent .= "* $interest\n";
                $noteContent .= "\n\n";
            }
            if(strlen($notes) > 0) $noteContent .= "Projects / Other Interests:\n$notes";

            $table = $this->getDi()->userTable;
            $user = $table->createRecord();
            $user->comment = $noteContent;
            $user->name_f = htmlspecialchars($firstName->getValue());
            $user->name_l = htmlspecialchars($lastName->getValue());
            $user->email = htmlspecialchars($email->getValue());
            $user->street = htmlspecialchars($address->getValue());
            $user->city = htmlspecialchars($city->getValue());
            $user->state = htmlspecialchars($province->getValue());
            $user->zip = htmlspecialchars($postalCode->getValue());
            $user->country = htmlspecialchars($country->getValue());
            if($idType) $user->id_type = htmlspecialchars($idType->getvalue());
            if($fobNumber) $user->fob = htmlspecialchars($fobNumber->getValue());
            if($waiverSigned) $user->waiver_signed = true;
            $user->generateLogin();
            $user->generatePassword();
            $user->insert();
            $user->sendRegistrationEmail();

            if(strlen($noteContent) > 0) {
                $table = $this->getDi()->userNoteTable;
                $note = $table->createRecord();
                $note->user_id = $user->user_id;
                $note->admin_id = $this->getDi()->authAdmin->getUser()->admin_id;
                $note->content = $noteContent;
                $note->insert();
            }

            $this->view->content = "<strong>{$user->name_f} {$user->name_l} has been added as a member.</strong><br><p>Shortly they will receive a welcome email with instructions on how to log in to their account.</p><br><p><a href='/admin-users?_u_a=edit&_u_id={$user->user_id}'>Click here</a> to edit the user.</p>";
        } else $this->view->content = (string)$form;

        $this->view->display("admin/layout.phtml");
    }
}