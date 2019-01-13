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

    function onGetPermissionsList(Am_Event $event)
    {
        $event->addReturn("ENTS: Add Member", 'ents-add-member');
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
            "Cosplay/Costume Design",
        );

        $advertisingOptions = array(
            "Twitter",
            "Facebook",
            "Instagram",
            "From a friend/colleague",
            "Web search",
            "Poster / print media",
            "K-Days",
            "Outreach event",
        );

        $interestOptions = array();
        foreach ($memberInterestOptions as $option) $interestOptions[$option] = $option; // convert to key:value pairs for magic select

        $adSourceOptiions = array();
        foreach ($advertisingOptions as $option) $adSourceOptiions[$option] = $option; // convert to key:value pairs for magic select

        $userCustomFields = $this->getDi()->userTable->customFields();
        $form = new Am_Form();

        $textFields = array();
        $requiredFields = array();

        $fs = $form->addFieldSet()->setLabel(___("Member Information"));
        $textFields[] = $requiredFields[] = $firstName = $fs->addText("firstName")->setLabel(___("First Name"));
        $textFields[] = $requiredFields[] = $lastName = $fs->addText("lastName")->setLabel(___("Last Name"));
        $textFields[] = $requiredFields[] = $email = $fs->addText("email")->setLabel(___("Email Address"));

        if ($userCustomFields->get("fob"))
            $textFields[] = $requiredFields[] = $fobNumber = $fs->addText("fobNumber")->setLabel(___("Fob Number\nWithout leading zeros"));
        else $fobNumber = null;

        $fs = $form->addFieldSet()->setLabel(___("Address Verification"));
        $textFields[] = $requiredFields[] = $address = $fs->addText("address")->setLabel(___("Address"));
        $textFields[] = $requiredFields[] = $city = $fs->addText("city")->setLabel(___("City"));
        $textFields[] = $requiredFields[] = $province = $fs->addText("province", array("value" => "AB"))->setLabel(___("Province"));
        $country = $fs->addSelect('country')->setLabel(___("Country"))->loadOptions($this->getDi()->countryTable->getOptions())->setValue("CA");
        $textFields[] = $requiredFields[] = $postalCode = $fs->addText("postalCode")->setLabel(___("Postal Code"));

        if ($userCustomFields->get("id_type"))
            $textFields[] = $requiredFields[] = $idType = $fs->addText("idType", array("value" => "Alberta Driver's License"))->setLabel("Photo ID Checked\nA small description of what kind of ID was used to verify the above address and name.");
        else $idType = null;

        if ($userCustomFields->get("waiver_signed")) {
            $fs = $form->addFieldSet()->setLabel(___("Additional Information"));
            $requiredFields[] = $waiverSigned = $fs->addAdvCheckbox("waiver_signed")->setLabel(___("Waiver Signed"));
        } else $waiverSigned = null;

        $fs = $form->addFieldSet()->setLabel(___("Interests and Projects"));
        $interests = $fs->addMagicSelect("interests")->loadOptions($interestOptions)->setLabel(___("Interests\nMore than one may be selected"));
        $adSource = $fs->addMagicSelect("ad_source")->loadOptions($adSourceOptiions)->setLabel(___("Where did they hear about us?\nMore than one may be selected"));
        $textFields[] = $projects = $fs->addTextarea("notes", array("style" => "height: 70px; width: 70%;"))->setLabel(___("Projects / Other"));

        $form->addElement("submit", null, array("value" => ___("Add Member")));

        foreach ($textFields as $field) $field->addFilter("trim");
        foreach ($requiredFields as $field) $field->addRule("required");

        if ($form->isSubmitted() && $form->validate()) {
            $notes = htmlspecialchars($projects->getValue());
            $interests = $interests->getValue(); // array
            $adSource = $adSource->getValue(); // array

            $noteContent = "";
            if (count($interests) > 0) {
                $noteContent .= "Areas of Interest:\n";
                foreach ($interests as $interest) $noteContent .= "* $interest\n";
                $noteContent .= "\n\n";
            }
            if (count($adSource) > 0) {
                $noteContent .= "Heard about the space from:\n";
                foreach ($adSource as $source) $noteContent .= "* $source\n";
                $noteContent .= "\n\n";
            }
            if (strlen($notes) > 0) $noteContent .= "Projects / Other:\n$notes";

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
            if ($idType) $user->id_type = htmlspecialchars($idType->getvalue());
            if ($fobNumber) $user->fob = htmlspecialchars($fobNumber->getValue());
            if ($waiverSigned) $user->waiver_signed = true;
            $user->generateLogin();
            $user->generatePassword();
            $user->insert();
            $user->sendRegistrationEmail();

            if (strlen($noteContent) > 0) {
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