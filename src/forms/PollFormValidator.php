<?php
namespace Mateusz\Polls\Forms;

use SilverStripe\Forms\RequiredFields;

/**
 * Customise the validation message. Also enforce at least one selection in multi-choice poll (checkboxes!)
 */
class PollFormValidator extends RequiredFields
{

    /**
     * @return boolean
     */
    public function php($data)
    {
        $this->form->Fields()->dataFieldByName('PollChoices')->setCustomValidationMessage('Please select at least one option.');
        return parent::php($data);
    }

    /**
     * @return string
     */
    public function javascript()
    {
        $js = <<<JS
        $('PollForm_Poll_PollChoices').requiredErrorMsg = 'Please select at least one option.';
        if (jQuery('#PollForm_Poll_PollChoices').find('input[checked]').length==0) {
            validationError(jQuery('#PollForm_Poll_PollChoices')[0], 'Please select at least one option.', 'required');
        }
JS;
        return $js . parent::javascript();
    }
}
